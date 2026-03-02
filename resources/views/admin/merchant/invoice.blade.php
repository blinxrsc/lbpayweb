<x-app-layout>
<!DOCTYPE html>
<html>
<head>
    <style>
        .receipt-box { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #eee; font-family: 'Helvetica', sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { max-width: 100px; margin-bottom: 10px; }
        .company-name { font-weight: bold; font-size: 1.2rem; display: block; }
        .details { font-size: 0.8rem; color: #555; line-height: 1.4; }
        .divider { border-top: 1px dashed #ccc; margin: 15px 0; }
        .table { width: 100%; font-size: 0.9rem; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<div class="receipt-box">
    <div class="header">
        @if($merchant->logo_path)
            <img src="{{ asset('storage/' . $merchant->logo_path) }}" class="logo">
        @endif
        
        <span class="company-name">{{ $merchant->company_name }}</span>
        <div class="details">
            {{ $merchant->reg_no }}<br>
            {{ $merchant->address }}, {{ $merchant->city }}<br>
            {{ $merchant->state }}, {{ $merchant->country }}<br>
            @if($merchant->support_number) Tel: {{ $merchant->support_number }} @endif
        </div>
    </div>

    <div class="divider"></div>

    <table class="table">
        <tr>
            <td><strong>Receipt #:</strong> {{ $transaction?->order_id }}</td>
            <td class="text-right">{{ $transaction?->created_at->format('d M Y') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="table">
        <tr>
            <td>Payment for: {{ $transaction?->meta }}</td>
            <td class="text-right">${{ number_format($transaction?->amount, 2) }}</td>
        </tr>
        <tr style="font-weight: bold;">
            <td>TOTAL</td>
            <td class="text-right">${{ number_format($transaction?->amount, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="header">
        <div class="details">
            Thank you for using {{ $merchant->company_name }}!<br>
            Visit us: {{ $merchant->website }}<br>
            Support: {{ $merchant->email }}
        </div>
    </div>
</div>

</body>
</html>
</x-app-layout>