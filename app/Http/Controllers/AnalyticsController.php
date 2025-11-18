<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Waste;
use App\Models\Donation;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // FILTER: default = monthly
        $filter = $request->get('filter', 'monthly');

        // Define date range based on filter
        if ($filter === 'weekly') {
            $startDate = Carbon::now()->startOfWeek(); // Monday
            $endDate = Carbon::now()->endOfWeek();     // Sunday
        } else {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // 1️⃣ Inventory Items
        $inventoryItems = InventoryItem::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->get();

        // 2️⃣ Waste (Filtered)
        $filteredWaste = Waste::where('user_id', $userId)
            ->whereBetween('date_expired', [$startDate, $endDate])
            ->sum('quantity_wasted');

        // All-time waste
        $totalWaste = Waste::where('user_id', $userId)->sum('quantity_wasted');

        // Waste category breakdown (all time)
        $wasteByCategory = Waste::where('user_id', $userId)
            ->selectRaw('category, SUM(quantity_wasted) AS total')
            ->groupBy('category')
            ->get();

        // 3️⃣ Donations (Filtered)
        $filteredDonated = Donation::where('donor_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('quantity');

        $filteredRedeemed = Donation::where('donor_id', $userId)
            ->whereIn('status', ['redeemed', 'picked_up'])
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('quantity');

        // All-time donations (offered + redeemed)
        $totalDonated = Donation::where('donor_id', $userId)->sum('quantity');
        $totalRedeemed = Donation::where('donor_id', $userId)
            ->whereIn('status', ['redeemed', 'picked_up'])
            ->sum('quantity');

        // 4️⃣ Used quantity (Filtered meals)
        $filteredUsed = $inventoryItems->sum(function ($item) use ($startDate, $endDate) {
            return $item->mealIngredients()
                ->whereHas('meal', function ($q) use ($startDate, $endDate) {
                    $q->where('date', '<', today());
                    $q->whereBetween('date', [$startDate, $endDate]);
                })
                ->sum('quantity_used');
        });

        // All-time used
        $totalUsed = $inventoryItems->sum(function ($item) {
            return $item->mealIngredients()
                ->whereHas('meal', function ($q) {
                    $q->where('date', '<', today());
                })
                ->sum('quantity_used');
        });

        // 5️⃣ FOOD SAVED = USED + REDEEMED
        $filteredFoodSaved = $filteredUsed + $filteredRedeemed;
        $totalFoodSaved = $totalUsed + $totalRedeemed;

        // 6️⃣ Percent Saved (Filtered)
        $filteredActivity = $filteredFoodSaved + $filteredWaste;
        $percentSavedFiltered = $filteredActivity > 0
            ? round(($filteredFoodSaved / $filteredActivity) * 100, 2)
            : 0;

        return view('analytics', compact(
            'filter',
            'startDate',
            'endDate',
            'filteredWaste',
            'filteredDonated',
            'filteredRedeemed',
            'filteredFoodSaved',
            'percentSavedFiltered',
            'wasteByCategory',
            'totalDonated',
            'totalRedeemed',
            'totalWaste',
            'totalFoodSaved'
        ));
    }
}
