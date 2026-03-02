<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Update Brand') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('brands.update', $brand) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Brand Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $brand->name) }}"
                               class="form-input mt-1 block w-full" required>
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block font-medium mb-2">Current Logo</label>
                        @if($brand->logo)
                            <img src="{{ asset('storage/' . $brand->logo) }}" class="w-20 h-20 object-contain mb-2 border p-1">
                        @else
                            <p class="text-sm text-gray-500">No logo uploaded.</p>
                        @endif
                        
                        <input type="file" name="logo" class="border rounded p-2 w-full">
                        @error('logo')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('brands.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                        <x-primary-button class="ml-4">
                            Update
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>