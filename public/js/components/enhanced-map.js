/**
 * EnhancedMapHandler - Interactive map with geocoding and draggable markers
 * Uses Leaflet.js with OpenStreetMap tiles and Nominatim for geocoding
 */
class EnhancedMapHandler {
    constructor(mapContainerId, searchInputId, searchBtnId) {
        this.mapContainer = document.getElementById(mapContainerId);
        this.searchInput = document.getElementById(searchInputId);
        this.searchBtn = document.getElementById(searchBtnId);

        if (!this.mapContainer) {
            console.warn('Map container not found:', mapContainerId);
            return;
        }

        // Initialize map centered on Indonesia
        this.map = L.map(mapContainerId).setView([-6.2088, 106.8456], 13);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.marker = null;
        this.onLocationChange = null;
        this.isSearching = false;

        this.setupGeocodingSearch();
        this.setupClickHandlers();
    }

    setupGeocodingSearch() {
        if (!this.searchBtn || !this.searchInput) return;

        this.searchBtn.addEventListener('click', async () => {
            await this.performSearch();
        });

        // Enter key to search
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });
    }

    async performSearch() {
        const query = this.searchInput.value.trim();
        if (!query || this.isSearching) return;

        this.isSearching = true;
        this.searchBtn.disabled = true;
        this.searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            await this.geocodeAddress(query);
        } finally {
            this.isSearching = false;
            this.searchBtn.disabled = false;
            this.searchBtn.innerHTML = '<i class="ti ti-search"></i> Search';
        }
    }

    async geocodeAddress(address) {
        try {
            // Nominatim API for forward geocoding
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&countrycodes=id`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'MyRVM-Assignment-System' // Required by Nominatim
                }
            });
            const results = await response.json();

            if (results.length > 0) {
                const { lat, lon, display_name } = results[0];
                this.setLocation(parseFloat(lat), parseFloat(lon), display_name);
                this.map.setView([lat, lon], 16);
            } else {
                this.showToast('Location not found. Please try a different search term.', 'warning');
            }
        } catch (error) {
            console.error('Geocoding error:', error);
            this.showToast('Failed to search location. Please try again.', 'error');
        }
    }

    async reverseGeocode(lat, lng) {
        try {
            // Nominatim API for reverse geocoding
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'MyRVM-Assignment-System'
                }
            });
            const result = await response.json();

            return result.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            return `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
    }

    setupClickHandlers() {
        this.map.on('click', async (e) => {
            const { lat, lng } = e.latlng;
            const address = await this.reverseGeocode(lat, lng);
            this.setLocation(lat, lng, address);
        });
    }

    setLocation(lat, lng, address) {
        // Update or create draggable marker
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        } else {
            this.marker = L.marker([lat, lng], {
                draggable: true,
                autoPan: true
            }).addTo(this.map);

            // Handle marker drag
            this.marker.on('dragend', async (e) => {
                const { lat, lng } = e.target.getLatLng();
                const address = await this.reverseGeocode(lat, lng);
                this.notifyLocationChange(lat, lng, address);
            });
        }

        // Add popup with address
        this.marker.bindPopup(`<strong>Installation Location</strong><br>${address}`).openPopup();

        this.notifyLocationChange(lat, lng, address);
    }

    notifyLocationChange(lat, lng, address) {
        if (this.onLocationChange) {
            this.onLocationChange({
                latitude: lat,
                longitude: lng,
                address: address
            });
        }
    }

    setLocationCallback(callback) {
        this.onLocationChange = callback;
    }

    // Refresh map when modal is shown (fixes display issues)
    invalidateSize() {
        if (this.map) {
            setTimeout(() => {
                this.map.invalidateSize();
            }, 100);
        }
    }

    // Set initial view to a location
    setView(lat, lng, zoom = 15) {
        if (this.map) {
            this.map.setView([lat, lng], zoom);
        }
    }

    showToast(message, type = 'info') {
        // Use Sneat/Bootstrap toast if available, otherwise console log
        if (typeof Toastify !== 'undefined') {
            Toastify({
                text: message,
                duration: 3000,
                gravity: 'top',
                position: 'right',
                backgroundColor: type === 'error' ? '#ff3e1d' : type === 'warning' ? '#ffab00' : '#71dd37'
            }).showToast();
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
}

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.EnhancedMapHandler = EnhancedMapHandler;
}
