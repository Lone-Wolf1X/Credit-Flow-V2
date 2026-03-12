/**
 * Nepali Date Converter (Frontend Version)
 * Mirrors logic from NepaliDateConverter.php for accuracy
 * Reference: 2070-01-01 BS = 2013-04-14 AD
 */

const NepaliDateConverter = (function () {

    // Calendar Data: Days in Months for BS Years (2070-2085)
    // Same source as PHP version
    const calendarData = {
        2070: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2071: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2072: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2073: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2074: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2075: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2076: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2077: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2078: [31, 32, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2079: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2080: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2081: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2082: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2083: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2084: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2085: [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30]
    };

    // Base Reference: 2070-01-01 BS = 2013-04-14 AD
    const baseAdDate = new Date(2013, 3, 14); // Month is 0-indexed: April = 3
    const baseBsYear = 2070;

    /**
     * Convert BS to AD
     * @param {string} bsDate YYYY-MM-DD
     * @returns {string} YYYY-MM-DD
     */
    function toAD(bsDate) {
        if (!bsDate) return '';
        const parts = bsDate.split('-');
        if (parts.length !== 3) return '';

        let bsYear = parseInt(parts[0]);
        let bsMonth = parseInt(parts[1]);
        let bsDay = parseInt(parts[2]);

        if (!calendarData[bsYear]) return ''; // Out of range

        let totalDays = 0;

        // Add days for full years
        for (let y = baseBsYear; y < bsYear; y++) {
            const daysInYear = calendarData[y].reduce((a, b) => a + b, 0);
            totalDays += daysInYear;
        }

        // Add days for full months
        const currentYearMonths = calendarData[bsYear];
        for (let m = 0; m < bsMonth - 1; m++) {
            totalDays += currentYearMonths[m];
        }

        // Add days in current month
        totalDays += (bsDay - 1);

        // Calculate AD Date
        const resultDate = new Date(baseAdDate);
        resultDate.setDate(resultDate.getDate() + totalDays);

        const y = resultDate.getFullYear();
        const m = String(resultDate.getMonth() + 1).padStart(2, '0');
        const d = String(resultDate.getDate()).padStart(2, '0');

        return `${y}-${m}-${d}`;
    }

    /**
     * Convert AD to BS
     * @param {string} adDate YYYY-MM-DD
     * @returns {string} YYYY-MM-DD
     */
    function toBS(adDate) {
        if (!adDate) return '';
        const targetAdDate = new Date(adDate);
        const baseDate = new Date(baseAdDate);

        // Difference in days
        const diffTime = targetAdDate - baseDate;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays < 0) return ''; // Before reference date (not handled for simplicity)

        let daysRemaining = diffDays;
        let bsYear = baseBsYear;
        let bsMonth = 1;

        // Subtract Years
        while (true) {
            if (!calendarData[bsYear]) break; // Out of range
            const daysInYear = calendarData[bsYear].reduce((a, b) => a + b, 0);
            if (daysRemaining < daysInYear) break;
            daysRemaining -= daysInYear;
            bsYear++;
        }

        // Subtract Months
        const months = calendarData[bsYear];
        for (let i = 0; i < months.length; i++) {
            if (daysRemaining < months[i]) {
                bsMonth = i + 1;
                break;
            }
            daysRemaining -= months[i];
        }

        const bsDay = daysRemaining + 1;

        return `${bsYear}-${String(bsMonth).padStart(2, '0')}-${String(bsDay).padStart(2, '0')}`;
    }

    return {
        toAD: toAD,
        toBS: toBS
    };

})();
