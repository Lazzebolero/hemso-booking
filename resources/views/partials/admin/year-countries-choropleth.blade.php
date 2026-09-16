{{-- Delad färgläggning för landskartor (jsVectorMap). --}}
<script>
window.HemsoCountryChoropleth = (function () {
    const SCALE = ['#dbeafe', '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8'];

    /**
     * Rangordnar länder efter vikt så färgskalan sprids jämnt.
     * Absolut skeva värden (1 vs 200) kollapsar annars till två nyanser.
     *
     * @param {Record<string, number>} rawValues
     * @returns {Record<string, number>}
     */
    function toSeriesValues(rawValues) {
        const entries = Object.entries(rawValues)
            .map(([code, weight]) => [code, Number(weight) || 0])
            .filter(([, weight]) => weight > 0)
            .sort((a, b) => a[1] - b[1] || String(a[0]).localeCompare(String(b[0])));

        const values = {};
        let rank = 0;
        let previousWeight = null;

        entries.forEach(([code, weight]) => {
            if (weight !== previousWeight) {
                rank += 1;
                previousWeight = weight;
            }
            values[code] = rank;
        });

        return values;
    }

    return {
        scale: SCALE,
        normalizeFunction: 'linear',
        toSeriesValues: toSeriesValues,
    };
})();
</script>
