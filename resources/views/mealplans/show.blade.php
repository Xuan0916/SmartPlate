<x-app-layout>
    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl sm:rounded-lg p-6">

            {{-- ✅ No need to check $mealPlans — use the single $mealPlan --}}
            @if ($mealPlan)
                @php
                    $currentPlan = $mealPlan;
                    $startDate = \Carbon\Carbon::parse($mealPlan->week_start);
                    $endDate = $startDate->clone()->addDays(6);
                    // Group meals by date and type
                    $mealsByDateAndType = $mealPlan->meals->groupBy(function ($meal) {
                        return \Carbon\Carbon::parse($meal->date)->format('Y-m-d') . '_' . $meal->meal_type;
                    });
                @endphp

                {{-- --- Navigation Header --- --}}
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Weekly Meal Plan</h2>
                    <div class="flex items-center space-x-2">

                        {{-- 🔹 Prev button --}}
                        @if($previousPlan)
                            <a href="{{ route('mealplans.show', $previousPlan->id) }}"
                            class="btn border px-3 py-2 rounded text-gray-700 bg-gray-100 hover:bg-gray-200 mx-2">
                                &larr; Prev
                            </a>
                        @else
                            <button class="btn border px-3 py-2 rounded text-gray-400 bg-gray-100 mx-2" disabled>
                                &larr; Prev
                            </button>
                        @endif

                        <span class="border px-4 py-2 rounded text-gray-700">
                            {{ $startDate->format('Y-m-d') }} — {{ $endDate->format('Y-m-d') }}
                        </span>

                        {{-- 🔹 Next button --}}
                        @if($nextPlan)
                            <a href="{{ route('mealplans.show', $nextPlan->id) }}"
                            class="btn border px-3 py-2 rounded text-gray-700 bg-gray-100 hover:bg-gray-200 mx-2">
                                Next &rarr;
                            </a>
                        @else
                            <button class="btn border px-3 py-2 rounded text-gray-400 bg-gray-100 mx-2" disabled>
                                Next &rarr;
                            </button>
                        @endif

                        <a href="{{ route('mealplans.edit', $mealPlan) }}" class="btn btn-success mx-2">
                            Edit Plan
                        </a>
                        <a href="{{ route('mealplans.create') }}" class="btn btn-primary">
                            + Plan New Week
                        </a>
                        <a href="{{ route('mealplans.index') }}" class="btn btn-secondary">
                            &larr; Back to Index
                        </a>
                    </div>
                </div>

                {{-- ✅ Weekly Plan Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="w-1/6 p-3 text-left text-sm font-semibold text-gray-600 border border-gray-200">Day</th>
                                @foreach(['Breakfast', 'Lunch', 'Dinner', 'Snack'] as $type)
                                    <th class="p-3 text-center text-sm font-semibold text-gray-600 border border-gray-200">{{ $type }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 7; $i++)
                                @php
                                    $currentDay = $startDate->clone()->addDays($i);
                                    $dateKey = $currentDay->format('Y-m-d');
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 border border-gray-200 text-sm font-medium text-gray-800">
                                        {{ $currentDay->format('l') }} <br>
                                        <span class="text-xs text-gray-500">{{ $currentDay->format('M j') }}</span>
                                    </td>

                                    @foreach(['breakfast', 'lunch', 'dinner', 'snack'] as $type)
                                        @php
                                            $mealKey = $dateKey . '_' . $type;
                                            $meal = $mealsByDateAndType[$mealKey] ?? null;
                                        @endphp

                                        <td class="p-3 border border-gray-200 align-top">
                                            @if ($meal && $meal->first())
                                                @php $m = $meal->first(); @endphp
                                                <div class="font-bold text-sm text-indigo-700 mb-1">{{ $m->recipe_name ?? 'Planned Meal' }}</div>

                                                {{-- Ingredients --}}
                                                @if($m->ingredients->isNotEmpty())
                                                    <ul class="ml-4 text-xs list-disc text-gray-600 space-y-0.5">
                                                        @foreach($m->ingredients as $ingredient)
                                                            <li>
                                                                {{ $ingredient->inventoryItem->name ?? 'Unknown Item' }} 
                                                                <span class="text-gray-400">({{ $ingredient->quantity_used }}x)</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">No ingredients tracked.</span>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400 italic">No meal planned.</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

            @else
                <div class="text-center p-10">
                    <p class="text-gray-600 mb-4">No meal plan found for this week.</p>
                    <a href="{{ route('mealplans.create') }}" class="btn btn-primary btn-sm">
                        + Plan Your First Week
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
