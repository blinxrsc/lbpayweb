@props(['status'])

@php
    // Since you passed $tx->status->name, $status is now just a string like "active"
    $name = $status ?? 'unknown';

    $colors = [
        // Light background + dark text of the same hue everywhere, for
        // consistent contrast (a couple of these were previously
        // dark-bg + dark-text, e.g. bg-green-600/text-green-800, which was
        // very hard to read).
        'online' => 'bg-green-100 text-green-800',
        'offline' => 'bg-red-100 text-red-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'suspended' => 'bg-gray-200 text-gray-800',
        'banned' => 'bg-gray-900 text-white',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'closed' => 'bg-red-100 text-red-700',
        'own' => 'bg-rose-100 text-rose-800',
        'franchise' => 'bg-amber-100 text-amber-800',
        'joint' => 'bg-indigo-100 text-indigo-800',
        'alacart' => 'bg-blue-100 text-blue-800',
        'assigned' => 'bg-green-100 text-green-800',
        'unassigned' => 'bg-yellow-100 text-yellow-800',
        'faulty' => 'bg-red-100 text-red-700',
        'repair' => 'bg-blue-100 text-blue-700',
        'Available' => 'bg-green-100 text-green-800',
        'Busy' => 'bg-amber-100 text-amber-800',
        'Disabled' => 'bg-red-100 text-red-700',
        'Washer'    =>  'bg-blue-100 text-blue-800',
        'Dryer' => 'bg-amber-100 text-amber-800',
        'Combo' => 'bg-indigo-100 text-indigo-800',
        'OK' => 'bg-green-100 text-green-800',
        'Fail' => 'bg-red-100 text-red-700',
    ];

    $colorClass = $colors[$name] ?? 'bg-gray-100 text-gray-800';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $colorClass }}">
    {{ ucfirst($name) }}
</span>
