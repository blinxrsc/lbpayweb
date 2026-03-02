<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'Edit Profile'],
        ]" />
    </x-slot>


    <div class="p-6 bg-white">
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.profile.update') }}">
            @csrf

            <div class="mb-4 flex space-x-2">
                <div class="w-1/3">
                    <label>Phone Country Code</label>
                    <input type="text" name="phone_country_code" value="{{ old('phone_country_code',$customer->phone_country_code) }}" class="form-input w-full" required>
                </div>
                <div class="w-2/3">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number',$customer->phone_number) }}" class="form-input w-full" required>
                </div>
            </div>

            <div class="mb-4">
                <label>Birthday</label>
                <input type="date" name="birthday" value="{{ old('birthday',$customer->birthday) }}" class="form-input w-full" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</x-customer-layout>