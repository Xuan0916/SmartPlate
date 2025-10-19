<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Food Item') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Update Item Details</h3>

                <form method="POST" action="{{ route('inventory.update', $item->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">Select category</option>
                                @foreach (['Dairy', 'Meat', 'Vegetable', 'Fruit', 'Grain', 'Drink', 'Snack', 'Other'] as $category)
                                    <option value="{{ $category }}" {{ $item->category == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity', $item->quantity) }}" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select name="unit" class="form-select" required>
                                @foreach (['pcs', 'packs', 'litres', 'g', 'ml'] as $unit)
                                    <option value="{{ $unit }}" {{ $item->unit == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" value="{{ old('expiry_date', $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '') }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach (['available', 'used', 'reserved', 'expired'] as $status)
                                    <option value="{{ $status }}" {{ $item->status == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
