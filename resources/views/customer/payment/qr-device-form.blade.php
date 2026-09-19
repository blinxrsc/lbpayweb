<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Redirecting to Payment</h2>
    </x-slot>

    <div class="max-w-md mx-auto mt-6 bg-white shadow-md rounded-lg p-8 text-center">
        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        <p class="text-gray-700 font-medium">Taking you to the payment gateway…</p>
        <p class="text-sm text-gray-400 mt-1">RM {{ number_format($amount, 2) }} — {{ $deviceOutlet->outlet->outlet_name }}</p>

        <form method="POST" action="https://pay.fiuu.com/RMS/pay/{{ $merchantId }}/" id="fiuuDeviceForm">
            <input type="hidden" name="amount" value="{{ number_format($amount, 2, '.', '') }}">
            <input type="hidden" name="orderid" value="{{ $orderId }}">
            <input type="hidden" name="bill_name" value="{{ $billName }}">
            <input type="hidden" name="bill_email" value="{{ $billEmail }}">
            <input type="hidden" name="bill_mobile" value="60{{ $billPhone }}">
            <input type="hidden" name="bill_desc" value="{{ $deviceOutlet->outlet->outlet_name }} {{ $deviceOutlet->machine_type }} {{ $deviceOutlet->machine_num }}">
            <input type="hidden" name="currency" value="MYR">
            <input type="hidden" name="vcode" value="{{ $signature }}">
            <input type="hidden" name="merchantid" value="{{ $merchantId }}">
            <input type="hidden" name="returnurl" value="{{ route('guest.payment.return') }}">
            <input type="hidden" name="callbackurl" value="{{ route('guest.payment.callback') }}">
       </form>
       <script>
            document.getElementById('fiuuDeviceForm').submit();
        </script>
    </div>
</x-guest-layout>
