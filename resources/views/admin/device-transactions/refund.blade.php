<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Process Refund') }} — {{ $transaction->order_id }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 sm:p-8">

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

                <!-- Transaction summary -->
                <div class="border-b pb-4 mb-6 text-sm space-y-1">
                    <p><strong>Order:</strong> {{ $transaction->order_id }}</p>
                    <p><strong>Amount:</strong> RM {{ number_format($transaction->amount, 2) }}</p>
                    <p><strong>Customer:</strong>
                        @if($transaction->customer)
                            {{ $transaction->customer->name }} ({{ $transaction->customer->email }})
                        @else
                            {{ $transaction->meta['guest_name'] ?? 'Guest' }}
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-500">GUEST — no wallet to refund to</span>
                        @endif
                    </p>
                    <p><strong>Machine:</strong> {{ $transaction->deviceOutlet->outlet->outlet_name ?? '—' }} — {{ $transaction->deviceOutlet->machine_type ?? '' }} #{{ $transaction->deviceOutlet->machine_num ?? '' }}</p>
                    <p><strong>Paid:</strong> {{ $transaction->updated_at->format('Y-m-d H:i') }}</p>
                </div>

                <form method="POST" action="{{ route('admin.device-transactions.refund.store', $transaction) }}"
                      enctype="multipart/form-data" class="space-y-6"
                      x-data="{ method: '{{ old('method', 'wallet') }}' }">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer's Proof of Payment <span class="text-red-500">*</span></label>
                        <input type="file" name="screenshot" accept="image/*" required
                            class="block w-full text-sm text-gray-600 border border-gray-200 rounded-lg cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">The screenshot/receipt the customer sent — kept as evidence for this refund.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Method <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            <label class="flex items-start gap-2 p-3 border rounded-md cursor-pointer" :class="method === 'wallet' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input type="radio" name="method" value="wallet" x-model="method" class="mt-1"
                                    {{ $transaction->customer_id ? '' : 'disabled' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Refund to Member Wallet</span>
                                    <span class="block text-xs text-gray-500">
                                        @if($transaction->customer_id)
                                            Credits RM {{ number_format($transaction->amount, 2) }} to {{ $transaction->customer->name }}'s balance immediately.
                                        @else
                                            Not available — this was a guest checkout with no account to credit.
                                        @endif
                                    </span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 p-3 border rounded-md cursor-pointer" :class="method === 'remote_start' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input type="radio" name="method" value="remote_start" x-model="method" class="mt-1">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Compensate via Remote Start</span>
                                    <span class="block text-xs text-gray-500">Sends a free credit pulse (same value) to a machine of your choice. Logged in the Remote Start log under your name.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 p-3 border rounded-md cursor-pointer" :class="method === 'external' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input type="radio" name="method" value="external" x-model="method" class="mt-1">
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">External (cash, bank transfer, etc.)</span>
                                    <span class="block text-xs text-gray-500">No money movement in the system — recorded for audit only.</span>
                                </span>
                            </label>
                        </div>
                        @error('method')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                    </div>

                    <div x-show="method === 'remote_start'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compensation Machine <span class="text-red-500">*</span></label>
                        <select name="compensation_device_outlet_id" class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">-- Select Outlet, then Machine --</option>
                            @foreach($outlets as $outlet)
                                @foreach($outlet->deviceOutlets as $deviceOutlet)
                                    <option value="{{ $deviceOutlet->id }}" {{ old('compensation_device_outlet_id') == $deviceOutlet->id ? 'selected' : '' }}>
                                        {{ $outlet->outlet_name }} — {{ $deviceOutlet->machine_type }} #{{ $deviceOutlet->machine_num }}
                                        ({{ $deviceOutlet->is_online ? 'Online' : 'Offline' }})
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('compensation_device_outlet_id')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason / Notes <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="3" required
                            placeholder="e.g. Customer reported no coin drop on Washer #2, confirmed via screenshot."
                            class="w-full rounded-md border-gray-300 text-sm">{{ old('reason') }}</textarea>
                        @error('reason')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('admin.device-transactions.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                            Cancel
                        </a>
                        <x-primary-button>Confirm Refund</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
