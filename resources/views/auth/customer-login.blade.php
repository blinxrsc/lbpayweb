<x-guest-layout>
    <div class="max-w-md mx-auto p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Customer Login</h2>
        <form method="POST" action="{{ route('customer.login.submit') }}">
            @csrf
            <div class="mb-4">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-input w-full @error('email') border-red-500 @enderror" value="{{ old('email') }}" required>
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label>Password</label>
                <input type="password" name="password" class="form-input w-full" required>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex justify-end items-center text-center w-full">
            <x-primary-button type="submit">Login</x-primary-button></div>
        </form>
        <p class="mt-4 text-center">
            <a href="{{ route('customer.register') }}">Sign Up</a> | 
            <a href="{{ route('customer.request') }}">Forgot Password?</a>
        </p>
        
        <p class="mt-4 text-center">
            <a href="{{ route('login') }}">Admin Login</a>
        </p>
    </div>
</x-guest-layout>