<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\InventoryItem;

class DonationController extends Controller
{
    // ✅ Show donation list
    public function index()
    {
        $donations = Donation::latest()->get();
        return view('managefoodinventory.donation', compact('donations'));
    }

    // ✅ Convert from inventory
    public function convert(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($request->item_id);

        Donation::create([
            'item_name' => $item->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'pickup_location' => $request->pickup_location,
            'pickup_duration' => $request->pickup_duration,
        ]);

        // Optional: remove item from inventory
        $item->delete(); // ✅ 从库存移除（如果你不想删除可以注释掉）

        // ✅ 带回成功提示
        return redirect()
            ->route('donation.index')
            ->with('success', 'Item successfully converted to donation!');
    }
}
