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
                                           

                                            {{-- Convert to Donation --}}
                                            @if ($item->status !== 'used' && $item->status !== 'redeemed')
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