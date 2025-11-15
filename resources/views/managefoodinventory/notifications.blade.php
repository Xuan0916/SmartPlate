{{-- resources/views/managefoodinventory/notifications.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- ✅ Success Message --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- ✅ Header --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0">Notifications</h5>

                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button class="btn btn-primary btn-sm" type="submit">
                        Mark all as read
                    </button>
                </form>
            </div>

            {{-- ✅ Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $notifications = $notifications->sortByDesc('expiry_date');
                            @endphp
                            @forelse ($notifications as $note)
                                <tr>
                                    <td>
                                        @if ($note->status === 'new')
                                            <span class="badge bg-danger">NEW</span>
                                        @else
                                            <span class="badge bg-secondary">READ</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 500px;">
                                        <strong>{{ $note->item_name }}</strong> — {{ $note->message }}
                                    </td>
                                    <td>
                                        {{ $note->expiry_date ? \Carbon\Carbon::parse($note->expiry_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        @if ($note->status === 'new')
                                            <form method="POST" action="{{ route('notifications.read', $note->id) }}" style="display:inline;">
                                                @csrf
                                                <button class="btn btn-link p-0 text-success">Mark as read</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('inventory.index') }}" class="ms-2 text-decoration-none">
                                            Go to inventory
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No notifications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Bootstrap JS for alert dismissal --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</x-app-layout>
