<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Manager') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('managers.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                               class="form-input mt-1 block w-full" required>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="form-input mt-1 block w-full">
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                               class="form-input mt-1 block w-full">
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="ssm" class="block text-sm font-medium text-gray-700">SSM</label>
                        <input id="ssm" type="text" name="ssm" value="{{ old('ssm') }}"
                               class="form-input mt-1 block w-full">
                        @error('ssm')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('managers.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                        <x-primary-button class="ml-4">
                            Save Manager
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>