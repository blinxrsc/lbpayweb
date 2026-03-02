<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Customer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('customers.update', $customer->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $customer->name) }}" 
                                placeholder="e.g. John Doe"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}" 
                                placeholder="e.g. john@example.com"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Phone --}}
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Country Code</label>
                                <input type="text" name="phone_country_code" value="{{ old('phone_country_code', $customer->phone_country_code) }}" 
                                    placeholder="60"
                                    class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}" 
                                    placeholder="e.g. 123456789"
                                    class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                            </div>
                        </div>

                        {{-- Username --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username', $customer->username) }}" 
                                placeholder="Choose a unique username"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Password (optional update) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" 
                                placeholder="Leave blank to keep current password"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Birthday (yyyy-mm-dd format) --}}
                        <div>
                            <label for="birthday" class="block text-sm font-medium text-gray-700">Birthday (YYYY-MM-DD)</label>
                            <input id="birthday" type="text" name="birthday"
                                value="{{ old('birthday', $customer->birthday) }}"
                                placeholder="YYYY-MM-DD"
                                pattern="\d{4}-\d{2}-\d{2}"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                            @error('birthday')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Tags --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tags</label>
                            <input type="text" name="tags" value="{{ old('tags', $customer->tags) }}" 
                                placeholder="e.g. VIP, Promo, Trial"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Sign In Method --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sign In Method</label>
                            <select name="sign_in" class="form-select w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                                <option value="web" {{ old('sign_in', $customer->sign_in) == 'web' ? 'selected' : '' }}>Web</option>
                                <option value="google" {{ old('sign_in', $customer->sign_in) == 'google' ? 'selected' : '' }}>Google</option>
                                <option value="facebook" {{ old('sign_in', $customer->sign_in) == 'facebook' ? 'selected' : '' }}>Facebook</option>
                            </select>
                        </div>

                        {{-- Status (checkboxes) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <div class="flex items-center space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="status" value="active" 
                                        {{ old('status', $customer->status) == 'active' ? 'checked' : '' }}
                                        class="form-checkbox text-blue-600">
                                    <span class="ml-2">Active</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="status" value="inactive" 
                                        {{ old('status', $customer->status) == 'inactive' ? 'checked' : '' }}
                                        class="form-checkbox text-blue-600">
                                    <span class="ml-2">Inactive</span>
                                </label>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end">
                            <x-primary-button class="ml-4">Update Customer</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
