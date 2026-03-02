@props(['active', 'icon' => null])

@php
$classes = ($active ?? false)
            ? 'flex items-center px-3 py-2 text-sm font-semibold text-blue-700 bg-blue-50 rounded-md border-l-4 border-blue-600 transition duration-150 ease-in-out'
            : 'flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md border-l-4 border-transparent transition duration-150 ease-in-out';

$iconClasses = ($active ?? false)
            ? 'w-5 h-5 mr-3 text-blue-600'
            : 'w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-dynamic-component :component="$icon" :class="$iconClasses" />
    @endif
    
    <span>{{ $slot }}</span>
</a>