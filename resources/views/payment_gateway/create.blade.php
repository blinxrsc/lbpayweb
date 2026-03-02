<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Payment Gateway') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __("Fill in your active payment gateway setting information.") }}
                        {{ __("e.g. Fiuu - MerchantID / Secret Key / Verify Key") }}
                        {{ __("e.g. Revenue Monster - ID / Client Secret Key / Client Public Key / Server Public Key") }}
                    </p>
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('payment_gateway.store') }}" class="space-y-6">
                        @csrf

                        {{-- merchant_id --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Merchant ID / ID<span class="text-red-500">*</span></label>
                            <input type="text" name="merchant_id" value="{{ old('merchant_id') }}" 
                                placeholder="e.g. merchant01"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>

                        {{-- terminal_id --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Terminal ID </label>
                            <input type="text" name="terminal_id" value="{{ old('terminal_id') }}" 
                                placeholder="e.g. TI-123456789"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- app_id --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">APP ID </label>
                            <input type="text" name="app_id" value="{{ old('app_id') }}" 
                                placeholder="e.g. 1234567890"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- client_id --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">CLient ID </label>
                            <input type="text" name="client_id" value="{{ old('client_id') }}"
                                placeholder="e.g. 1234567890abc"
                                class="form-input w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        {{-- secret_key --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Secret Key / Client Secret Key</label>
                            <textarea name="secret_key" class="form-textarea w-full" placeholder="e.g. 23fdbd55bcbcv5565">{{ old('secret_key') }}</textarea>
                        </div>

                        {{-- public_key --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Public Key / Client Public Key</label>
                            <textarea name="public_key" class="form-textarea w-full" placeholder="e.g. 23fdbd55bcbcv5565">{{ old('public_key') }}</textarea>
                        </div>

                        {{-- private_key --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Private Key or Verify Key</label>
                            <textarea name="private_key" class="form-textarea w-full" placeholder="e.g. 23fdbd55bcbcv5565">{{ old('private_key') }}</textarea>
                        </div>

                        {{-- api_key --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">API Key / Server Public Key</label>
                            <textarea name="api_key" class="form-textarea w-full" placeholder="e.g. 23fdbd55bcbcv5565">"{{ old('api_key') }}"</textarea>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                            <select name="status" class="form-select w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                <option value="" {{ old('status') == '' ? 'selected' : '' }}>None</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="disable" {{ old('status') == 'disable' ? 'selected' : '' }}>Disable</option>
                            </select>
                        </div>

                        {{-- Sandbox --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sandbox <span class="text-red-500">*</span></label>
                            <select name="sandbox" class="form-select w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                                <option value="" {{ old('sandbox') == '' ? 'selected' : '' }}>None</option>
                                <option value="on" {{ old('sandbox') == 'on' ? 'selected' : '' }}>On</option>
                                <option value="off" {{ old('sandbox') == 'off' ? 'selected' : '' }}>Off</option>
                            </select>
                        </div>
                        <!-- Container with Flexbox styles -->
                        <div class="flex justify-between items-center">
                            <!-- Back Button (left aligned) -->
                            <a href="{{ url()->previous() ?? route('outlets.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md">
                                ← Back
                            </a>

                            {{-- Submit Button (right aligned) --}}
                            <x-primary-button>
                                Save
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>