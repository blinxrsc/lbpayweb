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

        @if(session('error'))
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

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
        <div x-data="{
                mode: null,
                price: 0,
                offline: {{ $paymentDisabled ? 'true' : 'false' }},
                select(mode, price) { this.mode = mode; this.price = price },
                active(mode) { return this.mode === mode ? 'border-blue-500 bg-blue-50 text-blue-700 ring-1 ring-blue-500' : 'border-gray-200 hover:border-gray-300' }
            }"
        >
            <!-- Package Options -->
            <p class="text-sm font-medium text-gray-600 mb-2">Select a mode:</p>
            @if($deviceOutlet->machine_type === 'Washer')
                <div class="grid grid-cols-1 gap-2 mb-4">
                    <button type="button" @click="select('washer_warm', {{ $device->washer_warm_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_warm')">
                        Normal <span class="float-right font-semibold">RM {{ number_format($device->washer_warm_price, 2) }}</span></button>
                    <button type="button" @click="select('washer_cold', {{ $device->washer_cold_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_cold')">
                        Cold <span class="float-right font-semibold">RM {{ number_format($device->washer_cold_price, 2) }}</span></button>
                    <button type="button" @click="select('washer_hot', {{ $device->washer_hot_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_hot')">
                        Hot <span class="float-right font-semibold">RM {{ number_format($device->washer_hot_price, 2) }}</span></button>
                </div>
            @elseif($deviceOutlet->machine_type === 'Dryer')
                <div class="grid grid-cols-1 gap-2 mb-4">
                    <button type="button" @click="select('dryer_low', {{ $device->dryer_low_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_low')">
                        Low <span class="float-right font-semibold">RM {{ number_format($device->dryer_low_price, 2) }}</span></button>
                    <button type="button" @click="select('dryer_med', {{ $device->dryer_med_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_med')">
                        Medium <span class="float-right font-semibold">RM {{ number_format($device->dryer_med_price, 2) }}</span></button>
                    <button type="button" @click="select('dryer_hi', {{ $device->dryer_hi_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_hi')">
                        High <span class="float-right font-semibold">RM {{ number_format($device->dryer_hi_price, 2) }}</span></button>
                </div>
            @endif

            {{-- Guest contact details — needed for the payment gateway's billing
                 fields since no account exists yet at this point. Skipped
                 entirely for Balance Payment, which requires login anyway. --}}
            @guest('customer')
            <div class="border-t pt-4 mb-4">
                <p class="text-sm font-medium text-gray-600 mb-2">Your details (for the payment receipt):</p>
                <div class="space-y-2">
                    <input type="text" form="mobilePaymentForm" name="guest_name" value="{{ old('guest_name') }}" placeholder="Full name"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                    <input type="email" form="mobilePaymentForm" name="guest_email" value="{{ old('guest_email') }}" placeholder="Email address"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                    <input type="text" form="mobilePaymentForm" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="Phone number"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                </div>
            </div>
            @endguest

            <!-- Payment Buttons -->
            <div class="space-y-3">
                <!-- Pay with Fiuu (guest, no login required) -->
                <form id="mobilePaymentForm" method="POST" action="{{ route('guest.payment.initiate') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="mode" x-bind:value="mode">
                    <x-primary-button
                        x-bind:disabled="offline || !mode"
                        class="w-full flex justify-center text-center disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"
                    >
                        <span x-text="mode ? 'Pay RM ' + Number(price).toFixed(2) + ' — Mobile Payment' : 'Select a mode to continue'"></span>
                    </x-primary-button>
                </form>

                <!-- Login for ewallet payment -->                
                <form method="GET" action="{{ route('customer.login') }}">
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="mode" x-bind:value="mode">
                    <x-primary-button
                        x-bind:disabled="offline || !mode"
                        class="w-full flex justify-center text-center disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"
                    >
                        Balance Payment (Login)
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
