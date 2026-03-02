<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Merchant Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update store's information and email address to be as headers for the receipt.") }}
        </p>
    </header>

    <form method="post" action="{{ route('admin.merchant.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')
        <div>
            <x-input-label for="company_name" :value="__('Company Name')" />
            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $merchant?->company_name)" required autofocus autocomplete="company_name" />
            <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Company Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $merchant?->email)" required autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="reg_no" :value="__('Registration ID/No')" />
            <x-text-input id="reg_no" name="reg_no" type="text" class="mt-1 block w-full" :value="old('reg_no', $merchant?->reg_no)" required autocomplete="reg_no" />
            <x-input-error class="mt-2" :messages="$errors->get('reg_no')" />
        </div>

        <div>
            <x-input-label for="address" :value="__('Company Registered Address')" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $merchant?->address)" required autocomplete="address" />
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
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
