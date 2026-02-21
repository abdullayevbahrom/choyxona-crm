<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 p-4 text-sm text-slate-700">
            <p>Havola yuborildi. Xatni olmagan bo'lsangiz, boshqa email manzil uchun qayta urinib ko'ring.</p>
            <a href="{{ route('password.request') }}" class="mt-3 inline-block text-sm font-medium text-blue-700 underline">
                Boshqa email manziliga yuborish
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button>
                    {{ __('Email Password Reset Link') }}
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
