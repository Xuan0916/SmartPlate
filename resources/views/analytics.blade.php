<x-app-layout>
    <div class="min-h-screen py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            
            {{-- 1️⃣ KEY METRICS CARDS (Flexbox with FIXED Inline Widths and Styles) --}}
            <div class="flex flex-wrap justify-between gap-4 mb-8">
                
                {{-- Total Food Saved Card --}}
                <div class="bg-white p-5 shadow-lg rounded-xl border-l-4" style="border-color: #10b981; width: 250px;;">
                    <p class="text-sm font-medium text-gray-500">Total Food Saved (All Time)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalFoodSaved ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Via Usage & Donation</p>
                </div>
                
                {{-- Total Waste Card --}}
                <div class="bg-white p-5 shadow-lg rounded-xl border-l-4" style="border-color: #ef4444; width: 250px;;">
                    <p class="text-sm font-medium text-gray-500">Total Waste (All Time)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalWaste ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Expired items tracked</p>
                </div>
                
                {{-- Monthly Saved Percentage Card --}}
                <div class="bg-white p-5 shadow-lg rounded-xl border-l-4" style="border-color: #3b82f6; width: 250px;">
                    <p class="text-sm font-medium text-gray-500">Food Saved Percentage (This Month)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $percentSavedMonthly ?? 0 }}%</p>
                    <p class="text-xs text-gray-400 mt-1">Of total monthly activity</p>
                </div>

                {{-- Total Donated Card --}}
                <div class="bg-white p-5 shadow-lg rounded-xl border-l-4" style="border-color: #f59e0b; width: 250px;">
                    <p class="text-sm font-medium text-gray-500">Total Donations Offered</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalDonated ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Items put up for donation</p>
                </div>

            </div>

            {{-- 2️⃣ ROW 1: Food Saved & Donations Charts (2-column on MD and up) --}}
            <div class="container flex flex-wrap justify-between mx-0 px-0 gap-2">

                {{-- Food Saved Chart (Left) --}}
                <div class="bg-white p-6 shadow-lg rounded-xl" style="width:560px;">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Food Saved vs. Wasted (All Time)</h2>
                    @if(($totalUsed ?? 0) + ($totalWaste ?? 0) + ($totalDonated ?? 0) == 0)
                        <p class="text-gray-500 text-center py-20">
                            You have no food items yet. Let's start a meal plan!
                        </p>
                    @else
                        <canvas id="foodSavedChart" height="200"></canvas>
                    @endif
                </div>

                {{-- Donations Chart (Right) --}}
                <div class="bg-white p-6 shadow-lg rounded-xl" style="width:560px;">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Donations Overview</h2>
                    @if(($totalDonated ?? 0) == 0)
                        <p class="text-gray-500 text-center py-20">
                            You have not donated any items yet. Start donating to see stats here!
                        </p>
                    @else
                        <canvas id="donationChart" height="200"></canvas>
                    @endif
                </div>
            </div>

            {{-- 3️⃣ ROW 2: Waste Category Chart & Placeholder (2-column on MD and up) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                
                {{-- Waste By Category Chart (Left) --}}
                <div class="bg-white p-6 shadow-lg rounded-xl">
                    <h2 class="text-xl font-semibold mb-4 text-gray-700">Waste By Category Breakdown</h2>
                    @if($wasteByCategory->isEmpty())
                        <p class="text-gray-500 text-center py-20">
                            No waste has been recorded yet.
                        </p>
                    @else
                        <canvas id="wasteCategoryChart" height="200"></canvas>
                    @endif
                </div>

                {{-- Quick Tip/CTA Card (Right) --}}
                <div class="bg-white p-6 shadow-lg rounded-xl flex items-center justify-center">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-green-600 mb-2">Need to Save More?</h3>
                        <p class="text-gray-600">Check your <a href="{{ route('inventory.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Inventory</a> for items near expiry!</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Chart.js Scripts (Keep these outside the main content div but inside x-app-layout) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @if(($totalUsed ?? 0) + ($totalWaste ?? 0) + ($totalDonated ?? 0) > 0)
    <script>
        const percentSavedTotal = {{ $percentSavedTotal ?? 0 }};
        const percentSavedMonthly = {{ $percentSavedMonthly ?? 0 }};

        // --- Food Saved Percentage Chart ---
        const ctxFoodSaved = document.getElementById('foodSavedChart').getContext('2d');
        const foodSavedChart = new Chart(ctxFoodSaved, {
            type: 'doughnut',
            data: {
                labels: ['Saved', 'Lost/Wasted'],
                datasets: [{
                    data: [percentSavedTotal, 100 - percentSavedTotal],
                    backgroundColor: ['#16a34a', '#ef4444'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Total Food Saved (%)'
                    }
                }
            }
        });
    </script>
    @endif

    @if(($totalDonated ?? 0) > 0)
    <script>
        // --- Donations Chart ---
        const ctxDonation = document.getElementById('donationChart').getContext('2d');
        let donationChart = new Chart(ctxDonation, {
            type: 'bar', // Bar chart for comparison
            data: {
                // Label for the X-axis (Total)
                labels: ['All Time Donations'], 
                datasets: [
                    {
                        label: 'Total Donated (Offered)', // The total quantity you offered
                        data: [{{ $totalDonated ?? 0 }}],
                        backgroundColor: '#facc15', // Yellow
                    },
                    {
                        label: 'Total Redeemed (Claimed)', // The total quantity that was claimed
                        data: [{{ $totalRedeemed ?? 0 }}],
                        backgroundColor: '#10b981', // Emerald Green
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { 
                    legend: { position: 'top' }, // Show legend to distinguish bars
                    title: { display: true, text: 'Total vs. Redeemed Donations (All Time)' }
                },
                scales: { 
                    x: { stacked: false },
                    y: { beginAtZero: true, stacked: false } 
                }
            }
        });

        // --- Donations filter logic REMOVED ---
    </script>
    @endif

    @if(!$wasteByCategory->isEmpty())
    <script>
        // --- Waste By Category Chart ---
        const ctxCategory = document.getElementById('wasteCategoryChart').getContext('2d');
        const wasteCategoryChart = new Chart(ctxCategory, {
            type: 'pie',
            data: {
                labels: [
                    @foreach($wasteByCategory as $category)
                        "{{ $category->category ?? 'Uncategorized' }}",
                    @endforeach
                ],
                datasets: [{
                    label: 'Units Wasted',
                    data: [
                        @foreach($wasteByCategory as $category)
                            {{ $category->total }},
                        @endforeach
                    ],
                    // Updated color palette for better contrast
                    backgroundColor: [
                        '#ef4444', '#f87171', '#fca5a5', '#dc2626', '#b91c1c', '#fca5a5', '#f87171', '#dc2626'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'right' } }
            }
        });
    </script>
    @endif
</x-app-layout>