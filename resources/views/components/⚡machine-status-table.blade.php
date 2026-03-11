<?php

use Livewire\Component;
use App\Models\DeviceOutlet;

class MachineStatusTable extends Component
{
    public function render()
    {
        return view('livewire.machine-status-table', [
            'machines' => DeviceOutlet::orderBy('last_seen_at', 'desc')->paginate(20)
        ]);
    }
};
?>

<div>
    {{-- Let all your things have their places; let each part of your business have its time. - Benjamin Franklin --}}
</div>
<div wire:poll.5s> <table class="min-w-full border">
        <thead>
            <tr>
                <th>Serial Number</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th>Current Coins</th>
            </tr>
        </thead>
        <tbody>
            @foreach($machines as $machine)
            <tr>
                <td>{{ $machine->device_serial_number }}</td>
                <td>
                    @if($machine->status === 'online')
                        <span style="color: green;">● Online</span>
                    @else
                        <span style="color: red;">○ Offline</span>
                    @endif
                </td>
                <td>{{ $machine->last_seen_at?->diffForHumans() }}</td>
                <td>{{ $machine->current_coins }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $machines->links() }}
</div>