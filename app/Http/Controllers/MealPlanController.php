<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MealPlan;
use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MealPlanController extends Controller
{
    public function index()
    {
        $mealPlans = MealPlan::where('user_id', Auth::id())
            ->with('meals.ingredients.inventoryItem')
            ->latest()
            ->get();

        return view('mealplans.index', compact('mealPlans'));
    }

    public function create()
    {
        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->get()
            ->map(function ($item) {
                // Calculate available quantity
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);

                // Replace quantity value with available
                $item->quantity = $available;
                return $item;
            });

        return view('mealplans.create', compact('inventoryItems'));
    }

    public function show(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403);
        }

        $mealPlan->load('meals.ingredients.inventoryItem');

        $startDate = \Carbon\Carbon::parse($mealPlan->week_start);
        $endDate = $startDate->clone()->addDays(6);

        // 🔹 Find previous and next plans (by week_start)
        $previousPlan = MealPlan::where('user_id', Auth::id())
            ->where('week_start', '<', $mealPlan->week_start)
            ->orderBy('week_start', 'desc')
            ->first();

        $nextPlan = MealPlan::where('user_id', Auth::id())
            ->where('week_start', '>', $mealPlan->week_start)
            ->orderBy('week_start', 'asc')
            ->first();

        return view('mealplans.show', compact('mealPlan', 'startDate', 'endDate', 'previousPlan', 'nextPlan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'week_start' => 'required|date',
            'meals' => 'required|array'
        ]);

        $mealPlan = MealPlan::create([
            'user_id' => Auth::id(),
            'week_start' => $request->week_start,
        ]);

        foreach ($request->meals as $mealData) {
            $submittedIngredients = $mealData['ingredients'] ?? [];

            $validIngredients = collect($submittedIngredients)
                ->filter(fn($ingredient) => !empty($ingredient['inventory_item_id']))
                ->toArray();

            $recipeName = $mealData['recipe_name'] ?? null;

            // Skip empty meal slots
            if (empty($recipeName) && empty($validIngredients)) continue;

            // ✅ Validate stock before saving
            foreach ($validIngredients as $ingredient) {
                $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                $quantityUsed = $ingredient['quantity_used'] ?? 1;

                if ($inventoryItem && ($inventoryItem->reserved_quantity + $quantityUsed) > $inventoryItem->quantity) {
                    throw ValidationException::withMessages([
                        'meals' => "You only have {$inventoryItem->quantity} units of {$inventoryItem->name} available (" .
                            ($inventoryItem->quantity - $inventoryItem->reserved_quantity) .
                            " left unreserved).",
                    ]);
                }
            }

            // ✅ Create the meal record
            $meal = $mealPlan->meals()->create([
                'date' => $mealData['date'],
                'meal_type' => $mealData['meal_type'],
                'recipe_name' => $recipeName,
                'notes' => $mealData['notes'] ?? null,
            ]);

            if (!empty($validIngredients)) {
                $ingredientsToInsert = collect($validIngredients)->map(function ($ingredient) {
                    return [
                        'inventory_item_id' => $ingredient['inventory_item_id'],
                        'quantity_used' => $ingredient['quantity_used'] ?? 1,
                    ];
                })->toArray();

                $meal->ingredients()->createMany($ingredientsToInsert);

                // ✅ Reserve the used quantities
                foreach ($validIngredients as $ingredient) {
                    $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                    $inventoryItem->reserved_quantity += $ingredient['quantity_used'] ?? 1;
                    $inventoryItem->save();
                }
            }
        }

        return redirect()->route('mealplans.index')->with('success', 'Meal plan created successfully!');
    }

    public function edit(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $mealPlan->load('meals.ingredients.inventoryItem');

        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);
                $item->quantity = $available;
                return $item;
            });

        return view('mealplans.edit', compact('mealPlan', 'inventoryItems'));
    }

    public function update(Request $request, MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'week_start' => 'required|date',
            'meals' => 'required|array',
        ]);

        // 🧹 Release previously reserved quantities
        foreach ($mealPlan->meals as $meal) {
            foreach ($meal->ingredients as $ingredient) {
                $inventoryItem = $ingredient->inventoryItem;
                if ($inventoryItem) {
                    $inventoryItem->reserved_quantity -= $ingredient->quantity_used;
                    if ($inventoryItem->reserved_quantity < 0) {
                        $inventoryItem->reserved_quantity = 0;
                    }
                    $inventoryItem->save();
                }
            }
        }

        // Delete meals (and cascade ingredients)
        $mealPlan->meals()->delete();

        $mealPlan->update(['week_start' => $request->week_start]);

        foreach ($request->meals as $mealData) {
            $submittedIngredients = $mealData['ingredients'] ?? [];
            $validIngredients = collect($submittedIngredients)
                ->filter(fn($ingredient) => !empty($ingredient['inventory_item_id']))
                ->toArray();

            $recipeName = $mealData['recipe_name'] ?? null;
            if (empty($recipeName) && empty($validIngredients)) continue;

            // ✅ Validate stock again before re-saving
            foreach ($validIngredients as $ingredient) {
                $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                $quantityUsed = $ingredient['quantity_used'] ?? 1;

                if ($inventoryItem && ($inventoryItem->reserved_quantity + $quantityUsed) > $inventoryItem->quantity) {
                    throw ValidationException::withMessages([
                        'meals' => "You only have {$inventoryItem->quantity} units of {$inventoryItem->name} available (" .
                            ($inventoryItem->quantity - $inventoryItem->reserved_quantity) .
                            " left unreserved).",
                    ]);
                }
            }

            // ✅ Create updated meal
            $meal = $mealPlan->meals()->create([
                'date' => $mealData['date'],
                'meal_type' => $mealData['meal_type'],
                'recipe_name' => $recipeName,
                'notes' => $mealData['notes'] ?? null,
            ]);

            if (!empty($validIngredients)) {
                $ingredientsToInsert = collect($validIngredients)->map(function ($ingredient) {
                    return [
                        'inventory_item_id' => $ingredient['inventory_item_id'],
                        'quantity_used' => $ingredient['quantity_used'] ?? 1,
                    ];
                })->toArray();

                $meal->ingredients()->createMany($ingredientsToInsert);

                // ✅ Re-reserve new ingredient quantities
                foreach ($validIngredients as $ingredient) {
                    $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                    $inventoryItem->reserved_quantity += $ingredient['quantity_used'] ?? 1;
                    $inventoryItem->save();
                }
            }
        }

        return redirect()->route('mealplans.index')->with('success', 'Meal plan updated successfully!');
    }

    // ✅ DELETE MEAL PLAN — includes releasing reserved quantities
    public function destroy(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403);
        }

        // Loop through all ingredients tied to this plan and restore reserved quantities
        foreach ($mealPlan->meals as $meal) {
            foreach ($meal->ingredients as $ingredient) {
                $inventoryItem = $ingredient->inventoryItem;

                if ($inventoryItem) {
                    $inventoryItem->quantity += $ingredient->quantity_used;
                    $inventoryItem->reserved_quantity -= $ingredient->quantity_used;
                    $inventoryItem->save();
                }
            }
        }

        // Delete the meal plan (this should also delete meals and ingredients if cascaded)
        $mealPlan->delete();

        return redirect()->route('mealplans.index')->with('success', 'Meal plan deleted and inventory restored.');
    }

}
