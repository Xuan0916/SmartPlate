<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waste;
use App\Models\Donation;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // ✅ Exclude soft-deleted inventory items
        $inventoryItems = InventoryItem::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        // ✅ Waste (include all, no soft deletes)
        $totalWaste = Waste::where('user_id', $userId)->sum('quantity_wasted');
        $monthlyWaste = Waste::where('user_id', $userId)
            ->whereBetween('date_expired', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity_wasted');

        // ✅ Waste by category
        $wasteByCategory = Waste::where('user_id', $userId)
            ->selectRaw('category, SUM(quantity_wasted) as total')
            ->groupBy('category')
            ->get();

        // ✅ Donations (no soft deletes)
        $totalDonated = Donation::where('donor_id', $userId)->sum('quantity');
        $monthlyDonated = Donation::where('donor_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->sum('quantity');

        // ✅ Used quantity
        $usedQuantity = $inventoryItems->sum(function ($item) {
            $original = $item->original_quantity ?? $item->quantity;
            $used = $original - $item->quantity - ($item->reserved_quantity ?? 0);
            return max($used, 0);
        });

        $monthlyUsedQuantity = $inventoryItems
            ->filter(fn($item) => $item->created_at->month === now()->month)
            ->sum(function ($item) {
                $original = $item->original_quantity ?? $item->quantity;
                $used = $original - $item->quantity - ($item->reserved_quantity ?? 0);
                return max($used, 0);
            });

        // ✅ Food saved totals
        $totalFoodSaved = $usedQuantity + $totalDonated;
        $monthlyFoodSaved = $monthlyUsedQuantity + $monthlyDonated;

        // ✅ Percentages
        $totalActivity = $totalWaste + $totalFoodSaved;
        $percentSavedTotal = $totalActivity > 0 ? round(($totalFoodSaved / $totalActivity) * 100, 2) : 0;

        $monthlyActivity = $monthlyWaste + $monthlyFoodSaved;
        $percentSavedMonthly = $monthlyActivity > 0 ? round(($monthlyFoodSaved / $monthlyActivity) * 100, 2) : 0;

        return view('analytics', compact(
            'totalWaste',
            'monthlyWaste',
            'wasteByCategory',
            'totalDonated',
            'totalFoodSaved',
            'monthlyFoodSaved',
            'percentSavedTotal',
            'percentSavedMonthly'
        ));
    }
}
