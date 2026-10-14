/**
 * Velvet Plate - Advanced Location System
 */

const LocationSystem = (() => {
    'use strict';

    let addresses = [];
    let currentPincode = localStorage.getItem('vp_pincode') || '';
    let currentAddress = localStorage.getItem('vp_address') || 'Select Location';

    const elements = {
        selector: document.getElementById('locationSelector'),
        display: document.getElementById('currentLocationDisplay'),
        modal: document.getElementById('locationModal'),
        closeModal: document.getElementById('closeLocationModal'),
        detectBtn: document.getElementById('detectLocationBtn'),
        addressList: document.getElementById('savedAddressList'),
        searchInput: document.getElementById('locationSearchBar'),
        searchBtn: document.getElementById('locationSearchBtn'),
        searchSuggestions: document.getElementById('locationSearchSuggestions'),
        serviceStatus: document.getElementById('serviceabilityStatus')
    };

    const init = () => {
        if (!elements.selector) return;

        updateDisplay();
        bindEvents();
        fetchAddresses();
    };

    const bindEvents = () => {
        elements.selector.addEventListener('click', openModal);
        if (elements.closeModal) elements.closeModal.addEventListener('click', closeModal);
        if (elements.detectBtn) elements.detectBtn.addEventListener('click', detectLocation);
        
        window.addEventListener('click', (e) => {
            if (e.target === elements.modal) closeModal();
        });

        if (elements.searchInput) elements.searchInput.addEventListener('input', debounce(handleSearch, 300));
        if (elements.searchBtn) elements.searchBtn.addEventListener('click', () => handleSearch(true));
    };

    const openModal = () => {
        if (!elements.modal) return;
        elements.modal.classList.add('is-active');
        document.body.style.overflow = 'hidden';
        renderAddresses();
    };

    const closeModal = () => {
        if (!elements.modal) return;
        elements.modal.classList.remove('is-active');
        document.body.style.overflow = '';
    };

    const updateDisplay = () => {
        if (elements.display) {
            elements.display.textContent = currentAddress;
        }
    };

    const fetchAddresses = async () => {
        try {
            const res = await SRMS.api.get('/api/location-addresses');
            if (res.success) {
                addresses = res.data;
                renderAddresses();
            }
        } catch (err) {
            console.error('Failed to fetch addresses', err);
        }
    };

    const renderAddresses = () => {
        if (!elements.addressList) return;

        if (addresses.length === 0) {
            elements.addressList.innerHTML =
                `<div class="empty-state">
                    <p>No saved addresses yet.</p>
                </div>`;
            return;
        }

        elements.addressList.innerHTML = addresses.map(addr =>
            `<div class="address-card ${addr.pincode === currentPincode ? 'is-active' : ''}" onclick="LocationSystem.selectAddress(${JSON.stringify(addr).replace(/"/g, '&quot;')})">
                <div class="address-icon">
                    ${getIconForType(addr.address_type)}
                </div>
                <div class="address-details">
                    <span class="address-label">${addr.address_type}</span>
                    <p class="address-text">${addr.flat_number ? addr.flat_number + ', ' : ''}${addr.street_address || ''}</p>
                    <p class="address-meta">${addr.city || ''}, ${addr.pincode || ''}</p>
                </div>
            </div>`
        ).join('');
    };

    const getIconForType = (type) => {
        switch(type) {
            case 'HOME': return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
            case 'WORK': return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>';
            default: return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        }
    };

    const detectLocation = () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        elements.detectBtn.classList.add('is-loading');
        elements.detectBtn.disabled = true;

        navigator.geolocation.getCurrentPosition(async (pos) => {
            try {
                const { latitude, longitude } = pos.coords;
                const res = await SRMS.api.get(`/api/location-geocode?lat=${latitude}&lng=${longitude}`);
                if (res.success) {
                    selectLocation(res.data.pincode, res.data.area || res.data.city);
                }
            } catch (err) {
                console.error(err);
                alert('Could not detect location. Please search manually.');
            } finally {
                elements.detectBtn.classList.remove('is-loading');
                elements.detectBtn.disabled = false;
            }
        }, (err) => {
            elements.detectBtn.classList.remove('is-loading');
            elements.detectBtn.disabled = false;
            console.error(err);
            alert('Location access denied. Please select manually.');
        });
    };

    const handleSearch = async (force = false) => {
        const query = elements.searchInput.value.trim();
        if (query.length < 3 && !force) return;

        // Auto-detect if query is pincode
        if (/^\d{6}$/.test(query)) {
            checkServiceability(query, query);
            return;
        }

        // Mock suggestions
        elements.searchSuggestions.innerHTML =
            `<div class="suggestion-item" onclick="LocationSystem.selectLocation('110001', '${query}')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span>${query} (Detected Area)</span>
            </div>`;
    };

    const checkServiceability = async (pincode, label) => {
        try {
            const res = await SRMS.api.get(`/api/location-check?pincode=${pincode}`);
            if (res.success) {
                if (res.available) {
                    selectLocation(pincode, label, res.data);
                } else {
                    alert(res.message);
                }
            }
        } catch (err) {
            console.error(err);
        }
    };

    const selectLocation = (pincode, label, serviceData = null) => {
        currentPincode = pincode;
        currentAddress = label;
        localStorage.setItem('vp_pincode', pincode);
        localStorage.setItem('vp_address', label);
        if (serviceData) {
            localStorage.setItem('vp_service', JSON.stringify(serviceData));
        }

        updateDisplay();
        closeModal();
        
        // Refresh page or trigger app-wide update
        window.location.reload();
    };

    const selectAddress = (addr) => {
        selectLocation(addr.pincode, addr.landmark || addr.city);
    };

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    return {
        init,
        selectLocation,
        selectAddress
    };
})();

window.LocationSystem = LocationSystem;
document.addEventListener('DOMContentLoaded', LocationSystem.init);
