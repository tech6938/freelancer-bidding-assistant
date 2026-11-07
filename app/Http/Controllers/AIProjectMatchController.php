<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FreelancerConnection;
use App\Models\BidTemplate;
use App\Models\BidTemplateSkill;
use App\Models\Skill;
use App\Models\SkillSet;

class AIProjectMatchController extends Controller
{
   public function index()
    {
        $userId = Auth::id();

        $connection = FreelancerConnection::where('user_id', $userId)->first();
        if (!$connection || empty($connection->access_token)) {
            return redirect()->route('freelancer.index')->with('warning', 'Please connect your Freelancer account first.');
        }

        // Fetch user templates with their attached skills
        $templates = BidTemplate::where('user_id', $userId)
            ->with('skills')
            ->latest()
            ->get();

        // Fetch all active skills & skill sets for modal selectors
        $skills = Skill::where('user_id', $userId)->where('is_active', true)->orderBy('name')->get();
        $skillSets = SkillSet::where('user_id', $userId)->with('skills')->get();
        $totalSkillsCount = $skills->count();

        return view('settings.ai-project-match', compact('templates', 'skills', 'skillSets', 'totalSkillsCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:Greetings,Skills,Portfolio,Closing',
            'content'  => 'required|string',
            'skills'   => 'nullable|array',
            'skills.*' => 'exists:skills,id',
        ]);

        $userId = Auth::id();

        $template = BidTemplate::create([
            'user_id'  => $userId,
            'category' => $request->category,
            'content'  => $request->content,
        ]);

        if (!empty($request->skills)) {
            $template->skills()->sync($request->skills);
        }

        return redirect()->back()->with('success', 'Proposal template added successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|in:Greetings,Skills,Portfolio,Closing',
            'content'  => 'required|string',
            'skills'   => 'nullable|array',
            'skills.*' => 'exists:skills,id',
        ]);

        $userId = Auth::id();
        $template = BidTemplate::where('user_id', $userId)->findOrFail($id);

        $template->update([
            'category' => $request->category,
            'content'  => $request->content,
        ]);

        $template->skills()->sync($request->skills ?? []);

        return redirect()->back()->with('success', 'Template updated successfully!');
    }

    public function addAllSkills($id)
    {
        $userId = Auth::id();
        $template = BidTemplate::where('user_id', $userId)->findOrFail($id);

        $allSkillIds = Skill::where('user_id', $userId)->where('is_active', true)->pluck('id');
        $template->skills()->sync($allSkillIds);

        return redirect()->back()->with('success', 'All skills attached to template!');
    }

    public function duplicate($id)
    {
        $userId = Auth::id();
        $template = BidTemplate::where('user_id', $userId)->with('skills')->findOrFail($id);

        $newTemplate = BidTemplate::create([
            'user_id'  => $userId,
            'category' => $template->category,
            'content'  => $template->content . "\n\n(Duplicate)",
        ]);

        $newTemplate->skills()->sync($template->skills->pluck('id')->toArray());

        return redirect()->back()->with('success', 'Template duplicated successfully!');
    }

    public function destroy($id)
    {
        $userId = Auth::id();
        $template = BidTemplate::where('user_id', $userId)->findOrFail($id);

        $template->skills()->detach();
        $template->delete();

        return redirect()->back()->with('success', 'Template deleted successfully!');
    }
}
