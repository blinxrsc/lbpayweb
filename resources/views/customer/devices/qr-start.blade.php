<x-guest-layout>
    <x-slot name="header">
        <!-- App Logo -->
        @php
            $logo = \App\Models\Setting::where('key', 'site_logo')->first();
            $favicon = \App\Models\Setting::where('key', 'site_favicon')->first();
        @endphp
        <div class="flex items-center justify-center space-x-2">
            {{-- Logo --}}
            <img src="{{ $logo ? asset('storage/'.$logo->value) : asset('images/default-logo.png') }}"
                alt="App Logo"
                class="h-10 w-auto">
            <link rel="icon" type="image/png" href="{{ $favicon ? asset('storage/'.$favicon->value) : asset('images/default-favicon.png') }}">
            {{-- Branding Name --}}
            <span class="text-xl font-bold text-gray-800">
                LBPayLinker
            </span>
        </div>
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
        <p class="mb-2"><strong>Type:</strong> {{ ucfirst($transaction->deviceOutlet->machine_type) }} {{ $transaction->deviceOutlet->machine_num }}</p>
        <p class="mb-2"><strong>Outlet:</strong> {{ $transaction->deviceOutlet->outlet->outlet_name }}</p>
        <p></p>
        <p>Please press the Start Button on the machine to START</p>
    </div>
</x-guest-layout>