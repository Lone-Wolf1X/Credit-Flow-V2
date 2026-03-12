/**
 * Preeti Font Converter Core
 * Bidirectional converter for Preeti, Kantipur, and PCS Nepali fonts
 * Based on the original preeti.js with added Unicode-to-Preeti conversion
 */

// Import the original rules from preeti.js
var all_rules = {
    "preeti": {
        "name": "Preeti",
        "post-rules": [["्ा", ""], ["(त्र|त्त)([^उभप]+?)m", "$1m$2"], ["त्रm", "क्र"], ["त्तm", "क्त"], ["([^उभप]+?)m", "m$1"], ["उm", "ऊ"], ["भm", "झ"], ["पm", "फ"], ["इ{", "ई"], ["ि((.्)*[^्])", "$1ि"], ["(.[ािीुूृेैोौंःँ]*?){", "{$1"], ["((.्)*){", "{$1"], ["{", "र्"], ["([ाीुूृेैोौंःँ]+?)(्(.्)*[^्])", "$2$1"], ["्([ाीुूृेैोौंःँ]+?)((.्)*[^्])", "्$2$1"], ["([ंँ])([ािीुूृेैोौः]*)", "$2$1"], ["ँँ", "ँ"], ["ंं", "ं"], ["ेे", "े"], ["ैै", "ै"], ["ुु", "ु"], ["ूू", "ू"], ["^ः", ":"], ["टृ", "ट्ट"], ["ेा", "ाे"], ["ैा", "ाै"], ["अाे", "ओ"], ["अाै", "औ"], ["अा", "आ"], ["एे", "ऐ"], ["ाे", "ो"], ["ाै", "ौ"]],
        "v": "2.2.0",
        "char-map": { "÷": "/", "v": "ख", "r": "च", "\"": "ू", "~": "ञ्", "z": "श", "ç": "ॐ", "f": "ा", "b": "द", "n": "ल", "j": "व", "×": "×", "V": "ख्", "R": "च्", "ß": "द्म", "^": "६", "Û": "!", "Z": "श्", "F": "ँ", "B": "द्य", "N": "ल्", "Ë": "ङ्ग", "J": "व्", "6": "ट", "2": "द्द", "¿": "रू", ">": "श्र", ":": "स्", "§": "ट्ट", "&": "७", "£": "घ्", "•": "ड्ड", ".": "।", "«": "्र", "*": "८", "„": "ध्र", "w": "ध", "s": "क", "g": "न", "æ": "“", "c": "अ", "o": "य", "k": "प", "W": "ध्", "Ö": "=", "S": "क्", "Ò": "¨", "_": ")", "[": "ृ", "Ú": "’", "G": "न्", "ˆ": "फ्", "C": "ऋ", "O": "इ", "Î": "ङ्ख", "K": "प्", "7": "ठ", "¶": "ठ्ठ", "3": "घ", "9": "ढ", "?": "रु", ";": "स", "'": "ु", "#": "३", "¢": "द्घ", "/": "र", "+": "ं", "ª": "ङ", "t": "त", "p": "उ", "|": "्र", "x": "ह", "å": "द्व", "d": "म", "`": "ञ", "l": "ि", "h": "ज", "T": "त्", "P": "ए", "Ý": "ट्ठ", "\\": "्", "Ù": ";", "X": "ह्", "Å": "हृ", "D": "म्", "@": "२", "Í": "ङ्क", "L": "ी", "H": "ज्", "4": "द्ध", "±": "+", "0": "ण्", "<": "?", "8": "ड", "¥": "र्‍", "$": "४", "¡": "ज्ञ्", ",": ",", "©": "र", "(": "९", "‘": "ॅ", "u": "ग", "q": "त्र", "}": "ै", "y": "थ", "e": "भ", "a": "ब", "i": "ष्", "‰": "झ्", "U": "ग्", "Q": "त्त", "]": "े", "˜": "ऽ", "Y": "थ्", "Ø": "्य", "E": "भ्", "A": "ब्", "M": "ः", "Ì": "न्न", "I": "क्ष्", "5": "छ", "´": "झ", "1": "ज्ञ", "°": "ङ्ढ", "=": ".", "Æ": "”", "‹": "ङ्घ", "%": "५", "¤": "झ्", "!": "१", "-": "(", "›": "द्र", ")": "०", "…": "‘", "Ü": "%" }
    },
    "pcs nepali": {
        "name": "PCS Nepali",
        "post-rules": [["्ा", ""], ["(त्र|त्त)([^उभप]+?)m", "$1m$2"], ["त्रm", "क्र"], ["त्तm", "क्त"], ["([^उभप]+?)m", "m$1"], ["उm", "ऊ"], ["भm", "झ"], ["पm", "फ"], ["इ{", "ई"], ["ि((.्)*[^्])", "$1ि"], ["(.[ािीुूृेैोौंःँ]*?){", "{$1"], ["((.्)*){", "{$1"], ["{", "र्"], ["([ाीुूृेैोौंःँ]+?)(्(.्)*[^्])", "$2$1"], ["्([ाीुूृेैोौंःँ]+?)((.्)*[^्])", "्$2$1"], ["([ंँ])([ािीुूृेैोौः]*)", "$2$1"], ["ँँ", "ँ"], ["ंं", "ं"], ["ेे", "े"], ["ैै", "ै"], ["ुु", "ु"], ["ूू", "ू"], ["^ः", ":"], ["टृ", "ट्ट"], ["ेा", "ाे"], ["ैा", "ाै"], ["अाे", "ओ"], ["अाै", "औ"], ["अा", "आ"], ["एे", "ऐ"], ["ाे", "ो"], ["ाै", "ौ"]],
        "v": "1.0.0",
        "char-map": { "t": "त", "÷": "/", "v": "ख", "ñ": "ङ", "p": "उ", "r": "च", "|": "्र", "~": "ङ", "x": "ह", "z": "श", "å": "द्व", "d": "म", "ç": "ॐ", "f": "ा", "`": "ञ्", "b": "द", "í": "ष", "l": "ि", "n": "ल", "é": "ङ्ग", "h": "ज", "j": "व", "T": "त्", "V": "ख्", "P": "ए", "R": "च्", "\\": "्", "ß": "द्म", "^": "ट", "Ù": "ह", "X": "ह्", "Z": "श्", "D": "म्", "F": "ा", "@": "द्द", "B": "द्य", "L": "ी", "N": "ल्", "H": "ज्", "J": "व्", "4": "४", "·": "ट्ठ", "6": "६", "0": "०", "2": "२", "<": "्र", "¿": "रु", ">": "श्र", "8": "८", ":": "स्", "¥": "ऋ", "$": "द्ध", "§": "ट्ट", "&": "ठ", "¡": "ज्ञ्", "£": "घ्", "\"": "ू", ",": ",", ".": "।", "©": "?", "(": "ढ", "*": "ड", "u": "ग", "w": "ध", "q": "त्र", "s": "क", "}": "ै", "y": "थ", "ø": "य्", "ú": "ू", "e": "भ", "g": "न", "æ": "“", "a": "ब", "c": "अ", "o": "य", "i": "ष्", "k": "प", "U": "ग्", "Ô": "क्ष", "W": "ध्", "Q": "त्त", "S": "क्", "Ò": "ू", "]": "े", "_": ")", "Y": "थ्", "Ø": "्य", "[": "ृ", "E": "भ्", "G": "न्", "Æ": "”", "A": "ब्", "C": "र्‍", "M": "ः", "O": "इ", "I": "क्ष्", "K": "प्", "5": "५", "´": "झ", "7": "७", "1": "१", "°": "ङ्क", "3": "३", "=": ".", "?": "रू", "9": "९", ";": "स", "%": "छ", "¤": "ँ", "'": "ु", "!": "ज्ञ", "#": "घ", "¢": "द्घ", "-": "(", "/": "र", "®": "+", ")": "ण्", "+": "ं", "ª": "ञ" }
    },
    "kantipur": {
        "name": "Kantipur",
        "post-rules": [["्ा", ""], ["(त्र|त्त)([^उभप]+?)m", "$1m$2"], ["त्रm", "क्र"], ["त्तm", "क्त"], ["([^उभप]+?)m", "m$1"], ["उm", "ऊ"], ["भm", "झ"], ["पm", "फ"], ["इ{", "ई"], ["ि((.्)*[^्])", "$1ि"], ["(.[ािीुूृेैोौंःँ]*?){", "{$1"], ["((.्)*){", "{$1"], ["{", "र्"], ["([ाीुूृेैोौंःँ]+?)(्(.्)*[^्])", "$2$1"], ["्([ाीुूृेैोौंःँ]+?)((.्)*[^्])", "्$2$1"], ["([ंँ])([ािीुूृेैोौः]*)", "$2$1"], ["ँँ", "ँ"], ["ंं", "ं"], ["ेे", "े"], ["ैै", "ै"], ["ुु", "ु"], ["ूू", "ू"], ["^ः", ":"], ["टृ", "ट्ट"], ["ेा", "ाे"], ["ैा", "ाै"], ["अाे", "ओ"], ["अाै", "औ"], ["अा", "आ"], ["एे", "ऐ"], ["ाे", "ो"], ["ाै", "ौ"]],
        "v": "2.2.0",
        "char-map": { "÷": "/", "v": "ख", "r": "च", "\"": "ू", "~": "ञ्", "z": "श", "ç": "ॐ", "f": "ा", "b": "द", "n": "ल", "j": "व", "V": "ख्", "R": "च्", "ß": "द्म", "^": "६", "Z": "श्", "F": "ा", "B": "द्य", "Ï": "फ्", "N": "ल्", "Ë": "ङ्ग", "J": "व्", "6": "ट", "2": "द्द", "¿": "रू", ">": "श्र", ":": "स्", "§": "ट्ट", "&": "७", "£": "घ्", "•": "ड्ड", "¯": "¯", ".": "।", "«": "्र", "*": "८", "„": "ध्र", "w": "ध", "s": "क", "g": "न", "æ": "“", "c": "अ", "o": "य", "k": "प", "W": "ध्", "S": "क्", "Ò": "¨", "_": ")", "[": "ृ", "Ú": "’", "G": "न्", "Æ": "”", "C": "ऋ", "Â": "र", "O": "इ", "Î": "फ्", "K": "प्", "7": "ठ", "¶": "ठ्ठ", "3": "घ", "9": "ढ", "?": "रु", ";": "स", "º": "फ्", "'": "ु", "#": "३", "¢": "द्घ", "/": "र", "®": "र", "+": "ं", "ª": "ङ", "t": "त", "p": "उ", "|": "्र", "x": "ह", "å": "द्व", "d": "म", "`": "ञ", "l": "ि", "h": "ज", "T": "त्", "P": "ए", "Œ": "त्त्", "\\": "्", "X": "हृ", "D": "म्", "@": "२", "Í": "ङ्क", "L": "ी", "H": "ज्", "µ": "र", "4": "द्ध", "±": "+", "0": "ण्", "<": "?", "8": "ड", "¥": "र्‍", "$": "४", "¡": "ज्ञ्", "†": "!", "™": "र", "­": "(", ",": ",", "©": "र", "(": "९", "“": "ँ", "‘": "ॅ", "u": "ग", "q": "त्र", "}": "ै", "y": "थ", "ø": "य्", "e": "भ", "a": "ब", "i": "ष्", "‰": "झ्", "U": "ग्", "Ô": "क्ष", "Q": "त्त", "œ": "त्र्", "]": "े", "˜": "ऽ", "Y": "थ्", "Ø": "्य", "E": "भ्", "A": "ब्", "M": "ः", "Ì": "न्न", "I": "क्ष्", "È": "ष", "5": "छ", "´": "झ", "1": "ज्ञ", "°": "ङ्ढ", "=": ".", "‹": "ङ्घ", "%": "५", "¤": "झ्", "!": "१", "-": "(", "¬": "…", "›": "ऽ", ")": "०", "¨": "ङ्ग", "…": "‘" }
    }
};

var PreetiConverterCore = (function () {
    'use strict';

    /**
     * Convert Preeti/Kantipur/PCS font text to Unicode
     * @param {string} text - Text in Preeti font format
     * @param {string} font - Font type: 'preeti', 'kantipur', or 'pcs nepali'
     * @returns {string} Unicode text
     */
    function toUnicode(text, font) {
        // get font rules - default to Preeti
        if (!font) {
            font = 'Preeti';
        }
        font = font.toLowerCase();
        var myFont = all_rules[font];
        if (!myFont) {
            throw 'font not included in module';
        }

        var output = '';
        for (var w = 0; w < text.length; w++) {
            var letter = text[w];
            output += myFont['char-map'][letter] || letter;
        }
        for (var r = 0; r < myFont['post-rules'].length; r++) {
            output = output.replace(new RegExp(myFont['post-rules'][r][0], 'g'), myFont['post-rules'][r][1]);
        }
        return output;
    }

    /**
     * Convert Unicode text to Preeti/Kantipur/PCS font format
     * @param {string} text - Unicode Nepali text
     * @param {string} font - Font type: 'preeti', 'kantipur', or 'pcs nepali'
     * @returns {string} Text in Preeti font format
     */
    function toPreeti(text, font) {
        if (!font) {
            font = 'Preeti';
        }
        font = font.toLowerCase();
        var myFont = all_rules[font];
        if (!myFont) {
            throw 'font not included in module';
        }

        // Create reverse mapping
        var reverseMap = {};
        for (var key in myFont['char-map']) {
            var value = myFont['char-map'][key];
            // For multiple keys mapping to same value, prefer simpler characters
            if (!reverseMap[value] || key.length < reverseMap[value].length) {
                reverseMap[value] = key;
            }
        }

        // Apply reverse post-processing rules (in reverse order)
        var output = text;
        for (var j = myFont['post-rules'].length - 1; j >= 0; j--) {
            var rule = myFont['post-rules'][j];
            // Reverse the rule (swap pattern and replacement)
            if (rule[1]) { // Only if replacement is not empty
                output = output.replace(new RegExp(rule[1].replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), rule[0]);
            }
        }

        // Character-by-character reverse mapping
        var result = '';
        var i = 0;
        while (i < output.length) {
            var matched = false;
            // Try to match longer sequences first (up to 4 characters for conjuncts)
            for (var len = 4; len >= 1; len--) {
                if (i + len <= output.length) {
                    var substr = output.substring(i, i + len);
                    if (reverseMap[substr]) {
                        result += reverseMap[substr];
                        i += len;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched) {
                result += output[i];
                i++;
            }
        }

        return result;
    }

    /**
     * Get list of supported fonts
     * @returns {Array} Array of font information objects
     */
    function getSupportedFonts() {
        var fonts = [];
        for (var key in all_rules) {
            fonts.push({
                id: key,
                name: all_rules[key].name,
                version: all_rules[key].v
            });
        }
        return fonts;
    }

    /**
     * Get character mapping for a specific font
     * @param {string} font - Font type
     * @returns {Object} Character mapping object
     */
    function getCharMap(font) {
        if (!font) {
            font = 'preeti';
        }
        font = font.toLowerCase();
        var myFont = all_rules[font];
        if (!myFont) {
            throw 'font not included in module';
        }
        return myFont['char-map'];
    }

    // Public API
    return {
        toUnicode: toUnicode,
        toPreeti: toPreeti,
        getSupportedFonts: getSupportedFonts,
        getCharMap: getCharMap,
        version: '2.0.0'
    };
})();

// Export for Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PreetiConverterCore;
}

// Export for browser
if (typeof window !== 'undefined') {
    window.PreetiConverterCore = PreetiConverterCore;
}
