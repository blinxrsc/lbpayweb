<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Device To Outlet') }}
        </h2>
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('device_outlets.store') }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="outlet_id" :value="__('Outlet')" />
                        <select name="outlet_id" id="outlet_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Select Outlet --</option>    
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->outlet_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label>Machine Number</label>
                        <input type="text" name="machine_num" value="{{ old('machine_num') }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label>Machine Name</label>
                        <input type="text" name="machine_name" value="{{ old('machine_name') }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <x-input-label for="machine_type" :value="__('Machine Type')" />
                        <select name="machine_type" id="machine_type" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Select Machine Type --</option>
                            <option>Washer</option><option>Dryer</option><option>Combo</option>
                            <option>Token Changer</option><option>Vending</option>  
                        </select>
                    </div>
                    <div class="mb-4">
                        <x-input-label for="device_serial_number" :value="__('Device Serial Number')" />
                        {{-- Place the check here --}}
                        @if($devices->isEmpty())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative mb-2 text-sm" role="alert">
                                <strong class="font-bold">Notice:</strong>
                                <span class="block sm:inline">No unassigned devices found.</span>
                            </div>
                        @endif
                        <select name="device_serial_number" id="device_serial_number" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 ...">
                            <option value="">-- Select Device --</option>    
                            @foreach($devices as $device)
                                <option value="{{ $device->serial_number }}" {{ old('device_serial_number') == $device->serial_number ? 'selected' : '' }}>
                                    {{ $device->serial_number }} ({{ $device->model }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <x-input-label for="status" :value="__('Status')" />
                        <select name="status" id="machine_type" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Select Status --</option>
                            <option value="Online">Online</option>
                            <option value="Offline" selected>Offline</option> 
                        </select>
                    </div>
                    <div class="mb-4">
                        <label>Availability</label>
                        <input type="checkbox" name="availability" value="1" checked> Available
                    </div>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="ml-4">
                            Assign Device
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
