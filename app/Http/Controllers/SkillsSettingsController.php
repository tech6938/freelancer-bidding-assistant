<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FreelancerConnection;
use App\Models\Skill;
use App\Models\SkillSet;
use Illuminate\Support\Facades\Http;

class SkillsSettingsController extends Controller
{
    public function skills()
    {
        $connection = Auth::check()
        ? FreelancerConnection::where('user_id', Auth::id())->first()
        : null;
        if (!$connection || empty($connection->access_token)) {
            return redirect()->route('freelancer.index');
        }
        $skills = Skill::where('user_id', $connection->user_id)->orderBy('name')->get();
        $activeCount = $skills->where('is_active', true)->count();
        return view('settings.skills', compact('skills', 'activeCount'));
    }

/**
     * Sync user's skills directly from Freelancer Profile API
     */
    public function syncFromFreelancer()
    {
        $userId = Auth::id();
        $connection = FreelancerConnection::where('user_id', $userId)->first();

        if (!$connection || empty($connection->access_token)) {
            return redirect()->back()->with('error', 'Freelancer account is not connected.');
        }

        try {
            $response = Http::withHeaders([
                'freelancer-oauth-v1' => $connection->access_token,
            ])->get('https://www.freelancer.com/api/users/0.1/self/',[
                 'jobs'    => 'true',
                'compact' => 'false',
            ]);

            if ($response->successful()) {
                $result = $response->json()['result'] ?? [];
            $jobs = $result['jobs'] ?? [];

            if (empty($jobs)) {
                return redirect()->back()->with('warning', 'No skills found in API response. Please check your Freelancer profile.');
            }

                foreach ($jobs as $job) {
                    Skill::updateOrCreate(
                        [
                            'user_id'           => $userId,
                            'freelancer_job_id' => $job['id'],
                        ],
                        [
                            'name'              => $job['name'],
                            'is_active'         => true,
                        ]
                    );
                }

                return redirect()->back()->with('success', count($jobs) . ' skills synced from your Freelancer profile!');
            }

            return redirect()->back()->with('error', 'Freelancer API error: ' . $response->body());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }
    /**
     * Search official skills from Freelancer API (Autocomplete)
     */
    public function searchApi(Request $request)
    {
        $query = trim($request->get('q', ''));

        // 2 characters check
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->get('https://www.freelancer.com/api/projects/0.1/jobs/', [
                    'job_names[]' => $query,
                    'limit'       => 10,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $jobs = $data['result'] ?? [];
                return response()->json($jobs);
            }

            return response()->json([]);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Store single skill
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'              => 'required|string|max:100',
            'freelancer_job_id' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $userId = $user->id;

        if (!$user->hasActiveSubscription()) {
            return redirect()->back()->with('error', '⚠️ Adding skills requires an active subscription plan. Please subscribe to a plan.');
        }

        $maxSkills = (int) $user->getPlanFeature('skills_limit', 0);
        $activeCount = Skill::where('user_id', $userId)->where('is_active', true)->count();

        if ($maxSkills > 0 && $activeCount >= $maxSkills) {
            return redirect()->back()->with('error', "⚠️ Your subscription plan allows a maximum of {$maxSkills} skills. Please upgrade your plan to add more skills.");
        }

        $skillName = trim($request->name);
        $jobId = $request->freelancer_job_id;

        // Auto-lookup Freelancer Job ID if missing
        if (empty($jobId)) {
            try {
                $response = Http::withoutVerifying()->get('https://www.freelancer.com/api/projects/0.1/jobs/', [
                    'job_names[]' => $skillName,
                    'limit'       => 1,
                ]);
                if ($response->successful() && !empty($response->json()['result'])) {
                    $jobId = $response->json()['result'][0]['id'] ?? null;
                }
            } catch (\Exception $e) {
                $jobId = null;
            }
        }

        Skill::updateOrCreate(
            [
                'user_id' => $userId,
                'name'    => $skillName,
            ],
            [
                'freelancer_job_id' => $jobId,
                'is_active'         => true,
            ]
        );

        return redirect()->back()->with('success', 'Skill added successfully!');
    }

    public function toggle(Request $request, $id)
    {
        $user = Auth::user();
        $userId = $user->id;
        $skill = Skill::where('user_id', $userId)->findOrFail($id);

        $targetState = $request->boolean('is_active');
        $maxSkills = (int) $user->getPlanFeature('skills_limit', 0);
        $activeCount = Skill::where('user_id', $userId)->where('is_active', true)->count();

        if ($targetState) {
            if (!$user->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Activating skills requires an active subscription.'
                ], 403);
            }

            if ($maxSkills > 0 && $activeCount >= $maxSkills) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ Maximum {$maxSkills} active skills allowed under your plan. Upgrade plan to activate more."
                ], 403);
            }
        }

        $skill->is_active = $targetState;
        $skill->save();

        $updatedActiveCount = Skill::where('user_id', $userId)->where('is_active', true)->count();

        return response()->json([
            'success'     => true,
            'is_active'   => $skill->is_active,
            'activeCount' => $updatedActiveCount,
        ]);
    }

    public function destroy($id)
    {
        $userId = Auth::id();
        Skill::where('user_id', $userId)->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Skill removed.');
    }

   //skill set module 

   public function skillSets()
    {
        $userId = Auth::id();

        $connection = FreelancerConnection::where('user_id', $userId)->first();
        if (!$connection || empty($connection->access_token)) {
            return redirect()->route('freelancer.index')->with('warning', 'Please connect Freelancer account first.');
        }

        // 1. Fetch user's Skill Sets with attached skills
        $skillSets = SkillSet::where('user_id', $userId)
            ->with('skills')
            ->latest()
            ->get();

        // 2. Fetch all active skills to show in the modal checkboxes
        $availableSkills = Skill::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('settings.skill-sets', compact('skillSets', 'availableSkills'));
    }

    /**
     * Store new Skill Set and sync selected skills
     */
    public function storeSkillSet(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'skills'   => 'required|array|min:1',
            'skills.*' => 'exists:skills,id',
        ]);

        $userId = Auth::id();

        $skillSet = SkillSet::create([
            'user_id' => $userId,
            'name'    => trim($request->name),
        ]);

        // Attach selected Skill IDs via Pivot table
        $skillSet->skills()->sync($request->skills);

        return redirect()->back()->with('success', 'Skill Set created successfully!');
    }

    public function updateSkillSet(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'skills'   => 'required|array|min:1',
            'skills.*' => 'exists:skills,id',
        ]);

        $userId = Auth::id();
        $skillSet = SkillSet::where('user_id', $userId)->findOrFail($id);

        $skillSet->update([
            'name' => trim($request->name),
        ]);

        $skillSet->skills()->sync($request->skills);

        return redirect()->back()->with('success', 'Skill Set updated successfully!');
    }

    /**
     * Duplicate a Skill Set
     */
    public function duplicateSkillSet($id)
    {
        $userId = Auth::id();
        $skillSet = SkillSet::where('user_id', $userId)->with('skills')->findOrFail($id);

        $newSkillSet = SkillSet::create([
            'user_id' => $userId,
            'name'    => $skillSet->name . ' (Copy)',
        ]);

        $skillIds = $skillSet->skills->pluck('id')->toArray();
        $newSkillSet->skills()->sync($skillIds);

        return redirect()->back()->with('success', 'Skill Set duplicated successfully!');
    }

    public function destroySkillSet($id)
    {
        $userId = Auth::id();
        $skillSet = SkillSet::where('user_id', $userId)->findOrFail($id);

        // Detach pivot records and delete set
        $skillSet->skills()->detach();
        $skillSet->delete();

        return redirect()->back()->with('success', 'Skill Set deleted successfully!');
    }
}
