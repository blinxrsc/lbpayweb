<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mail Server Setting') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end items-end mb-4">
                <!-- Link to create a new roles -->
                <a href="{{ route('mailserver.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add Mail Server</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Permissions Table -->
					<div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Host</th>
                                    <th class="border px-4 py-2">Port</th>
                                    <th class="border px-4 py-2">Username</th>
                                    <th class="border px-4 py-2">Encryption</th>
                                    <th class="border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($settings as $s)
                                    <tr>
                                        <td class="border px-4 py-2">{{ $s->host }}</td>
                                        <td class="border px-4 py-2">{{ $s->port }}</td>
                                        <td class="border px-4 py-2">{{ $s->username }}</td>
                                        <td class="border px-4 py-2">{{ $s->encryption }}</td>
                                        <td class="border px-4 py-2">
                                            <!-- Edit Button -->
                                            <a href="{{ route('mailserver.edit',$s) }}" 
                                                class="inline-flex items-center px-2 py-1 text-indigo-600 hover:text-indigo-800"
                                                title="Edit"
                                            >
                                                <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                            </a>
                                            <!-- Delete Form/Button -->
                                            <form action="{{ route('mailserver.destroy',$s) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-800"
                                                    title="Delete" 
                                                    onclick="return confirm('Are you sure to delete?')"
                                                >
                                                    <x-heroicon-o-trash class="w-5 h-5"/>
                                                </button>
                                            </form>
                                            <form action="{{ route('mailserver.test',$s) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" 
                                                    class="inline-flex items-center px-2 py-1 text-amber-600 hover:text-amber-800"
                                                    title="Test Mail" 
                                                    onclick="return confirm('Are you sure to test?')"
                                                >
                                                    <x-heroicon-o-envelope class="w-5 h-5"/>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
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
