<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Outlet Details') }}
            </h2>
            <a href="{{ route('outlets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
                &larr; Back to Directory
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">           
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden p-6"> 
                @can('outlets.update')
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('outlets.edit', $outlet->id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 transition">
                        Edit Outlet
                    </a>
                </div>  
                @endcan
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-white/20 backdrop-blur-md rounded-lg border border-white/30">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-black">{{ $outlet->brand->name }} {{ $outlet->outlet_name }}</h1>
                        <p class="text-emerald-100 flex items-center">
                            ID: #{{ $outlet->id }}
                        </p>
                    </div> 
                </div> 
                <div class="py-6" />
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                        <div class="ml-3">
                            <p class="text-xs text-gray-500 uppercase">Outlet Type</p>
                            <p class="text-lg font-semibold text-gray-800"><x-status-badge :status="$outlet->type->name" /></p>
                        </div>
                    </div>
                    <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                        <div class="ml-3">
                            <p class="text-emerald-100 flex items-center">
                                <span class="bg-emerald-700/50 px-2 py-0.5 rounded text-xs uppercase tracking-wider mr-2">
                                    @if($outlet->brand->logo) 
                                        <img src="{{ asset('storage/' . $outlet->brand->logo) }}" 
                                                alt="{{ $outlet->brand->name }}" 
                                                class="w-40 h-auto max-h-12 object-scale-down mx-auto">
                                    @endif
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                        <div class="ml-3">
                            <p class="text-xs text-gray-500 uppercase">Outlet Status</p>
                            <p class="text-lg font-semibold text-gray-800"><x-status-badge :status="$outlet->status->name" /></p>
                        </div>
                    </div>
                </div>
                <div class="py-6" />
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                        <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                            <div class="ml-3">
                                <p class="text-xs text-gray-500 uppercase">Machine Number</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $outlet->machine_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                            <div class="ml-3">
                                <p class="text-xs text-gray-500 uppercase">Manager</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $outlet->manager->name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center p-4 bg-gray-50 rounded-xl">
                            <div class="ml-3">
                                <p class="text-xs text-gray-500 uppercase">Operating Hours</p>
                                <p class="text-lg font-semibold text-gray-800">{{ $outlet->business_hours }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="py-6" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div>
                            <h3 class="flex items-center text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Location Details
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-500">Full Address</p>
                                    <p class="text-gray-800 font-medium">{{ $outlet->address }}</p>
                                    <p class="text-gray-800">{{ $outlet->city }}, {{ $outlet->province }}</p>
                                    <p class="text-gray-800">{{ $outlet->country }}</p>
                                </div>
                                <div class="pt-4 border-t border-gray-100">
                                    <x-heroicon-s-map-pin class="w-5 h-5"/><p class="text-sm text-gray-500 mb-1">Google Maps Link</p>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $outlet->latitude }},{{ $outlet->longitude }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm inline-flex items-center">
                                        {{ $outlet->latitude }}, {{ $outlet->longitude }}
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="flex items-center text-sm font-bold text-gray-400 uppercase tracking-widest mb-6">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Contact & Audit
                            </h3>
                            <dl class="grid grid-cols-1 gap-y-4">
                                <div class="flex justify-between items-center">
                                    <dt class="text-sm text-gray-500">Phone Number</dt>
                                    <dd class="text-sm font-semibold text-gray-800">{{ $outlet->phone }}</dd>
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                                    <dt class="text-sm text-gray-500">Registration Date</dt>
                                    <dd class="text-sm text-gray-800">{{ $outlet->created_at->format('M d, Y H:i') }}</dd>
                                </div>
                                <div class="flex justify-between items-center border-t border-gray-100 pt-4">
                                    <dt class="text-sm text-gray-500">Last Updated</dt>
                                    <dd class="text-sm text-gray-800">{{ $outlet->updated_at->diffForHumans() }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
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
                    <div id="map" style="height: 400px; min-height: 400px;" class="w-full rounded-md shadow-sm border" x-ignore></div>
                </div>
                @push('scripts')
                <script>
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('mapManager', () => ({
                            map: null,
                            marker: null,
                            lat: '{{ old('latitude', $outlet->latitude) }}',
                            lng: '{{ old('longitude', $outlet->longitude) }}',

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
                <div class="bg-gray-50 px-8 py-4 flex justify-between items-center">
                    <p class="text-xs text-gray-400 italic">Last system sync: {{ now()->format('Y-m-d') }}</p>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>