<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Mail Server') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('mailserver.store') }}" class="space-y-6">
                        @csrf

                        {{-- Host --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Host <span class="text-red-500">*</span></label>
                            <input type="text" name="host" value="{{ old('host') }}" 
                                placeholder="e.g. smtp.google.com"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Port --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Port </label>
                            <input type="number" name="port" value="{{ old('port') }}" 
                                placeholder="e.g. 456"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Username --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username') }}" 
                                placeholder="Choose a unique username"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" 
                                placeholder="Enter a secure password"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Sign In Method --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Encryption</label>
                            <select name="encryption" class="form-select w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                <option value="" {{ old('encryption') == '' ? 'selected' : '' }}>None</option>
                                <option value="ssl" {{ old('encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="tls" {{ old('encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                            </select>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end">
                            <x-primary-button class="ml-4">Save Mail Server</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>