<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Device Parameters') }} — {{ $deviceOutlet->device_serial_number }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="{ tab: 'parameters' }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <p class="text-sm text-gray-500 mb-4">
                    {{ optional($deviceOutlet->outlet)->outlet_name }} —
                    {{ ucfirst($deviceOutlet->machine_type) }} #{{ $deviceOutlet->machine_num }}
                    ({{ $deviceOutlet->machine_name }})
                </p>

                @if(session('success'))
                    <div class="mb-4 rounded-md border border-green-300 bg-green-50 p-3 text-green-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Tab Navigation --}}
                <div class="border-b mb-4 flex space-x-4">
                    <button type="button"
                        class="px-4 py-2 font-semibold"
                        :class="tab === 'parameters' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                        @click="tab = 'parameters'">
                        Device Parameters
                    </button>
                    <button type="button"
                        class="px-4 py-2 font-semibold"
                        :class="tab === 'audit' ? 'border-b-2 border-indigo-500 text-indigo-600' : 'text-gray-500'"
                        @click="tab = 'audit'">
                        Audit Trail
                    </button>
                </div>

                {{-- Device Parameters Tab --}}
                <div x-show="tab === 'parameters'">
                    <form method="POST" action="{{ route('device_outlets.parameters.update', $deviceOutlet) }}">
                        @csrf
                        @method('PUT')

                        <h3 class="text-md font-semibold text-gray-700 mb-2">Cycle Prices</h3>
                        @foreach([
                            'washer_cold_price','washer_warm_price','washer_hot_price',
                            'dryer_low_price','dryer_med_price','dryer_hi_price',
                        ] as $field)
                            <div class="mb-4">
                                <label>{{ ucwords(str_replace('_',' ', $field)) }}</label>
                                <input type="text" name="{{ $field }}" value="{{ old($field, $device->$field) }}" class="form-input w-full" required>
                                @error($field)<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                        @endforeach

                        <h3 class="text-md font-semibold text-gray-700 mb-2 mt-6">Pulse &amp; Vend Settings</h3>

                        <div class="mb-4">
                            <label>Pulse Price (RM per pulse)</label>
                            <input type="text" name="pulse_price" value="{{ old('pulse_price', $device->pulse_price) }}" class="form-input w-full" required>
                            @error('pulse_price')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Pulse → Added Minutes</label>
                            <input type="text" name="pulse_add_min" value="{{ old('pulse_add_min', $device->pulse_add_min) }}" class="form-input w-full" required>
                            @error('pulse_add_min')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Pulse Width (ms)</label>
                            <input type="text" name="pulse_width" value="{{ old('pulse_width', $device->pulse_width) }}" class="form-input w-full" required>
                            @error('pulse_width')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Pulse Delay (ms)</label>
                            <input type="text" name="pulse_delay" value="{{ old('pulse_delay', $device->pulse_delay) }}" class="form-input w-full" required>
                            @error('pulse_delay')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Max Vend Price (RM)</label>
                            <input type="text" name="max_vend_price" value="{{ old('max_vend_price', $device->max_vend_price) }}" class="form-input w-full">
                            <p class="text-xs text-gray-400 mt-1">
                                Upper limit enforced by the backend when starting this machine (technician app, dashboard remote start).
                                Not sent to the device — it's a pricing/authorization cap, not a hardware setting.
                            </p>
                            @error('max_vend_price')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        <div class="mb-4">
                            <label>Pulse Pull Down or Up</label>
                            {{-- Hidden 0 + checkbox 1 with the same name: unchecked submits 0, checked submits 1 --}}
                            <input type="hidden" name="pulse_pull_up" value="0">
                            <label class="inline-flex items-center mt-1">
                                <input type="checkbox" name="pulse_pull_up" value="1"
                                    {{ old('pulse_pull_up', $device->pulse_pull_up) ? 'checked' : '' }}
                                    class="form-control mr-2">
                                Pull Up (unchecked = Pull Down)
                            </label>
                            @error('pulse_pull_up')<span class="text-red-500 text-sm block">{{ $message }}</span>@enderror
                        </div>

                        <h3 class="text-md font-semibold text-gray-700 mb-2 mt-6">Coin Signal Settings</h3>

                        <div class="mb-4">
                            <label>Coin Signal Width (ms)</label>
                            <input type="text" name="coin_signal_width" value="{{ old('coin_signal_width', $device->coin_signal_width) }}" class="form-input w-full" required>
                            @error('coin_signal_width')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Coin Signal Idle Level</label>
                            <input type="hidden" name="coin_signal_idle_high" value="0">
                            <label class="inline-flex items-center mt-1">
                                <input type="checkbox" name="coin_signal_idle_high" value="1"
                                    {{ old('coin_signal_idle_high', $device->coin_signal_idle_high) ? 'checked' : '' }}
                                    class="form-control mr-2">
                                Idle High (unchecked = Idle Low)
                            </label>
                            @error('coin_signal_idle_high')<span class="text-red-500 text-sm block">{{ $message }}</span>@enderror
                        </div>
                        <div class="mb-4">
                            <label>Coin Signal Sensitive (debounce, ms)</label>
                            <input type="text" name="coin_signal_sensitivity" value="{{ old('coin_signal_sensitivity', $device->coin_signal_sensitivity) }}" class="form-input w-full" required>
                            <p class="text-xs text-gray-400 mt-1">Lower = more sensitive (accepts faster pulses); higher = more debounce/noise rejection.</p>
                            @error('coin_signal_sensitivity')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-3">
                            <x-primary-button type="submit">
                                Save &amp; Send to Device
                            </x-primary-button>
                        </div>
                    </form>

                    {{-- Separate action: re-push whatever is currently saved, without editing anything --}}
                    <form method="POST" action="{{ route('device_outlets.sendConfig', $deviceOutlet) }}" class="mt-2 flex justify-end">
                        @csrf
                        <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                            Resend Current Settings to Device
                        </button>
                    </form>
                </div>

                {{-- Audit Trail Tab --}}
                <div x-show="tab === 'audit'">
                    <h3 class="text-lg font-semibold mb-4">Audit Trail</h3>
                    <table class="table-auto w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1">Field</th>
                                <th class="px-2 py-1">Old Value</th>
                                <th class="px-2 py-1">New Value</th>
                                <th class="px-2 py-1">Changed By</th>
                                <th class="px-2 py-1">Changed At</th>
                                <th class="px-2 py-1">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr>
                                    <td class="border px-2 py-1">{{ $log->field }}</td>
                                    <td class="border px-2 py-1">{{ $log->old_value }}</td>
                                    <td class="border px-2 py-1">{{ $log->new_value }}</td>
                                    <td class="border px-2 py-1">{{ optional($log->user)->name }}</td>
                                    <td class="border px-2 py-1">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="border px-2 py-1">
                                        <form method="POST" action="{{ route('devices.rollback', $log) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-danger">Rollback</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="border px-2 py-1 text-gray-400" colspan="6">No changes recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
