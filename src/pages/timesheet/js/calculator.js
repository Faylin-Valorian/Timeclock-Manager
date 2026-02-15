export const RowCalculator = {
    /**
     * Distributes 100% evenly across a number of items.
     */
    distributeEvenly(count) {
        if (count === 0) return [];
        const base = Math.floor(100 / count);
        let remainder = 100 % count;
        
        // Return array of integers summing to 100
        return Array(count).fill(0).map(() => {
            const val = base + (remainder > 0 ? 1 : 0);
            remainder--;
            return val;
        });
    },

    /**
     * Recalculates other values when a user manually edits one input.
     */
    rebalance(userValue, totalInputsCount) {
        // Clamp user value
        let val = parseInt(userValue) || 0;
        if (val < 0) val = 0;
        if (val > 100) val = 100;

        const remainingTotal = 100 - val;
        const othersCount = totalInputsCount - 1;

        if (othersCount <= 0) return { userValue: val, others: [] };

        const base = Math.floor(remainingTotal / othersCount);
        let remainder = remainingTotal % othersCount;

        const others = Array(othersCount).fill(0).map(() => {
            const v = base + (remainder > 0 ? 1 : 0);
            remainder--;
            return v;
        });

        return { userValue: val, others };
    }
};