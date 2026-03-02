<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Mail Server') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('mailserver.update', $mailserver->id) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Host --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Host <span class="text-red-500">*</span></label>
                            <input type="text" name="host" value="{{ old('host', $mailserver->host) }}" 
                                placeholder="e.g. smtp.google.com"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Port --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Port</label>
                            <input type="number" name="port" value="{{ old('port', $mailserver->port) }}" 
                                placeholder="e.g. 456"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Username --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="{{ old('username', $mailserver->username) }}" 
                                placeholder="Choose a unique username"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- Password (optional update) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" value="{{ old('password', $mailserver->password) }}" 
                                placeholder="Leave blank to keep current password"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- Encryption --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Encryption</label>
                            <select name="encryption" class="form-select w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                <option value="" {{ old('encryption', $mailserver->encryption) == '' ? 'selected' : '' }}>None</option>
                                <option value="ssl" {{ old('encryption', $mailserver->encryption) == 'ssl' ? 'selected' : '' }}>SSL</option>
                                <option value="tls" {{ old('encryption', $mailserver->encryption) == 'tls' ? 'selected' : '' }}>TLS</option>
                            </select>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end">
                            <x-primary-button class="ml-4">Update Mail Server</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
