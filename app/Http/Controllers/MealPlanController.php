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
                    $q->where('status', '!=', 'expired');
                });
            }, 'meals.ingredients.inventoryItem' => function($query) {
                $query->where('status', '!=', 'expired');
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
                    ->orWhereColumn('quantity', '>', 'reserved_quantity');
            })
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);
                $item->quantity = $available;
                return $item;
            });

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

        $startDate = Carbon::parse($mealPlan->week_start);
        $endDate = $startDate->clone()->addDays(6);

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

            // Validate & update inventory
            foreach ($ingredients as $ingredient) {
                $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                $quantityUsed = $ingredient['quantity_used'] ?? 1;

                if ($inventoryItem) {
                    if ($quantityUsed > $inventoryItem->quantity) {
                        throw ValidationException::withMessages([
                            'meals' => "You only have {$inventoryItem->quantity} units of {$inventoryItem->name} available."
                        ]);
                    }

                    $inventoryItem->quantity -= $quantityUsed;
                    $inventoryItem->reserved_quantity += $quantityUsed;
                    $inventoryItem->save();
                }
            }

            // Create meal entry
            $meal = $mealPlan->meals()->create([
                'date' => $mealData['date'],
                'meal_type' => $mealData['meal_type'],
                'recipe_name' => $recipeName,
                'notes' => $mealData['notes'] ?? null,
            ]);

            if (!empty($ingredients)) {
                $meal->ingredients()->createMany($ingredients);
            }

            // ⭐ Add Notification WITH jump target
            $reminderDate = Carbon::parse($meal->date)->subDay();

            Notification::create([
                'user_id'     => Auth::id(),
                'item_name'   => $meal->recipe_name,
                'message'     => 'Reminder: You have "' . $meal->recipe_name . '" scheduled for ' . $meal->date,
                'expiry_date' => $reminderDate,
                'status'      => 'new',

                // ⭐⭐ Added fields
                'target_type' => 'mealplan',
                'target_id'   => $mealPlan->id,
            ]);
        }

        return redirect()->route('mealplans.index')->with('success', 'Meal plan created successfully!');
    }

    public function edit(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->where('status', '!=', 'expired')
            ->where(function ($query) {
                $query->whereNull('reserved_quantity')
                    ->orWhereColumn('quantity', '>', 'reserved_quantity');
            })
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->quantity - $reserved, 0);
                $item->quantity = $available;
                return $item;
            });

        $recipes = [
            ['name' => 'Fried Rice', 'ingredients' => [['name' => 'Rice', 'quantity_used' => 2], ['name' => 'Egg', 'quantity_used' => 1], ['name' => 'Oil', 'quantity_used' => 1]]],
            ['name' => 'Chicken Soup', 'ingredients' => [['name' => 'Chicken', 'quantity_used' => 1], ['name' => 'Water', 'quantity_used' => 1], ['name' => 'Salt', 'quantity_used' => 1]]],
            ['name' => 'Salad Bowl', 'ingredients' => [['name' => 'Lettuce', 'quantity_used' => 1], ['name' => 'Tomato', 'quantity_used' => 1], ['name' => 'Cucumber', 'quantity_used' => 1]]],
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

        // restore inventory
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

        // delete old meals
        $mealPlan->meals()->delete();
        $mealPlan->update(['week_start' => $request->week_start]);

        foreach ($request->input('meals', []) as $mealData) {

            $ingredients = collect($mealData['ingredients'] ?? [])
                ->filter(fn($i) => !empty($i['inventory_item_id']))
                ->toArray();

            $recipeName = $mealData['recipe_name'] ?: ($mealData['custom_recipe_name'] ?? null);
            if (empty($recipeName) && empty($ingredients)) continue;

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

    public function destroy(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

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

        $mealPlan->delete();

        return redirect()->route('mealplans.index')->with('success', 'Meal plan deleted and inventory restored.');
    }
}
