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
    </x-slot>

    <div class="bg-white shadow-md rounded-lg p-6 max-w-md mx-auto">
        @php
            $deviceOutlet = $device->deviceOutlets->first();
            $isOnline = $deviceOutlet?->is_online;
            $paymentDisabled = !$isOnline;
        @endphp

        <h2 class="text-xl font-bold text-center mb-4">Payment Confirmation for {{ ucfirst($device->serial_number) }}</h2>

        @unless($isOnline)
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4">
                <p class="text-red-700 font-bold">Cashless system temporarily offline</p>
                <p class="text-red-600 text-sm mt-1">This machine is currently offline and unable to accept payments. We apologize for the inconvenience! Please try another machine.</p>
            </div>
        @endunless

        <!-- Machine Info -->
        <div class="border-b pb-4 mb-4">
            <p>Machine #: <strong>{{ ucfirst($deviceOutlet->machine_type) }} {{ $deviceOutlet->machine_num }} ({{ $deviceOutlet->machine_name }})</strong></p>
            <p>Outlet: <strong>{{ $deviceOutlet->outlet->outlet_name }}</strong></p>
            <p>Status: <strong><span class="{{ $isOnline ? 'text-green-600' : 'text-red-600' }} font-medium"> {{ ucfirst($deviceOutlet->status) }}</span></strong></p>
            <p>Availability: <strong>{{ $deviceOutlet->availability ? 'Available' : 'Busy' }}</strong></p>
        </div>

        <!-- Alpine.js reactive block -->
        <div 
            x-data="{
                value: 0,
                setPackage(price) { this.value = price },
                increase() { this.value += 1 },
                decrease() { if(this.value > 0) this.value -= 1 }
            }"
        >
            <!-- Textfield with + / - buttons -->
            <div class="flex items-center space-x-2 mb-4">
                <button type="button" @click="decrease" class="px-3 py-1 bg-gray-200 rounded">−</button>
                <input type="text" x-model="value" class="w-20 text-center border rounded" readonly>
                <button type="button" @click="increase" class="px-3 py-1 bg-gray-200 rounded">+</button>
            </div>

            <!-- Package Options -->
            @if($deviceOutlet->machine_type === 'Washer')
                <div class="space-y-2 mb-4">
                    <button type="button" @click="setPackage({{ $device->washer_warm_price }})"
                        class="w-full px-3 py-2 border rounded">Normal (RM {{ number_format($device->washer_warm_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $device->washer_cold_price }})"
                        class="w-full px-3 py-2 border rounded">Cold (RM {{ number_format($device->washer_cold_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $device->washer_hot_price }})"
                        class="w-full px-3 py-2 border rounded">Hot (RM {{ number_format($device->washer_hot_price, 2) }})</button>
                </div>
            @elseif($deviceOutlet->machine_type === 'Dryer')
                <div class="space-y-2 mb-4">
                    <button type="button" @click="setPackage({{ $device->dryer_low_price }})"
                        class="w-full px-3 py-2 border rounded">Low (RM {{ number_format($device->dryer_low_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $device->dryer_med_price }})"
                        class="w-full px-3 py-2 border rounded">Medium (RM {{ number_format($device->dryer_med_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $device->dryer_hi_price }})"
                        class="w-full px-3 py-2 border rounded">High (RM {{ number_format($device->dryer_hi_price, 2) }})</button>
                </div>
            @endif

            <!-- Payment Buttons -->
            <div class="space-y-3">
                <!-- Pay with Fiuu -->
                <form method="POST" action="{{ route('guest.payment.initiate') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $device->id }}">
                    <input type="hidden" name="amount" :value="value">
                    <x-primary-button
                        :disabled="$paymentDisabled"
                        class="w-full flex justify-center text-center {{ $paymentDisabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                    >Mobile Payment</x-primary-button>
                </form>
                <button></button>
                <!-- Login for ewallet payment -->                
                <form method="GET" action="{{ route('customer.login') }}">
                    <input type="hidden" name="device_outlet_id" value="{{ $device->id }}">
                    <input type="hidden" name="amount" x-bind:value="value">
                    <x-primary-button
                        :disabled="$paymentDisabled"
                        class="w-full flex justify-center text-center {{ $paymentDisabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                    >
                        Balance Payment (Login)
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>