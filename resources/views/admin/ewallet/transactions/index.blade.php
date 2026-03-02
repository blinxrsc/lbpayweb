<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Member Account Transaction') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.ewallet.transaction') }}" class="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm">
                <div class="p-4 flex flex-wrap gap-4 items-end relative z-20">
                    <div x-data="{ from: '{{ request('from') }}', to: '{{ request('to') }}', instanceFrom: null, instanceTo: null }" class="flex items-end gap-2">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">From</label>
                            <input x-ref="fromPicker" x-init="instanceFrom = flatpickr($refs.fromPicker, { dateFormat: 'Y-m-d', defaultDate: from })" type="text" name="from" x-model="from" class="w-32 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">To</label>
                            <input x-ref="toPicker" x-init="instanceTo = flatpickr($refs.toPicker, { dateFormat: 'Y-m-d', defaultDate: to })" type="text" name="to" x-model="to" class="w-32 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</label>
                        <div x-data="{ open: false, selected: '{{ request('transaction_type', '') }}', options: { '': 'Any...', 'recharge': 'Recharge', 'deduction': 'Deduction', 'admin_topup': 'Admin Topup', 'referral': 'Referral' } }" class="relative">
                            <input type="hidden" name="transaction_type" :value="selected">
                            <button @click="open = !open" type="button" class="flex items-center w-40 justify-between gap-2 py-2 rounded-lg bg-white text-sm text-gray-800 border border-gray-200 px-3">
                                <span x-text="options[selected]"></span>
                                <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                            </button>
                            <div x-show="open" x-cloak@click.outside="open = false" class="absolute left-0 w-full min-w-[200px] rounded-lg shadow-xl mt-1 z-[100] bg-white p-1 border border-gray-200">
                                <template x-for="(label, value) in options" :key="value">
                                    <button type="button" @click="selected = value; open = false" class="px-3 py-2 w-full text-left text-sm rounded-md hover:bg-blue-50 hover:text-blue-600" :class="selected === value ? 'bg-blue-50 font-bold' : ''">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Outlet</label>
                        <input type="text" name="outlet_name" value="{{ request('outlet_name') }}" placeholder="Outlet..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</label>
                        <input type="text" name="admin_name" value="{{ request('admin_name') }}" placeholder="Admin..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Device SN</label>
                        <input type="text" name="device_serial_number" value="{{ request('device_serial_number') }}" placeholder="S/N..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button type="submit" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply Filters
                        </button>

                        <a href="{{ route('admin.ewallet.transaction') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
                            Clear All
                        </a>
                    </div>

                    <button type="submit" name="export" value="excel" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export to CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <!-- Transactions Table -->
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Outlet
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Machine ID
                        </th>
                        <th scope="col" class="px-8 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Device SN
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Bonus
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Deduct Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Deduct Bonus
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Balance
                        </th>
                        <th scope="col" class="px-8 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Reference
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Admin
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-bold tracking-wider whitespace-nowrap">
                            Reason
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $txn->transaction_time ? \Carbon\Carbon::parse($txn->transaction_time)->format('Y-m-d H:i') : $txn->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->outlet_name }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->customer_name }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->machine_num }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->device_serial_number }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $txn->transaction_type == 'recharge' ? 'bg-blue-100 text-white-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($txn->transaction_type) }}
                                </span>
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                RM {{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                RM {{ number_format($txn->bonus_amount, 2) }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                RM {{ number_format($txn->deduct_amount, 2) }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                RM {{ number_format($txn->deduct_bonus, 2) }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                RM {{ number_format($txn->remaining_balance, 2) }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->reference }}
                            </td>
                            <td class="border px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $txn->admin_email ?? optional($txn->admin)->name ?? '-' }}
                            </td>
                            <td class="border px-6 py-4 text-sm text-gray-500 italic min-w-[200px]">
                                {{ $txn->meta['reason'] ?? '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium">No transactions found</p>
                                    <p class="text-gray-400 text-sm">Try adjusting your filters or resetting the search.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transactions->count() > 0)
                    <tfoot class="bg-blue-50/50 font-bold border-t-2 border-blue-100">
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-right text-sm text-blue-900">Totals:</td>
                            <td class="px-6 py-4 text-sm text-blue-900">RM {{ number_format($summary['total_amount'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-blue-900">RM {{ number_format($summary['total_bonus'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-blue-900">RM {{ number_format($summary['net_balance'], 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
        <div class="mt-6 flex justify-end">
            <a href="{{ route('admin.ewallet.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md"
            >
                ← Back
            </a>
        </div>
    </div>                       
</x-app-layout>