<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Gateway Setting') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end items-end mb-4">
                <!-- Link to create a new roles -->
                <a href="{{ route('payment_gateway.create') }}" class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Add Payment Gateway</a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Permissions Table -->
					<div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Merchant ID</th>
                                    <th class="border px-4 py-2">Terminal ID</th>
                                    <th class="border px-4 py-2">App ID</th>
                                    <th class="border px-4 py-2">Client ID</th>
                                    <th class="border px-4 py-2">Status</th>
                                    <th class="border px-4 py-2">Sandbox</th>
                                    <th class="sticky right-0 bg-white border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($settings as $gw)
                                    <tr>
                                        <td class="border px-4 py-2">{{ $gw->merchant_id }}</td>
                                        <td class="border px-4 py-2">{{ $gw->terminal_id }}</td>
                                        <td class="border px-4 py-2">{{ $gw->app_id }}</td>
                                        <td class="border px-4 py-2">{{ $gw->client_id }}</td>
                                        <td class="border px-4 py-2">{{ $gw->status == 'active' ? '✅' : '❌' }}</td>
                                        <td class="border px-4 py-2">{{ $gw->sandbox }}</td>
                                        <td class="sticky right-0 bg-white border px-4 py-2">
                                            <!-- Edit Button -->
                                            <a href="{{ route('payment_gateway.edit',$gw) }}" 
                                                class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                            title="Edit"
                                            >
                                                <x-heroicon-o-pencil-square class="w-5 h-5"/>
                                            </a>
                                            <!-- Delete Form/Button -->
                                            <form action="{{ route('payment_gateway.destroy',$gw) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="inline-flex items-center px-2 py-1 text-red-500 hover:text-blue-800"
                                                    title="Delete" 
                                                    onclick="return confirm('Are you sure to delete?')"
                                                >
                                                    <x-heroicon-o-trash class="w-5 h-5"/>
                                                </button>
                                            </form>
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
                        <div class="mt-4">
                            {{ $settings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
