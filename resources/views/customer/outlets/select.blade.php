<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'Select Outlet'],
        ]" />
    </x-slot>
    <!--
    <h2 class="flex item-center justify-center text-center font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Select Outlet') }}
    </h2>
        -->
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('customer.outlet.set') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="outlet_id" class="block text-sm font-medium text-black-700">Select Outlet</label>
                        <select name="outlet_id" id="outlet_id"
                                class="block mt-1 w-full rounded-md shadow-sm border-gray-300 
                                       focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->outlet_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Confirm') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-customer-layout>