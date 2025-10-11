<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Donation;

class InventoryController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index()
    {
        // Get all inventory items ordered by expiry date
        $items = InventoryItem::orderBy('expiry_date', 'asc')->get();

        return view('managefoodinventory.inventory', compact('items'));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'New item added successfully!');
    }

    /**
     * Update an existing item.
     */
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);

        $item->update($validated);

        return redirect()->route('inventory.index')
            ->with('success', 'Item updated successfully!');
    }

    /**
     * Remove an item from inventory.
     */
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Item deleted successfully!');
    }

    /**
     * Show the form for converting an item to donation.
     */
    public function convertForm($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('managefoodinventory.convert_donation', compact('item'));
    }

    /**
     * Handle conversion to donation.
     */
    public function convertStore(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        // Create new donation entry
        Donation::create([
            'item_name' => $item->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'pickup_location' => $validated['pickup_location'],
            'pickup_duration' => $validated['pickup_duration'],
        ]);

        // Remove from inventory after converting
        $item->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Item converted to donation successfully!');
    }
}
