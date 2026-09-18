<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between max-w-3xl mx-auto">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Customer') }}
            </h2>
            <a href="{{ route('customers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition">
                &larr; Back to Customers
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl border border-gray-100 overflow-hidden">
                <form method="POST" action="{{ route('customers.store') }}" class="p-6 sm:p-8 space-y-8">
                    @csrf

                    {{-- Section: Personal Information --}}
                    <div>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Personal Details</h3>
                            <p class="text-xs text-gray-500">Basic contact and identifying information for this customer.</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    placeholder="e.g. John Doe"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('name') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror" required>
                                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        placeholder="john@example.com"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('email') border-red-300 focus:border-red-500 focus:ring-red-100 @enderror" required>
                                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <div class="flex rounded-lg shadow-sm">
                                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-xs">
                                            +
                                        </span>
                                        <input type="text" name="phone_country_code" value="{{ old('phone_country_code', '60') }}"
                                            placeholder="60"
                                            class="w-16 border-gray-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                            placeholder="123456789"
                                            class="flex-1 rounded-r-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('phone_number') border-red-300 @enderror">
                                    </div>
                                    @error('phone_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="birthday" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input id="birthday" type="date" name="birthday" value="{{ old('birthday') }}"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('birthday') border-red-300 @enderror">
                                    @error('birthday')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Section: Account Login --}}
                    <div>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Account Credentials</h3>
                            <p class="text-xs text-gray-500">Set authentication details for customer portal access.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                                        placeholder="johndoe"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('username') border-red-300 @enderror" required>
                                    @error('username')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" id="password" name="password"
                                        placeholder="••••••••"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition @error('password') border-red-300 @enderror" required>
                                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="sign_in" class="block text-sm font-medium text-gray-700 mb-1">Sign In Provider</label>
                                    <select id="sign_in" name="sign_in" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                        <option value="web" {{ old('sign_in') == 'web' ? 'selected' : '' }}>Standard Web</option>
                                        <option value="google" {{ old('sign_in') == 'google' ? 'selected' : '' }}>Google SSO</option>
                                        <option value="facebook" {{ old('sign_in') == 'facebook' ? 'selected' : '' }}>Facebook OAuth</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Section: Preferences & Status --}}
                    <div>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Preferences & Status</h3>
                            <p class="text-xs text-gray-500">Categorize customer status and apply marketing tags.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                                <input type="text" id="tags" name="tags" value="{{ old('tags') }}"
                                    placeholder="VIP, Promo, Trial"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                <p class="text-[11px] text-gray-400 mt-1">Separate tags with commas.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50 w-full sm:w-auto" x-data="{ status: '{{ old('status', 'active') }}' }">
                                    <label class="flex-1 sm:flex-initial cursor-pointer text-center px-4 py-1.5 rounded-md text-sm font-medium transition-all"
                                        :class="status === 'active' ? 'bg-white text-emerald-700 shadow-sm border border-gray-200/50' : 'text-gray-500 hover:text-gray-700'">
                                        <input type="radio" name="status" value="active" x-model="status" class="hidden">
                                        Active
                                    </label>
                                    <label class="flex-1 sm:flex-initial cursor-pointer text-center px-4 py-1.5 rounded-md text-sm font-medium transition-all"
                                        :class="status === 'inactive' ? 'bg-white text-gray-700 shadow-sm border border-gray-200/50' : 'text-gray-500 hover:text-gray-700'">
                                        <input type="radio" name="status" value="inactive" x-model="status" class="hidden">
                                        Inactive
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Controls --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('customers.index') }}" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <x-primary-button class="rounded-lg shadow-sm px-5 py-2">
                            Save Customer
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>