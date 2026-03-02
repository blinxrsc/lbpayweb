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

        <!-- Machine Info -->
        <div class="border-b pb-4 mb-4">
            <p><strong>Machine #: </strong>{{ ucfirst($deviceOutlet->machine_type) }} {{ $deviceOutlet->machine_num }} ({{ $deviceOutlet->machine_name }})</p>
            <p><strong>Outlet: </strong> {{ $deviceOutlet->outlet->outlet_name }}</p>
            <p><strong>Status: </strong><span class="text-green-600 font-medium"> {{ ucfirst($deviceOutlet->status) }}</span></p>
            <p><strong>Availability: </strong> {{ $deviceOutlet->availability ? 'Available' : 'Busy' }}</p>
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
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->washer_warm_price }})"
                        class="w-full px-3 py-2 border rounded">Normal (RM {{ number_format($deviceOutlet->device->washer_warm_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->washer_cold_price }})"
                        class="w-full px-3 py-2 border rounded">Cold (RM {{ number_format($deviceOutlet->device->washer_cold_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->washer_hot_price }})"
                        class="w-full px-3 py-2 border rounded">Hot (RM {{ number_format($deviceOutlet->device->washer_hot_price, 2) }})</button>
                </div>
            @elseif($deviceOutlet->machine_type === 'Dryer')
                <div class="space-y-2 mb-4">
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->dryer_low_price }})"
                        class="w-full px-3 py-2 border rounded">Low (RM {{ number_format($deviceOutlet->device->dryer_low_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->dryer_med_price }})"
                        class="w-full px-3 py-2 border rounded">Medium (RM {{ number_format($deviceOutlet->device->dryer_med_price, 2) }})</button>
                    <button type="button" @click="setPackage({{ $deviceOutlet->device->dryer_hi_price }})"
                        class="w-full px-3 py-2 border rounded">High (RM {{ number_format($deviceOutlet->device->dryer_hi_price, 2) }})</button>
                </div>
            @endif

            <!-- Payment Buttons -->
            <div class="space-y-3">
                <!-- Pay with Fiuu -->
                <form method="POST" action="{{ route('customer.payment.initiate') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="amount" :value="value">
                    <x-primary-button class="w-full">Pay with Fiuu</x-primary-button>
                </form>

                <!-- Pay with Wallet -->
                <form method="POST" action="{{ route('customer.payment.ewallet') }}">
                    @csrf
                    <input type="hidden" name="device_outlet_id" value="{{ $deviceOutlet->id }}">
                    <input type="hidden" name="amount" :value="value">
                    <x-secondary-button class="w-full">Pay with Wallet</x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-customer-layout>