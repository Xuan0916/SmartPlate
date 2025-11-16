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
use Illuminate\Support\Facades\DB;

class MealPlanController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // --- 1️⃣ Send notifications for meals happening tomorrow ---
        $mealsTomorrow = Meal::whereHas('mealPlan', fn($q) => $q->where('user_id', $userId))
            ->where('date', $tomorrow->toDateString())
            ->get();

        foreach ($mealsTomorrow as $meal) {
            // Check if a notification already exists today
            $exists = Notification::where('user_id', $userId)
                ->where('item_name', $meal->recipe_name)
                ->where('message', 'like', '%scheduled for ' . $meal->date . '%')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$exists) {
                Notification::create([
                    'user_id' => $userId,
                    'item_name' => $meal->recipe_name,
                    'message' => 'Reminder: You have "' . $meal->recipe_name . '" scheduled for ' . $meal->date,
                    'expiry_date' => $meal->date,
                    'status' => 'new',
                    'target_type' => 'mealplan',          //  新增
                    'target_id'   => $meal->meal_plan_id, // 新增
                ]);
            }
        }

        // --- 2️⃣ Load meal plans ---
        $mealPlans = MealPlan::where('user_id', $userId)
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
            ->get()
            
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->original_quantity - $reserved, 0); // available = original - reserved
                $item->quantity = $available; // available quantity for JS max
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

        DB::transaction(function() use ($request) {

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

                // --- 1️⃣ Check inventory availability first ---
                foreach ($ingredients as $ingredient) {
                    $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                    $quantityUsed = $ingredient['quantity_used'] ?? 1;

                    if (!$inventoryItem || $quantityUsed > $inventoryItem->quantity) {
                        throw ValidationException::withMessages([
                            'meals' => "Not enough {$inventoryItem->name} available for this ingredient."
                        ]);
                    }
                }

                // --- 2️⃣ Deduct inventory only after all ingredients pass check ---
                foreach ($ingredients as $ingredient) {
                    $inventoryItem = InventoryItem::find($ingredient['inventory_item_id']);
                    $inventoryItem->quantity -= $ingredient['quantity_used'] ?? 1;
                    $inventoryItem->save();
                }

                // --- 3️⃣ Create meal and ingredients ---
                $meal = $mealPlan->meals()->create([
                    'date' => $mealData['date'],
                    'meal_type' => $mealData['meal_type'],
                    'recipe_name' => $recipeName,
                ]);

                if (!empty($ingredients)) {
                    $meal->ingredients()->createMany(
                        collect($ingredients)
                            ->filter(fn($i) => !empty($i['inventory_item_id']))
                            ->map(fn($i) => [
                                'inventory_item_id' => $i['inventory_item_id'],
                                'quantity_used' => $i['quantity_used'] ?? 1
                            ])
                            ->toArray()
                    );
                }
            }
        });

        return redirect()->route('mealplans.index')->with('success', 'Meal plan created successfully!');
    }

    public function edit(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

        $inventoryItems = InventoryItem::where('user_id', Auth::id())
            ->where('status', '!=', 'expired')
            ->get()
            ->map(function ($item) {
                $reserved = $item->reserved_quantity ?? 0;
                $available = max($item->original_quantity - $reserved, 0); // available = original - reserved
                $item->quantity = $available; // available quantity for JS max
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

        return view('mealplans.edit', compact('mealPlan', 'inventoryItems', 'recipes'));
    }

    public function update(Request $request, MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

        $request->validate([
            'week_start' => 'required|date',
            'meals' => 'array',
        ]);

        DB::transaction(function() use ($request, $mealPlan) {

            // 🔄 STEP 1: Restore inventory from old meals
            foreach ($mealPlan->meals as $meal) {
                foreach ($meal->ingredients as $ingredient) {
                    $item = InventoryItem::find($ingredient->inventory_item_id);
                    if ($item) {
                        $item->quantity += $ingredient->quantity_used; // restore availability
                        $item->save();
                    }
                }
            }

            // 🔄 STEP 2: Delete old meals and update week_start
            $mealPlan->meals()->delete();
            $mealPlan->update(['week_start' => $request->week_start]);

            // 🔄 STEP 3: Save new meals
            foreach ($request->input('meals', []) as $mealData) {

                $ingredients = collect($mealData['ingredients'] ?? [])
                    ->filter(fn($i) => !empty($i['inventory_item_id']))
                    ->toArray();

                $recipeName = $mealData['recipe_name'] ?: ($mealData['custom_recipe_name'] ?? null);
                if (empty($recipeName) && empty($ingredients)) continue;

                // --- 3a️⃣ Check all ingredients first ---
                foreach ($ingredients as $ingredient) {
                    $item = InventoryItem::find($ingredient['inventory_item_id']);
                    $quantityUsed = $ingredient['quantity_used'] ?? 1;

                    if (!$item || $quantityUsed > $item->quantity) {
                        throw ValidationException::withMessages([
                            'meals' => "Not enough {$item->name} available for this ingredient."
                        ]);
                    }
                }

                // --- 3b️⃣ Deduct inventory after all ingredients pass check ---
                foreach ($ingredients as $ingredient) {
                    $item = InventoryItem::find($ingredient['inventory_item_id']);
                    $item->quantity -= $ingredient['quantity_used'] ?? 1;
                    $item->save();
                }

                // --- 3c️⃣ Create meal and ingredients ---
                $meal = $mealPlan->meals()->create([
                    'date' => $mealData['date'],
                    'meal_type' => $mealData['meal_type'],
                    'recipe_name' => $recipeName,
                ]);

                if (!empty($ingredients)) {
                    $meal->ingredients()->createMany(
                        collect($ingredients)
                            ->filter(fn($i) => !empty($i['inventory_item_id']))
                            ->map(fn($i) => [
                                'inventory_item_id' => $i['inventory_item_id'],
                                'quantity_used' => $i['quantity_used'] ?? 1
                            ])
                            ->toArray()
                    );
                }
            }
        });

        return redirect()->route('mealplans.index')->with('success', 'Meal plan updated successfully!');
    }


    public function destroy(MealPlan $mealPlan)
    {
        if (Auth::id() != $mealPlan->user_id) abort(403);

        // Restore inventory
        foreach ($mealPlan->meals as $meal) {
            foreach ($meal->ingredients as $ingredient) {
                $inventoryItem = $ingredient->inventoryItem;
                if ($inventoryItem) {
                    $inventoryItem->quantity += $ingredient->quantity_used;
                    $inventoryItem->save();
                }
            }
        }

        $mealPlan->delete();

        return redirect()->route('mealplans.index')->with('success', 'Meal plan deleted and inventory restored.');
    }

}
