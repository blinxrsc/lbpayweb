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

    <div class="max-w-md mx-auto mt-6 bg-white shadow-md rounded-lg p-6 text-center"
        x-data="{
            status: '{{ $transaction->status }}',
            polling: {{ $transaction->status === 'activated' ? 'true' : 'false' }},
            init() {
                if (!this.polling) return;
                const poll = () => {
                    fetch('{{ route('payment.ack-status', $transaction) }}')
                        .then(r => r.json())
                        .then(data => {
                            this.status = data.status;
                            if (['completed','failed','busy','rejected','duplicate_ignored'].includes(data.status)) {
                                this.polling = false;
                                return;
                            }
                            setTimeout(poll, 2000);
                        })
                        .catch(() => setTimeout(poll, 3000));
                };
                poll();
            }
        }"
        x-init="init()"
    >
        <template x-if="status === 'completed'">
            <div class="mb-4 p-4 rounded-md bg-green-50 border border-green-300 text-green-700">
                <p class="font-semibold">✅ Your machine has started!</p>
                <p class="text-sm mt-1">Order {{ $transaction->order_id }} · RM {{ number_format($transaction->amount, 2) }}</p>
            </div>
        </template>
        <template x-if="['activated','received','awaiting_device'].includes(status)">
            <div class="mb-4 p-4 rounded-md bg-blue-50 border border-blue-300 text-blue-700">
                <svg class="animate-spin h-5 w-5 mx-auto mb-2" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="font-semibold" x-text="status === 'received' ? 'Machine received the command…' : 'Starting your machine…'"></p>
                <p class="text-sm mt-1">Order {{ $transaction->order_id }} · RM {{ number_format($transaction->amount, 2) }}</p>
            </div>
        </template>
        <template x-if="['busy','rejected','duplicate_ignored'].includes(status)">
            <div class="mb-4 p-4 rounded-md bg-amber-50 border border-amber-300 text-amber-700">
                <p class="font-semibold">⚠️ The machine couldn't start right now</p>
                <p class="text-sm mt-1">Please contact the outlet — quote order {{ $transaction->order_id }}.</p>
            </div>
        </template>
        <template x-if="status === 'paid'">
            <div class="mb-4 p-4 rounded-md bg-blue-50 border border-blue-300 text-blue-700">
                <p class="font-semibold">Payment received — starting your machine…</p>
                <p class="text-sm mt-1">Order {{ $transaction->order_id }} · RM {{ number_format($transaction->amount, 2) }}</p>
            </div>
        </template>
        <template x-if="status === 'failed'">
            <div class="mb-4 p-4 rounded-md bg-red-50 border border-red-300 text-red-700">
                <p class="font-semibold">Payment failed</p>
                <p class="text-sm mt-1">Order {{ $transaction->order_id }} — no charge was made. Please try again.</p>
            </div>
        </template>
        <template x-if="status === 'initiated'">
            <div class="mb-4 p-4 rounded-md bg-gray-50 border border-gray-300 text-gray-600">
                <p class="font-semibold">Processing your payment…</p>
                <p class="text-sm mt-1">Order {{ $transaction->order_id }}</p>
            </div>
        </template>

        <!-- Machine Info -->
        <p class="mb-2"><strong>Machine:</strong> {{ $transaction->deviceOutlet->machine_name }}</p>
        <p class="mb-2"><strong>Type:</strong> {{ ucfirst($transaction->deviceOutlet->machine_type) }} {{ $transaction->deviceOutlet->machine_num }}</p>
        <p class="mb-4"><strong>Outlet:</strong> {{ $transaction->deviceOutlet->outlet->outlet_name }}</p>

        <template x-if="status === 'failed'">
            <a href="{{ route('device.scan', $transaction->deviceOutlet->device_serial_number) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                Try Again
            </a>
        </template>
    </div>
</x-guest-layout>
