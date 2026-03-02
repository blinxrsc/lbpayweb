<!-- resources/views/ewallet/success.blade.php -->
<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Payment Successful</h2>
    </x-slot>

    <div class="p-6 bg-white shadow rounded">
        <p class="text-green-600 text-lg mb-4">
            🎉 Your payment was successful! Your wallet has been credited.
        </p>

        <h3 class="text-md font-semibold mb-2">Transaction Summary</h3>
        <table class="min-w-full divide-y divide-gray-200 mb-4">
            <tbody class="divide-y divide-gray-100">
                <tr>
                    <td class="px-4 py-2 font-medium">Order ID</td>
                    <td class="px-4 py-2">{{ $orderId }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Amount</td>
                    <td class="px-4 py-2">RM {{ number_format($amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Date</td>
                    <td class="px-4 py-2">{{ $date->format('d M Y H:i') }}</td>
                </tr>
            </tbody>
        </table>

        <a href="{{ route('customer.dashboard') }}"
           class="inline-block px-4 py-2 bg-indigo-600 text-white rounded">
            Back to Dashboard
        </a>
    </div>
</x-customer-layout>
