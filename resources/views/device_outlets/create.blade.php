<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Assign Device to Outlet') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8">

                    @if($devices->isEmpty())
                        <div class="mb-6 rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <strong class="font-semibold">No unassigned devices found.</strong>
                            Every device is already assigned to an outlet — add a new device first.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('device_outlets.store') }}" class="space-y-8">
                        @csrf

                        {{-- Assignment --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Assignment</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="outlet_id" :value="__('Outlet')" />
                                    <select name="outlet_id" id="outlet_id"
                                        class="block mt-1 w-full rounded-md shadow-sm border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100"
                                        required>
                                        <option value="">-- Select Outlet --</option>
                                        @foreach($outlets as $outlet)
                                            <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>{{ $outlet->outlet_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('outlet_id')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <x-input-label for="device_serial_number" :value="__('Device')" />
                                    <select name="device_serial_number" id="device_serial_number"
                                        class="block mt-1 w-full rounded-md shadow-sm border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100"
                                        required {{ $devices->isEmpty() ? 'disabled' : '' }}>
                                        <option value="">-- Select Device --</option>
                                        @foreach($devices as $device)
                                            <option value="{{ $device->serial_number }}" {{ old('device_serial_number') == $device->serial_number ? 'selected' : '' }}>
                                                {{ $device->serial_number }} ({{ $device->model }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('device_serial_number')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        {{-- Machine Details --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Machine Details</h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Machine Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="machine_num" value="{{ old('machine_num') }}"
                                        placeholder="e.g. 1"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100" required>
                                    @error('machine_num')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Machine Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="machine_name" value="{{ old('machine_name') }}"
                                        placeholder="e.g. 14kg"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100" required>
                                    @error('machine_name')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <div>
                                <x-input-label for="machine_type" :value="__('Machine Type')" />
                                <select name="machine_type" id="machine_type"
                                    class="block mt-1 w-full sm:w-1/2 rounded-md shadow-sm border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100"
                                    required>
                                    <option value="">-- Select Machine Type --</option>
                                    @foreach(['Washer','Dryer','Combo','Token Changer','Vending'] as $type)
                                        <option {{ old('machine_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('machine_type')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="border-t border-gray-100"></div>

                        {{-- Initial Status --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-4">Initial Status</h3>
                            <p class="text-xs text-gray-400 mb-4">
                                This is just the starting value — once the device connects, its real status is kept in
                                sync automatically from its own heartbeat.
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    {{-- Values must be lowercase — the same convention used everywhere else
                                         this column is read/written (ProcessMachineMessage, dashboard counts). --}}
                                    <select name="status" id="status"
                                        class="block mt-1 w-full rounded-md shadow-sm border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                        <option value="online" {{ old('status') == 'online' ? 'selected' : '' }}>Online</option>
                                        <option value="offline" {{ old('status', 'offline') == 'offline' ? 'selected' : '' }}>Offline</option>
                                    </select>
                                    @error('status')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                </div>
                                <div class="flex items-center">
                                    <label class="inline-flex items-center mt-6">
                                        <input type="hidden" name="availability" value="0">
                                        <input type="checkbox" name="availability" value="1"
                                            {{ old('availability', '1') ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-200">
                                        <span class="ml-2 text-sm text-gray-700">Available for use</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('device_outlets.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                                Cancel
                            </a>
                            <x-primary-button :disabled="$devices->isEmpty()">
                                Assign Device
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
