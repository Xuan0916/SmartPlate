<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\Waste;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $userId = Auth::id();
        $today = Carbon::today();
        $threeDaysLater = $today->copy()->addDays(3);

        // 1️⃣ Automatically track expired items as waste
        $expiredItems = InventoryItem::where('user_id', $userId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', $today)
            ->where('status', '!=', 'expired')
            ->get();

        foreach ($expiredItems as $item) {
            // Skip if quantity already 0
            if ($item->quantity <= 0) continue;

            // Skip if already logged in waste table
            if (Waste::where('inventory_item_id', $item->id)->exists()) continue;

            // Save new waste record
            Waste::create([
                'user_id' => $userId,
                'inventory_item_id' => $item->id,
                'item_name' => $item->name,
                'category' => $item->category,
                'quantity_wasted' => $item->quantity,
                'unit' => $item->unit,
                'date_expired' => $item->expiry_date,
            ]);

            // Mark expired
            $item->status = 'expired';
            $item->quantity = 0;
            $item->save();
        }


        // 2️⃣ Notify about items expiring in 3 days
        $expiringItems = InventoryItem::where('user_id', $userId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $threeDaysLater])
            ->get();

        foreach ($expiringItems as $item) {
            $exists = Notification::where('user_id', $userId)
                ->where('item_name', $item->name)
                ->where('message', 'like', '%will expire%')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$exists) {
                $daysLeft = Carbon::parse($item->expiry_date)->diffInDays($today, false);
                if ($daysLeft < 0) $daysLeft = abs($daysLeft);

                Notification::create([
                    'user_id' => $userId,
                    'item_name' => $item->name,
                    'message' => $item->name . ' will expire in ' . $daysLeft . ' day' . ($daysLeft > 1 ? 's' : ''),
                    'expiry_date' => now(),
                    'status' => 'new',
                ]);
            }
        }

        // 3️⃣ Retrieve current inventory
        $items = InventoryItem::where('user_id', $userId)
            ->orderBy('expiry_date', 'asc')
            ->with('user')
            ->get();

        return view('managefoodinventory.inventory', compact('items'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);

        InventoryItem::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'category' => $validated['category'] ?? null,
            'quantity' => $validated['quantity'],
            'original_quantity' => $validated['quantity'], // track original purchased amount
            'unit' => $validated['unit'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'status' => 'available',
        ]);

        return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
    }

    public function edit($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('managefoodinventory.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:1',
            'unit' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);

        $item->update($validated);

        // Optional: update original_quantity if you want to reset it on update
        // $item->original_quantity = $validated['quantity'];
        // $item->save();

        return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        Waste::where('inventory_item_id', $item->id)->delete();
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
    }

    public function convertForm($id)
    {
        $item = InventoryItem::findOrFail($id);
        return view('managefoodinventory.convert_donation', compact('item'));
    }

    public function convertStore(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $item = InventoryItem::findOrFail($id);
        $validated = $request->validate([
            'pickup_location' => 'required|string|max:255',
            'pickup_duration' => 'required|string|max:255',
        ]);

        Donation::create([
            'donor_id' => Auth::id(),
            'user_id' => Auth::id(),
            'inventory_item_id' => $item->id,
            'item_name' => $item->name,
            'category' => $item->category,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'expiry_date' => $item->expiry_date,
            'status' => 'available',
            'pickup_location' => $validated['pickup_location'],
            'pickup_duration' => $validated['pickup_duration'],
        ]);

        Notification::create([
            'user_id' => Auth::id(),
            'item_name' => $item->name,
            'message' => 'You have successfully donated "' . $item->name . '".',
            'expiry_date' => now(),
            'status' => 'new',
        ]);

        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Item converted to donation successfully!');
    }

    public function markUsed($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->status = 'used';
        $item->save();
        return back()->with('success', 'Item marked as used successfully.');
    }

    public function planMeal($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->status = 'reserved';
        $item->save();
        return back()->with('success', 'Item reserved for meal successfully.');
    }
}
