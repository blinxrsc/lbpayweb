<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Brand Management') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end items-end mb-4">
                <!-- Link to create a new roles -->
                <a href="{{ route('managers.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add Outlet Manager</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Permissions Table -->
					<div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Manager</th>
									<th class="border px-4 py-2">Email</th>
									<th class="border px-4 py-2">Phone</th>
									<th class="border px-4 py-2">SSM</th>
                                    <th class="border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($managers as $manager)
                                    <tr>
                                        <td class="border px-4 py-2">{{ $manager->name }}</td>
										<td class="border px-4 py-2">{{ $manager->email }}</td>
										<td class="border px-4 py-2">{{ $manager->phone }}</td>
										<td class="border px-4 py-2">{{ $manager->ssm }}</td>
                                        <td class="border px-4 py-2">
                                            <!-- Edit Button -->
                                            <a href="{{ route('managers.edit', $manager) }}" 
                                                class="inline-flex items-center px-2 py-1 text-indigo-500 hover:text-indigo-800"
                                                title="Edit"
                                            >
                                                <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                            </a>
                                            <!-- Delete Form/Button -->
                                            <button type="button" 
                                                class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                                                title="Delete" 
                                                x-data
                                                x-on:click="$dispatch('open-modal', 'confirm-delete-{{ $manager->name }}')"
                                            >
                                                <x-heroicon-o-trash class="w-5 h-5"/>
                                            </button>
                                            <!-- Modal -->
                                            <x-modal name="confirm-delete-{{ $manager->name }}" maxWidth="2xl">
                                                <div class="p-6">
                                                    <h2 class="text-lg font-medium text-gray-900">
                                                        Delete selected manager ({{ $manager->name }})?
                                                    </h2>
                                                    <p class="mt-2 text-sm text-gray-600">
                                                        This action cannot be undone.
                                                    </p>
                                                    <div class="mt-6 flex justify-end gap-3">
                                                        <x-primary-button
                                                            type="button"
                                                            class="px-4 py-2 bg-gray-200 rounded"
                                                            x-data
                                                            x-on:click="$dispatch('close-modal', 'confirm-delete-{{ $manager->name }}')"
                                                        >
                                                            Cancel
                                                        </x-primary-button>
                                                        <form action="{{ route('managers.destroy', $manager) }}" method="POST" style="display:inline;">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>