{{-- resources/views/managefoodinventory/inventory.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Food Inventory') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex gap-6">
                {{-- ✅ Sidebar --}}
                <aside class="w-56 bg-white shadow-sm rounded-lg p-4 border border-gray-200">
                    <nav class="flex flex-col space-y-2">
                        <a href="{{ route('inventory.index') }}" 
                           class="px-3 py-2 rounded-md {{ request()->routeIs('inventory.index') ? 'bg-blue-100 font-semibold text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                           Inventory
                        </a>
                        <a href="{{ route('donation.index') }}" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Donation</a>
                        <a href="{{ route('browse.index') }}" class="px-3 py-2 rounded-md text-gray-700 hover:bg-gray-50">Browse Food Items</a>
                    </nav>
                </aside>

                {{-- ✅ Main Content --}}
                <div class="flex-1">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">{{ __('Food Inventory') }}</h3>
                            <button class="btn btn-primary btn-sm" id="toggleAddForm" type="button">
                                <i class="bi bi-plus-lg"></i> Add new item
                            </button>
                        </div>

                        {{-- Add Item Form (unchanged) --}}
                        <div id="add-new-item" class="mt-4" style="display: none;">
                            <div class="card mb-1">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">{{ __('Add Item') }}</h6>
                                    <form method="POST" action="{{ route('inventory.store') }}">
                                        @csrf
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Item name</label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Category</label>
                                                <select name="category" class="form-select" required>
                                                    <option value="">Select category</option>
                                                    <option value="Dairy">Dairy</option>
                                                    <option value="Meat">Meat</option>
                                                    <option value="Vegetable">Vegetable</option>
                                                    <option value="Fruit">Fruit</option>
                                                    <option value="Grain">Grain</option>
                                                    <option value="Drink">Drink</option>
                                                    <option value="Snack">Snack</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Quantity</label>
                                                <input type="number" name="quantity" class="form-control" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Unit</label>
                                                <select name="unit" class="form-select" required>
                                                    <option>pcs</option>
                                                    <option>packs</option>
                                                    <option>litres</option>
                                                    <option>g</option>
                                                    <option>ml</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Expiry date</label>
                                                <input type="date" name="expiry_date" class="form-control" min="{{ date('Y-m-d') }}">
                                            </div>
                                        </div>

                                        <div class="mt-3 flex gap-2">
                                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                            <button class="btn btn-outline-secondary btn-sm" type="reset">Reset</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- ✅ Inventory Table --}}
                        <div class="table-responsive mb-4">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $item)
                                        @php
                                            $reserved = $item->reserved_quantity ?? 0;
                                            $available = max($item->quantity - $reserved, 0);
                                            $percent = $item->quantity > 0 ? round(($reserved / $item->quantity) * 100) : 0;

                                            // Color logic for progress bar
                                            $barColor = $percent < 40 ? 'bg-success' : ($percent < 80 ? 'bg-warning' : 'bg-danger');
                                        @endphp

                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->category ?? '-' }}</td>
                                            <td>
                                                <div>
                                                    <strong>{{ $available }}</strong> / {{ $item->quantity }} {{ $item->unit }}
                                                    @if ($reserved > 0)
                                                        <div class="small text-muted mt-1">
                                                            Reserved: {{ $reserved }} ({{ $percent }}%)
                                                        </div>
                                                        <div class="progress mt-1" style="height: 8px;">
                                                            <div 
                                                                class="progress-bar {{ $barColor }}" 
                                                                role="progressbar" 
                                                                style="width: {{ $percent }}%; transition: width 0.3s ease;"
                                                                aria-valuenow="{{ $percent }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $item->expiry_date ? $item->expiry_date->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                @if ($item->status === 'expired')
                                                    <span class="badge bg-danger text-dark">Expired</span>
                                                @elseif ($reserved > 0 && $reserved < $item->quantity)
                                                    <span class="badge bg-warning text-dark">Partially Reserved</span>
                                                @elseif ($reserved >= $item->quantity)
                                                    <span class="badge bg-secondary">Fully Reserved</span>
                                                @else
                                                    <span class="badge bg-success">Available</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-outline-primary btn-sm me-1">
                                                    Edit
                                                </a>

                                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="inline d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('Delete this item?')">
                                                        Delete
                                                    </button>
                                                </form>

                                                @if ($item->status !== 'used' && $reserved < $item->quantity && $item->status !== 'expired')
                                                    <a href="{{ route('inventory.convert.form', $item->id) }}">
                                                        <button type="button" class="btn btn-outline-success btn-sm ms-1"
                                                            onclick="return confirm('Convert this item into a donation?')">
                                                            Donate
                                                        </button>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No items found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>

                            </table>
                        </div>      

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toggle Add Form --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleAddForm');
            const formSection = document.getElementById('add-new-item');
            toggleButton.addEventListener('click', function() {
                formSection.style.display = formSection.style.display === 'block' ? 'none' : 'block';
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
