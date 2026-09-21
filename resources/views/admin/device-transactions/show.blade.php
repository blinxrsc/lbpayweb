<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Transaction #{{ $transaction->id }}
        </h2>
    </x-slot>

    <div class="bg-white shadow-md rounded-lg p-6 max-w-lg mx-auto">
        <h3 class="text-lg font-bold mb-4">Receipt #{{ $transaction->id }}-{{ $transaction->provider_txn_id }}</h3>

        <!-- Customer Info -->
        <div class="mb-4">
            <p><strong>Customer:</strong>
                @if($transaction->customer)
                    {{ $transaction->customer->name }} ({{ $transaction->customer->email }})
                @else
                    {{ $transaction->meta['guest_name'] ?? 'Guest' }} ({{ $transaction->meta['guest_email'] ?? 'no account' }})
                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">GUEST</span>
                @endif
            </p>
            <p><strong>Outlet:</strong> {{ $transaction->deviceOutlet->outlet->outlet_name }}</p>
            <p><strong>Machine:</strong> {{ $transaction->deviceOutlet->machine_type }} {{ $transaction->deviceOutlet->machine_num }}</p>
        </div>

        <!-- Transaction Details -->
        <table class="w-full text-sm border">
            <tr>
                <td class="border px-3 py-2">Order ID #</td>
                <td class="border px-3 py-2">{{ $transaction->order_id }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Transaction ID #</td>
                <td class="border px-3 py-2">{{ $transaction->provider_txn_id }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Amount</td>
                <td class="border px-3 py-2">RM {{ number_format($transaction->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Payment Method</td>
                <td class="border px-3 py-2">{{ ucfirst($transaction->provider) }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Status</td>
                <td class="border px-3 py-2">{{ ucfirst($transaction->status) }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Created At</td>
                <td class="border px-3 py-2">{{ $transaction->created_at }}</td>
            </tr>
            <tr>
                <td class="border px-3 py-2">Updated At</td>
                <td class="border px-3 py-2">{{ $transaction->updated_at }}</td>
            </tr>
        </table>

        <!-- IoT / Meta Logs -->
        @if($transaction->meta)
            <div class="mt-4">
                <h4 class="font-semibold">IoT / Meta Data</h4>
                <pre class="bg-gray-100 p-3 rounded text-xs">{{ json_encode($transaction->meta, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif

        <!-- Refund / Compensation record -->
        @if($transaction->refund)
            <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-4">
                <h4 class="font-semibold text-sm text-gray-700 mb-2">Refund Record</h4>
                <p class="text-sm">Method: <strong>{{ ucfirst(str_replace('_', ' ', $transaction->refund->method)) }}</strong></p>
                @if($transaction->refund->amount)
                    <p class="text-sm">Amount credited: RM {{ number_format($transaction->refund->amount, 2) }}</p>
                @endif
                @if($transaction->refund->compensationDeviceOutlet)
                    <p class="text-sm">Compensation machine: {{ $transaction->refund->compensationDeviceOutlet->outlet->outlet_name }} — {{ $transaction->refund->compensationDeviceOutlet->machine_type }} #{{ $transaction->refund->compensationDeviceOutlet->machine_num }}</p>
                @endif
                <p class="text-sm">Processed by: {{ $transaction->refund->resolvedBy->name }} on {{ $transaction->refund->created_at->format('Y-m-d H:i') }}</p>
                <p class="text-sm mt-1">Reason: {{ $transaction->refund->reason }}</p>
                @if($transaction->refund->screenshot_path)
                    <a href="{{ Storage::url($transaction->refund->screenshot_path) }}" target="_blank" class="inline-block mt-2 text-sm text-blue-600 hover:underline">
                        View customer's screenshot →
                    </a>
                @endif
            </div>
        @endif

        <!-- Actions -->
        <div class="mt-6 flex space-x-3">
            @if($transaction->status === 'paid')
                <form method="POST" action="{{ route('admin.device-transactions.activate', $transaction) }}">
                    @csrf
                    <x-primary-button onclick="return confirm('Re-send the start pulse to this machine?')">Retry Pulse</x-primary-button>
                </form>
            @endif
            @if(!$transaction->refund && in_array($transaction->status, ['paid','activated','completed','failed']))
                @can('transactions.refund')
                <a href="{{ route('admin.device-transactions.refund.create', $transaction) }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    Process Refund
                </a>
                @endcan
            @endif
        </div>
        
        <div class="mt-6 flex justify-end">
            <a href="{{ url()->previous() ?? route('outlets.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md">
                ← Back
            </a>
        </div>

    </div>
</x-app-layout>