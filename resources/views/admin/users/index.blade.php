<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('System User Management') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Search and Add New User --}}
            <div class="flex justify-between items-center mb-4">
                <form method="GET" action="{{ route('users.index') }}" class="flex items-center space-x-2">
                    <input type="text" name="email" placeholder="email" class="input input-bordered" 
                        value="{{ request('email') }}" />
                    <select name="roles[]" class="border rounded px-3 py-2 text-sm"> 
                        <option value="">-- Roles --</option> 
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ in_array($role->id, (array) request('roles')) ? 'checked' : '' }}> {{ $role->name }} </option> 
                        @endforeach 
                    </select>
                    <x-primary-button class="ml-4">Search</x-primary-button>
                </form>
                <a href="{{ route('users.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add User</a>
            </div>

            <div class="p-6 bg-white border-b border-gray-200">
                {{-- User Table --}}
                <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                    <table class="min-w-full border-collapse border">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-4 py-2">Name</th>
                                <th class="border px-4 py-2">Login Email</th>
                                <th class="border px-4 py-2">Role</th>
                                <th class="border px-4 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="border px-4 py-2">{{ $user->name }}</td>
                                    <td class="border px-4 py-2">{{ $user->email }}</td>
                                    <td class="border px-4 py-2">{{ $user->roles->pluck('name')->join(', ') }}</td>
                                    <td class="border px-4 py-2">
                                        <!-- Show Button -->
                                        <a href="{{ route('users.show', $user->id) }}" 
                                            class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                            title="Show Details"
                                        >
                                            <x-heroicon-o-window class="w-5 h-5"/>
                                        </a>
                                        <!-- Edit Button -->
                                        @can('users.update')
                                        <a href="{{ route('users.edit', $user->id) }}" 
                                            class="inline-flex items-center px-2 py-1 text-indigo-500 hover:text-indigo-800"
                                            title="Edit"
                                        >
                                            <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                        </a>
                                        @endcan
                                        @can('users.delete')
                                        <!-- Delete Form/Button -->
                                        <button type="button" 
                                            class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                                            title="Delete" 
                                            x-data
                                            x-on:click="$dispatch('open-modal', 'confirm-delete')"
                                        >
                                            <x-heroicon-o-trash class="w-5 h-5"/>
                                        </button>
                                        @endcan
                                        <!-- Modal -->
                                        <x-modal name="confirm-delete" maxWidth="2xl">
                                            <div class="p-6">
                                                <h2 class="text-lg font-medium text-gray-900">
                                                    Delete selected user ({{ $user->name }})?
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
                                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
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
                                    <td colspan="7" class="px-6 py-12 text-center">
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
</x-app-layout>