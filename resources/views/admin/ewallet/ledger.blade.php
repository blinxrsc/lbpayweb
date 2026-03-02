<x-app-layout>
    <div class="p-6 bg-white rounded shadow">
        <h2 class="text-lg font-bold mb-4">Ledger for {{ $customer->name }}</h2>

        <table class="min-w-full text-sm text-left border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Amount</th>
                    <th class="px-4 py-2">Bonus</th>
                    <th class="px-4 py-2">Remaining Balance</th>
                    <th class="px-4 py-2">Reference</th>
                    <th class="px-4 py-2">Admin</th>
                    <th class="px-4 py-2">Reason</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $txn)
                    <tr class="border-t">
                        <td class="px-4 py-2">
                            {{ $txn->transaction_time 
                                ? \Carbon\Carbon::parse($txn->transaction_time)->format('Y-m-d H:i') 
                                : $txn->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-2">{{ $txn->transaction_type }}</td>
                        <td class="px-4 py-2">
                            RM {{ number_format($txn->amount, 2) }}
                        </td>
                        <td class="px-4 py-2">
                            RM {{ number_format($txn->bonus_amount, 2) }}
                        </td>
                        <td class="px-4 py-2 font-semibold">
                            RM {{ number_format($txn->remaining_balance, 2) }}
                        </td>
                        <td class="px-4 py-2">{{ $txn->reference }}</td>
                        <td class="px-4 py-2">
                            {{ $txn->admin_email ?? optional($txn->admin)->name ?? '-' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $txn->meta['reason'] ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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