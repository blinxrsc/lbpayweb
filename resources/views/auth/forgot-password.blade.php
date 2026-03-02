<x-guest-layout>
    <div class="fixed inset-0 z-0 w-full h-full">
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" 
             style="background-image: url('{{ asset('storage/images/laundry-bg.jpg') }}');">
        </div>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-right justify-top p-4 bg-gray-100">
        <div class="mb-6">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-white flex items-center justify-between" />
                
                </a>
        </div>
        <div class="mb-4 text-sm text-gray-600">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

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
    </div>
</x-guest-layout>
