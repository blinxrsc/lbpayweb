<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Device') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="{ tab: 'purchase' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                {{-- Tab Navigation --}}
                <div class="border-b mb-4 flex space-x-4">
                    <button type="button"
                        class="px-4 py-2 font-semibold"
                        :class="tab === 'purchase' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                        @click="tab = 'purchase'">
                        Purchase
                    </button>
                    <button type="button"
                        class="px-4 py-2 font-semibold"
                        :class="tab === 'parameters' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                        @click="tab = 'parameters'">
                        Device Parameters
                    </button>
                    <button type="button"
                        class="px-4 py-2 font-semibold"
                        :class="tab === 'audit' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                        @click="tab = 'audit'">
                        Audit Trail
                    </button>
                </div>

                <form method="POST" action="{{ route('devices.update', $device) }}">
                    @csrf 
                    @method('PUT')

                    {{-- Purchase Tab --}}
                    <div x-show="tab === 'purchase'">
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
                    </div>

                    {{-- Device Parameters Tab --}}
                    <div x-show="tab === 'parameters'">
                        @foreach([
                            'washer_cold_price','washer_warm_price','washer_hot_price',
                            'dryer_low_price','dryer_med_price','dryer_hi_price',
                            'pulse_price','pulse_add_min','pulse_width','pulse_delay','coin_signal_width'
                        ] as $field)
                            <div class="mb-4">
                                <label>{{ ucwords(str_replace('_',' ', $field)) }}</label>
                                <input type="text" name="{{ $field }}" value="{{ old($field, $device->$field) }}" class="form-input w-full">
                            </div>
                        @endforeach
                    </div>

                    {{-- Audit Trail Tab --}}
                    <div x-show="tab === 'audit'">
                        <h3 class="text-lg font-semibold mb-4">Audit Trail</h3>
                        <table class="table-auto w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-2 py-1">Field</th>
                                    <th class="px-2 py-1">Old Value</th>
                                    <th class="px-2 py-1">New Value</th>
                                    <th class="px-2 py-1">Changed By</th>
                                    <th class="px-2 py-1">Changed At</th>
                                    <th class="px-2 py-1">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($device->auditLogs as $log)
                                    <tr>
                                        <td class="border px-2 py-1">{{ $log->field }}</td>
                                        <td class="border px-2 py-1">{{ $log->old_value }}</td>
                                        <td class="border px-2 py-1">{{ $log->new_value }}</td>
                                        <td class="border px-2 py-1">{{ $log->user->name }}</td>
                                        <td class="border px-2 py-1">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="border px-2 py-1">
                                            <form method="POST" action="{{ route('devices.rollback', $log) }}">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-danger">Rollback</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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