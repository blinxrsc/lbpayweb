<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Firmware;

new class extends Component
{
    use WithFileUploads;

    public $version;
    public $firmwareFile;
    public $notes;

    public function save() {
        $this->validate([
            'version' => 'required|unique:firmware,version',
            'firmwareFile' => 'required|max:2048', // Max 2MB for ESP32
        ]);

        $path = $this->firmwareFile->store('firmware'); // Storing in public disk

        Firmware::create([
            'version' => $this->version,
            'file_path' => $path,
            'notes' => $this->notes,
        ]);
        // Reset the form fields
        $this->reset(['version', 'firmwareFile', 'notes']);

        session()->flash('message', 'Firmware v' . $this->version . ' uploaded successfully.');
    }
};
?>

<div class="p-6 bg-white rounded shadow" >
    <form wire:submit.prevent="save" enctype="multipart/form-data">
        <input type="text" wire:model="version" placeholder="Version (e.g. 1.0.2)" class="border p-2 w-full mb-4">
        @error('version') <span class="text-red-500 block text-sm">{{ $message }}</span> @enderror
        <input type="file" wire:model="firmwareFile" class="mb-4">
        @error('firmwareFile') <span class="text-red-500 block text-sm">{{ $message }}</span> @enderror
        <div wire:loading wire:target="firmwareFile">Uploading...</div>
        
        <textarea wire:model="notes" placeholder="Release notes" class="border p-2 w-full mb-4"></textarea>
        
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
            Upload & Save Firmware
        </button>
    </form>
</div>