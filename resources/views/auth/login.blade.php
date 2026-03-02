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
                <x-application-logo class="w-20 h-20 fill-current text-white flex items-center justify-center" />
                
                </a>
        </div>
        <div class="w-full max-w-md bg-white/95 backdrop-blur-md px-8 py-10 shadow-2xl rounded-2xl">
            
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900">Admin Portal</h2>
                <p class="text-sm text-gray-500 mt-2">Laundry Management System</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" >
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" class="font-semibold" />
                    <x-text-input id="email" class="block mt-1 w-full border-gray-300 focus:ring-yellow-500 focus:border-yellow-500" 
                                 type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-6" x-data="{ show: false }">
                    <x-input-label for="password" :value="__('Password')" class="font-semibold" />
                    
                    <div class="relative mt-1">
                        <x-text-input id="password" 
                                     class="block w-full border-gray-300 focus:ring-yellow-500 focus:border-yellow-500"
                                     ::type="show ? 'text' : 'password'"
                                     name="password" required />
                        
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.411m0 0L21 21m-2.105-2.105m-2.79-2.79A3 3 0 1111.21 11.21m.065 7.676A10.059 10.059 0 013.343 13.5m1.5-1.5L21 3" />
                            </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex items-center justify-between mt-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-yellow-600 shadow-sm focus:ring-yellow-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a class="text-sm text-yellow-700 hover:underline" href="{{ route('password.request') }}">Forgot username?</a>
                    @endif
                </div>

                <div class="mt-8">
                    <x-primary-button type="submit" class="w-full flex items-center justify-between bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 rounded-lg shadow-lg transition duration-200">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-600 mb-3">Don't have an admin account?</p>
                    <a href="{{ route('register') }}" 
                    class="inline-block w-full py-2 px-4 border-2 border-yellow-500 text-yellow-700 font-bold rounded-lg hover:bg-yellow-500 hover:text-white transition duration-200">
                        Create New Account
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>