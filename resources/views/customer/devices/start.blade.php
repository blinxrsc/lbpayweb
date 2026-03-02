<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Start Machine
        </h2>
    </x-slot>

    <div class="max-w-md mx-auto mt-6 bg-white shadow-md rounded-lg p-6 text-center">
        <!-- Success Notification -->
        <div class="mb-4 p-4 rounded bg-green-100 border border-green-400 text-green-700">
            ✅ Payment successful! Order {{ $transaction->order_id }}  
            Amount: RM {{ number_format($transaction->amount, 2) }}
        </div>

        <!-- Machine Info -->
        <p class="mb-2"><strong>Machine:</strong> {{ $transaction->deviceOutlet->machine_name }}</p>
        <p class="mb-2"><strong>Type:</strong> {{ ucfirst($transaction->deviceOutlet->machine_type) }}</p>
        <p class="mb-2"><strong>Outlet:</strong> {{ $transaction->deviceOutlet->outlet->name }}</p>
        <p></p>
        <p>Please press the Start Button on the machine to START</p>
        <!-- Back to Device Listing -->
        <a href="{{ route('customer.devices.index') }}"
           class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
            ← Back to Devices
        </a>
    </div>
</x-customer-layout>