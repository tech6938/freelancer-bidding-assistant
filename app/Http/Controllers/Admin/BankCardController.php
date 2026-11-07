<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankCardController extends Controller
{
    /**
     * Display a listing of bank cards / accounts.
     */
    public function index()
    {
        $cards = BankCard::withTrashed()
            ->latest()
            ->paginate(15);

        return view('admin.bank_cards.index', compact('cards'));
    }

    /**
     * Store a newly created bank card.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name'  => 'required|string|max:100',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active'  => 'sometimes|boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo_image')) {
            $logoPath = $request->file('logo_image')->store('bank_logos', 'public');
        }

        BankCard::create([
            'bank_name'  => $validated['bank_name'],
            'logo_image' => $logoPath,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.bank-cards.index')
            ->with('success', "Bank / Card '{$validated['bank_name']}' added successfully.");
    }

    /**
     * Update the specified bank card.
     */
    public function update(Request $request, $id)
    {
        $card = BankCard::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'bank_name'  => 'required|string|max:100',
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'is_active'  => 'sometimes|boolean',
        ]);

        $logoPath = $card->logo_image;
        if ($request->hasFile('logo_image')) {
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $request->file('logo_image')->store('bank_logos', 'public');
        }

        $card->update([
            'bank_name'  => $validated['bank_name'],
            'logo_image' => $logoPath,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.bank-cards.index')
            ->with('success', "Bank / Card '{$card->bank_name}' updated successfully.");
    }

    /**
     * Quick toggle active status.
     */
    public function toggleActive($id)
    {
        $card = BankCard::withTrashed()->findOrFail($id);
        $card->is_active = !$card->is_active;
        $card->save();

        $statusStr = $card->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.bank-cards.index')
            ->with('success', "Bank / Card '{$card->bank_name}' has been {$statusStr}.");
    }

    /**
     * Soft-delete the specified bank card.
     */
    public function destroy($id)
    {
        $card = BankCard::findOrFail($id);
        $card->delete();

        return redirect()->route('admin.bank-cards.index')
            ->with('success', "Bank / Card '{$card->bank_name}' archived successfully.");
    }

    /**
     * Restore a soft-deleted bank card.
     */
    public function restore($id)
    {
        $card = BankCard::withTrashed()->findOrFail($id);
        $card->restore();

        return redirect()->route('admin.bank-cards.index')
            ->with('success', "Bank / Card '{$card->bank_name}' restored successfully.");
    }
}
