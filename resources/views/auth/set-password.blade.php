<x-guest-layout>
    <form method="POST" action="{{ route('set.password.submit') }}">
        @csrf
        <input type="hidden" name="email" value="{{ session('email') }}">

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" name="password" type="password" required autofocus />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" required />
        </div>

        <div class="mt-4">
            <x-primary-button>{{ __('Set Password') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
