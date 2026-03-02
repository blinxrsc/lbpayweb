<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Device Details') }}: {{ $device->serial_number }}
            </h2>
            <x-status-badge :status="$device->status" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">     
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Hardware Info -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Hardware Info</h3>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">Model:</dt>
                        <dd class="font-semibold text-gray-900">{{ $device->model }}</dd>
                        
                        <dt class="text-gray-500">Version:</dt>
                        <dd class="font-semibold text-gray-900">{{ $device->version }}</dd>
                        
                        <dt class="text-gray-500">Purchase Date:</dt>
                        <dd class="text-gray-900">{{ $device->purchase_date ?? 'N/A' }}</dd>
                    </dl>
                </div>
                <!-- Supplier Details -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Supply Details</h3>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">Supplier:</dt>
                        <dd class="text-gray-900">{{ $device->supplier->name ?? 'N/A' }}</dd>
                        
                        <dt class="text-gray-500">Purchase Cost:</dt>
                        <dd class="text-gray-900">{{ number_format($device->purchase_cost, 2) }}</dd>
                    </dl>
                </div>
                <!-- Current Placement -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Current Placement</h3>
                    @if($device->deviceOutlets)
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <dt class="text-gray-500">Outlet:</dt>
                            <dd class="font-semibold text-indigo-600">{{ $device->deviceOutlets->outlet->outlet_name }}</dd>
                            
                            <dt class="text-gray-500">Machine Name:</dt>
                            <dd class="text-gray-900">{{ $device->deviceOutlets->machine_name }} (#{{ $device->deviceOutlets->machine_num }})</dd>
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 italic">This device is not currently assigned to any outlet.</p>
                    @endif
                </div>
            </div>
            <!-- Moement Service History -->
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Movement & Service History</h3>
                </div>
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($movementLogs as $log)
                                <tr class="text-sm">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium {{ str_contains($log->action, 'Faulty') ? 'text-red-600' : 'text-gray-900' }}">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $log->outlet->outlet_name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $log->user->name ?? 'System' }}</td>
                                    <td class="px-4 py-3 text-gray-500 italic">{{ $log->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">No movement history found for this device.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('devices.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                
                @if($device->status != 'faulty' && $device->status != 'assigned')
                    <form action="{{ route('devices.markFaulty', $device->serial_number) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('Mark this device as faulty?')" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Report Faulty
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <!-- modal -->
    <div x-data="{ open: false }">
    <button @click="open = true" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">
        Report Faulty / Move to Repair
    </button>

    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('devices.markFaulty', $device->serial_number) }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Report Device Fault</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Please describe the issue. Marking this device as faulty will automatically remove it from its current outlet.
                                    </p>
                                    <textarea name="notes" rows="3" required
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="E.g. LED screen cracked, not receiving coins..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm Faulty Status
                        </button>
                        <button type="button" @click="open = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@if($device->status == 'Faulty')
    <div x-data="{ repairOpen: false }">
        <button @click="repairOpen = true" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
            Mark Repair as Completed
        </button>

        <div x-show="repairOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('devices.repairCompleted', $device->serial_number) }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Finalize Repair</h3>
                            <div class="mt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Technician's Repair Notes</label>
                                <textarea name="repair_notes" rows="3" required
                                    class="w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm"
                                    placeholder="Describe what was fixed (e.g., Replaced faulty capacitor on power board)"></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Complete & Return to Stock
                            </button>
                            <button type="button" @click="repairOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
</x-app-layout>