@props(['links' => []])

<nav class="flex text-sm text-gray-500" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        @foreach($links as $index => $link)
            <li class="inline-flex items-center">
                @if(!$loop->last)
                    <a href="{{ $link['url'] }}" 
                       class="inline-flex items-center text-gray-500 hover:text-blue-600">
                        @if(isset($link['icon']))
                            <x-dynamic-component :component="$link['icon']" class="w-4 h-4 mr-1" />
                        @endif
                        {{ $link['label'] }}
                    </a>
                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400 mx-1" />
                @else
                    <span class="ml-1 text-gray-700">{{ $link['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>