<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Permission') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('permissions.store') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Permission')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" required autofocus />
                        </div>
                       
                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('permissions.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                            <x-primary-button class="ml-4">
                                {{ __('Create') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>