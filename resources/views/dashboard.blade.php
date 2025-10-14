<x-app-layout>
    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Welcome Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h1 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
                        Welcome to Smart Plate, {{ Auth::user()->name }}! 👋
                    </h1>
                    <p class="text-gray-600">Here’s a quick summary of your food-saving journey.</p>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="card-group text-center gap-4">

                <!-- Meal Plan Progress -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-white" 
                        style="height: 250px; background: linear-gradient(135deg, #16a34a, #4ade80);">
                        <h5 class="card-title fw-semibold mb-2">Meal Plan Progress</h5>
                        <h2 class="fw-bold fs-1">{{ $mealProgress ?? '0%' }}</h2>
                        <p class="text-light small mb-0 opacity-75">Completed this week</p>
                    </div>
                </div>

                <!-- Food Saved Last Month -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-white" 
                        style="height: 250px; background: linear-gradient(135deg, #2563eb, #60a5fa);">
                        <h5 class="card-title fw-semibold mb-2">Food Saved (Last Month)</h5>
                        <h2 class="fw-bold fs-1">{{ $foodSavedLastMonth ?? 'No data' }}</h2>
                        <p class="text-light small mb-0 opacity-75">Total weight or items saved</p>
                    </div>
                </div>

                <!-- Total Meals Completed -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-white" 
                        style="height: 250px; background: linear-gradient(135deg, #f59e0b, #fcd34d);">
                        <h5 class="card-title fw-semibold mb-2">Total Meals Completed</h5>
                        <h2 class="fw-bold fs-1">{{ $totalMeals ?? 0 }}</h2>
                        <p class="text-light small mb-0 opacity-75">Since you joined</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
