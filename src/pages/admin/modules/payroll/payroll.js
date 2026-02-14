import { StechAPI } from '../../../../api/api.js';

export const PayrollAdmin = {
    settings: {},

    load() {
        console.log("Loading Payroll Module...");
        this.bindEvents();
        this.fetchSettings();
    },

    bindEvents() {
        if (this._eventsBound) return;

        // Frequency Toggle
        document.getElementById('pay-frequency')?.addEventListener('change', (e) => {
            this.toggleOptions(e.target.value);
        });

        // Color Sync
        const colorInput = document.getElementById('pay-color');
        const colorText = document.getElementById('pay-color-text');
        
        if (colorInput && colorText) {
            colorInput.addEventListener('input', (e) => colorText.value = e.target.value);
            colorText.addEventListener('input', (e) => colorInput.value = e.target.value);
        }

        // Save Button
        document.getElementById('btn-save-payroll')?.addEventListener('click', () => this.save());

        this._eventsBound = true;
    },

    toggleOptions(val) {
        const standard = document.getElementById('freq-standard-options');
        const custom = document.getElementById('freq-custom-options');
        
        if (val === 'custom_twice') {
            standard.classList.add('hidden');
            custom.classList.remove('hidden');
        } else {
            standard.classList.remove('hidden');
            custom.classList.add('hidden');
        }
    },

    async fetchSettings() {
        try {
            const data = await StechAPI.admin.getSettings();
            this.settings = data || {};
            this.populateForm();
        } catch (e) {
            console.error("Failed to load payroll settings", e);
        }
    },

    populateForm() {
        if (this.settings.pay_frequency) {
            document.getElementById('pay-frequency').value = this.settings.pay_frequency;
            this.toggleOptions(this.settings.pay_frequency);
        }
        if (this.settings.pay_start_date) document.getElementById('pay-start-date').value = this.settings.pay_start_date;
        if (this.settings.pay_color) {
            document.getElementById('pay-color').value = this.settings.pay_color;
            document.getElementById('pay-color-text').value = this.settings.pay_color;
        }
        
        // Custom dates (if stored as json or separate fields)
        if (this.settings.pay_date_1) document.getElementById('pay-date-1').value = this.settings.pay_date_1;
        if (this.settings.pay_date_2) document.getElementById('pay-date-2').value = this.settings.pay_date_2;
    },

    async save() {
        const payload = {
            pay_frequency: document.getElementById('pay-frequency').value,
            pay_start_date: document.getElementById('pay-start-date').value,
            pay_color: document.getElementById('pay-color').value,
            pay_date_1: document.getElementById('pay-date-1').value,
            pay_date_2: document.getElementById('pay-date-2').value
        };

        try {
            await StechAPI.admin.saveSettings(payload);
            const msg = document.getElementById('payroll-msg');
            msg.style.display = 'inline';
            setTimeout(() => msg.style.display = 'none', 3000);
        } catch (e) {
            alert("Error saving settings.");
        }
    }
};