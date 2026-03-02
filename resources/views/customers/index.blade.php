<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customer Account Management') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Top Controls --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-wrap items-center justify-between space-y-4 sm:space-y-0 sm:space-x-4">
                <!-- Grouped Left Side: Import and Export -->
                <div class="flex items-center gap-x-2">
                    <form action="{{ route('customers.CSV') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
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
                
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('customers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Customer</a>
                </div>
            </div>

            {{-- Filter Box --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, username, referral" class="form-input">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply
                        </button>
                        <a href="{{ route('customers.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
                            Clear All
                        </a>
                    </div>
                </form>
            </div>

            {{-- Customer List Table --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full border-collapse border">
                    <thead>
                        <tr class="bg-gray-100 text-sm">
                            <th class="border px-4 py-2">Name</th>
                            <th class="border px-4 py-2">Email</th>
                            <th class="border px-4 py-2">Phone</th>
                            <th class="border px-4 py-2">Username</th>
                            <th class="border px-4 py-2">Birthday</th>
                            <th class="border px-4 py-2">Referral Code</th>
                            <th class="border px-4 py-2">Sign In</th>
                            <th class="border px-4 py-2">Active</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $c)
                            <tr class="text-sm">
                                <td class="border px-4 py-2">{{ $c->name }}</td>
                                <td class="border px-4 py-2">{{ $c->email }}</td>
                                <td class="border px-4 py-2">{{ $c->phone_country_code }}{{ $c->phone_number }}</td>
                                <td class="border px-4 py-2">{{ $c->username }}</td>
                                <td class="border px-4 py-2">{{ $c->birthday }}</td>
                                <td class="border px-4 py-2">{{ $c->referral_code }}</td>
                                <td class="border px-4 py-2">{{ ucfirst($c->sign_in) }}</td>
                                <td class="border px-4 py-2">
                                    <x-status-badge :status="$c->status" />
                                </td>
                                <td class="border px-4 py-2">
                                    <!-- View Button -->
                                    <a href="{{ route('customers.show', $c) }}" 
                                        class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                        title="View Details"
                                    >
                                        <x-heroicon-o-eye class="w-5 h-5"/>
                                    </a>
                                    <!-- Edit Button -->
                                    <a href="{{ route('customers.edit', $c) }}" 
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
                                        x-on:click="$dispatch('open-modal', 'confirm-delete')"
                                    >
                                        <x-heroicon-o-trash class="w-5 h-5"/>
                                    </button>
                                    <!-- Modal -->
                                    <x-modal name="confirm-delete" maxWidth="2xl">
                                        <div class="p-6">
                                            <h2 class="text-lg font-medium text-gray-900">
                                                Delete selected customer ({{ $c->name }})?
                                            </h2>
                                            <p class="mt-2 text-sm text-gray-600">
                                                This action cannot be undone.
                                            </p>
                                            <div class="mt-6 flex justify-end gap-3">
                                                <x-primary-button
                                                    type="button"
                                                    class="px-4 py-2 bg-gray-200 rounded"
                                                    x-data
                                                    x-on:click="$dispatch('close-modal', 'confirm-delete')"
                                                >
                                                    Cancel
                                                </x-primary-button>
                                                <form action="{{ route('customers.destroy', $c) }}" method="POST" style="display:inline;">
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
                <div class="mt-4">
                    {{ $customers->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
