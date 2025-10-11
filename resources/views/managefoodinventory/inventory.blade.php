{{-- resources/views/managefoodinventory/inventory.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Food Inventory') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ✅ Success message --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- ✅ Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                {{-- Header row --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ __('Food Inventory') }}</h3>
                    <div class="flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" type="button">
                            <i class="bi bi-funnel"></i> Filters
                        </button>
                        <a class="btn btn-primary btn-sm" href="#add-new-item">
                            <i class="bi bi-plus-lg"></i> Add new item
                        </a>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive mb-4">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Expiry Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td>{{ $item->expiry_date ? $item->expiry_date->format('d/m/Y') : '-' }}</td>
                                    <td class="text-end">
                                        {{-- Delete --}}
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-0 m-0"
                                                onclick="return confirm('Delete this item?')">
                                                Delete
                                            </button>
                                        </form>

                                        {{-- Convert --}}
                                        <a href="{{ route('inventory.convert.form', $item->id) }}"
                                            class="text-success ms-2">Convert</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Add Item Form --}}
                <div id="add-new-item" class="mt-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">{{ __('Add Item') }}</h6>
                            <form method="POST" action="{{ route('inventory.store') }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Item name</label>
                                        <input type="text" name="name" class="form-control" placeholder="e.g., Milk" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" placeholder="e.g., 1" required>
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
                                        <input type="date" name="expiry_date" class="form-control">
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
            </div>
        </div>
    </div>

    {{-- Bootstrap JS for dismissible alerts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
