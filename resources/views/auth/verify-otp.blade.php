<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Enter the 6-digit verification code sent to your email.') }}
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verify.otp.submit') }}">
        @csrf

        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <x-input-label for="code" :value="__('Verification Code')" />
            <x-text-input id="code" name="code" type="text" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <x-primary-button>
                {{ __('Verify Code') }}
            </x-primary-button>
        </div>
    </form>

    {{-- Separate form for resend --}}
    <form method="POST" action="{{ route('resend.code') }}" class="mt-3">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <x-secondary-button type="submit">
            {{ __('Resend Code') }}
        </x-secondary-button>
    </form>
</x-guest-layout>
