<x-app-layout>
    <div class="min-h-screen py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Charts Section --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Total Food Saved Chart (Percentage) -->
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Food Saved Overview</h2>
                    <canvas id="foodSavedChart" height="200"></canvas>
                </div>

                <!-- Donations Chart with filter -->
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Donations Overview</h2>

                    <div class="mb-3">
                        <label for="donationFilter" class="form-label text-sm font-medium">Filter by:</label>
                        <select id="donationFilter" class="form-select form-select-sm w-40">
                            <option value="day">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="all">All Time</option>
                        </select>
                    </div>

                    <canvas id="donationChart" height="200"></canvas>
                </div>

            </div>

            {{-- Waste By Category --}}
            <div class="mt-6 bg-white p-6 shadow-sm rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Waste By Category</h2>
                <canvas id="wasteCategoryChart" height="200"></canvas>
            </div>

        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        // --- Donations Chart with filter ---
        const ctxDonation = document.getElementById('donationChart').getContext('2d');
        let donationChart = new Chart(ctxDonation, {
            type: 'bar',
            data: {
                labels: ['Donations'],
                datasets: [{
                    label: 'Units Donated',
                    data: [{{ $totalDonated ?? 0 }}],
                    backgroundColor: ['#facc15']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // --- Donations filter ---
        const donationFilter = document.getElementById('donationFilter');
        donationFilter.addEventListener('change', function() {
            const filter = this.value;

            // Fetch filtered data via AJAX
            fetch(`/analytics/donations?filter=${filter}`)
                .then(res => res.json())
                .then(data => {
                    donationChart.data.labels = data.labels;
                    donationChart.data.datasets[0].data = data.values;
                    donationChart.update();
                })
                .catch(err => console.error(err));
        });

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
                    backgroundColor: [
                        '#ef4444', '#f87171', '#fca5a5', '#dc2626', '#b91c1c', '#fca5a5'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'right' } }
            }
        });
    </script>
</x-app-layout>
