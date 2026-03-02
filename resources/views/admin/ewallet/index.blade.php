<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Customer Wallets') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 bg-white border-b border-gray-200">
            <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
                    {{-- User Table --}}
                    <table class="min-w-full border-collapse border">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-4 py-2">Customer</th>
                                <th class="border px-4 py-2">Email</th>
                                <th class="border px-4 py-2">Credit Balance</th>
                                <th class="border px-4 py-2">Bonus Balance</th>
                                <th class="border px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td class="border px-4 py-2">{{ $customer->name }}</td>
                                <td class="border px-4 py-2">{{ $customer->email }}</td>
                                <td class="border px-4 py-2">RM {{ number_format(optional($customer->ewalletAccount)->credit_balance ?? 0, 2) }}</td>
                                <td class="border px-4 py-2">RM {{ number_format(optional($customer->ewalletAccount)->bonus_balance ?? 0, 2) }}</td>
                                <td class="border px-4 py-2">
                                    <a href="{{ route('admin.ewallet.adjust', $customer->id) }}"
                                        class="inline-flex items-center px-2 py-1 text-indigo-600 hover:text-indigo-800"
                                        title="Adjust"
                                    >
                                        <x-heroicon-o-wallet class="w-5 h-5"/>
                                    </a>
                                    <a href="{{ route('admin.ewallet.ledger', $customer->id) }}"
                                        class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                        title="Ledger"
                                    >
                                        <x-heroicon-o-table-cells class="w-5 h-5"/>
                                    </a>
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
                        {{ $customers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>