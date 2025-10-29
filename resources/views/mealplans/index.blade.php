<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Plan Weekly Meals') }}
        </h2>
    </x-slot>
    <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-end mb-6">
            <a href="{{ route('mealplans.create') }}"
            class="btn btn-primary">
                + Plan New Week
            </a>
        </div>
        @if ($mealPlans->isNotEmpty())
            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($mealPlans as $plan)
                    @php
                        $start = \Carbon\Carbon::parse($plan->week_start);
                        $end = $start->clone()->addDays(6);
                        $firstMeals = $plan->meals->take(3); // just preview few
                    @endphp

                    <div class="bg-white border shadow-sm rounded-lg p-5 hover:shadow-md transition">
                        <h3 class="text-lg font-semibold text-indigo-700">
                            {{ $start->format('M j') }} – {{ $end->format('M j, Y') }}
                        </h3>

                        <ul class="mt-3 text-sm text-gray-600 space-y-1">
                            @forelse ($firstMeals as $meal)
                                <li>🍽️ {{ ucfirst($meal->meal_type) }} – {{ $meal->recipe_name ?? 'Untitled' }}</li>
                            @empty
                                <li class="italic text-gray-400">No meals added</li>
                            @endforelse
                        </ul>

                        <div class="mt-4 flex justify-between items-center">
                            <a href="{{ route('mealplans.show', $plan) }}" class="text-indigo-600 text-sm font-medium hover:underline">
                                View Details
                            </a>
                            <a href="{{ route('mealplans.edit', $plan) }}" class="text-gray-500 text-sm hover:text-indigo-700">
                                ✏️ Edit
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-600 mb-4">You don't have any meal plans yet.</p>
                <a href="{{ route('mealplans.create') }}" class="btn btn-primary">
                    + Create your first plan
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
