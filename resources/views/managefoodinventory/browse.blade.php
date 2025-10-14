{{-- resources/views/managefoodinventory/browse.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Browse Food Items') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex gap-6">
                {{-- ✅ Sidebar --}}
                <aside class="w-56 bg-white shadow-sm rounded-lg p-4 border border-gray-200">
                    <nav class="flex flex-col space-y-2">
                        <a href="{{ route('inventory.index') }}" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Inventory</a>
                        <a href="{{ route('donation.index') }}" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Donation</a>
                        <a href="{{ route('browse.index') }}" 
                           class="px-3 py-2 rounded-md {{ request()->routeIs('browse.index') ? 'bg-blue-100 font-semibold text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                           Browse Food Items
                        </a>
                    </nav>
                </aside>

                {{-- ✅ Main Content --}}
                <div class="flex-1 bg-white shadow-sm sm:rounded-lg p-6">
                    {{-- Page Title --}}
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">{{ __('Available Food Items') }}</h3>
                    </div>

                    {{-- ✅ Filter Form --}}
                    <form method="GET" action="{{ route('browse.index') }}" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="">All Sources</option>
                                <option value="inventory" {{ request('type') == 'inventory' ? 'selected' : '' }}>My Inventory</option>
                                <option value="donation" {{ request('type') == 'donation' ? 'selected' : '' }}>Donations</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="Dairy" {{ request('category') == 'Dairy' ? 'selected' : '' }}>Dairy</option>
                                <option value="Meat" {{ request('category') == 'Meat' ? 'selected' : '' }}>Meat</option>
                                <option value="Vegetable" {{ request('category') == 'Vegetable' ? 'selected' : '' }}>Vegetable</option>
                                <option value="Fruit" {{ request('category') == 'Fruit' ? 'selected' : '' }}>Fruit</option>
                                <option value="Grain" {{ request('category') == 'Grain' ? 'selected' : '' }}>Grain</option>
                                <option value="Drink" {{ request('category') == 'Drink' ? 'selected' : '' }}>Drink</option>
                                <option value="Snack" {{ request('category') == 'Snack' ? 'selected' : '' }}>Snack</option>
                                <option value="Other" {{ request('category') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <input type="date" name="expiry_date" class="form-control" value="{{ request('expiry_date') }}">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </form>

                    {{-- ✅ Browse Table --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Expiry Date</th>
                                    <th>From</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>{{ $item->name ?? $item->item_name }}</td>
                                        <td>{{ $item->category ?? '-' }}</td>
                                        <td>{{ $item->quantity ?? '-' }}</td>
                                        <td>{{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            @if (isset($item->user))
                                                {{ $item->user->name }}
                                            @else
                                                You
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->status === 'available')
                                                <span class="badge bg-success">Available</span>
                                            @elseif ($item->status === 'used')
                                                <span class="badge bg-secondary">Used</span>
                                            @elseif ($item->status === 'reserved')
                                                <span class="badge bg-warning text-dark">Reserved</span>
                                            @elseif ($item->status === 'expired')
                                                <span class="badge bg-danger text-dark">Expired</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                           {{-- Mark as Used --}}
                                            @if ($item->status !== 'used')
                                                <form action="{{ route('inventory.markUsed', $item->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                        onclick="return confirm('Are you sure you want to mark this item as used?')">
                                                        Mark as Used
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Plan for Meal --}}
                                            @if ($item->status !== 'reserved' && $item->status !== 'used')
                                                <form action="{{ route('inventory.planMeal', $item->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-outline-warning btn-sm"
                                                        onclick="return confirm('Do you want to reserve this item for a meal?')">
                                                        Plan for Meal
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Convert to Donation --}}
                                            @if ($item->status !== 'used' && $item->status !== 'reserved')
                                                <a href="{{ route('inventory.convert.form', $item->id) }}" class="text-success ms-2">
                                                    <button type="submit" class="btn btn-outline-success btn-sm"
                                                        onclick="return confirm('Do you want to convert this item into a donation?')">
                                                        Donate
                                                    </button>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">No items found. Please adjust your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div> {{-- end main --}}
            </div>
        </div>
    </div>
</x-app-layout>