<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Site Branding Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    {{-- Logo Upload Form --}}
                    <form action="{{ route('admin.logo.update') }}" method="POST" enctype="multipart/form-data" class="mb-8">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block font-medium mb-2">Upload Logo</label>
                            <input type="file" name="logo" class="border rounded p-2 w-full">
                            @error('logo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($logo && $logo->value)
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Current Logo:</p>
                                <img src="{{ asset('storage/'.$logo->value) }}" class="h-16 mt-2">
                            </div>
                        @endif

                        <x-primary-button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Save Logo
                        </x-primary-button>
                    </form>
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                {{-- Favicon Upload Form --}}
                <form action="{{ route('admin.favicon.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block font-medium mb-2">Upload Favicon</label>
                        <input type="file" name="favicon" class="border rounded p-2 w-full">
                        @error('favicon')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($favicon = \App\Models\Setting::where('key','site_favicon')->first())
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">Current Favicon:</p>
                            <img src="{{ asset('storage/'.$favicon->value) }}" class="h-8 w-8 mt-2">
                        </div>
                    @endif

                    <x-primary-button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Save Favicon
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>