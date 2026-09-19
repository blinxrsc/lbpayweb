<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home'],
            ['url' => route('customer.outlet.select'), 'label' => 'Outlet'],
            ['url' => '#', 'label' => 'Payment Confirmation'],
        ]" />
    </x-slot>

    <div class="bg-white shadow-md rounded-lg p-6 max-w-md mx-auto">
        <h2 class="text-xl font-bold text-center mb-4">Payment Confirmation</h2>

        @if(session('error'))
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @php
            $isOnline = $deviceOutlet->is_online;
            $priceMap = [
                'washer_cold' => $deviceOutlet->device->washer_cold_price ?? 0,
                'washer_warm' => $deviceOutlet->device->washer_warm_price ?? 0,
                'washer_hot'  => $deviceOutlet->device->washer_hot_price ?? 0,
                'dryer_low'   => $deviceOutlet->device->dryer_low_price ?? 0,
                'dryer_med'   => $deviceOutlet->device->dryer_med_price ?? 0,
                'dryer_hi'    => $deviceOutlet->device->dryer_hi_price ?? 0,
            ];
            $preselectedPrice = $priceMap[$preselectedMode] ?? 0;
        @endphp

        @unless($isOnline)
            <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-4">
                <p class="text-red-700 font-bold">Cashless system temporarily offline</p>
                <p class="text-red-600 text-sm mt-1">This machine is currently offline and unable to accept payments. We apologize for the inconvenience! Please try another machine.</p>
            </div>
        @endunless

        <!-- Machine Info -->
        <div class="border-b pb-4 mb-4">
            <p><strong>Machine #: </strong>{{ ucfirst($deviceOutlet->machine_type) }} {{ $deviceOutlet->machine_num }} ({{ $deviceOutlet->machine_name }})</p>
            <p><strong>Outlet: </strong> {{ $deviceOutlet->outlet->outlet_name }}</p>
            <p><strong>Status: </strong><span class="{{ $isOnline ? 'text-green-600' : 'text-red-600' }} font-medium"> {{ ucfirst($deviceOutlet->status) }}</span></p>
            <p><strong>Availability: </strong> {{ $deviceOutlet->availability ? 'Available' : 'Busy' }}</p>
        </div>

        <!-- Alpine.js reactive block -->
        <div x-data="{
                mode: {{ $preselectedMode ? "'{$preselectedMode}'" : 'null' }},
                price: {{ $preselectedPrice }},
                offline: {{ !$isOnline ? 'true' : 'false' }},
                select(mode, price) { this.mode = mode; this.price = price },
                active(mode) { return this.mode === mode ? 'border-blue-500 bg-blue-50 text-blue-700 ring-1 ring-blue-500' : 'border-gray-200 hover:border-gray-300' }
            }"
        >
            <p class="text-sm font-medium text-gray-600 mb-2">Select a mode:</p>
            @if($deviceOutlet->machine_type === 'Washer')
                <div class="grid grid-cols-1 gap-2 mb-4">
                    <button type="button" @click="select('washer_warm', {{ $deviceOutlet->device->washer_warm_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_warm')">
                        Normal <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->washer_warm_price, 2) }}</span></button>
                    <button type="button" @click="select('washer_cold', {{ $deviceOutlet->device->washer_cold_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_cold')">
                        Cold <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->washer_cold_price, 2) }}</span></button>
                    <button type="button" @click="select('washer_hot', {{ $deviceOutlet->device->washer_hot_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('washer_hot')">
                        Hot <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->washer_hot_price, 2) }}</span></button>
                </div>
            @elseif($deviceOutlet->machine_type === 'Dryer')
                <div class="grid grid-cols-1 gap-2 mb-4">
                    <button type="button" @click="select('dryer_low', {{ $deviceOutlet->device->dryer_low_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_low')">
                        Low <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->dryer_low_price, 2) }}</span></button>
                    <button type="button" @click="select('dryer_med', {{ $deviceOutlet->device->dryer_med_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_med')">
                        Medium <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->dryer_med_price, 2) }}</span></button>
                    <button type="button" @click="select('dryer_hi', {{ $deviceOutlet->device->dryer_hi_price }})"
                        class="w-full px-3 py-2 border rounded-md text-left transition-colors" :class="active('dryer_hi')">
                        High <span class="float-right font-semibold">RM {{ number_format($deviceOutlet->device->dryer_hi_price, 2) }}</span></button>
                </div>
            @endif

            <!-- Payment Buttons -->
            <div class="space-y-3">
                <!-- Pay with Fiuu -->
                <form method="POST" action="{{ route('customer.payment.initiate') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="mode" x-bind:value="mode">
                    <x-primary-button
                        x-bind:disabled="offline || !mode"
                        class="w-full flex justify-center text-center disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"
                    >
                        <span x-text="mode ? 'Pay RM ' + Number(price).toFixed(2) + ' with Fiuu' : 'Select a mode to continue'"></span>
                    </x-primary-button>
                </form>

                <!-- Pay with Wallet -->
                <form method="POST" action="{{ route('customer.payment.ewallet') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="mode" x-bind:value="mode">
                    <x-secondary-button
                        type="submit"
                        x-bind:disabled="offline || !mode"
                        class="w-full flex justify-center text-center disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none"
                    >
                        <span x-text="mode ? 'Pay RM ' + Number(price).toFixed(2) + ' with Wallet' : 'Select a mode to continue'"></span>
                    </x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-customer-layout>
