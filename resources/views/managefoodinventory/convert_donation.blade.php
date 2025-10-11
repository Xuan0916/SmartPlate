{{-- resources/views/convert_donation.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Convert to Donation') }}
        </h2>
    </x-slot>

    <div class="py-12" style="background:#fafafa;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- NOTE:
                     - Replace $item->name with real data later.
                     - This assumes controller passes the $item variable.
                --}}
                <p class="text-lg mb-4">
                    {{ __('Are you sure you want to convert') }}
                    <strong>{{ $item->name ?? 'Milk' }}</strong>
                    {{ __('to donation?') }}
                </p>

                {{-- Form section --}}
                <form method="POST" action="{{ route('donation.convert') }}">
                    @csrf
                    {{-- Optionally add @method('PUT') if you’re updating an existing record --}}

                    <div class="mb-3">
                        <label class="form-label">{{ __('Pickup Location') }}</label>
                        <input 
                            type="text"
                            name="pickup_location"
                            class="form-control"
                            placeholder="{{ __('Enter pickup location (e.g., Lobby A)') }}"
                            required
                        />
                        {{-- Validation tip: will be required in backend --}}
                    </div>

                    <div class="mb-4">
                        <label class="form-label">{{ __('Pickup Duration') }}</label>
                        <input 
                            type="text"
                            name="pickup_duration"
                            class="form-control"
                            placeholder="{{ __('Enter available time (e.g., Today 2–5 PM)') }}"
                            required
                        />
                        {{-- Later you can switch to a select or datetime range --}}
                    </div>

                    <div class="d-flex gap-2">
                        {{-- Cancel: redirect back to inventory list --}}
                        <a class="btn btn-outline-secondary" href="{{ route('inventory.index') }}">
                            {{ __('Cancel') }}
                        </a>

                        {{-- Convert button --}}
                        <button class="btn btn-primary" type="submit">
                            {{ __('Convert') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
