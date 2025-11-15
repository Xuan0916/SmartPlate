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

        // ✅ Waste
        $totalWaste = Waste::where('user_id', $userId)->sum('quantity_wasted');
        $monthlyWaste = Waste::where('user_id', $userId)
            ->whereBetween('date_expired', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity_wasted');

        // ✅ Waste by category
        $wasteByCategory = Waste::where('user_id', $userId)
            ->selectRaw('category, SUM(quantity_wasted) as total')
            ->groupBy('category')
            ->get();

        // ------------------------------
        // 🔄 DONATIONS (OFFERED)
        // ------------------------------

        // Total Donated (All items the user offered as a donation)
        $totalDonated = Donation::where('donor_id', $userId)
            ->sum('quantity'); // ⬅️ New calculation for ALL donations

        // Monthly Donated (All items the user offered this month)
        $monthlyDonated = Donation::where('donor_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->sum('quantity');

        // ------------------------------
        // 🔄 DONATIONS (REDEEMED) → Only REDEEMED or PICKED UP count as "saved"
        // ------------------------------

        $totalRedeemed = Donation::where('donor_id', $userId)
            ->whereIn('status', ['redeemed', 'picked_up'])
            // You should use updated_at here for accurate "saved" tracking
            ->whereYear('updated_at', now()->year)
            ->sum('quantity');

        $monthlyRedeemed = Donation::where('donor_id', $userId)
            ->whereIn('status', ['redeemed', 'picked_up'])
            // You should use updated_at here for accurate "saved" tracking
            ->whereMonth('updated_at', now()->month)
            ->sum('quantity');

        // ------------------------------
        // 🔄 USED QUANTITY CALCULATION
        // ------------------------------

        $usedQuantity = $inventoryItems->sum(function ($item) {

            // Removed the $original and $used calculations entirely.
            
            // Add reserved ingredients ONLY for past meals
            $pastReserved = $item->mealIngredients()
                ->whereHas('meal', function ($q) {
                    // Using today() here is correct for filtering meals that are 'past' (i.e., date < today)
                    $q->where('date', '<', today()); 
                })
                ->sum('quantity_used');

            // Return only the past reserved quantity
            return max($pastReserved, 0); 
        });


        $monthlyUsedQuantity = $inventoryItems->sum(function ($item) {
    
            // Calculate reserved ingredients ONLY for past meals AND this month
            $monthlyPastReserved = $item->mealIngredients()
                ->whereHas('meal', function ($q) {
                    
                    // 1. Ensure the meal is in the past
                    $q->where('date', '<', today()); 
                    
                    // 2. Ensure the meal occurred in the current month
                    $q->whereMonth('date', now()->month); 
                    
                    // 3. (Optional but good practice) Ensure the meal occurred in the current year
                    $q->whereYear('date', now()->year);
                })
                ->sum('quantity_used');

            // Return only the monthly past reserved quantity
            return max($monthlyPastReserved, 0); 
        });


        // ------------------------------
        // 🔄 FOOD SAVED = USED + REDEEMED
        // ------------------------------

        $totalFoodSaved = $usedQuantity + $totalRedeemed;
        $monthlyFoodSaved = $monthlyUsedQuantity + $monthlyRedeemed;

        // ------------------------------
        // 🔄 PERCENTAGES
        // ------------------------------

        $totalActivity = $totalWaste + $totalFoodSaved;
        $percentSavedTotal = $totalActivity > 0
            ? round(($totalFoodSaved / $totalActivity) * 100, 2)
            : 0;

        $monthlyActivity = $monthlyWaste + $monthlyFoodSaved;
        $percentSavedMonthly = $monthlyActivity > 0
            ? round(($monthlyFoodSaved / $monthlyActivity) * 100, 2)
            : 0;

        return view('analytics', compact(
            'totalWaste',
            'monthlyWaste',
            'wasteByCategory',
            'totalRedeemed',
            'totalDonated',       // 🔄 Added so you can show “Total Redeemed Donations”
            'totalFoodSaved',
            'monthlyFoodSaved',
            'percentSavedTotal',
            'percentSavedMonthly'
        ));
    }
}
