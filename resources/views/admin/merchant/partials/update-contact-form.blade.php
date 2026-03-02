<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Merchant Contact Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update store's contact to be as headers for the receipt.") }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.merchant.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="support_number" :value="__('Company Support Number')" />
            <x-text-input id="support_number" name="support_number" type="text" class="mt-1 block w-full" :value="old('support_number', $merchant?->support_number)" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('support_number')" />
        </div>

        <div>
            <x-input-label for="toll_free" :value="__('Company Toll Free Number')" />
            <x-text-input id="toll_free" name="toll_free" type="text" class="mt-1 block w-full" :value="old('toll_free', $merchant?->toll_free)" />
            <x-input-error class="mt-2" :messages="$errors->get('toll_free')" />
        </div>

        <div>
            <x-input-label for="website" :value="__('Company Website')" />
            <x-text-input id="website" name="website" type="text" class="mt-1 block w-full" :value="old('website', $merchant?->website)" />
            <x-input-error class="mt-2" :messages="$errors->get('website')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'admin-merchant-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
