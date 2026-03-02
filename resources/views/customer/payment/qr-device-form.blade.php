<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Device Payment (Mobile Payment)</h2>
    </x-slot>

    <div class="p-6 bg-white shadow rounded">
        <p class="mb-4">Redirecting you to Fiuu payment gateway…</p>
        <p>amount ={{ number_format($amount, 2, '.', '') }}</p>
        <p>orderid ={{ $orderId }}</p>
        <p>bill_name ={{ $customer->name }}</p>
        <p>bill_email ={{ $customer->email }}</p>
        <p>bill_mobile =60{{ $customer->phone_number }}</p>
        <p>bill_desc = {{$deviceOutlet->outlet->outlet_name}} {{$deviceOutlet->machine_type}} {{$deviceOutlet->machine_num}}</p>
        <p>currency =MYR</p>
        <p>vcode = {{ $signature }}</p>
        <p>merchantid = {{ $merchantId }}</p>
        <?php echo md5('1.00'.'paynwash02'.'ORD-20260109085122-1'.'d13a34f7e22dadd1f50eee901f471707') ?>
        <form method="POST" action="https://pay.fiuu.com/RMS/pay/{{ $merchantId }}/" id="fiuuDeviceForm">
            <input type="hidden" name="amount" value="{{ number_format($amount, 2, '.', '') }}">
            <input type="hidden" name="orderid" value="{{ $orderId }}">
            <input type="hidden" name="bill_name" value="{{ $customer->name }}">
            <input type="hidden" name="bill_email" value="{{ $customer->email }}">
            <input type="hidden" name="bill_mobile" value="60{{ $customer->phone_number }}">
            <input type="hidden" name="bill_desc" value="{{$deviceOutlet->outlet->outlet_name}} {{$deviceOutlet->machine_type}} {{$deviceOutlet->machine_num}}">
            <input type="hidden" name="currency" value="MYR">
            <input type="hidden" name="vcode" value="{{ $signature }}">
            <input type="hidden" name="returnurl" value="{{ route('guest.payment.return') }}">
            <input type="hidden" name="callbackurl" value="{{ route('guest.payment.callback') }}">
       </form>
       <script>
            document.getElementById('fiuuDeviceForm').submit();
        </script>
    </div>
</x-guest-layout>