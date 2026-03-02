<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Device') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('devices.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number') }}" class="form-input w-full" required>
                        @error('serial_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label>Model</label>
                        <input type="text" name="model" value="{{ old('model') }}" class="form-input w-full" required>
                        @error('model')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label>Version #</label>
                        <input type="text" name="version" value="{{ old('version') }}" class="form-input w-full">
                        @error('version')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label>Order Number</label>
                        <input type="text" name="order_number" value="{{ old('order_number') }}" class="form-input w-full">
                        @error('order_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div x-data="{ purchaseDate: '{{ request(old('purchase_date', $device->purchase_date ?? ''), date('Y-m-d')) }}', instance: null }" class="flex flex-col gap-1">
                        <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
                        <input 
                            x-ref="purchaseDatePicker" 
                            x-init="instance = flatpickr($refs.purchaseDatePicker, { dateFormat: 'Y-m-d', defaultDate: purchaseDate })" 
                            type="text" 
                            name="purchase_date" 
                            x-model="purchaseDate" 
                            class="w-32 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        @error('purchase_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <x-input-label for="supplier_id" :value="__('Supplier')" />
                        <select name="supplier_id" id="supplier_id" 
                            class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
                            required
                        >
                            <option value="">-- Select Supplier --</option>    
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label>Purchase Cost</label>
                        <input type="text" name="purchase_cost" value="{{ old('purchase_cost') }}" class="form-input w-full" placeholder=0.00>
                        @error('purchase_cost')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="ml-4">
                            Save Device
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>