/**
 * Bidirectional Preeti-Unicode Form Handler (Text Only)
 * Handles focus/blur events for seamless conversion on .nepali-input fields.
 * 
 * Strategy:
 * - Focus: Convert Unicode Value -> Preeti Value. (Font handled by preeti-font.css :focus)
 * - Blur: Convert Preeti Value -> Unicode Value. (Font handled by preeti-font.css :focus removal)
 * - Uses Event Delegation for support of dynamic elements (e.g. Family Members).
 */

class PreetiFormHandler {
    constructor() {
        this.preetiConverter = null;
    }

    /**
     * Initialize the form handler
     */
    init() {
        // Check for PreetiConverterCore
        if (typeof window.PreetiConverterCore !== 'undefined') {
            this.preetiConverter = window.PreetiConverterCore;
        } else {
            console.error('[PreetiForm] PreetiConverterCore not found!');
            return;
        }

        // Attach events using delegation
        this.attachEvents();

        console.log('[PreetiForm] Text Handler Initialized');
    }

    /**
     * Attach events using delegation to support dynamic elements
     */
    attachEvents() {
        // Use event delegation on document body for .nepali-input only

        // On FOCUS: Convert Unicode → Preeti (for editing)
        document.body.addEventListener('focusin', (e) => {
            if (e.target.classList && e.target.classList.contains('nepali-input')) {
                this.handleFocus(e);
            }
        });

        // On BLUR: Convert Preeti → Unicode (for storage)
        document.body.addEventListener('focusout', (e) => {
            if (e.target.classList && e.target.classList.contains('nepali-input')) {
                this.handleBlur(e);
            }
        });
    }

    /**
     * Handle focus event - Show Preeti for editing
     */
    handleFocus(event) {
        const input = event.target;
        if (input.readOnly || input.disabled) return;

        // Note: preeti-font.css handles the font-family change on :focus automatically.
        // We just need to handle the VALUE conversion.

        // Try to retrieve Preeti text from dataset or convert back
        let preetiText = input.dataset.preetiValue;

        if (!preetiText && input.value) {
            try {
                // Heuristic: If we don't have a cached preeti value, try to convert Unicode -> Preeti
                preetiText = this.preetiConverter.toPreeti(input.value, 'preeti');
            } catch (e) {
                preetiText = input.value;
            }
        }

        if (preetiText) {
            input.value = preetiText;
        }
    }

    /**
     * Handle blur event - Convert to Unicode
     */
    handleBlur(event) {
        const input = event.target;
        const preetiText = input.value.trim();

        if (!preetiText) return;

        try {
            // Convert Preeti → Unicode
            const unicodeText = this.convertPreetiToUnicode(preetiText);

            // Store Preeti value for later editing logic
            input.dataset.preetiValue = preetiText;

            // Update display to Unicode
            // preeti-font.css removes the Preeti font when focus is lost, so this will display correctly in System font.
            input.value = unicodeText;

        } catch (error) {
            // console.error(`[PreetiForm] Conversion error: ${error.message}`);
        }
    }

    /**
     * Convert Preeti text to Unicode
     */
    convertPreetiToUnicode(text) {
        if (!this.preetiConverter) return text;
        return this.preetiConverter.toUnicode(text, 'preeti');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    window.PreetiFormHandler = new PreetiFormHandler();
    setTimeout(() => {
        window.PreetiFormHandler.init();
    }, 100);
});
