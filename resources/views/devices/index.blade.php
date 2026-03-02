<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Management') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4">
                <!-- Grouped Left Side: Import and Export -->
                <div class="flex items-center gap-x-2">
                    <form action="{{ route('devices.CSV') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <!-- File Input -->
                        <input type="file" name="csv_file" class="form-input text-sm" >
                        @error('csv_file')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                        <!-- Import Button -->
                        <button type="submit" name="import" value="1" class="flex items-center bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition">
                            <x-heroicon-s-arrow-up-on-square-stack class="h-4 w-4 text-blue-800 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" />
                            Import CSV
                        </button>
                        <!-- Export Button -->
                        <button type="submit" name="export" value="1" class="flex items-center bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition">
                            <x-heroicon-s-arrow-down-on-square-stack class="h-4 w-4 text-green-600 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" />
                            Export to CSV
                        </button>
                    </form>
                </div>
                <!-- Right Side: Add Device -->
                <a href="{{ route('devices.create') }}" class="btn btn-primary px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 text-sm font-semibold">
                    Add Device
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Brands Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100 text-sm">
                                    <th class="border px-4 py-2">Serial Number</th>
                                    <th class="border px-4 py-2">Model</th>
                                    <th class="border px-4 py-2">Version #</th>
                                    <th class="border px-4 py-2">Status</th>
                                    <th class="border px-4 py-2">Order #</th>
                                    <th class="border px-4 py-2">Purchased Date</th>
                                    <th class="border px-4 py-2">Supplier</th>
                                    <th class="border px-4 py-2">Cost</th>
                                    <th class="border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($devices as $device)
                                    <tr class="text-sm">
                                        <td class="border px-4 py-2"><span class="px-2 py-1 rounded bg-blue-200 text-blue-800">{{ $device->serial_number }}</span></td>
                                        <td class="border px-4 py-2">{{ $device->model }}</td>
                                        <td class="border px-4 py-2">{{ $device->version }}</td>
                                        <td class="border px-4 py-2">
                                            <x-status-badge :status="$device->status" />
                                        </td>
                                        <td class="border px-4 py-2">{{ $device->order_number }}</td>
                                        <td class="border px-4 py-2">{{ $device->purchase_date }}</td>
                                        <td class="border px-4 py-2">{{ optional($device->supplier)->supplier_name }}</td>
                                        <td class="border px-4 py-2">{{ $device->purchase_cost }}</td>
                                        <td class="border px-4 py-2">
                                            <!-- Show Button -->
                                            <a href={{ route('devices.show', $device) }} 
                                                class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                                title="Show Detail"
                                            >
                                                <x-heroicon-o-window class="w-5 h-5"/>
                                            </a>
                                            <!-- Edit Button -->
                                            <a href={{ route('devices.edit', $device) }} 
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
                                                x-on:click="$dispatch('open-modal', 'confirm-delete-{{ $device->serial_number }}')"
                                            >
                                                <x-heroicon-o-trash class="w-5 h-5"/>
                                            </button>
                                            <!-- Modal -->
                                            <x-modal name="confirm-delete-{{ $device->serial_number }}" maxWidth="2xl">
                                                <div class="p-6">
                                                    <h2 class="text-lg font-medium text-gray-900">
                                                        Delete selected device ({{ $device->serial_number }})?
                                                    </h2>
                                                    <p class="mt-2 text-sm text-gray-600">
                                                        This action cannot be undone.
                                                    </p>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-primary-button
                                                            type="button"
                                                            class="px-4 py-2 bg-gray-200 rounded"
                                                            x-data
                                                            x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $device->serial_number }}')"
                                                        >
                                                            Cancel
                                                        </x-primary-button>
                                                        <form action="{{ route('devices.destroy', $device) }}" method="POST" style="display:inline;">
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
                            {{ $devices->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
