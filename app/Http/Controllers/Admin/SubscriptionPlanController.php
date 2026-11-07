<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans (including trashed).
     */
    public function index()
    {
        $plans = SubscriptionPlan::withTrashed()
            ->latest()
            ->paginate(15);

        return view('admin.subscription_plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create()
    {
        $plan = new SubscriptionPlan();
        return view('admin.subscription_plans.form', compact('plan'));
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:100',
            'price'                => 'required|numeric|min:0',
            'duration_days'        => 'required|integer|min:1|max:3650',
            'badge'                => 'nullable|string|max:50',
            'description'          => 'nullable|string|max:500',
            'is_active'            => 'sometimes|boolean',
            // Dynamic Structured Feature Form Inputs
            'has_autobid'          => 'sometimes|boolean',
            'free_ai_bids'         => 'required|integer|min:0',
            'place_bids_limit'     => 'required|integer|min:0',
            'skills_limit'         => 'required|integer|min:0',
            'has_bid_price_matrix' => 'sometimes|boolean',
            'countries_limit'      => 'required|integer|min:0',
            'languages_limit'      => 'required|integer|min:0',
            'currencies_limit'     => 'required|integer|min:0',
        ]);

        $freeAiBids = (int) $request->input('free_ai_bids', 0);

        $features = [
            'has_autobid'          => $request->boolean('has_autobid'),
            'free_ai_bids'         => $freeAiBids,
            'place_bids_limit'     => (int) $request->input('place_bids_limit', 0),
            'skills_limit'         => (int) $request->input('skills_limit', 0),
            'has_bid_price_matrix' => $request->boolean('has_bid_price_matrix'),
            'countries_limit'      => (int) $request->input('countries_limit', 0),
            'languages_limit'      => (int) $request->input('languages_limit', 0),
            'currencies_limit'     => (int) $request->input('currencies_limit', 0),
        ];

        SubscriptionPlan::create([
            'name'             => $validated['name'],
            'slug'             => Str::slug($validated['name']) . '-' . Str::random(4),
            'price'            => $validated['price'],
            'duration_days'    => $validated['duration_days'],
            'included_ai_bids' => $freeAiBids,
            'badge'            => $validated['badge'] ?? null,
            'description'      => $validated['description'] ?? null,
            'features'         => $features,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Subscription Plan '{$validated['name']}' created successfully.");
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit($id)
    {
        $plan = SubscriptionPlan::withTrashed()->findOrFail($id);
        return view('admin.subscription_plans.form', compact('plan'));
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(Request $request, $id)
    {
        $plan = SubscriptionPlan::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name'                 => 'required|string|max:100',
            'price'                => 'required|numeric|min:0',
            'duration_days'        => 'required|integer|min:1|max:3650',
            'badge'                => 'nullable|string|max:50',
            'description'          => 'nullable|string|max:500',
            'is_active'            => 'sometimes|boolean',
            // Dynamic Structured Feature Form Inputs
            'has_autobid'          => 'sometimes|boolean',
            'free_ai_bids'         => 'required|integer|min:0',
            'place_bids_limit'     => 'required|integer|min:0',
            'skills_limit'         => 'required|integer|min:0',
            'has_bid_price_matrix' => 'sometimes|boolean',
            'countries_limit'      => 'required|integer|min:0',
            'languages_limit'      => 'required|integer|min:0',
            'currencies_limit'     => 'required|integer|min:0',
        ]);

        $freeAiBids = (int) $request->input('free_ai_bids', 0);

        $features = [
            'has_autobid'          => $request->boolean('has_autobid'),
            'free_ai_bids'         => $freeAiBids,
            'place_bids_limit'     => (int) $request->input('place_bids_limit', 0),
            'skills_limit'         => (int) $request->input('skills_limit', 0),
            'has_bid_price_matrix' => $request->boolean('has_bid_price_matrix'),
            'countries_limit'      => (int) $request->input('countries_limit', 0),
            'languages_limit'      => (int) $request->input('languages_limit', 0),
            'currencies_limit'     => (int) $request->input('currencies_limit', 0),
        ];

        $plan->update([
            'name'             => $validated['name'],
            'price'            => $validated['price'],
            'duration_days'    => $validated['duration_days'],
            'included_ai_bids' => $freeAiBids,
            'badge'            => $validated['badge'] ?? null,
            'description'      => $validated['description'] ?? null,
            'features'         => $features,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', "Subscription Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Soft-delete the specified subscription plan.
     */
    public function destroy($id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' has been safely archived (soft deleted). Active users are unaffected.");
    }

    /**
     * Restore a soft-deleted subscription plan.
     */
    public function restore($id)
    {
        $plan = SubscriptionPlan::withTrashed()->findOrFail($id);
        $plan->restore();

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' has been restored to active catalog.");
    }
}
