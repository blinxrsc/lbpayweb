<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Payment Result</h2>
    </x-slot>

    <div class="p-6 bg-white shadow rounded">
        @if($status === '00')
            <p class="text-green-600">Your payment was successful. Wallet credited.</p>
        @else
            <p class="text-red-600">Payment failed or cancelled.</p>
        @endif

        <a href="{{ route('customer.dashboard') }}" class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white rounded">
            Back to Dashboard
        </a>
    </div>
</x-customer-layout>