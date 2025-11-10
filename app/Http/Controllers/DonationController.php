<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\InventoryItem;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    // ✅ Show donation list
    public function index()
    {
        $donations = Donation::latest()->get();
        return view('managefoodinventory.donation', compact('donations'));
    }

    // ✅ Convert from inventory to donation
    public function convert(Request $request)
    {
        // 登录安全检查
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($request->item_id);

        // ✅ 创建 Donation
        $donation = Donation::create([
            'donor_id' => Auth::id(),
            'user_id' => Auth::id(),
            'inventory_item_id' => $item->id,
            'item_name' => $item->name,
            'category' => $item->category,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'status' => 'available',
            'pickup_location' => $request->pickup_location,
            'pickup_duration' => $request->pickup_duration,
        ]);

        // ✅ 给捐赠者发私人通知
        Notification::create([
            'user_id' => Auth::id(),
            'item_name' => $item->name,
            'message' => 'You have successfully donated "' . $item->name . '".',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        // 从库存移除
        $item->delete();

        return redirect()->route('donation.index')
            ->with('success', 'Item successfully converted to donation!');
    }

    // ✅ Remove donation and return to inventory
    public function destroy($id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->inventory_item_id) {
            $item = InventoryItem::withTrashed()->find($donation->inventory_item_id);
            if ($item) $item->restore();
        } else {
            InventoryItem::create([
                'user_id' => $donation->donor_id,
                'name' => $donation->item_name,
                'quantity' => $donation->quantity ?? 1,
                'unit' => $donation->unit ?? 'pcs',
                'expiry_date' => $donation->expiry_date ?? now()->addDays(7),
            ]);
        }

        $donation->delete();

        Notification::create([
            'user_id' => $donation->donor_id,
            'item_name' => $donation->item_name,
            'message' => 'Your donation of "' . $donation->item_name . '" has been removed and returned to inventory.',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        return redirect()->route('donation.index')
            ->with('success', 'Donation removed and item returned to inventory.');
    }

    // ✅ Redeem donation (claim)
    public function redeem($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $donation = Donation::findOrFail($id);

        if ($donation->status === 'redeemed') {
            return redirect()->back()->with('error', 'This item has already been redeemed.');
        }

        $donation->update([
            'status' => 'redeemed',
            'user_id' => Auth::id(),
        ]);

        InventoryItem::create([
            'user_id' => Auth::id(),
            'name' => $donation->item_name,
            'category' => $donation->category,
            'quantity' => $donation->quantity,
            'unit' => $donation->unit,
            'expiry_date' => $donation->expiry_date,
        ]);

        // ✅ 通知双方
        Notification::create([
            'user_id' => $donation->donor_id,
            'item_name' => $donation->item_name,
            'message' => 'Your item "' . $donation->item_name . '" has been claimed by another user.',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        Notification::create([
            'user_id' => Auth::id(),
            'item_name' => $donation->item_name,
            'message' => 'You have successfully claimed "' . $donation->item_name . '".',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        return redirect()->back()->with('success', 'Item successfully redeemed and added to your inventory!');
    }

    // ✅ Pickup donation (领取人确认取走)
    public function pickup($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $donation = Donation::findOrFail($id);

        if ($donation->status !== 'redeemed') {
            return redirect()->back()->with('error', 'This item must be redeemed before pickup.');
        }

        $donation->update(['status' => 'picked_up']);

        // ✅ 通知双方
        Notification::create([
            'user_id' => $donation->donor_id,
            'item_name' => $donation->item_name,
            'message' => 'Your item "' . $donation->item_name . '" has been picked up.',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        Notification::create([
            'user_id' => $donation->user_id,
            'item_name' => $donation->item_name,
            'message' => 'You have successfully picked up "' . $donation->item_name . '".',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        return redirect()->back()->with('success', 'Pickup confirmed successfully!');
    }
}
