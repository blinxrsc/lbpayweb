<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Device') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8">

                    <form method="POST" action="{{ route('devices.store') }}" class="space-y-8">
                        @csrf

                        {{-- Device Identity --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Device Identity</h3>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number <span class="text-red-500">*</span></label>
                                <input type="text" name="serial_number" value="{{ old('serial_number') }}"
                                    placeholder="e.g. NYJ312007A100216290"
                                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100" required>
                                @error('serial_number')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Model <span class="text-red-500">*</span></label>
                                    <input type="text" name="model" value="{{ old('model') }}"
                                        placeholder="e.g. ESP32-WROVER-IE"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100" required>
                                    @error('model')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Version #</label>
                                    <input type="text" name="version" value="{{ old('version') }}"
                                        placeholder="e.g. v1.5.1"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                    @error('version')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        {{-- Purchase Information --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Purchase Information</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Number</label>
                                    <input type="text" name="order_number" value="{{ old('order_number') }}"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                    @error('order_number')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div x-data="{ purchaseDate: '{{ old('purchase_date', date('Y-m-d')) }}' }">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                                    <input
                                        x-ref="purchaseDatePicker"
                                        x-init="flatpickr($refs.purchaseDatePicker, { dateFormat: 'Y-m-d', defaultDate: purchaseDate })"
                                        type="text"
                                        name="purchase_date"
                                        x-model="purchaseDate"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                    @error('purchase_date')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="supplier_id" :value="__('Supplier')" />
                                    <select name="supplier_id" id="supplier_id"
                                        class="block mt-1 w-full rounded-md shadow-sm border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100"
                                        required>
                                        <option value="">-- Select Supplier --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Cost (RM)</label>
                                    <input type="text" name="purchase_cost" value="{{ old('purchase_cost') }}"
                                        placeholder="0.00"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                    @error('purchase_cost')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('devices.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                                Cancel
                            </a>
                            <x-primary-button>Save Device</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
