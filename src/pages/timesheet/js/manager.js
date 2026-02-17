import { RowCalculator } from './calculator.js';
import { RowRenderer } from './renderer.js';

export const RowManager = {
    containerId: 'work-rows-container',
    jobList: [],

    init() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Listener: Remove Row
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-remove-row') || e.target.closest('.btn-remove-row')) {
                e.preventDefault();
                const btn = e.target.classList.contains('btn-remove-row') ? e.target : e.target.closest('.btn-remove-row');
                btn.closest('.work-row').remove();
                this.updateBalances(null); // Recalculate even split
            }
        });

        // Listener: Input Change (Recalculate Percentages)
        container.addEventListener('input', (e) => {
            if (e.target.classList.contains('work-percent')) {
                this.updateBalances(e.target);
            }
        });

        // Keep values clamped even if browser applies value on blur/change only
        container.addEventListener('change', (e) => {
            if (e.target.classList.contains('work-percent')) {
                this.updateBalances(e.target);
            }
        });
    },

    setJobs(jobs) {
        this.jobList = jobs || [];
    },

    add(desc = '', percent = 0, isUserAction = false) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        const row = RowRenderer.createRow(this.jobList, desc, percent);
        container.appendChild(row);

        if (isUserAction) {
            this.updateBalances(null);
        }
    },

    clear() {
        RowRenderer.clearContainer(this.containerId);
    },

    getAll() {
        return RowRenderer.getValues(this.containerId);
    },

    updateBalances(sourceInput = null) {
        const allInputs = Array.from(document.querySelectorAll(`#${this.containerId} .work-percent`));
        if (allInputs.length === 0) return;

        if (!sourceInput) {
            // Mode 1: Distribute Evenly
            const values = RowCalculator.distributeEvenly(allInputs.length);
            allInputs.forEach((input, index) => {
                input.value = values[index];
            });
        } else {
            // Mode 2: User Edited One Field
            const result = RowCalculator.rebalance(sourceInput.value, allInputs.length);
            
            // Update the source input (clamped value)
            sourceInput.value = result.userValue;

            // Update others
            const otherInputs = allInputs.filter(i => i !== sourceInput);
            otherInputs.forEach((input, index) => {
                input.value = result.others[index];
            });
        }
    }
}
