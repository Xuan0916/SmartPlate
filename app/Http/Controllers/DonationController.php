<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\InventoryItem;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Waste;

class DonationController extends Controller
{
    // Show donation list
    public function index()
    {
        $this->handleExpiredDonations();

        $donations = Donation::latest()->get();
        return view('managefoodinventory.donation', compact('donations'));
    }

    // Convert inventory item to donation
    public function convert(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $request->validate([
            'item_id'         => 'required|exists:inventory_items,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        $item = InventoryItem::findOrFail($request->item_id);

        // Create donation
        $donation = Donation::create([
            'donor_id'         => Auth::id(),
            'user_id'          => Auth::id(),
            'inventory_item_id'=> $item->id,
            'item_name'        => $item->name,
            'category'         => $item->category,
            'quantity'         => $item->quantity,
            'unit'             => $item->unit,
            'expiry_date'      => $item->expiry_date,
            'status'           => 'available',
            'pickup_location'  => $request->pickup_location,
            'pickup_duration'  => $request->pickup_duration,
        ]);

        // Notification (donated)
        Notification::create([
            'user_id'     => Auth::id(),
            'item_name'   => $item->name,
            'message'     => 'You have successfully donated "' . $item->name . '".',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $donation->id,
        ]);

        // Remove inventory item
        $item->delete();

        return redirect()->route('donation.index')
            ->with('success', 'Item successfully converted to donation!');
    }

    // Delete donation and return to inventory
    public function destroy($id)
    {
        $donation = Donation::findOrFail($id);

        if ($donation->inventory_item_id) {
            $item = InventoryItem::withTrashed()->find($donation->inventory_item_id);
            if ($item) $item->restore();
        } else {
            InventoryItem::create([
                'user_id'      => $donation->donor_id,
                'name'         => $donation->item_name,
                'quantity'     => $donation->quantity ?? 1,
                'unit'         => $donation->unit ?? 'pcs',
                'expiry_date'  => $donation->expiry_date ?? now()->addDays(7),
            ]);
        }

        $donation->delete();

        Notification::create([
            'user_id'     => $donation->donor_id,
            'item_name'   => $donation->item_name,
            'message'     => 'Your donation of "' . $donation->item_name . '" has been removed and returned to inventory.',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $id, // donation still exists for this notification
        ]);

        return redirect()->route('donation.index')
            ->with('success', 'Donation removed and item returned to inventory.');
    }

    // Redeem donation (claim)
    public function redeem($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $donation = Donation::findOrFail($id);

        if ($donation->status === 'redeemed') {
            return redirect()->back()->with('error', 'This item has already been redeemed.');
        }

        if ($donation->expiry_date && now()->greaterThan($donation->expiry_date)) {
            return redirect()->back()->with('error', 'This item has expired and cannot be redeemed.');
        }

        $donation->update([
            'status' => 'redeemed',
            'user_id' => Auth::id(),
        ]);

        InventoryItem::create([
            'user_id'           => Auth::id(),
            'name'              => $donation->item_name,
            'category'          => $donation->category,
            'quantity'          => $donation->quantity,
            'original_quantity' => $donation->quantity,
            'unit'              => $donation->unit,
            'expiry_date'       => $donation->expiry_date,
        ]);

        // Notify donor
        Notification::create([
            'user_id'     => $donation->donor_id,
            'item_name'   => $donation->item_name,
            'message'     => 'Your item "' . $donation->item_name . '" has been claimed by another user. '
                            . 'Pickup Location: ' . $donation->pickup_location . '. '
                            . 'Pickup Duration: ' . $donation->pickup_duration . '.',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $donation->id,
        ]);

        // Notify claimer
        Notification::create([
            'user_id'     => Auth::id(),
            'item_name'   => $donation->item_name,
            'message'     => 'You have successfully claimed "' . $donation->item_name . '". '
                            . 'Pickup Location: ' . $donation->pickup_location . '. '
                            . 'Pickup Duration: ' . $donation->pickup_duration . '.',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $donation->id,
        ]);

        return redirect()->back()->with('success', 'Item successfully redeemed and added to your inventory!');
    }

    // Pickup donation
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

        Notification::create([
            'user_id'     => $donation->donor_id,
            'item_name'   => $donation->item_name,
            'message'     => 'Your item "' . $donation->item_name . '" has been picked up.',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $donation->id,
        ]);

        Notification::create([
            'user_id'     => $donation->user_id,
            'item_name'   => $donation->item_name,
            'message'     => 'You have successfully picked up "' . $donation->item_name . '".',
            'expiry_date' => now(),
            'status'      => 'new',
            'target_type' => 'donation',
            'target_id'   => $donation->id,
        ]);

        return redirect()->back()->with('success', 'Pickup confirmed successfully!');
    }

    // Handle expired donations
    public function handleExpiredDonations()
    {
        $today = Carbon::today();

        $expiredDonations = Donation::where('expiry_date', '<', $today)
            ->where('status', 'available')
            ->get();

        foreach ($expiredDonations as $donation) {
            if ($donation->quantity <= 0 || empty($donation->item_name)) continue;

            Waste::create([
                'user_id'            => $donation->donor_id,
                'inventory_item_id'  => $donation->inventory_item_id,
                'item_name'          => $donation->item_name,
                'category'           => $donation->category,
                'quantity_wasted'    => $donation->quantity,
                'unit'               => $donation->unit,
                'date_expired'       => $donation->expiry_date,
            ]);

            $donation->update([
                'status' => 'expired',
                'quantity' => 0,
            ]);

            Notification::create([
                'user_id'     => $donation->donor_id,
                'item_name'   => $donation->item_name,
                'message'     => 'Your donation of "' . $donation->item_name . '" has expired and was logged as waste.',
                'expiry_date' => now(),
                'status'      => 'new',
                'target_type' => 'donation',
                'target_id'   => $donation->id,
            ]);
        }
    }
}
