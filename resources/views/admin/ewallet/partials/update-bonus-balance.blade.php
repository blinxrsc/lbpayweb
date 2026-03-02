<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Adjust Customer Wallet - Bonus Balance') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update customer's ewallet bonus balance.") }}
        </p>
    </header>
    <form method="POST" action="{{ route('admin.ewallet.storeAdjust', $account->id) }}" class="mt-6 space-y-6">
        @csrf
        <input type="hidden" name="status" value="bonus">
        <div>
            <x-input-label for="bonus_amount" :value="__('Amount')" />
            <x-text-input id="bonus_amount" name="bonus_amount" type="number" step="1.00" class="mt-1 block w-full" :value="old('bonus_amount', '0')" required autofocus autocomplete="bonus_amount" />
            <x-input-error class="mt-2" :messages="$errors->get('bonus_amount')" />
        </div>
        <div>
            <x-input-label for="bonus_type" :value="__('Type of transaction')" />
            <select name="bonus_type" class="w-full border rounded px-3 py-2" required>
                <option value="credit_bonus">Credit Bonus</option>
                <option value="debit_bonus">Debit Bonus</option>
            </select>
        </div>
        <div>
            <x-input-label for="bonus_note" :value="__('Reason')" />
            <x-text-input id="bonus_note" name="bonus_note" type="text" class="mt-1 block w-full" :value="old('bonus_note', '')" required autofocus autocomplete="bonus_note" />
            <x-input-error class="mt-2" :messages="$errors->get('bonus_note')" />
        </div>
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Apply Bonus') }}</x-primary-button>
        </div>
    </form>
    </div>
    <div class="mt-6 flex justify-end">
        <a href="{{ url()->previous() ?? route('admin.ewallet.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md"
        >
            ← Back
        </a>
    </div>
</section>