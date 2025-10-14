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

                   
                </div> {{-- end main --}}
            </div>
        </div>
    </div>
</x-app-layout>