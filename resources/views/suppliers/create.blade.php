<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Supplier') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8">

                    <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Name <span class="text-red-500">*</span></label>
                            <input type="text" name="supplier_name" value="{{ old('supplier_name') }}"
                                placeholder="e.g. Munzprufer Diamant Trend"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100" required>
                            @error('supplier_name')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}"
                                    placeholder="e.g. sales@supplier.com"
                                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                @error('email')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    placeholder="e.g. 012-3456789"
                                    class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">
                                @error('phone')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="3"
                                placeholder="Street, city, postcode"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring focus:ring-blue-100">{{ old('address') }}</textarea>
                            @error('address')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <a href="{{ route('suppliers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                                Cancel
                            </a>
                            <x-primary-button>Save Supplier</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
