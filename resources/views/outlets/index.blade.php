<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Outlet Management') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4">
                <!-- Filters -->
                <form method="GET" action="{{ route('outlets.index') }}" class="flex items-center gap-2">
                    <select name="status" class="border rounded px-3 py-2 text-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="closed">Closed Down</option>
                    </select>
                    <select name="type" class="border rounded px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="own">Own</option>
                        <option value="joint">JV</option>
                        <option value="franchise">Franchise</option>
                        <option value="alacart">Alacart</option>
                    </select>
                    <input type="text" name="outlet" placeholder="Outlet name" class="border rounded px-3 py-2 text-sm">
                    <input type="text" name="city" placeholder="City" class="border rounded px-3 py-2 text-sm">
                    <input type="text" name="state" placeholder="State" class="border rounded px-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply
                        </button>
                        <a href="{{ route('outlets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
                            Clear All
                        </a>
                    </div>
                </form>
                <!-- Link to create a new user -->
                <a href="{{ route('outlets.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add Outlet</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Transactions Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead class="text-sm">
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Outlet</th>
                                    <th class="border px-4 py-2">Total Machine</th>
                                    <th class="border px-4 py-2">Business Hours</th>
                                    <th class="border px-4 py-2">Country</th>
                                    <th class="border px-4 py-2">State</th>
                                    <th class="border px-4 py-2">City</th>
                                    <th class="border px-4 py-2">Address</th>
                                    <th class="border px-4 py-2">Longitude</th>
                                    <th class="border px-4 py-2">Latitude</th>
                                    <th class="border px-4 py-2">Phone</th>
                                    <th class="border px-4 py-2">Brand</th>
                                    <th class="border px-4 py-2">Active</th>
                                    <th class="border px-4 py-2">Type</th>
                                    <th class="border px-4 py-2">Manager</th>
                                    <th class="sticky right-0 bg-gray-100 border px-4 py-2 z-10">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($transactions as $tx)
                                    <tr>
                                        <td class="border px-4 py-2">{{ $tx->outlet_name }}</td>
                                        <td class="border px-4 py-2">{{ $tx->machine_number }}</td>
                                        <td class="border px-4 py-2">{{ $tx->business_hours }}</td>
                                        <td class="border px-4 py-2">{{ $tx->country }}</td>
                                        <td class="border px-4 py-2">{{ $tx->province }}</td>
                                        <td class="border px-4 py-2">{{ $tx->city }}</td>
                                        <td class="border px-4 py-2">{{ $tx->address }}</td>
                                        <td class="border px-4 py-2">{{ $tx->longitude }}</td>
                                        <td class="border px-4 py-2">{{ $tx->latitude }}</td>
                                        <td class="border px-4 py-2">{{ $tx->phone }}</td>
                                        <td class="border px-4 py-2 text-center bg-gray-50/50">
                                            @if($tx->brand->logo) 
                                                <img src="{{ asset('storage/' . $tx->brand->logo) }}" 
                                                     alt="{{ $tx->brand->name }}" 
                                                     class="w-20 h-auto max-h-12 object-scale-down mx-auto">
                                            @else
                                                <span class="text-xs text-gray-400 italic">{{ $tx->brand->name }}</span>
                                            @endif
                                        </td>
                                        <td class="border px-4 py-2">
                                             <x-status-badge :status="$tx->status->name" />
                                            
                                        </td>
                                        <td class="border px-4 py-2">
                                            <x-status-badge :status="$tx->type->name" />
                                        </td>
                                        <td class="border px-4 py-2">{{ $tx->manager->name }}</td>
                                        <td class="sticky right-0 bg-white border px-4 py-2">
                                            <!-- View Button -->
                                            <a href="{{ route('outlets.show', $tx) }}" 
                                                class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                                title="View Details"
                                            >
                                                <x-heroicon-o-window class="w-5 h-5"/>
                                            </a>
                                            <!-- Edit Button -->
                                             @can('outlets.update')
                                            <a href="{{ route('outlets.edit', $tx) }}" 
                                                class="inline-flex items-center px-2 py-1 text-indigo-500 hover:text-indigo-800"
                                                title="Edit"
                                            >
                                                <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                            </a>
                                            @endcan
                                            <!-- Delete Form/Button -->
                                            @can('outlets.delete')
                                            <button type="button" 
                                                class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                                                title="Delete" 
                                                x-data
                                                x-on:click="$dispatch('open-modal', 'confirm-delete-{{ $tx->id }}')"
                                            >
                                                <x-heroicon-o-trash class="w-5 h-5"/>
                                            </button>
                                            @endcan
                                            <!-- Modal -->
                                            <x-modal name="confirm-delete-{{ $tx->id }}" maxWidth="2xl">
                                                <div class="p-6">
                                                    <h2 class="text-lg font-medium text-gray-900">
                                                        Delete selected outlet ({{ $tx->outlet_name }})?
                                                    </h2>
                                                    <p class="mt-2 text-sm text-gray-600">
                                                        This action cannot be undone.
                                                    </p>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-primary-button
                                                            type="button"
                                                            class="px-4 py-2 bg-gray-200 rounded"
                                                            x-data
                                                            x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $tx->id }}')"
                                                        >
                                                            Cancel
                                                        </x-primary-button>
                                                        <form action="{{ route('outlets.destroy', $tx) }}" method="POST" style="display:inline;">
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
                                        <td colspan="15" class="px-6 py-12 text-center">
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
                    </div>
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div> 
</x-app-layout>