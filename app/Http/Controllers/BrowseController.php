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
        $queryInventory = InventoryItem::where('user_id', Auth::id());
        $queryDonation = Donation::where('status', 'available')->with('user');

        if ($request->filled('category')) {
            $queryInventory->where('category', $request->category);
            $queryDonation->where('category', $request->category);
        }

        if ($request->filled('expiry_date')) {
            $queryInventory->whereDate('expiry_date', '<=', $request->expiry_date);
            $queryDonation->whereDate('expiry_date', '<=', $request->expiry_date);
        }

        // Filter by type (inventory or donation)
        if ($request->type === 'inventory') {
            $items = $queryInventory->get();
        } elseif ($request->type === 'donation') {
            $items = $queryDonation->get();
        } else {
            $items = $queryInventory->get()->merge($queryDonation->get());
        }

        return view('managefoodinventory.browse', compact('items'));
    }
}
