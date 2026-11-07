<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BidPackage;
use Illuminate\Http\Request;

class BidPackageController extends Controller
{
    /**
     * Display a listing of AI bid packages (including trashed).
     */
    public function index()
    {
        $packages = BidPackage::withTrashed()
            ->latest()
            ->paginate(15);

        return view('admin.bid_packages.index', compact('packages'));
    }

    /**
     * Store a newly created AI bid package.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'bid_quantity' => 'required|integer|min:1',
            'price'        => 'required|numeric|min:0',
            'badge'        => 'nullable|string|max:50',
            'description'  => 'nullable|string|max:500',
            'is_active'    => 'sometimes|boolean',
        ]);

        BidPackage::create([
            'name'         => $validated['name'],
            'bid_quantity' => $validated['bid_quantity'],
            'price'        => $validated['price'],
            'badge'        => $validated['badge'] ?? null,
            'description'  => $validated['description'] ?? null,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.packages.index')
            ->with('success', "AI Bid Package '{$validated['name']}' created successfully.");
    }

    /**
     * Update the specified AI bid package.
     */
    public function update(Request $request, $id)
    {
        $package = BidPackage::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'bid_quantity' => 'required|integer|min:1',
            'price'        => 'required|numeric|min:0',
            'badge'        => 'nullable|string|max:50',
            'description'  => 'nullable|string|max:500',
            'is_active'    => 'sometimes|boolean',
        ]);

        $package->update([
            'name'         => $validated['name'],
            'bid_quantity' => $validated['bid_quantity'],
            'price'        => $validated['price'],
            'badge'        => $validated['badge'] ?? null,
            'description'  => $validated['description'] ?? null,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.packages.index')
            ->with('success', "AI Bid Package '{$package->name}' updated successfully.");
    }

    /**
     * Soft-delete the specified AI bid package.
     */
    public function destroy($id)
    {
        $package = BidPackage::findOrFail($id);
        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', "Package '{$package->name}' has been archived (soft deleted). Existing order history preserved.");
    }

    /**
     * Restore a soft-deleted AI bid package.
     */
    public function restore($id)
    {
        $package = BidPackage::withTrashed()->findOrFail($id);
        $package->restore();

        return redirect()->route('admin.packages.index')
            ->with('success', "Package '{$package->name}' restored successfully.");
    }
}
