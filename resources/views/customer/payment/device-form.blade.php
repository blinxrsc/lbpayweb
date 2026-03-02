<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Device Payment (Mobile Payment)</h2>
    </x-slot>

    <div class="p-6 bg-white shadow rounded">
        <p class="mb-4">Redirecting you to Fiuu payment gateway…</p>
        <p>amount ={{ number_format($amount, 2, '.', '') }}</p>
        <p>orderid ={{ $orderId }}</p>
        <p>bill_name ={{ auth('customer')->user()->name }}</p>
        <p>bill_email ={{ auth('customer')->user()->email }}</p>
        <p>bill_mobile =60{{ auth('customer')->user()->phone_number }}</p>
        <p>bill_desc = Topup</p>
        <p>currency =MYR</p>
        <p>vcode = {{ $signature }}</p>
        <p>merchantid = {{ $merchantId }}</p>
        <?php echo md5('1.00'.'paynwash02'.'ORD-20260109085122-1'.'d13a34f7e22dadd1f50eee901f471707') ?>

        <form method="POST" action="https://pay.fiuu.com/RMS/pay/{{ $merchantId }}/" id="fiuuDeviceForm">
            @csrf
            <input type="hidden" name="amount" value="{{ number_format($amount, 2, '.', '') }}">
            <input type="hidden" name="orderid" value="{{ $orderId }}">
            <input type="hidden" name="bill_name" value="{{ auth('customer')->user()->name }}">
            <input type="hidden" name="bill_email" value="{{ auth('customer')->user()->email }}">
            <input type="hidden" name="bill_mobile" value="60{{ auth('customer')->user()->phone_number }}">
            <input type="hidden" name="bill_desc" value="Topup">
            <input type="hidden" name="currency" value="MYR">
            <input type="hidden" name="vcode" value="{{ $signature }}">
            <input type="hidden" name="returnurl" value="{{ route('customer.payment.return') }}">
            <input type="hidden" name="callbackurl" value="{{ route('customer.payment.callback') }}">
       </form>
       <script>
            document.getElementById('fiuuDeviceForm').submit();
        </script>
 
    </div>
</x-customer-layout>