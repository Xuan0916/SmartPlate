<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-2">
                        Welcome to Save Plate, {{ Auth::user()->name }}! 👋
                    </h1>
                    <p class="text-gray-600">Here’s a quick summary of your food-saving journey.</p>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Meal Plan Progress --}}
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Meal Plan Progress</h3>
                    <p class="text-3xl font-bold text-green-600">{{ $mealProgress ?? '0%' }}</p>
                    <p class="text-gray-500 text-sm">Completed this week</p>
                </div>

                {{-- Food Saved Last Month --}}
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Food Saved (Last Month)</h3>
                    <p class="text-3xl font-bold text-green-600">
                        {{ $foodSavedLastMonth ?? 'No data' }}
                    </p>
                    <p class="text-gray-500 text-sm">Total weight or items saved</p>
                </div>

                {{-- Total Meals Completed --}}
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Meals Completed</h3>
                    <p class="text-3xl font-bold text-green-600">{{ $totalMeals ?? 0 }}</p>
                    <p class="text-gray-500 text-sm">Since you joined</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
