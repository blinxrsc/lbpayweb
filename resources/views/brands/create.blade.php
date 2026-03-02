<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Brand') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('brands.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Brand Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}"
                               class="form-input mt-1 block w-full" required>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block font-medium mb-2">Upload Logo</label>
                        <input type="file" name="logo" class="border rounded p-2 w-full">
                        @error('logo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('brands.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                        <x-primary-button class="ml-4">
                            Save
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
