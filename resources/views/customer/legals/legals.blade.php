<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Legal Agreements & Policies
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="mb-6 text-gray-600 text-sm">
                    Below are the current policies and notices you have agreed to as a member of lbpayweb.
                </p>

                <div class="space-y-4">
                    @foreach($terms as $term)
                    <div class="border rounded-lg overflow-hidden" x-data="{ open: false }">
                        <div @click="open = !open" class="flex justify-between items-center p-4 bg-gray-50 cursor-pointer hover:bg-gray-100 transition">
                            <div>
                                <h3 class="font-bold text-gray-800">{{ $term->title }}</h3>
                                <p class="text-xs text-gray-500">
                                    Signed Version: {{ $term->version_signed }} | 
                                    Agreed on: {{ $term->signed_at ? $term->signed_at->format('d M Y') : 'Pending' }}
                                </p>
                            </div>
                            <svg class="w-5 h-5 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>

                        <div x-show="open" x-cloak class="p-4 bg-white border-t text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                            {!! nl2br(e($term->content)) !!}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-customer-layout>