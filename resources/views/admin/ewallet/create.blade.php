<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Top-Up Package') }}
        </h2>
    </x-slot>

    <div class="py-12">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Your form content goes here -->
                    <form method="POST" action="{{ route('admin.packages.store') }}">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="topup_amount" :value="__('Top-up Amount (RM)')" />
                            <x-text-input type="number" step="1.00" name="topup_amount" class="block mt-1 w-full" required autofocus />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="bonus_amount" :value="__('Bonus Amount (RM)')" />
                            <x-text-input type="number" step="1.00" name="bonus_amount" class="block mt-1 w-full" required />
                        </div>

                        <div class="mb-4">
                            <label class="block font-medium">Active</label>
                            <select name="is_active" class="border rounded px-3 py-2 w-full">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                     
                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('admin.packages.index') }}" class="px-4 py-2 bg-gray-300 rounded">Cancel</a>
                            <x-primary-button class="ml-4">
                                {{ __('Save') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>