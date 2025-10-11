{{-- resources/views/managefoodinventory/donation.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Donated Items') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex gap-6">
            
            {{-- ✅ Sidebar --}}
            <aside class="w-56 bg-white shadow-sm rounded-lg p-4 border border-gray-200">
                <nav class="flex flex-col space-y-2">
                    <a href="{{ route('inventory.index') }}" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Inventory</a>
                    <a href="{{ route('donation.index') }}" class="px-3 py-2 rounded-md bg-blue-100 font-semibold text-blue-700">Donation</a>
                    <a href="#" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Browse Food Items</a>
                </nav>
            </aside>

            {{-- ✅ Main Content --}}
            <div class="flex-1">

                {{-- ✅ Success Message --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- ❌ Error Message --}}
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- ✅ Main Table Card --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Donation List</h3>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Pickup Location</th>
                                    <th>Pickup Duration</th>
                                    <th>Expiry Date</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($donations as $donation)
                                    <tr>
                                        <td>{{ $donation->item_name }}</td>
                                        <td>{{ $donation->quantity }} {{ $donation->unit }}</td>
                                        <td>{{ $donation->pickup_location }}</td>
                                        <td>{{ $donation->pickup_duration }}</td>
                                        <td>
                                            {{ $donation->expiry_date 
                                                ? \Carbon\Carbon::parse($donation->expiry_date)->format('d/m/Y') 
                                                : '-' 
                                            }}
                                        </td>
                                        <td class="text-end">
                                            {{-- ✅ Remove Donation 按钮 --}}
                                            <form action="{{ route('donation.destroy', $donation->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this donation?')">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            No donated items yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
