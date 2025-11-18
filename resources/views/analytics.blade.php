<x-app-layout>
    <div class="min-h-screen py-10 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- FILTER UI --}}
            <form method="GET" id="filterForm" class="mb-6 flex justify-end">
                <div class="dropdown">
                    <button 
                        class="btn btn-outline-secondary dropdown-toggle flex items-center gap-2 px-4 py-2"
                        type="button" 
                        id="filterMenuButton" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                    >
                        <span>{{ ucfirst($filter) }}</span>

                    </button>

                    <ul class="dropdown-menu" aria-labelledby="filterMenuButton">
                        <li>
                            <a class="dropdown-item {{ $filter === 'weekly' ? 'active' : '' }}" 
                            href="{{ route('analytics.index', ['filter' => 'weekly']) }}">
                                Weekly
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ $filter === 'monthly' ? 'active' : '' }}" 
                            href="{{ route('analytics.index', ['filter' => 'monthly']) }}">
                                Monthly
                            </a>
                        </li>
                    </ul>
                </div>
            </form>

            {{-- TOP CARDS --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

                {{-- Food Saved --}}
                <div class="bg-white p-5 shadow rounded-xl border-l-4 border-green-500">
                    <p class="text-sm text-gray-500">Food Saved ({{ ucfirst($filter) }})</p>
                    <p class="text-3xl font-bold mt-1">{{ $filteredFoodSaved ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Used + Redeemed</p>
                </div>

                {{-- Waste --}}
                <div class="bg-white p-5 shadow rounded-xl border-l-4 border-red-500">
                    <p class="text-sm text-gray-500">Waste ({{ ucfirst($filter) }})</p>
                    <p class="text-3xl font-bold mt-1">{{ $filteredWaste ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Expired Items</p>
                </div>

                {{-- Saved % --}}
                <div class="bg-white p-5 shadow rounded-xl border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500">Food Saved % ({{ ucfirst($filter) }})</p>
                    <p class="text-3xl font-bold mt-1">{{ $percentSavedFiltered ?? 0 }}%</p>
                    <p class="text-xs text-gray-400 mt-1">Of total activity</p>
                </div>

                {{-- Donations --}}
                <div class="bg-white p-5 shadow rounded-xl border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-500">Donated ({{ ucfirst($filter) }})</p>
                    <p class="text-3xl font-bold mt-1">{{ $filteredDonated ?? 0 }} Units</p>
                    <p class="text-xs text-gray-400 mt-1">Given away</p>
                </div>
            </div>

            {{-- CHARTS --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                {{-- Food Saved vs Waste --}}
                <div class="bg-white p-6 shadow rounded-xl">
                    <h2 class="text-xl font-semibold mb-4">Saved vs. Waste ({{ ucfirst($filter) }})</h2>

                    @if(($filteredFoodSaved + $filteredWaste) == 0)
                        <p class="text-gray-500 text-center py-20">
                            No activity yet — a great time to start planning your meals! 🌱  
                            Every small step helps reduce waste.
                        </p>
                    @else
                        <canvas id="savedChart"></canvas>
                    @endif
                </div>

                {{-- Donations --}}
                <div class="bg-white p-6 shadow rounded-xl">
                    <h2 class="text-xl font-semibold mb-4">Donations ({{ ucfirst($filter) }})</h2>

                    @if($filteredDonated == 0)
                        <p class="text-gray-500 text-center py-20">
                            No donations recorded yet.  
                            Take your first step today — even a small donation can make a big difference! 💛
                        </p>
                    @else
                        <canvas id="donationsChart"></canvas>
                    @endif
                </div>

            </div>

            {{-- Waste By Category --}}
            <div class="bg-white p-6 shadow rounded-xl mt-6">
                <h2 class="text-xl font-semibold mb-4">Waste By Category (All Time)</h2>

                @if($wasteByCategory->isEmpty())
                    <p class="text-gray-500 text-center py-20">
                        No waste recorded — amazing! 🌟  
                        Keep up the great habit of reducing food waste.
                    </p>

                @else
                    <canvas id="categoryChart"></canvas>
                @endif
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Saved vs Waste --}}
    @if(($filteredFoodSaved + $filteredWaste) > 0)
    <script>
        new Chart(document.getElementById('savedChart'), {
            type: 'doughnut',
            data: {
                labels: ['Saved', 'Wasted'],
                datasets: [{
                    data: [{{ $filteredFoodSaved }}, {{ $filteredWaste }}],
                    backgroundColor: ['#16a34a', '#ef4444']
                }]
            }
        });
    </script>
    @endif

    {{-- Donations --}}
    @if($filteredDonated > 0)
    <script>
        new Chart(document.getElementById('donationsChart'), {
            type: 'bar',
            data: {
                labels: ['Donated', 'Redeemed'],
                datasets: [{
                    label: 'Units',
                    data: [{{ $filteredDonated }}, {{ $filteredRedeemed }}],
                    backgroundColor: ['#facc15', '#10b981']
                }]
            }
        });
    </script>
    @endif

    {{-- Category --}}
    @if(!$wasteByCategory->isEmpty())
    <script>
        new Chart(document.getElementById('categoryChart'), {
            type: 'pie',
            data: {
                labels: [
                    @foreach($wasteByCategory as $cat)
                        "{{ $cat->category }}",
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($wasteByCategory as $cat)
                            {{ $cat->total }},
                        @endforeach
                    ],
                    backgroundColor: ['#ef4444','#dc2626','#f87171','#fca5a5','#991b1b']
                }]
            }
        });
    </script>
    @endif
</x-app-layout>
