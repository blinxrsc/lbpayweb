<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nearby Devices') }}
        </h2>
    </x-slot>

    <div class="p-6">
        <p class="mb-4">Click below to detect your location and show devices at the nearest outlet.</p>
        <x-primary-button id="detectBtn" 
                class="px-4 py-2 bg-indigo-600 text-black rounded hover:bg-indigo-700">
            Detect Location
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
