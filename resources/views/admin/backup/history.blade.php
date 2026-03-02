<!-- resources/views/admin/backup-history.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Backup History') }}
        </h2>
    </x-slot>

<div class="container">
    <table class="w-full text-sm border">
        <thead>
            <tr>
                <th class="border px-3 py-2">Date/Time</th>
                <th class="border px-3 py-2">Action</th>
                <th class="border px-3 py-2">File</th>
                <th class="border px-3 py-2">Format</th>
                <th class="border px-3 py-2">User</th>
                <th class="border px-3 py-2">Deleted?</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="border px-3 py-2">{{ $entry['datetime'] }}</td>
                    <td class="border px-3 py-2">{{ ucfirst($entry['action']) }}</td>
                    <td class="border px-3 py-2">{{ $entry['file'] ?? '-' }}</td>
                    <td class="border px-3 py-2">{{ $entry['format'] ?? '-' }}</td>
                    <td class="border px-3 py-2">{{ $entry['created_by'] ?? '-' }}</td>
                    <td class="border px-3 py-2">
                        @if(!empty($entry['deleted_after_download']) && $entry['deleted_after_download'])
                            ✅
                        @else
                            ❌
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-3">No backup activity recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-app-layout>