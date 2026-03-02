<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Adjust Customer Wallet') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update customer's ewallet credit and bonus balance.") }}
        </p>
    </header>
    <form method="POST" action="{{ route('admin.ewallet.storeAdjust', $account->id) }}" class="mt-6 space-y-6">
        @csrf
        <div>
            <x-input-label for="amount" :value="__('Amount')" />
            <x-text-input id="amount" name="amount" type="number" step="1.00" class="mt-1 block w-full" :value="old('amount', '0')" required autofocus autocomplete="amount" />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>
        <div>
            <x-input-label for="type" :value="__('Type of transaction')" />
            <select name="type" class="w-full border rounded px-3 py-2" required>
                <option value="credit_adjust">Credit Adjust</option>
                <option value="debit_adjust">Debit Adjust</option>
            </select>
        </div>
        <div>
            <x-input-label for="bonus_amount" :value="__('Bonus Amount')" />
            <x-text-input id="bonus_amount" name="bonus_amount" type="number" step="1.00" class="mt-1 block w-full" :value="old('bonus_amount', '0')" required autofocus autocomplete="bonus_amount" />
            <x-input-error class="mt-2" :messages="$errors->get('bonus_amount')" />
        </div>
        <div>
            <x-input-label for="bonus_type" :value="__('Type of bonus transaction')" />
            <select name="bonus_type" class="w-full border rounded px-3 py-2" required>
                <option value="credit_bonus">Credit Bonus</option>
                <option value="debit_bonus">Debit Bonus</option>
            </select>
        </div>
        <div>
            <x-input-label for="reason" :value="__('Reason')" />
            <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason', '')" required autofocus autocomplete="reason" />
            <x-input-error class="mt-2" :messages="$errors->get('reason')" />
        </div>
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Apply') }}</x-primary-button>
        </div>
    </form>
    </div>
</section>