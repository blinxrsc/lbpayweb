<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Operation Management') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-x-2">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('device_outlets.index') }}" class="mb-4 flex space-x-2">
                    <select name="brand_id" id="brand_id" class="border rounded px-3 py-2 text-sm"> 
                        <option value="">-- Brands --</option> 
                        @foreach($brands as $brand) 
                            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}> {{ $brand->name }} </option> 
                        @endforeach 
                    </select>
                    <select name="outlet_type" id="outlet_type" class="border rounded px-3 py-2 text-sm"> 
                        <option value="">-- Outlet type --</option>
                        <option value="own" {{ request('outlet_type') == 'own' ? 'selected' : '' }}>Own</option>
                        <option value="jv" {{ request('outlet_type') == 'jv' ? 'selected' : '' }}>JV</option>
                        <option value="franchise" {{ request('outlet_type') == 'franchise' ? 'selected' : '' }}>Franchise</option>
                        <option value="alacart" {{ request('outlet_type') == 'alacart' ? 'selected' : '' }}>Alacart</option> 
                    </select>
                    <select name="status" id="status" class="border rounded px-3 py-2 text-sm">
                        <option value="">-- Status --</option>
                        <option value="offline">Offline</option>
                        <option value="online">Online</option>
                    </select>
                    <input type="text" name="outlet_name" value="{{ request('outlet_name') }}" placeholder="Outlet name" class="border rounded px-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply
                        </button>
                        <a href="{{ route('device_outlets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
                            Clear All
                        </a>
                        <!-- Export Button -->
                        
                    </div>
                </form>
                <form action="{{ route('device_outlets.CSV') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <!-- Export Button -->
                    <button type="submit" name="export" value="1" class="flex items-center bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition">
                        <x-heroicon-s-arrow-down-on-square-stack class="h-4 w-4 text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" />
                        Export to CSV
                    </button>
                </form>
                <!-- Button assign devices -->
                <a href="{{ route('device_outlets.create') }}" 
                    class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                >Assign Device</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Transactions Table -->
                    <table class="min-w-full border-collapse border">
                        <thead>
                            <tr class="bg-gray-100 text-sm">
                                <th class="border px-4 py-2">Outlet</th>
                                <th class="border px-4 py-2">Device SN</th>
                                <th class="border px-4 py-2">Brand</th>
                                <th class="border px-4 py-2">Ownership</th>
                                <th class="border px-4 py-2">Machine #</th>
                                <th class="border px-4 py-2">Machine Name</th>
                                <th class="border px-4 py-2">Status</th>
                                <th class="border px-4 py-2">Availability</th>
                                <th class="border px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaction as $tx)
                                <tr class="text-sm">
                                    <td class="border px-4 py-2">{{ optional($tx->outlet)->outlet_name }}</td>
                                    <td class="border px-4 py-2">
                                        <button
                                            type="button"
                                            class="px-2 py-1 rounded bg-blue-200 text-blue-800"
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'show-qr-{{ $tx->device_serial_number }}')"
                                        >{{ $tx->device_serial_number }}</button>
                                        <x-modal name="show-qr-{{ $tx->device_serial_number }}" maxWidth="2xl">
                                            <div class="p-6 text-center">
                                                <h2 class="text-lg font-medium text-gray-900">
                                                    QR Code for {{ $tx->device_serial_number }}
                                                </h2>

                                                <div class="mt-4 flex justify-center">
                                                    {{-- Inline QR code --}}
                                                    {!! QrCode::size(250)->generate(route('device.scan', ['serial' => $tx->device_serial_number])) !!}
                                                </div>

                                                <div class="mt-6 flex justify-center gap-3">
                                                    <button
                                                        type="button"
                                                        class="px-4 py-2 bg-gray-200 rounded"
                                                        x-data
                                                        x-on:click="$dispatch('close-modal', 'show-qr-{{ $tx->device_serial_number }}')">Close</button>
                                                    <x-primary-button class="ml-4" onclick="window.location='{{ route('devices.qrcode', $tx) }}'">Download QR</x-primary-button>
                                                </div>
                                            </div>
                                        </x-modal>
                                    </td>
                                    <td class="border px-4 py-2">{{ optional(optional($tx->outlet)->brand)->name }}</td>
                                    <td class="border px-4 py-2">
                                        <x-status-badge :status="$tx->outlet->type->name" /> 
                                    </td>
                                    <td class="border px-4 py-2">
                                        <x-status-badge :status="$tx->machine_type" /> # <strong>{{ $tx->machine_num }}</strong>
                                    </td>
                                    <td class="border px-4 py-2">{{ $tx->machine_name }}</td>
                                    <td class="border px-4 py-2">
                                        <x-status-badge :status="$tx->status" /> 
                                    </td>
                                    <td class="border px-4 py-2">
                                        <x-status-badge :status="$tx->availability ? 'Available' : 'Busy'" /> 
                                    </td>
                                    <td class="border px-4 py-2">
                                        <!-- View Button -->
                                        <a href="{{ route('device_outlets.show', $tx) }}" 
                                            class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                            title="View Details"
                                        >
                                            <x-heroicon-o-eye class="w-5 h-5"/>
                                        </a>
                                        <!-- Edit Button -->
                                        <a href="{{ route('device_outlets.edit', $tx) }}" 
                                            class="inline-flex items-center px-2 py-1 text-indigo-600 hover:text-indigo-800"
                                            title="Edit"
                                        >
                                            <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                        </a>
                                        <!-- Delete Form/Button -->
                                        <button type="button" 
                                            class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                                            title="Delete" 
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'confirm-delete-{{ $tx->device_serial_number }}')"
                                        >
                                            <x-heroicon-o-trash class="w-5 h-5"/>
                                        </button>
                                        <!-- Modal -->
                                        <x-modal name="confirm-delete-{{ $tx->device_serial_number }}" maxWidth="2xl">
                                            <div class="p-6">
                                                <h2 class="text-lg font-medium text-gray-900">
                                                    Delete selected device outlet ({{ optional($tx->outlet)->outlet_name }})?
                                                </h2>
                                                <p class="mt-2 text-sm text-gray-600">
                                                    Are you sure you want to unassign device {{ $tx->device_serial_number }} ? This will set the device status back to Unassigned.
                                                </p>
                                                <div class="mt-6 flex justify-end gap-3">
                                                    <x-primary-button
                                                        type="button"
                                                        class="px-4 py-2 bg-gray-200 rounded"
                                                        x-data
                                                        x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $tx->device_serial_number }}')"
                                                    >
                                                        Cancel
                                                    </x-primary-button>
                                                    <form action="{{ route('device_outlets.destroy', $tx) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="px-4 py-2 bg-red-600 text-white rounded"
                                                        >
                                                            Yes, Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </x-modal>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <p class="text-gray-500 text-lg font-medium">
                                                <x-heroicon-m-exclamation-circle />No transactions found
                                            </p>
                                            <p class="text-gray-400 text-sm">Try adjusting your filters or resetting the search.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $transaction->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div> 
</x-app-layout>