<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MealPlan;
use App\Models\Meal;
use App\Models\InventoryItem;
use App\Models\Donation;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;

        $today = Carbon::today();

        // -----------------------------
        // 1️⃣ Meal Plan Progress (This Week)
        // -----------------------------
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $mealPlansThisWeek = MealPlan::where('user_id', $userId)
            ->whereBetween('week_start', [$weekStart, $weekEnd])
            ->pluck('id');

        $totalMealsThisWeek = Meal::whereIn('meal_plan_id', $mealPlansThisWeek)->count();
        $completedMealsThisWeek = Meal::whereIn('meal_plan_id', $mealPlansThisWeek)
            ->where('date', '<=', now())
            ->count();

        $mealProgress = $totalMealsThisWeek > 0
            ? round(($completedMealsThisWeek / $totalMealsThisWeek) * 100) . '%'
            : '0%';

        // -----------------------------
        // 2️⃣ Food Saved (Last Month)
        // -----------------------------
        $previousMonth = Carbon::now()->subMonth(); // Get a Carbon instance in the previous month
        $startOfLastMonth = $previousMonth->startOfMonth()->copy(); // Get the 1st day of last month, and COPY it
        $endOfLastMonth = $previousMonth->endOfMonth()->copy(); // Get the last day of last month, and COPY it
                                                                
        // Get last month's inventory items
        $inventoryItems = InventoryItem::where('user_id', $userId)->get();

        $foodUsedLastMonth = $inventoryItems->sum(function ($item) use ($startOfLastMonth, $endOfLastMonth) {
            return $item->mealIngredients()
                ->whereHas('meal', function ($q) use ($startOfLastMonth, $endOfLastMonth) {
                    $q->whereBetween('date', [$startOfLastMonth, $endOfLastMonth]);
                })
                ->sum('quantity_used');
        });

        // Only redeemed or picked-up donations last month
        $totalRedeemedLastMonth = Donation::where('donor_id', $userId)
            ->whereIn('status', ['redeemed', 'picked_up'])
            ->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('quantity');

        $foodSavedLastMonth = $foodUsedLastMonth + $totalRedeemedLastMonth;


        // -----------------------------
        // 3️⃣ Total Meals Completed (All Time)
        // -----------------------------
        $allMealPlans = MealPlan::where('user_id', $userId)->pluck('id');

        $totalMealsCompleted = Meal::whereIn('meal_plan_id', $allMealPlans)
            ->whereDate('date', '<=', today())
            ->count();

        // -----------------------------
        // Return view
        // -----------------------------
        return view('dashboard', compact(
            'mealProgress',
            'foodSavedLastMonth',
            'totalMealsCompleted'
        ));
    }
}
