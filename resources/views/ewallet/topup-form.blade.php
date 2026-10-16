<!-- resources/views/ewallet/topup-form.blade.php -->
<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Top‑up Payment</h2>
    </x-slot>

    <div class="p-6 bg-white shadow rounded">
        <p class="mb-4">Redirecting you to the payment gateway…</p>

    
        <form method="POST" action="https://pay.fiuu.com/RMS/pay/{{ $merchantId }}/" id="fiuuTopupForm">
            <input type="hidden" name="amount" value="{{ number_format($amount, 2, '.', '') }}">
            <input type="hidden" name="orderid" value="{{ $orderId }}">
            <input type="hidden" name="bill_name" value="{{ auth('customer')->user()->name }}">
            <input type="hidden" name="bill_email" value="{{ auth('customer')->user()->email }}">
            <input type="hidden" name="bill_mobile" value="60{{ auth('customer')->user()->phone_number }}">
            <input type="hidden" name="bill_desc" value="Topup">
            <input type="hidden" name="currency" value="MYR">
            <input type="hidden" name="vcode" value="{{ $signature }}">
            <input type="hidden" name="returnurl" value="{{ route('gateway.return') }}">
            <input type="hidden" name="callbackurl" value="{{ route('gateway.callback') }}">
       </form>
       <script>
            document.getElementById('fiuuTopupForm').submit();
        </script>
    </div>
</x-customer-layout>