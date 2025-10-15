<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Donation;
use Illuminate\Support\Facades\Auth;

class BrowseController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Show only your own inventory items
        $queryInventory = InventoryItem::where('user_id', Auth::id());

        // ✅ Show only your own donations (still not redeemed)
        $queryDonation = Donation::where('user_id', Auth::id())
            ->where('status', 'available') // Only show donated, not redeemed
            ->with('user');

        // ✅ Optional filters
        if ($request->filled('category')) {
            $queryInventory->where('category', $request->category);
            $queryDonation->where('category', $request->category);
        }

        if ($request->filled('expiry_date')) {
            $queryInventory->whereDate('expiry_date', '<=', $request->expiry_date);
            $queryDonation->whereDate('expiry_date', '<=', $request->expiry_date);
        }

        // ✅ Filter by type
        if ($request->type === 'inventory') {
            $items = $queryInventory->get();
        } elseif ($request->type === 'donation') {
            $items = $queryDonation->get();
        } else {
            // Show both (inventory + donated but not redeemed)
            $items = $queryInventory->get()->merge($queryDonation->get())->values();
        }

        return view('managefoodinventory.browse', compact('items'));
    }
}
