@props(['status'])

@php
    // Since you passed $tx->status->name, $status is now just a string like "active"
    $name = $status ?? 'unknown';

    $colors = [
        'online' => 'bg-green-600 text-green-800',
        'offline' => 'bg-red-100 text-red-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'suspended' => 'bg-gray-300 text-gray-800',
        'banned' => 'bg-black text-white',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'closed' => 'bg-red-100 text-red-700',
        'own' => 'bg-rose-600 text-white',
        'franchise' => 'bg-amber-600 text-white',
        'joint' => 'bg-indigo-600 text-white',
        'alacart' => 'bg-blue-100 text-blue-800',
        'assigned' => 'bg-green-600 text-green-800',
        'unassigned' => 'bg-yellow-100 text-yellow-800',
        'Washer'    =>  'bg-blue-600 text-white',
        'Dryer' => 'bg-amber-600 text-white',
        'Combo' => 'bg-indigo-600 text-white',
    ];

    $colorClass = $colors[$name] ?? 'bg-gray-500 text-white';
@endphp

<span class="px-2 py-1 text-xs font-semibold rounded {{ $colorClass }}">
    {{ ucfirst($name) }}
</span>
