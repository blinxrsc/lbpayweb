<x-guest-layout>
    <div class="max-w-md mx-auto p-6 bg-white shadow rounded">
        <h2 class="text-xl font-bold mb-4">Customer Sign Up</h2>

        {{-- Display global error message --}}
        @if ($errors->any())
            <div class="mb-4 text-red-600">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register.submit') }}">
            @csrf

            <!-- Name -->
            <div class="mb-4">
                <label>Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input w-full @error('name') border-red-500 @enderror" required>
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-4">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input w-full @error('email') border-red-500 @enderror" required>
                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Username -->
            <div class="mb-4">
                <label>Username</label>
                <input type="text" name="username" value="{{ old('username') }}" class="form-input w-full @error('username') border-red-500 @enderror" required>
                @error('username')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div class="mb-4">
                <label>Phone Number</label>
                <div class="flex">
                    <input type="hidden" name="phone_country_code" value="60">
                    <span class="inline-flex items-center px-3 bg-gray-200 border border-r-0 border-gray-300 rounded-l">
                        +60
                    </span>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="123456789" class="form-input flex-1 rounded-r @error('phone_number') border-red-500 @enderror" required>
                </div>
                <small class="text-gray-500">Enter your number without leading 0</small>
                @error('phone_number')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Birthdate -->
            <div class="mb-4">
                <label>Birthdate</label>
                <input type="date" name="birthday" value="{{ old('birthday') }}" class="form-input w-full @error('birthday') border-red-500 @enderror" required>
                @error('birthday')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label>Password</label>
                <input type="password" name="password" class="form-input w-full @error('password') border-red-500 @enderror" required>
                @error('password')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-input w-full" required>
            </div>
            <!-- agreement -->
            <div class="mt-6 space-y-4" x-data="{ 
                @foreach(\App\Models\TermsOfService::where('is_active', true)->get() as $term)
                    scrolled_{{ $term->id }}: false,
                @endforeach
            }">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Legal Agreements</h3>
                
                @foreach(\App\Models\TermsOfService::where('is_active', true)->get() as $term)
                <div class="p-3 border rounded-lg bg-gray-50">
                    <label class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="terms[]" value="{{ $term->id }}" required
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50">
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="text-gray-600">I have read and agree to the </span>
                            <button type="button" @click="$dispatch('open-modal', 'term-{{ $term->id }}')" class="text-blue-600 font-semibold hover:underline">
                                {{ $term->title }}
                            </button>
                        </div>
                    </label>

                    <x-modal name="term-{{ $term->id }}" focusable>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900">{{ $term->title }}</h2>
                            <div class="mt-4 overflow-y-auto max-h-60 p-4 bg-gray-100 rounded text-sm text-gray-600">
                               <div class="legal-content prose max-w-none">
                                    {!! nl2br(e($term->content)) !!}
                                </div> 
                                
                            </div>
                            <div class="mt-6 flex justify-end">
                                <x-secondary-button x-on:click="$dispatch('close')">Close</x-secondary-button>
                            </div>
                        </div>
                    </x-modal>
                </div>
                @endforeach
                
                @error('terms')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">Register</button>
        </form>
    </div>
</x-guest-layout>