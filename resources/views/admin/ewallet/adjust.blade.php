<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Adjust Wallet for ') }} {{ $account->customer->name }} ({{ $account->customer->email }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('admin.ewallet.partials.update-credit-balance')
                    <div class="mt-6 flex justify-end">
                        <a href="{{ route('admin.ewallet.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md"
                        >
                            ← Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>