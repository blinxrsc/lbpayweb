import './bootstrap';

import Alpine from 'alpinejs';
import L from 'leaflet';
import * as esriGeocode from 'esri-leaflet-geocoder';
window.L = L;
L.esri = L.esri || {}; // Ensure esri object exists
window.L.esri = {
    Geocoding: esriGeocode
};
window.Alpine = Alpine;

Alpine.start();
