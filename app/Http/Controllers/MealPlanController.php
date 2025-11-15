<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MealPlan;
use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Notification;
use Carbon\Carbon;

class MealPlanController extends Controller
{
    public function index()
    {
        $mealPlans = MealPlan::where('user_id', Auth::id())
            ->with(['meals.ingredients' => function($query) {
                $query->whereHas('inventoryItem', function($q) {
                    $q->where('status', '!=', 'expired'); // exclude expired items
                });
            }, 'meals.ingredients.inventoryItem' => function($query) {
                $query->where('status', '!=', 'expired'); // exclude expired items
            }])
            ->latest()
            ->get();

        return view('mealplans.index', compact('mealPlans'));
    }

    public function create()
    {
        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->where('status', '!=', 'expired')
            ->where(function ($query) {
                $query->whereNull('reserved_quantity')
                    ->orWhereColumn('quantity', '>', 'reserved_quantity'); // only show items with available stock
            })
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);

                // Replace quantity with available
                $item->quantity = $available;
                return $item;
            });
        // ✅ Hardcoded recipes
        $recipes = [
            [
                'name' => 'Fried Rice',
                'ingredients' => [
                    ['name' => 'Rice', 'quantity_used' => 2],
                    ['name' => 'Egg', 'quantity_used' => 1],
                    ['name' => 'Oil', 'quantity_used' => 1],
                ],
            ],
            [
                'name' => 'Chicken Soup',
                'ingredients' => [
                    ['name' => 'Chicken', 'quantity_used' => 1],
                    ['name' => 'Water', 'quantity_used' => 1],
                    ['name' => 'Salt', 'quantity_used' => 1],
                ],
            ],
            [
                'name' => 'Salad Bowl',
                'ingredients' => [
                    ['name' => 'Lettuce', 'quantity_used' => 1],
                    ['name' => 'Tomato', 'quantity_used' => 1],
                    ['name' => 'Cucumber', 'quantity_used' => 1],
                ],
            ],
        ];

        return view('mealplans.create', compact('inventoryItems', 'recipes'));
    }

    public function show(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403);
        }

        $mealPlan->load([
            'meals.ingredients' => function($query) {
                $query->whereHas('inventoryItem', function($q) {
                    $q->where('status', '!=', 'expired');
                });
            },
            'meals.ingredients.inventoryItem' => function($query) {
                $query->where('status', '!=', 'expired');
            }
        ]);

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
            $ingredients = collect($mealData['ingredients'] ?? [])
                ->filter(fn($i) => !empty($i['inventory_item_id']))
                ->toArray();

            $recipeName = $mealData['recipe_name'] ?: ($mealData['custom_recipe_name'] ?? null);
            if (empty($recipeName) && empty($ingredients)) continue;

            // 1️⃣ Validate stock and update inventory
            foreach ($ingredients as $ingredient) {
                $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                $quantityUsed = $ingredient['quantity_used'] ?? 1;

                if ($inventoryItem) {
                    $availableQty = $inventoryItem->quantity;
                    if ($quantityUsed > $availableQty) {
                        throw ValidationException::withMessages([
                            'meals' => "You only have {$availableQty} units of {$inventoryItem->name} available."
                        ]);
                    }

                    // Update inventory: subtract quantity & reserve
                    $inventoryItem->quantity -= $quantityUsed;
                    $inventoryItem->reserved_quantity += $quantityUsed;
                    $inventoryItem->save();
                }
            }

            // 2️⃣ Create meal and ingredients
            $meal = $mealPlan->meals()->create([
                'date' => $mealData['date'],
                'meal_type' => $mealData['meal_type'],
                'recipe_name' => $recipeName,
                'notes' => $mealData['notes'] ?? null,
            ]);

            if (!empty($ingredients)) {
                $meal->ingredients()->createMany($ingredients);
            }

            // 3️⃣ Optional: Notification
            $reminderDate = Carbon::parse($meal->date)->subDay();
            Notification::create([
                'user_id' => Auth::id(),
                'item_name' => $meal->recipe_name,
                'message' => 'Reminder: You have "' . $meal->recipe_name . '" scheduled for ' . $meal->date,
                'expiry_date' => $reminderDate,
                'status' => 'new',
            ]);
        }

        return redirect()->route('mealplans.index')->with('success', 'Meal plan created successfully!');
    }


    public function edit(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->where('status', '!=', 'expired')
            ->where(function ($query) {
                $query->whereNull('reserved_quantity')
                    ->orWhereColumn('quantity', '>', 'reserved_quantity'); // only show items with available stock
            })
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);

                // Replace quantity with available
                $item->quantity = $available;
                return $item;
            });
        // ✅ Same hardcoded recipes
        $recipes = [
            [
                'name' => 'Fried Rice',
                'ingredients' => [
                    ['name' => 'Rice', 'quantity_used' => 2],
                    ['name' => 'Egg', 'quantity_used' => 1],
                    ['name' => 'Oil', 'quantity_used' => 50],
                ],
            ],
            [
                'name' => 'Chicken Soup',
                'ingredients' => [
                    ['name' => 'Chicken', 'quantity_used' => 1],
                    ['name' => 'Water', 'quantity_used' => 1],
                    ['name' => 'Salt', 'quantity_used' => 1],
                ],
            ],
            [
                'name' => 'Salad Bowl',
                'ingredients' => [
                    ['name' => 'Lettuce', 'quantity_used' => 1],
                    ['name' => 'Tomato', 'quantity_used' => 1],
                    ['name' => 'Cucumber', 'quantity_used' => 1],
                ],
            ],
        ];

        $mealPlan->load([
            'meals.ingredients' => function($query) {
                $query->whereHas('inventoryItem', function($q) {
                    $q->where('status', '!=', 'expired');
                });
            },
            'meals.ingredients.inventoryItem' => function($query) {
                $query->where('status', '!=', 'expired');
            }
        ]);

        return view('mealplans.edit', compact('mealPlan', 'inventoryItems','recipes'));
    }

    public function update(Request $request, MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

        $request->validate([
            'week_start' => 'required|date',
            'meals' => 'array',
        ]);

        // 1️⃣ Restore inventory from old meals
        foreach ($mealPlan->meals as $meal) {
            foreach ($meal->ingredients as $ingredient) {
                $item = InventoryItem::find($ingredient->inventory_item_id);
                if ($item) {
                    $item->quantity += $ingredient->quantity_used;
                    $item->reserved_quantity -= $ingredient->quantity_used;
                    if ($item->reserved_quantity < 0) $item->reserved_quantity = 0;
                    $item->save();
                }
            }
        }

        // Delete old meals
        $mealPlan->meals()->delete();
        $mealPlan->update(['week_start' => $request->week_start]);

        // 2️⃣ Recreate new meals with inventory updates
        foreach ($request->input('meals', []) as $mealData) {
            $ingredients = collect($mealData['ingredients'] ?? [])
                ->filter(fn($i) => !empty($i['inventory_item_id']))
                ->toArray();

            $recipeName = $mealData['recipe_name'] ?: ($mealData['custom_recipe_name'] ?? null);
            if (empty($recipeName) && empty($ingredients)) continue;

            // Update inventory
            foreach ($ingredients as $ingredient) {
                $item = InventoryItem::find($ingredient['inventory_item_id']);
                $quantityUsed = $ingredient['quantity_used'] ?? 1;
                if ($item) {
                    if ($quantityUsed > $item->quantity) {
                        throw ValidationException::withMessages([
                            'meals' => "You only have {$item->quantity} units of {$item->name} available."
                        ]);
                    }

                    $item->quantity -= $quantityUsed;
                    $item->reserved_quantity += $quantityUsed;
                    $item->save();
                }
            }

            $meal = $mealPlan->meals()->create([
                'date' => $mealData['date'],
                'meal_type' => $mealData['meal_type'],
                'recipe_name' => $recipeName,
            ]);

            if (!empty($ingredients)) {
                $meal->ingredients()->createMany($ingredients);
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
