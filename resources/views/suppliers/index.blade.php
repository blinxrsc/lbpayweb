<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Supplier Management') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end items-end mb-4">
                <!-- Link to create a new brand-->
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add Supplier</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Brands Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100 text-sm">
                                    <th class="border px-4 py-2">Supplier</th>
                                    <th class="border px-4 py-2">Email</th>
                                    <th class="border px-4 py-2">Phone</th>
                                    <th class="border px-4 py-2">Address</th>
                                    <th class="border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($suppliers as $supplier)
                                    <tr class="text-sm">
                                        <td class="border px-4 py-2">{{ $supplier->supplier_name }}</td>
                                        <td class="border px-4 py-2">{{ $supplier->email }}</td>
                                        <td class="border px-4 py-2">{{ $supplier->phone }}</td>
                                        <td class="border px-4 py-2">{{ $supplier->address }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <!-- Edit Button -->
                                            <a href="{{ route('suppliers.edit', $supplier) }}" 
                                                class="inline-flex items-center px-2 py-1 text-indigo-600 hover:text-indigo-800"
                                                title="Edit"
                                            >
                                                <x-heroicon-o-pencil-square class="w-5 h-5"/></a>
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
                                                        Delete selected supplier ({{ $supplier->supplier_name }})?
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
                                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" style="display:inline;">
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
                                        <td colspan="5" class="px-6 py-12 text-center">
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
                            {{ $suppliers->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
