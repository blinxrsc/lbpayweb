<x-customer-layout>
    <x-slot name="header">
        <x-breadcrumbs :links="[
            ['url' => route('customer.dashboard'), 'label' => 'Home', 'icon' => 'heroicon-o-home'],
            ['url' => '#', 'label' => 'My Ewallet'],
        ]" />
    </x-slot>


    <div class="space-y-6 pb-20">
        <!-- padding bottom for nav bar -->

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-2 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Balance Card -->
        <div class="bg-white rounded-xl shadow-md p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-700">Balance</h3>
            <p class="text-3xl font-bold text-blue-600 mt-2">
                RM {{ number_format(auth('customer')->user()->wallet_balance, 2) }}
            </p>
            <div class="flex justify-center items-center mt-4 text-sm text-gray-600 space-x-8">
            <div class="text-center">
                <p class="font-semibold">Credit</p>
                <p>RM {{ number_format(auth('customer')->user()->ewalletAccount->credit_balance ?? 0, 2) }}</p>
            </div>
            <div class="text-center">
                <p class="font-semibold">Bonus</p>
                <p>RM {{ number_format(auth('customer')->user()->ewalletAccount->bonus_balance ?? 0, 2) }}</p>
            </div>
            <div class="text-center">
                <a href="{{ route('ewallet.topuplist') }}">
                <x-primary-button>Add Top Up</x-primary-button>
            </div>
        </div>
    </div>
    <!-- Nearby devices button -->
    <div class="p-6 flex flex-col items-center text-center">
        <p class="mb-4">Click below to detect your location and show devices at the nearest outlet.</p>
        <x-primary-button id="detectBtn" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Nearby Devices
        </x-primary-button>
    </div>
    {{-- Script section --}}
    <script>
        document.getElementById('detectBtn').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch("{{ route('customer.outlet.detect') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        })
                    }).then(res => {
                        if (res.redirected) {
                            window.location.href = res.url;
                        } else {
                            window.location.href = "{{ route('customer.devices.index') }}";
                        }
                    });
                }, function(error) {
                    alert("Location access denied. Please select outlet manually.");
                    window.location.href = "{{ route('customer.outlet.select') }}";
                });
            } else {
                alert("Geolocation not supported.");
                window.location.href = "{{ route('customer.outlet.select') }}";
            }
        });
    </script>
</x-customer-layout>
