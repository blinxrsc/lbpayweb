<?php

use App\Models\DeviceOutlet;
use App\Models\Device;
use App\Models\Firmware;
use PhpMqtt\Client\Facades\MQTT;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Redis;
use App\Jobs\SendMqttCommand;

new class extends Component {
    use WithPagination;
    // 1. Define listeners properly: Listen for Echo events to refresh the table
    protected $listeners = ["echo:machines,MachineUpdated" => '$refresh'];

    // 2. Define Actions as anonymous functions
    public function resetDevice($serial)
    {
        $lockKey = "reset_lock_{$serial}";

        // Check if a reset was already sent in the last 60 seconds
        if (Redis::get($lockKey)) {
            $this->dispatch('notify', message: "Reset already in progress for $serial", type: 'error');
            return;
        }
        // Set a 60-second lock in Redis
        Redis::setex($lockKey, 60, 'true');
        // Dispatch the Reset Job to Horizon
        SendMqttCommand::dispatch($serial, 'REBOOT',[],auth()->id());
        $this->dispatch('close-modal', "confirm-reset-{$serial}");
        $this->dispatch('notify', message: "Reboot command sent to $serial", type: 'success');
    }

    // Bulk Reset
    public function resetOutlet($outletId)
    {
        // 1. Get all serial numbers for this outlet
        $serials = DeviceOutlet::where('outlet_id', $outletId)
            ->pluck('device_serial_number');

        if ($serials->isEmpty()) {
            $this->dispatch('notify', message: "No machines found.", type: 'error');
            return;
        }

        // 2. The Throttled Batch Loop
        foreach ($serials as $index => $serial) {
            // We multiply the index by 100ms. 
            // Machine 1: 0ms delay
            // Machine 2: 100ms delay
            // Machine 10: 1000ms (1 second) delay
            SendMqttCommand::dispatch($serial, 'REBOOT',[], auth()->id())
                ->delay(now()->addMilliseconds($index * 100));
        }
        $this->dispatch('notify', message: "Reboot sequence for " . $serials->count() . " machines queued.", type: 'success');
    }

    public function triggerUpdate($serial, $firmwareId)
    {
        $firmware = Firmware::find($firmwareId);
        if (!$firmware) {
            $this->dispatch('notify', message: "Firmware not found.", type: 'error');
            return;
        }

        $url = route('firmware.download', $firmware->id);
        // Better: Dispatch as a job instead of direct Publish to ensure reliability
        SendMqttCommand::dispatch($serial, 'UPDATE', ['url'=> $url], auth()->id());
        // Send the MQTT command
        //MQTT::publish("machines/{$serial}/cmd", "UPDATE:{$url}");
        $this->dispatch('close-modal', "confirm-ota-{$serial}");
        $this->dispatch('notify', message: "Firmware update pushed!", type: 'success');
    }

    // Trigger the Remote Start
    public function remoteStart($serial, $type, $price) {
        $lockKey = "start_lock_{$serial}";

        if (Redis::get($lockKey)) {
            $this->dispatch('notify', message: "Machine is busy. Please wait.", type: 'error');
            return;
        }

        // Lock for 30 seconds to prevent double-clicks
        Redis::setex($lockKey, 30, 'true');

        // Hand off the work to Redis/Horizon
        // Dispatch the Job
        SendMqttCommand::dispatch(
            $serial, 
            'REMOTE_START', 
            ['type' => $type, 'price' => $price], // We send the 'type' (e.g., WASHER_HOT) so the ESP32 knows which relay to click
            auth()->id()
        );
        $this->dispatch('close-modal', "confirm-restart-{$serial}");
        $this->dispatch('notify', message: "Remote Start ($type) queued for $serial", type: 'success');
    }

    // 3. Define data for the view
    public function with()
    {
        $machines = DeviceOutlet::with(['device', 'outlet']) // Added 'outlet' eager load
            ->orderBy('last_seen_at', 'desc')
            ->paginate(20);

        // Get Outlet Info from the first machine in the list for the header
        $firstMachine = $machines->first();
        //$this->dispatch('notify', message: "Dashboard refreshed ", type: 'success');
        return [
            'machines' => $machines,
            'allFirmwares' => Firmware::latest()->get(),
            // Provide defaults so the view doesn't crash if no machines exist
            'outletName' => $firstMachine?->outlet?->name ?? 'No Outlet Found',
            'outletId'   => $firstMachine?->outlet_id,
        ];
    }
};
?>
<div wire:poll.10s> {{-- Increased to 10s to save server resources --}}
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <h1><strong>Live Machine Status</strong></h1>
                <div class="flex justify-between items-center bg-gray-100 p-4 rounded-t-lg border">
                    <h2 class="font-bold text-lg">Outlet: {{ $outletName }}</h2>
                    
                    @if($outletId)
                    <button 
                        wire:click="resetOutlet({{ $outletId }})"
                        wire:confirm="Are you sure you want to REBOOT ALL machines in this outlet?"
                        class="bg-orange-500 hover:bg-orange-600 text-white text-xs px-4 py-2 rounded-full shadow-sm flex items-center gap-2"
                    >
                        🔄 Reset All Machines
                    </button>
                    @endif
                </div>

                <table class="min-w-full border text-sm">
                    <thead>
                        <tr>
                            <th>Serial Number</th>
                            <th>Outlet</th>
                            <th>Machine</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Last Reboot</th>
                            <th>Current Coins</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($machines as $machine)
                        <tr class="border-b items-center" wire:key="machine-row-{{ $machine->device_serial_number }}">
                            <td class="p-2 font-mono text-xs">{{ $machine->device_serial_number }}</td>
                            <td class="p-2">{{ $machine->outlet->outlet_name }}</td>
                            <td class="p-2">{{ $machine->machine_type }} #{{ $machine->machine_num }}</td>
                            <td class="p-2">
                                @if($machine->is_online)
                                    <span class="text-green-600 font-bold">● Online </span>
                                @else
                                    <span class="text-red-500">○ Offline</span>
                                @endif
                            </td>
                            <td class="p-2 text-xs text-gray-500">{{ \Carbon\Carbon::parse($machine->last_seen_at)->diffForHumans() }}</td>
                            <td class="p-2 text-xs text-gray-500">{{ \Carbon\Carbon::parse($machine->last_reboot_at)->diffForHumans() }}</td>
                            <td class="p-2">{{ $machine->current_coins }}</td>
                            <td class="p-2 flex justify-end gap-1">
                                

                                {{-- JSON Payload Tooltip --}}
                                <div class="relative group mt-1 mr-2">
                                    <span class="cursor-help text-blue-500 text-[10px] font-bold bg-blue-50 px-1 rounded">JSON</span>
                                    <div class="absolute z-50 hidden group-hover:block bg-black text-white text-[10px] p-2 rounded shadow-xl min-w-[200px] right-0 bottom-6">
                                        <pre class="whitespace-pre-wrap font-mono">{{ $machine->last_payload }}</pre>
                                    </div>
                                </div>
                                @can('devices.ota')
                                {{-- OTA Button --}}
                                <button x-on:click="$wire.dispatch('open-modal', 'confirm-ota-{{ $machine->device_serial_number }}')" Title="Firmware Update" class="text-yellow-600"><x-heroicon-s-cloud-arrow-up class="w-5 h-5"/></button>
                                @endcan
                                {{-- Reset Button --}}
                                <button wire:click="dispatch('open-modal', 'confirm-reset-{{ $machine->device_serial_number }}')" Title="Reboot" class="text-gray-600"><x-heroicon-s-power class="w-5 h-5"/></button>
                                <!-- Reset Modal -->
                                <x-modal name="confirm-reset-{{ $machine->device_serial_number }}" maxWidth="2xl">
                                    <div x-data class="p-6">
                                        <h2 class="text-lg font-medium text-gray-900">Reboot device {{ $machine->device_serial_number }}?</h2>
                                        <div class="mt-6 flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-reset-{{ $machine->device_serial_number }}')">Cancel</x-secondary-button>
                                            <x-danger-button 
                                                x-on:click="$dispatch('close-modal', 'confirm-reset-{{ $machine->device_serial_number }}'); triggerReboot('{{ $machine->device_serial_number }}')">
                                                Confirm Reboot
                                            </x-danger-button>
                                        </div>
                                    </div>
                                </x-modal>
                                <script>
                                    function triggerReboot(serialNumber) {
                                        // Option 1: If calling from within an Alpine component
                                        //Alpine.evaluate(document.getElementById('confirm-reset-{{ $machine->device_serial_number }}'), '$wire.call("resetDevice", "' + serialNumber + '")');

                                        // Option 2: Dispatch close event (if needed)
                                        //Livewire.dispatch('close-modal', 'confirm-reset-' + serialNumber);
                                        
                                        // Option 3: Direct call (if script is inside the Livewire component view)
                                        @this.call('resetDevice', serialNumber);
                                    }
                                </script>
                                <!-- REMOTE RESTART MODAL -->
                                <button x-on:click="$wire.dispatch('open-modal', 'confirm-restart-{{ $machine->device_serial_number }}')" Title="Remote Start" class="text-indigo-600"><x-heroicon-s-play-circle class="w-5 h-5"/></button>
                                <!-- Restart Modal -->
                                <x-modal name="confirm-restart-{{ $machine->device_serial_number }}" maxWidth="2xl">
                                    <div class="p-6" 
                                        x-data="remoteStart('{{ $machine->price ?? 1.00 }}', '{{ $machine->machine_type }}')">

                                        <h2 class="text-lg font-medium text-gray-900">
                                            Remote Start: {{ $machine->machine_type }} #{{ $machine->machine_num }}
                                        </h2>

                                        <!-- Debug -->
                                        <p>Package: <span x-text="selectedPackage"></span></p>
                                        <p>Price: <span x-text="value"></span></p>

                                        <!-- Price controls -->
                                        <div class="flex items-center space-x-2 mb-4">
                                            <button type="button" @click="decrease" class="px-3 py-1 bg-gray-200 rounded font-bold hover:bg-gray-300">−</button>
                                            <div class="relative">
                                                <span class="absolute left-2 top-1 text-gray-500">RM</span>
                                                <input type="text" x-model="value" class="w-24 text-center border rounded pl-6" readonly>
                                            </div>
                                            <button type="button" @click="increase" class="px-3 py-1 bg-gray-200 rounded font-bold hover:bg-gray-300">+</button>
                                        </div>

                                        <!-- Package Options -->
                                        <div class="space-y-2 mb-4">
                                            @if($machine->machine_type === 'Washer')
                                                <button type="button" @click="setPackage('{{ $machine->device->washer_warm_price }}', 'Warm')" 
                                                    :class="selectedPackage === 'Warm' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    Normal/Warm (RM {{ number_format($machine->device->washer_warm_price, 2) }})
                                                </button>
                                                <button type="button" @click="setPackage('{{ $machine->device->washer_cold_price }}', 'Cold')" 
                                                    :class="selectedPackage === 'Cold' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    Cold (RM {{ number_format($machine->device->washer_cold_price, 2) }})
                                                </button>
                                                <button type="button" @click="setPackage('{{ $machine->device->washer_hot_price }}', 'Hot')" 
                                                    :class="selectedPackage === 'Hot' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    Hot (RM {{ number_format($machine->device->washer_hot_price, 2) }})
                                                </button>
                                            @elseif($machine->machine_type === 'Dryer')
                                                <button type="button" @click="setPackage('{{ $machine->device->dryer_low_price }}', 'Low')" 
                                                    :class="selectedPackage === 'Low' ? 'border-orange-500 bg-orange-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    Low (RM {{ number_format($machine->device->dryer_low_price, 2) }})
                                                </button>
                                                <button type="button" @click="setPackage('{{ $machine->device->dryer_med_price }}', 'Medium')" 
                                                    :class="selectedPackage === 'Medium' ? 'border-orange-500 bg-orange-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    Medium (RM {{ number_format($machine->device->dryer_med_price, 2) }})
                                                </button>
                                                <button type="button" @click="setPackage('{{ $machine->device->dryer_hi_price }}', 'High')" 
                                                    :class="selectedPackage === 'High' ? 'border-orange-500 bg-orange-50' : 'border-gray-300'"
                                                    class="w-full px-3 py-2 border rounded text-left">
                                                    High (RM {{ number_format($machine->device->dryer_hi_price, 2) }})
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Action buttons -->
                                        <div class="mt-6 flex justify-end gap-3">
                                            <x-secondary-button 
                                                x-on:click="$dispatch('close-modal', 'confirm-restart-{{ $machine->device_serial_number }}')">
                                                Cancel
                                            </x-secondary-button>

                                            <x-danger-button type="button"
                                                @click="$dispatch('close-modal', 'confirm-restart-{{ $machine->device_serial_number }}'); 
                                                        triggerRestart('{{ $machine->device_serial_number }}', selectedPackage, value)">
                                                Confirm Restart
                                            </x-danger-button>
                                        </div>
                                    </div>
                                </x-modal>
                                <script>
                                    document.addEventListener('alpine:init', () => {
                                        Alpine.data('remoteStart', (initialPrice, initialPackage) => ({
                                            selectedPackage: initialPackage,
                                            value: parseFloat(initialPrice).toFixed(2),

                                            setPackage(price, name) {
                                                this.value = parseFloat(price).toFixed(2);
                                                this.selectedPackage = name;
                                            },
                                            increase() {
                                                this.value = (parseFloat(this.value) + 1).toFixed(2);
                                            },
                                            decrease() {
                                                if (this.value > 1.00) {
                                                    this.value = (parseFloat(this.value) - 1).toFixed(2);
                                                }
                                            }
                                        }));
                                    });

                                    function triggerRestart(serialNumber, type, price) {
                                        @this.call('remoteStart', serialNumber, type, price);
                                    }
                                </script>
                            </td>
                        </tr>
                        {{-- MODALS NESTED INSIDE FOREACH TO ACCESS $machine --}}
                        <!-- OTA Modal -->
                        <x-modal name="confirm-ota-{{ $machine->device_serial_number }}" maxWidth="2xl">
                            <div class="p-6" x-data="{ selectedFw: '{{ $allFirmwares->first()?->id }}' }">
                                <h2 class="text-lg font-medium text-gray-900">Push new firmware to {{ $machine->device_serial_number }}</h2>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700">Select Firmware Version</label>
                                    <select x-model="selectedFw" class="text-xs border rounded">
                                        @foreach($allFirmwares as $fw)
                                            <option value="{{ $fw->id }}">v{{ $fw->version }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-ota-{{ $machine->device_serial_number }}')" wire:loading.attr="disabled">
                                        Cancel
                                    </x-secondary-button>
                                    {{-- Pass the Alpine variable to the Livewire function --}}
                                    <button 
                                        type="button"
                                        wire:click="triggerUpdate('{{ $machine->device_serial_number }}', selectedFw)"
                                        {{-- Disable button and show spinner while processing --}}
                                        wire:loading.attr="disabled"
                                        wire:target="triggerUpdate('{{ $machine->device_serial_number }}', selectedFw)"
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none disabled:opacity-50 transition">
                                        {{-- This text hides when loading --}}
                                        <span wire:loading.remove wire:target="triggerUpdate('{{ $machine->device_serial_number }}', selectedFw)">
                                            Update Firmware
                                        </span>

                                        {{-- This spinner shows only when loading --}}
                                        <span wire:loading wire:target="triggerUpdate('{{ $machine->device_serial_number }}', selectedFw)">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Processing...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </x-modal>

                        
                        
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $machines->links() }}
            </div>
        </div>
    </div>
    {{-- Toast Notification --}}
    <div 
    x-data="{ 
        show: false, 
        message: '', 
        type: 'success',
        showToast(event) {
            this.message = event.detail.message;
            this.type = event.detail.type || 'success';
            this.show = true;
            setTimeout(() => { this.show = false }, 3000);
        }
    }"
    x-on:notify.window="showToast($event)"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed bottom-5 right-5 z-[100] max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden"
    style="display: none;"
>
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                {{-- Success Icon --}}
                <template x-if="type === 'success'">
                    <x-heroicon-s-check-circle class="h-6 w-6 text-green-400" />
                </template>
                {{-- Error Icon --}}
                <template x-if="type === 'error'">
                    <x-heroicon-s-x-circle class="h-6 w-6 text-red-400" />
                </template>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p x-text="message" class="text-sm font-medium text-gray-900"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="show = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                    <x-heroicon-s-x-mark class="h-5 w-5" />
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@livewireScripts
<script>
Livewire.on('notify', ({ message, type }) => {
    // show toast here
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
});
</script>
