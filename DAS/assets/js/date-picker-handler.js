/**
 * Generalized Date Picker Handler (DAS Version)
 * Handles BS/AD Conversion with Preeti/Legacy Support
 * Uses Event Delegation for dynamic element support
 */

class DatePickerHandler {
    constructor() {
        this.dateConverter = null;

        // Nepali to English numeral mapping
        this.nepaliNumerals = {
            '०': '0', '१': '1', '२': '2', '३': '3', '४': '4',
            '५': '5', '६': '6', '७': '7', '८': '8', '९': '9'
        };
    }

    /**
     * Initialize date picker
     */
    init() {
        // Wait for DateConverter library
        if (typeof window.DateConverter !== 'undefined') {
            this.dateConverter = window.DateConverter;
        } else {
            console.error('[DatePicker] Date converter not found!');
            return;
        }

        // Attach events with delegation
        this.attachEvents();

        console.log('[DatePicker] Initialized successfully');
    }

    /**
     * Attach conversion events using delegation
     */
    attachEvents() {
        // Handle changes on any nepali-date-picker (BS input)
        // We listen to 'change' and 'blur' (delegated)

        const handleBSChange = (e) => {
            if (e.target.classList && e.target.classList.contains('nepali-date-picker')) {
                const bsInput = e.target;

                // Find corresponding AD input dynamically
                let adInput = null;
                if (bsInput.id) adInput = document.getElementById(bsInput.id + '_ad');
                if (!adInput && bsInput.name) adInput = document.querySelector(`[name="${bsInput.name}_ad"]`);

                if (adInput) {
                    this.convertBStoAD(bsInput, adInput);
                }
            }
        };

        const handleADChange = (e) => {
            // How to identify AD inputs safely? They usually end in _ad in name or ID
            // But we don't have a class. 
            // We can check if name ends in _ad AND there exists a corresponding BS input?
            // Or look for class 'ad-date-picker' if we added it? (We didn't)

            // For now, let's look for known AD inputs by ID convention if feasible, 
            // OR iterate checking if it matches the pattern input_name_ad

            const input = e.target;
            if (!input.name && !input.id) return;

            // Check if it is an AD input
            let isAdInput = false;
            let bsInput = null;

            if (input.id && input.id.endsWith('_ad')) {
                const baseId = input.id.slice(0, -3);
                bsInput = document.getElementById(baseId);
                if (bsInput && bsInput.classList.contains('nepali-date-picker')) isAdInput = true;
            }
            else if (input.name && input.name.endsWith('_ad')) {
                const baseName = input.name.slice(0, -3);
                bsInput = document.querySelector(`[name="${baseName}"]`);
                if (bsInput && bsInput.classList.contains('nepali-date-picker')) isAdInput = true;
            }

            if (isAdInput && bsInput && !input.readOnly) {
                this.convertADtoBS(input, bsInput);
            }
        };

        // Attach delegates
        document.body.addEventListener('change', handleBSChange);
        document.body.addEventListener('focusout', handleBSChange); // Handles blur bubbling

        document.body.addEventListener('change', handleADChange);
        document.body.addEventListener('focusout', handleADChange);
    }

    /**
     * Convert Nepali numerals to English numerals & Normalize Separators
     */
    nepaliToEnglish(text) {
        if (!text) return text;

        let result = text;
        // Convert Nepali numerals
        for (const [nepali, english] of Object.entries(this.nepaliNumerals)) {
            result = result.replace(new RegExp(nepali, 'g'), english);
        }

        // Normalize separators: replace various dashes/slashes with standard hyphen
        // \u2013 (en dash), \u2014 (em dash), etc.
        result = result.replace(/[\u002F\u2010-\u2015\u2212\uFF0D]/g, '-');

        return result;
    }

    /**
     * Convert BS to AD
     */
    convertBStoAD(bsInput, adInput) {
        const bsDateRaw = bsInput.value.trim();

        if (!bsDateRaw) {
            adInput.value = '';
            this.updateAge(''); // clear age
            return;
        }

        try {
            // Convert Nepali numerals to English & Normalize
            const bsDate = this.nepaliToEnglish(bsDateRaw);

            // Parse BS date (YYYY-MM-DD)
            const parts = bsDate.split('-');

            if (parts.length !== 3) {
                return;
            }

            const bsYear = parseInt(parts[0]);
            const bsMonth = parseInt(parts[1]);
            const bsDay = parseInt(parts[2]);

            if (isNaN(bsYear) || isNaN(bsMonth) || isNaN(bsDay)) return;

            // Convert BS → AD
            const converter = new this.dateConverter(bsYear, bsMonth, bsDay);
            const adString = converter.convertToAD().toADString();

            // Format AD date as YYYY-MM-DD
            const [adY, adM, adD] = adString.split('-');
            const paddedAD = `${adY}-${adM.padStart(2, '0')}-${adD.padStart(2, '0')}`;

            // Set AD input
            if (adInput.value !== paddedAD) {
                adInput.value = paddedAD;
            }

            // Update BS input with normalized value (English numerals) for consistency?
            // The user seems OK with normalized English in Test Form result
            const normalizedBS = `${bsYear}-${String(bsMonth).padStart(2, '0')}-${String(bsDay).padStart(2, '0')}`;
            if (bsInput.value !== normalizedBS) {
                bsInput.value = normalizedBS;
            }

            // Calculate age if this is DOB field
            if (bsInput.name === 'date_of_birth' || bsInput.id === 'date_of_birth') {
                this.calculateAge(paddedAD);
            }

        } catch (error) {
            // console.error('[DatePicker] BS→AD error:', error.message);
        }
    }

    /**
     * Convert AD to BS
     */
    convertADtoBS(adInput, bsInput) {
        const adDateRaw = adInput.value.trim();

        if (!adDateRaw) {
            bsInput.value = '';
            this.updateAge('');
            return;
        }

        try {
            const adDate = this.nepaliToEnglish(adDateRaw);
            const parts = adDate.split('-');

            if (parts.length !== 3) return;

            const adYear = parseInt(parts[0]);
            const adMonth = parseInt(parts[1]);
            const adDay = parseInt(parts[2]);

            // Convert AD → BS
            const converter = new this.dateConverter(adYear, adMonth, adDay);
            const bsString = converter.convertToBS().toBSString();

            // Set BS input
            if (bsInput.value !== bsString) {
                bsInput.value = bsString;
            }

            // Calculate age
            if (bsInput.name === 'date_of_birth' || bsInput.id === 'date_of_birth') {
                this.calculateAge(adDate);
            }

        } catch (error) {
            // console.error('[DatePicker] AD→BS error:', error.message);
        }
    }

    /**
     * Calculate age
     */
    calculateAge(adDate) {
        try {
            const birthDate = new Date(adDate);
            const today = new Date();

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            this.updateAge(age + ' years');

        } catch (error) {
            console.error('[DatePicker] Age error:', error.message);
        }
    }

    updateAge(val) {
        const ageInput = document.getElementById('age');
        if (ageInput) ageInput.value = val;
    }
}

// Global instance
window.DatePickerHandler = new DatePickerHandler();

// Auto-initialize
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        window.DatePickerHandler.init();
    }, 200);
});
