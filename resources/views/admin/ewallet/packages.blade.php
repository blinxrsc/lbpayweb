<x-app-layout>
</form>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Top-Up Packages') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Add New Topup Package --}}
            <div class="flex justify-end items-end mb-4">
                <a href="{{ route('admin.packages.create') }}" 
                    class="btn btn-primary mb-4 inline-block px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                >
                    Add Topup Package
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    {{-- User Table --}}
                    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                        <table class="min-w-full border-collapse border">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Top-Up Amount</th>
                                    <th class="border px-4 py-2">Bonus Amount</th>
                                    <th class="border px-4 py-2">Active</th>
                                    <th class="border px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                    <tr>
                                        <td class="border px-4 py-2">RM {{ number_format($package->topup_amount, 2) }}</td>
                                        <td class="border px-4 py-2">RM {{ number_format($package->bonus_amount, 2) }}</td>
                                        <td class="border px-4 py-2">
                                            <form method="POST" action="{{ route('admin.packages.toggle', $package->id) }}">
                                                @csrf
                                                <button type="submit" class="px-2 py-1">
                                                    {{ $package->is_active ? '✅' : '❌' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="border px-4 py-2">
                                            <form method="POST" action="{{ route('admin.packages.update', $package->id) }}" class="flex space-x-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" step="0.01" name="topup_amount" value="{{ $package->topup_amount }}"
                                                    class="border rounded px-2 py-1 w-24">
                                                <input type="number" step="0.01" name="bonus_amount" value="{{ $package->bonus_amount }}"
                                                    class="border rounded px-2 py-1 w-24">
                                                <x-primary-button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded">Update</x-primary-button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="border px-4 py-2 text-center text-gray-500">
                                            🛑 No packages found.
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
