{{-- resources/views/managefoodinventory/convert_donation.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Convert to Donation') }}
        </h2>
    </x-slot>

    <div class="py-12" style="background:#fafafa;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- ✅ Success Message (after redirect) --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- ✅ Validation Errors --}}
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                {{-- Page intro text --}}
                <p class="text-lg mb-4">
                    {{ __('Are you sure you want to convert') }}
                    <strong>{{ $item->name ?? 'Sample Item' }}</strong>
                    {{ __('to donation?') }}
                </p>

                {{-- ✅ Form section --}}
                <form method="POST" action="{{ route('donation.convert') }}">
                    @csrf

                    {{-- Hidden field to pass the selected item ID --}}
                    <input type="hidden" name="item_id" value="{{ $item->id }}">

                    {{-- Pickup Location --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Pickup Location') }}</label>
                        <input 
                            type="text"
                            name="pickup_location"
                            class="form-control"
                            placeholder="{{ __('Enter pickup location (e.g., Lobby A)') }}"
                            required
                        />
                    </div>

                    {{-- Pickup Duration --}}
                    <div class="mb-4">
                        <label class="form-label">{{ __('Pickup Duration') }}</label>
                        <input 
                            type="datetime-local"
                            name="pickup_duration"
                            class="form-control"
                            min="{{ date('Y-m-d\TH:i') }}"
                            required
                        />
                        {{-- Example: User can select “2025-10-12T14:00” --}}
                    </div>

                    {{-- Action buttons --}}
                    <div class="d-flex gap-2">
                        {{-- Cancel: redirect back to inventory --}}
                        <a class="btn btn-outline-secondary" href="{{ route('inventory.index') }}">
                            {{ __('Cancel') }}
                        </a>

                        {{-- Confirm Convert --}}
                        <button class="btn btn-primary" type="submit">
                            {{ __('Convert') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ✅ Bootstrap JS for alert dismissal --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
