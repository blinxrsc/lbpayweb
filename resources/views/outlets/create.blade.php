<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add New Outlet') }}
            </h2>
            <a href="{{ route('outlets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
                &larr; Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('outlets.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white shadow-sm border border-gray-100 sm:rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">General Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <x-input-label for="outlet_name" :value="__('Outlet Name')" />
                                    <x-text-input id="outlet_name" name="outlet_name" type="text" class="mt-1 block w-full" :value="old('outlet_name')" required autofocus placeholder="e.g. Central Square Branch" />
                                    <x-input-error :messages="$errors->get('outlet_name')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="machine_number" :value="__('Total Machines')" />
                                    <x-text-input id="machine_number" name="machine_number" type="number" class="mt-1 block w-full" :value="old('machine_number')" />
                                    <x-input-error :messages="$errors->get('machine_number')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="phone" :value="__('Contact Phone')" />
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" placeholder="+60..." />
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="business_hours" :value="__('Business Hours')" />
                                    <x-text-input id="business_hours" name="business_hours" type="text" class="mt-1 block w-full" :value="old('business_hours')" placeholder="e.g. 9:00 AM - 10:00 PM" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow-sm border border-gray-100 sm:rounded-xl p-6" x-data="mapManager()" x-init="initMap()">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Location Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div>
                                    <x-input-label for="country" :value="__('Country')" />
                                    <x-text-input id="country" name="country" type="text" class="mt-1 block w-full bg-gray-50" :value="old('country')" />
                                </div>
                                <div>
                                    <x-input-label for="province" :value="__('State/Province')" />
                                    <x-text-input id="province" name="province" type="text" class="mt-1 block w-full bg-gray-50" :value="old('province')" />
                                </div>
                                <div>
                                    <x-input-label for="city" :value="__('City')" />
                                    <x-text-input id="city" name="city" type="text" class="mt-1 block w-full bg-gray-50" :value="old('city')" />
                                </div>
                                <div class="md:col-span-3">
                                    <x-input-label for="address" :value="__('Full Address')" />
                                    <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('address') }}</textarea>
                                </div>
                            </div>

                            <div class="space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex flex-col md:flex-row gap-4 mb-2">
                                    <div class="flex-1">
                                        <input type="text" 
                                            x-on:keydown.enter.prevent="searchAddress($event.target.value)"
                                            placeholder="Search location on map..." 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500">
                                    </div>
                                    <div class="flex gap-2">
                                        <input type="text" name="latitude" x-model="lat" readonly class="text-xs border-none bg-transparent text-gray-500 w-24" placeholder="Lat">
                                        <input type="text" name="longitude" x-model="lng" readonly class="text-xs border-none bg-transparent text-gray-500 w-24" placeholder="Lng">
                                    </div>
                                </div>
                                <div id="map" style="height: 350px;" class="w-full rounded-md shadow-inner border z-0" x-ignore></div>
                                <p class="text-xs text-gray-500 italic">Click on the map to refine the exact location marker.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white shadow-sm border border-gray-100 sm:rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Classification</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="brand_id" :value="__('Brand')" />
                                    <x-dropdown-status name="brand_id" :options="$brands" :selected="old('brand_id')" class="w-full mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="type_id" :value="__('Outlet Type')" />
                                    <x-dropdown-status name="type_id" :options="$types" :selected="old('type_id')" class="w-full mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="status_id" :value="__('Operation Status')" />
                                    <x-dropdown-status name="status_id" :options="$statuses" :selected="old('status_id')" class="w-full mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="manager_id" :value="__('Assigned Manager')" />
                                    <x-dropdown-status name="manager_id" :options="$managers" :selected="old('manager_id')" class="w-full mt-1" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 border border-dashed border-gray-300 sm:rounded-xl p-6 flex flex-col gap-3">
                            <x-primary-button class="w-full justify-center py-3">
                                {{ __('Create Outlet') }}
                            </x-primary-button>
                            <a href="{{ route('outlets.index') }}" class="text-center text-sm text-gray-600 hover:text-red-500 transition">
                                Cancel and Discard
                            </a>
                        </div>
                    </div>

                </div>
            </form>
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