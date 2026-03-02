<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Merchant Logo') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update logo to be as include in the receipt.") }}
        </p>
    </header>

    <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
        <div class="max-w-xl">
            {{-- Logo Upload Form --}}
            <form action="{{ route('admin.merchant.update') }}" method="POST" enctype="multipart/form-data" class="mb-8">
                @csrf
                @method('patch')

                <div class="mb-4">
                    <label class="block font-medium mb-2">Upload Logo</label>
                    <input type="file" name="logo" class="border rounded p-2 w-full">
                    @error('logo')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if($merchant?->logo_path)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Current Logo:</p>
                        <img src="{{ asset('storage/'.$merchant?->logo_path) }}" class="h-16 mt-2">
                    </div>
                @endif

                <x-primary-button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Save Logo
                </x-primary-button>
            </form>
        </div>
    </div>
</section>
