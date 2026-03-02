<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Transactions') }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.device-transactions.index') }}" class="mb-6 bg-white rounded-xl border border-gray-100 shadow-sm">
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
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                        <div x-data="{ open: false, selected: '{{ request('status', '') }}', options: { '': 'Any...', 'initiated': 'Initiated', 'paid': 'Paid', 'activated': 'Activated', 'completed': 'Completed', 'failed': 'Failed', 'refunded': 'Refunded' } }" class="relative">
                            <input type="hidden" name="status" :value="selected">
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
                        <input type="text" name="outlet" value="{{ request('outlet') }}" placeholder="Outlet..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Customer</label>
                        <input type="text" name="customer" value="{{ request('customer') }}" placeholder="Name/Email..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500">
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

                        <a href="{{ route('admin.device-transactions.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium px-3 py-2 transition">
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
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <!-- Transactions Table -->
                <table class="min-w-full border-collapse border">
                    <thead class="bg-gray-100 sticky top-0 z-10 shadow-sm">
                        <tr class="bg-gray-100">
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Date Time</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Customer Email</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Customer Phone</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Outlet</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Machine</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Provider</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Trans #</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Order #</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Amount</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap">Status</th>
                            <th class="border px-4 py-2 text-sm tracking-wider whitespace-nowrap sticky right-0 bg-gray-100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">
                                    {{ $tx->updated_at ? \Carbon\Carbon::parse($tx->updated_at)->format('Y-m-d H:i') : $tx->updated_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $tx->customer->email }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $tx->customer->phone_country_code }}{{ $tx->customer->phone_number }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $tx->deviceOutlet->outlet->outlet_name }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                                {{ $tx->deviceOutlet->machine_type == 'Washer' ? 'bg-blue-600 text-white' : '' }}
                                                {{ $tx->deviceOutlet->machine_typee == 'Dryer' ? 'bg-amber-600 text-white' : '' }}
                                                {{ $tx->deviceOutlet->machine_type == 'Combo' ? 'bg-indigo-600 text-white' : '' }}
                                                {{ !in_array($tx->deviceOutlet->machine_type, ['Washer', 'Dryer', 'Combo']) ? 'bg-indigo-100 text-white' : '' }}
                                            ">
                                                {{ $tx->deviceOutlet->machine_type }} {{ $tx->deviceOutlet->machine_num }}
                                            </span>
                                </td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($tx->provider) }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $tx->provider_txn_id }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">{{ $tx->order_id }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm text-gray-600">RM {{ number_format($tx->amount, 2) }}</td>
                                <td class="border px-4 py-2 whitespace-nowrap text-sm" 
                                    x-data="{ 
                                        status: '{{ $tx->status }}',
                                        statusClasses: {
                                            'initiated': 'bg-gray-100 text-gray-700',
                                            'paid': 'bg-green-100 text-green-700',
                                            'activated': 'bg-indigo-100 text-indigo-700',
                                            'failed': 'bg-red-100 text-red-700',
                                            'refunded': 'bg-yellow-100 text-yellow-500',
                                            'cancelled': 'bg-amber-100 text-amber-700'
                                        }
                                    }">
                                    <span 
                                        :class="statusClasses[status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                        x-text="status"
                                    >
                                    </span>
                                </td>
                                <td class="border px-4 py-2 sticky right-0 bg-white">
                                    <!-- New View button -->
                                    <a href="{{ route('admin.device-transactions.show', $tx) }}" 
                                        class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                        title="View Details"
                                    >
                                        <x-heroicon-o-eye class="w-5 h-5"/>
                                    </a>
                                    @if($tx->status === 'paid')
                                        <form method="POST" action="{{ route('admin.device-transactions.activate', $tx) }}">
                                            @csrf
                                            <button type="submit" 
                                                class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                                title="Remote Start" 
                                                onclick="return confirm('Are you want to remote start?')"
                                            >
                                                <x-heroicon-o-play-circle class="w-5 h-5"/>
                                            </button>
                                        </form>
                                    @endif
                                    @if($tx->status === 'completed' || $tx->status === 'failed')
                                        <form method="POST" action="{{ route('admin.device-transactions.refund', $tx) }}">
                                            @csrf
                                            <button type="submit" 
                                                class="inline-flex items-center px-2 py-1 text-blue-600 hover:text-blue-800"
                                                title="Refund" 
                                                onclick="return confirm('Are you sure to refund?')"
                                            >
                                                <x-heroicon-o-currency-dollar class="w-5 h-5"/>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center">
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
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>