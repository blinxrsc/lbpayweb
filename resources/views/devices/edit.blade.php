<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Device') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-500 mb-4">
                    Assignment and purchase information only. Pulse settings, coin
                    signal settings, and this device's audit trail now live under
                    Outlet &gt; Manage Device-Outlet — open the gear icon next to
                    the outlet this device is assigned to.
                </p>

                <form method="POST" action="{{ route('devices.update', $device) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" class="form-input w-full" required>
                        @error('serial_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label>Model</label>
                        <input type="text" name="model" value="{{ old('model', $device->model) }}" class="form-input w-full" required>
                    </div>
                    <div class="mb-4">
                        <label>Version #</label>
                        <input type="text" name="version" value="{{ old('version', $device->version) }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label>Order Number</label>
                        <input type="text" name="order_number" value="{{ old('order_number', $device->order_number) }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label for="purchase_date">Purchase Date</label>
                        <input id="purchase_date" type="date" name="purchase_date"
                            value="{{ old('purchase_date', $device->purchase_date ?? '') }}"
                            class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-select w-full" required>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ old('supplier_id', $device->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label>Purchase Cost</label>
                        <input type="text" name="purchase_cost" value="{{ old('purchase_cost', $device->purchase_cost) }}" class="form-input w-full">
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="ml-4">
                            Update Device
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
