<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Outlet') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('outlets.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="outlet_name" class="block text-sm font-medium text-gray-700">Outlet Name</label>
                        <input id="outlet_name" type="text" name="outlet_name" value="{{ old('outlet_name') }}"
                               class="form-input mt-1 block w-full text-sm" required>
                        @error('outlet_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="machine_number" class="block text-sm font-medium text-gray-700">Total Machine</label>
                        <input id="machine_number" type="text" name="machine_number" value="{{ old('machine_number') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('machine_number')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="business_hours" class="block text-sm font-medium text-gray-700">Business Hours</label>
                        <input id="business_hours" type="text" name="business_hours" value="{{ old('business_hours') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('business_hours')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <input id="country" type="text" name="country" value="{{ old('country') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('country')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="province" class="block text-sm font-medium text-gray-700">State</label>
                        <input id="province" type="text" name="province" value="{{ old('province') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('province')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('city')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input id="address" type="text" name="address" value="{{ old('address') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('address')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <x-input-label for="brand_id" :value="__('Brand')" />
                        <x-dropdown-status name="brand_id" :options="$brands" :selected="$outlet->brand_id ?? null" class=" text-sm" />
                    </div>
                    <!-- status -->
                    <div class="mb-4">
                        <x-input-label for="status_id" :value="__('Status')" />
                        <x-dropdown-status name="status_id" :options="$statuses" :selected="$outlet->status_id ?? null" class=" text-sm" />
                    </div>
                    <!-- type -->
                    <div class="mb-4">
                        <x-input-label for="type_id" :value="__('Type')" />
                        <x-dropdown-status name="type_id" :options="$types" :selected="$outlet->type_id ?? null" class=" text-sm" />
                    </div>
                    <div class="mb-4">
                        <x-input-label for="manager_id" :value="__('Manager')" />
                        <x-dropdown-status name="manager_id" :options="$managers" :selected="$outlet->manager_id ?? null" class=" text-sm" />
                    </div>
                    <div class="mb-4">
                        <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                        <input id="latitude" type="text" name="latitude" value="{{ old('latitude') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('latitude')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                        <input id="longitude" type="text" name="longitude" value="{{ old('longitude') }}"
                               class="form-input mt-1 block w-full text-sm" >
                        @error('longitude')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Map Picker -->
                    <div x-data="mapManager()" x-init="initMap()" class="space-y-4 border-t pt-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900">Location Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                                <input id="latitude" type="text" name="latitude" x-model="lat"
                                       class="form-input mt-1 block w-full text-sm bg-gray-50" readonly>
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                                <input id="longitude" type="text" name="longitude" x-model="lng"
                                       class="form-input mt-1 block w-full text-sm bg-gray-50" readonly>
                            </div>
                        </div>

                        <div class="relative">
                            <input type="text" 
                                x-on:keydown.enter.prevent="searchAddress($event.target.value)"
                                placeholder="Search address and press Enter..." 
                                class="w-full border-gray-300 rounded-md shadow-sm mb-2">

                            <div id="map" style="height: 400px; min-height: 400px;" class="w-full rounded-md shadow-sm border" x-ignore></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('outlets.index') }}" class="text-gray-600 hover:underline">← Back to List</a>
                        <x-primary-button class="ml-4">
                            Save Outlet
                        </x-primary-button>
                    </div>                 
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mapManager', () => ({
                map: null,
                marker: null,
                lat: '{{ old('latitude', 3.1390) }}',
                lng: '{{ old('longitude', 101.6869) }}',

                initMap() {
                    this.$nextTick(() => {
                        // Fix icons for Vite
                        delete L.Icon.Default.prototype._getIconUrl;
                        L.Icon.Default.mergeOptions({
                            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                        });

                        this.map = L.map('map').setView([this.lat, this.lng], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(this.map);

                        this.updateMarker(this.lat, this.lng);

                        // FIXED: Use Arrow Function
                        this.map.on('click', (e) => {
                            this.updateMarker(e.latlng.lat, e.latlng.lng);
                        });

                        setTimeout(() => { this.map.invalidateSize(); }, 200);
                    });
                },

                updateMarker(lat, lng) {
                    this.lat = parseFloat(lat).toFixed(6);
                    this.lng = parseFloat(lng).toFixed(6);

                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                    this.map.panTo([lat, lng]);
                },

                searchAddress(query) {
                    if (!query || !L.esri || !L.esri.Geocoding) return;

                    // FIXED: Use Arrow Function
                    L.esri.Geocoding.geocode().text(query).run((err, results) => {
                        if (results?.results?.length > 0) {
                            const latlng = results.results[0].latlng;
                            this.updateMarker(latlng.lat, latlng.lng);
                            this.map.setZoom(16);
                        }
                    });
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>