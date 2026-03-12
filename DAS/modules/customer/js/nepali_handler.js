/**
 * Nepali Input & Date Handler
 * Handles Preeti to Unicode conversion and Auto Date Conversion
 */

(function ($) {
    'use strict';

    const PREETI_FONT_SIZE = '32px'; // Larger font for Preeti input
    const NORMAL_FONT_SIZE = ''; // Reset to default

    // Ensure converters are loaded
    if (typeof PreetiConverterCore === 'undefined') {
        console.error('PreetiConverterCore not loaded');
    }
    if (typeof DateConverter === 'undefined') {
        console.error('DateConverter not loaded');
    }

    /**
     * Preeti Input Handler
     * Focus: Switch to Preeti font, larger size
     * Blur: Convert to Unicode, reset font
     */
    function initPreetiInputs() {
        $(document).on('focus', '.nepali-input', function () {
            const $this = $(this);

            // If this is a date field and already has a valid unicode date (YYYY-MM-DD), 
            // DO NOT switch to Preeti immediately unless user clears it or starts typing?
            // User requirement: "if manually typed then first take input in preeti"
            // So if they focus, they might intend to type.
            // But if they just focus to open the calendar, we don't want to break the display.
            // Compromise: Switch to Preeti, BUT if it is a valid date, keep it as is?
            // Actually, if it's a valid date "2080-01-01", showing it in Preeti is the "Mapping Char" issue.

            // Fix: If it is a date picker field AND has a valid Unicode date value, do NOT switch yet.
            // Wait for keypress or input to switch?
            // But they want "focus to type".

            // Refined Logic for Date Pickers:
            if ($this.hasClass('nepali-date-picker') && /^\d{4}-\d{2}-\d{2}$/.test($this.val())) {
                // It has a valid date, likely from DB or previous pick. Keep default font.
                // We will only switch to Preeti if they clear it or start typing (handled by keypress/input?)
                // For now, let's NOT force Preeti on focus if it's already a valid date.
                return;
            }

            $this.css({
                'font-family': 'Preeti',
                'font-size': PREETI_FONT_SIZE + ' !important'
            });
            // Force via attribute if CSS fails
            $this.attr('style', $this.attr('style') + '; font-size: ' + PREETI_FONT_SIZE + ' !important;');

            // Reverse convert logic remains...
            if ($this.val() && isUnicode($this.val())) {
                // Existing logic...
                try {
                    const preetiVal = PreetiConverterCore.toPreeti($this.val(), 'preeti');
                    $this.val(preetiVal);
                } catch (e) { }
            }
        });

        // Keypress handler for digit mapping removed to allow natural mapping via preeti-converter.js

        $(document).on('blur', '.nepali-input', function () {
            const $this = $(this);
            let val = $this.val();

            if (val) {
                try {
                    // Convert to Unicode
                    const unicodeVal = PreetiConverterCore.toUnicode(val, 'preeti');
                    $this.val(unicodeVal);

                    // CRITICAL: Reset font IMMEDIATELY to prevent Preeti font from displaying Unicode characters
                    $this.css({
                        'font-family': '',
                        'font-size': NORMAL_FONT_SIZE
                    });
                    // Remove inline style attribute that might have Preeti
                    $this.removeAttr('style');

                    // Also update hidden fields if any (like duplicate inputs for logic)
                    $this.trigger('change');
                } catch (e) {
                    console.error('Conversion error:', e);
                }
            } else {
                // Even if empty, reset font
                $this.css({
                    'font-family': '',
                    'font-size': NORMAL_FONT_SIZE
                });
                $this.removeAttr('style');
            }
        });
    }

    // Helper to check if string contains unicode (simplified check for Devanagari range)
    function isUnicode(str) {
        for (let i = 0; i < str.length; i++) {
            if (str.charCodeAt(i) >= 2304 && str.charCodeAt(i) <= 2431) {
                return true;
            }
        }
        return false;
    }

    /**
     * Date Conversion Logic
     */
    function handleDateChange(bsDateStr, $input) {
        if (!bsDateStr || bsDateStr.trim() === '') return;

        try {
            // 1. Convert any Nepali digits in input to English for calculation
            const engDateStr = convertToEnglishDigits(bsDateStr);

            // Expected format YYYY-MM-DD
            const parts = engDateStr.split(/[-/.]/); // Allow - / or .
            if (parts.length !== 3) return;

            const y = parseInt(parts[0]);
            const m = parseInt(parts[1]);
            const d = parseInt(parts[2]);

            // Normalize BS Date (Padding to YYYY-MM-DD)
            const paddedM = m.toString().padStart(2, '0');
            const paddedD = d.toString().padStart(2, '0');
            const normalizedBsDate = `${y}-${paddedM}-${paddedD}`;

            // Convert BS to AD (Using English digits)
            const converter = new DateConverter(y, m, d);
            let adDate = converter.convertToAD().toADString(); // YYYY-M-D or YYYY-MM-DD (unpadded from lib)

            // Normalize AD Date (Padding to YYYY-MM-DD)
            const adParts = adDate.split('-');
            if (adParts.length === 3) {
                const adY = adParts[0];
                const adM = adParts[1].padStart(2, '0');
                const adD = adParts[2].padStart(2, '0');
                adDate = `${adY}-${adM}-${adD}`;
            }

            // Find target AD field
            const fieldName = $input.attr('name');
            const targetAdId = '#' + fieldName + '_ad';

            if ($(targetAdId).length) {
                // USER REQUEST: Display AD date in Nepali digits
                $(targetAdId).val(convertToNepaliDigits(adDate));
            }

            // Calculate Age if this is DOB
            if (fieldName === 'date_of_birth' || $input.attr('id') === 'date_of_birth') {
                calculateAge(adDate);
            }

            // 2. Convert the input value to Nepali Digits for display (User requirement)
            // Use the NORMALIZED (Padded) BS Date
            const nepaliDateStr = convertToNepaliDigits(normalizedBsDate);
            if ($input.val() !== nepaliDateStr) {
                $input.val(nepaliDateStr);
            }

        } catch (e) {
            console.error('Date conversion error:', e);
        }
    }

    function calculateAge(adDateStr) {
        if (!adDateStr) return;
        const birthDate = new Date(adDateStr);
        const today = new Date();

        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        // Update age field (English digits usually preferred for calculations/display elsewhere, but user might want Nepali? keeping English for Age/AD unless requested)
        // User requested AD Date in Nepali, assuming Age should be consistent.
        $('#age').val(convertToNepaliDigits(age.toString()));
        // Also update hidden age if exists
        $('#hidden_age').val(age);
    }

    // --- Helper Functions for Digit Conversion ---

    function convertToEnglishDigits(str) {
        if (!str) return str;
        var mapping = {
            '०': '0', '१': '1', '२': '2', '३': '3', '४': '4',
            '५': '5', '६': '6', '७': '7', '८': '8', '९': '9'
        };
        return str.toString().replace(/[०-९]/g, function (match) {
            return mapping[match];
        });
    }

    function convertToNepaliDigits(str) {
        if (!str) return str;
        var mapping = {
            '0': '०', '1': '१', '2': '२', '3': '३', '4': '४',
            '5': '५', '6': '६', '7': '७', '8': '८', '9': '९'
        };
        return str.toString().replace(/[0-9]/g, function (match) {
            return mapping[match];
        });
    }

    /**
     * Initialize
     */
    // Expose conversion logic globally so specific form handlers can call it BEFORE creating FormData
    window.convertAllPreetiToUnicode = function () {
        $('.nepali-input').each(function () {
            const $this = $(this);
            const val = $this.val();
            // Simplified check: If it has value and doesn't look like standard ASCII numbers/symbols only (unless it's mapping chars)
            // Better to rely on isUnicode failing.
            if (val && !isUnicode(val)) {
                try {
                    const unicodeVal = PreetiConverterCore.toUnicode(val, 'preeti');
                    $this.val(unicodeVal);
                    // Reset font
                    $this.css({ 'font-family': '', 'font-size': NORMAL_FONT_SIZE });
                } catch (e) {
                    console.error("Manual conversion failed", e);
                }
            }
        });
    };

    $(document).ready(function () {
        initPreetiInputs();

        // Listen for Nepali Date Picker changes (Selection from Calendar)
        $('.nepali-date-picker').on('change', function () {
            const $this = $(this);
            const val = $this.val();

            // Reset font to normal
            $this.css({ 'font-family': '', 'font-size': NORMAL_FONT_SIZE });

            handleDateChange(val, $this);
        });

        // Also listen for manual input on date fields
        $('.nepali-date-picker').on('blur', function () {
            handleDateChange($(this).val(), $(this));
        });

        // Initial check for existing values
        $('.nepali-date-picker').each(function () {
            if ($(this).val()) {
                handleDateChange($(this).val(), $(this));
            }
        });

        // SAFETY NET: Keep generic listener BUT rely on explicit calls in forms main handler mostly
        $('form').on('submit', function () {
            if (window.convertAllPreetiToUnicode) {
                window.convertAllPreetiToUnicode();
            }
        });
    });

})(jQuery);
