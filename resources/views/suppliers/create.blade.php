<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Supplier') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('suppliers.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label>Supplier</label>
                        <input type="text" name="supplier_name" value="{{ old('supplier_name') }}" class="form-input w-full" required>
                    </div>
                    <div class="mb-4">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label>Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-input w-full">
                    </div>
                    <div class="mb-4">
                        <label>Address</label>
                        <textarea name="address" class="form-textarea w-full">{{ old('address') }}</textarea>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button class="ml-4">
                            Save Supplier
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>