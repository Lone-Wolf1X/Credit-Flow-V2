/**
 * Location Cascade JavaScript
 * Handles cascading dropdowns for Province -> District -> Municipality -> Ward
 */

class LocationCascade {
    constructor(provinceSelect, districtSelect, municipalitySelect, wardSelect) {
        this.provinceSelect = document.getElementById(provinceSelect);
        this.districtSelect = document.getElementById(districtSelect);
        this.municipalitySelect = document.getElementById(municipalitySelect);
        this.wardSelect = document.getElementById(wardSelect);

        this.init();
    }

    init() {
        // Load provinces on page load and store promise
        this.loadProvincesPromise = this.loadProvinces();

        // Add event listeners
        if (this.provinceSelect) {
            this.provinceSelect.addEventListener('change', () => this.loadDistricts());
        }

        if (this.districtSelect) {
            this.districtSelect.addEventListener('change', () => this.loadMunicipalities());
        }

        if (this.municipalitySelect) {
            this.municipalitySelect.addEventListener('change', () => this.loadWards());
        }
    }

    async loadProvinces() {
        try {
            const response = await fetch('../../api/customer_api.php?action=get_provinces');
            const data = await response.json();

            if (data.success) {
                this.populateSelect(this.provinceSelect, data.data);
            }
        } catch (error) {
            console.error('Error loading provinces:', error);
        }
    }

    async loadDistricts() {
        const province = this.provinceSelect.value;

        if (!province) {
            this.clearSelect(this.districtSelect);
            this.clearSelect(this.municipalitySelect);
            this.clearSelect(this.wardSelect);
            return;
        }

        try {
            const response = await fetch(`../../api/customer_api.php?action=get_districts&province=${encodeURIComponent(province)}`);
            const data = await response.json();

            if (data.success) {
                this.populateSelect(this.districtSelect, data.data);
                this.clearSelect(this.municipalitySelect);
                this.clearSelect(this.wardSelect);
            }
        } catch (error) {
            console.error('Error loading districts:', error);
        }
    }

    async loadMunicipalities() {
        const province = this.provinceSelect.value;
        const district = this.districtSelect.value;

        if (!province || !district) {
            this.clearSelect(this.municipalitySelect);
            this.clearSelect(this.wardSelect);
            return;
        }

        try {
            const response = await fetch(`../../api/customer_api.php?action=get_municipalities&province=${encodeURIComponent(province)}&district=${encodeURIComponent(district)}`);
            const data = await response.json();

            if (data.success) {
                this.populateSelect(this.municipalitySelect, data.data.map(m => m.name));
                this.clearSelect(this.wardSelect);

                // Store wada_count for later use
                this.municipalityData = data.data;
            }
        } catch (error) {
            console.error('Error loading municipalities:', error);
        }
    }

    async loadWards() {
        const municipality = this.municipalitySelect.value;

        if (!municipality) {
            this.clearSelect(this.wardSelect);
            return;
        }

        try {
            const response = await fetch(`../../api/customer_api.php?action=get_wards&municipality=${encodeURIComponent(municipality)}`);
            const data = await response.json();

            if (data.success) {
                this.populateSelectNumbers(this.wardSelect, data.data);
            }
        } catch (error) {
            console.error('Error loading wards:', error);
        }
    }

    populateSelect(selectElement, options) {
        if (!selectElement) return;

        // Clear existing options except first
        selectElement.innerHTML = selectElement.options[0].outerHTML;

        // Add new options
        options.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option;
            opt.textContent = option;
            selectElement.appendChild(opt);
        });

        // Only enable if NOT in view mode
        if (!this.isViewMode()) {
            selectElement.disabled = false;
        }
        selectElement.dispatchEvent(new Event('change'));
    }

    populateSelectNumbers(selectElement, numbers) {
        if (!selectElement) return;

        // Clear existing options except first
        selectElement.innerHTML = selectElement.options[0].outerHTML;

        // Add new options
        numbers.forEach(num => {
            const opt = document.createElement('option');
            opt.value = num;
            opt.textContent = num;
            selectElement.appendChild(opt);
        });

        // Only enable if NOT in view mode
        if (!this.isViewMode()) {
            selectElement.disabled = false;
        }
        selectElement.dispatchEvent(new Event('change'));
    }

    // Helper to check view mode status
    isViewMode() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlViewMode = urlParams.get('view_mode') === '1';
        const globalViewMode = (typeof window.FORCE_VIEW_MODE !== 'undefined' && window.FORCE_VIEW_MODE === true);
        return urlViewMode || globalViewMode;
    }

    clearSelect(selectElement) {
        if (!selectElement) return;

        selectElement.innerHTML = selectElement.options[0].outerHTML;
        selectElement.disabled = true;
        selectElement.dispatchEvent(new Event('change'));
    }

    // Set values (useful for edit mode)
    // Note: We don't dispatch change here to avoid clearing downstream fields while loading
    // The hidden fields are already populated by PHP on page load
    async setValues(province, district, municipality, ward) {
        // Wait for provinces to load first
        if (this.loadProvincesPromise) {
            await this.loadProvincesPromise;
        }

        if (province) {
            this.provinceSelect.value = province;

            // Sync hidden field for Province (Critical Fix)
            if (document.getElementById(this.provinceSelect.id + '_hidden')) {
                document.getElementById(this.provinceSelect.id + '_hidden').value = province;
            }

            // dispatch change to ensure UI sync but we need to be careful not to trigger cascading clears if not needed
            // Actually, loadDistricts() will be called if we trigger change on provinceSelect.
            // But setValues calls loadDistricts() explicitly.

            this.loadDistricts().then(() => {
                if (district) {
                    this.districtSelect.value = district;
                    // Sync hidden field
                    if (document.getElementById(this.districtSelect.id + '_hidden')) {
                        document.getElementById(this.districtSelect.id + '_hidden').value = district;
                    }

                    this.loadMunicipalities().then(() => {
                        if (municipality) {
                            this.municipalitySelect.value = municipality;
                            // Sync hidden field
                            if (document.getElementById(this.municipalitySelect.id + '_hidden')) {
                                document.getElementById(this.municipalitySelect.id + '_hidden').value = municipality;
                            }

                            this.loadWards().then(() => {
                                if (ward) {
                                    this.wardSelect.value = ward;
                                    // Sync hidden field
                                    if (document.getElementById(this.wardSelect.id + '_hidden')) {
                                        document.getElementById(this.wardSelect.id + '_hidden').value = ward;
                                    }
                                }
                            });
                        }
                    });
                }
            });
        }
    }
}

// Helper function to copy address
function copyAddress(fromPrefix, toPrefix) {
    // Copy text input fields
    const textFields = ['country', 'town_village', 'street_name', 'street_number'];
    textFields.forEach(field => {
        const fromField = document.querySelector(`[name="${fromPrefix}_${field}"]`);
        const toField = document.querySelector(`[name="${toPrefix}_${field}"]`);

        if (fromField && toField) {
            toField.value = fromField.value;
        }
    });

    // Copy select fields with cascading
    const fromProv = document.getElementById(`${fromPrefix}_province`);
    const toProv = document.getElementById(`${toPrefix}_province`);

    if (fromProv && toProv && fromProv.value) {
        toProv.value = fromProv.value;
        toProv.dispatchEvent(new Event('change'));

        // Wait for districts to load, then copy
        setTimeout(() => {
            const fromDist = document.getElementById(`${fromPrefix}_district`);
            const toDist = document.getElementById(`${toPrefix}_district`);
            if (fromDist && toDist && fromDist.value) {
                toDist.value = fromDist.value;
                toDist.dispatchEvent(new Event('change'));

                // Wait for municipalities to load
                setTimeout(() => {
                    const fromMun = document.getElementById(`${fromPrefix}_municipality_vdc`);
                    const toMun = document.getElementById(`${toPrefix}_municipality_vdc`);
                    if (fromMun && toMun && fromMun.value) {
                        toMun.value = fromMun.value;
                        toMun.dispatchEvent(new Event('change'));

                        // Wait for wards to load
                        setTimeout(() => {
                            const fromWard = document.getElementById(`${fromPrefix}_ward_no`);
                            const toWard = document.getElementById(`${toPrefix}_ward_no`);
                            if (fromWard && toWard && fromWard.value) {
                                toWard.value = fromWard.value;
                                toWard.dispatchEvent(new Event('change'));
                            }
                        }, 500);
                    }
                }, 500);
            }
        }, 500);
    }
}
