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
            <p><strong>Customer:</strong> {{ $transaction->customer->name }} ({{ $transaction->customer->email }})</p>
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

        <!-- Actions -->
        <div class="mt-6 flex space-x-3">
            @if($transaction->status === 'paid')
                <form method="POST" action="{{ route('admin.device-transactions.activate', $transaction) }}">
                    @csrf
                    <x-primary-button>Activate</x-primary-button>
                </form>
            @endif
            @if(in_array($transaction->status, ['completed','failed']))
                <form method="POST" action="{{ route('admin.device-transactions.refund', $transaction) }}">
                    @csrf
                    <x-secondary-button>Refund</x-secondary-button>
                </form>
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