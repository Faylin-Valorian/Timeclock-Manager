import { AnalysisAPI } from './api.js';
import { Popup } from 'src/components/popup/popup.js';

const formatHours = (v) => `${Number(v || 0).toFixed(2)}h`;
const formatMoney = (v) => `$${Number(v || 0).toFixed(2)}`;
const formatInt = (v) => `${parseInt(v || 0, 10) || 0}`;

export const AnalysisOverlay = {
    isOpen: false,
    detailMetricConfig: {
        'metric-total-hours': { metric: 'total_hours', title: 'Total Hours by Job' },
        'metric-pto-hours': { metric: 'pto_hours', title: 'PTO Dates and Hours' },
        'metric-overtime-hours': { metric: 'overtime_hours', title: 'Overtime Hours by Job' },
        'metric-per-diem-requests': { metric: 'per_diem_requests', title: 'Per Diem Request Dates' },
        'metric-road-scanning-days': { metric: 'road_scanning_days', title: 'Road Scanning Dates' },
        'metric-overnight-days': { metric: 'overnight_days', title: 'Overnight Dates' }
    },

    init() {
        const overlay = document.getElementById('analysis-overlay');
        if (!overlay) return;

        document.getElementById('analysis-overlay-close')?.addEventListener('click', () => this.close());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.close();
        });
        overlay.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });

        const range = document.getElementById('analysis-range');
        const startEl = document.getElementById('analysis-start');
        const endEl = document.getElementById('analysis-end');
        range?.addEventListener('change', () => {
            this.applyPresetRange(range.value);
            this.loadSummary();
        });
        startEl?.addEventListener('change', () => this.loadSummary());
        endEl?.addEventListener('change', () => this.loadSummary());

        this.bindDetailClicks();
        this.applyPresetRange('weekly');
    },

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    },

    open() {
        const overlay = document.getElementById('analysis-overlay');
        if (!overlay) return;

        const adminOverlay = window.TimeclockManager?.Modules?.AdminOverlay;
        adminOverlay?.close?.();

        overlay.style.display = 'flex';
        this.isOpen = true;
        this.setSidebarActive(true);
        this.loadSummary();
    },

    close() {
        const overlay = document.getElementById('analysis-overlay');
        if (!overlay) return;
        overlay.style.display = 'none';
        this.isOpen = false;
        this.setSidebarActive(false);
    },

    setSidebarActive(active) {
        const link = document.getElementById('nav-link-analysis');
        if (link) link.classList.toggle('active', !!active);
    },

    applyPresetRange(preset) {
        const startEl = document.getElementById('analysis-start');
        const endEl = document.getElementById('analysis-end');
        if (!startEl || !endEl) return;

        const now = new Date();
        const end = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const start = new Date(end);

        if (preset === 'weekly') start.setDate(end.getDate() - 6);
        if (preset === 'biweekly') start.setDate(end.getDate() - 13);
        if (preset === 'monthly') start.setDate(1);

        const isCustom = preset === 'custom';
        startEl.disabled = !isCustom;
        endEl.disabled = !isCustom;

        if (!isCustom) {
            startEl.value = this.toISO(start);
            endEl.value = this.toISO(end);
        } else {
            if (!startEl.value) startEl.value = this.toISO(start);
            if (!endEl.value) endEl.value = this.toISO(end);
        }
    },

    toISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    },

    async loadSummary() {
        const start = document.getElementById('analysis-start')?.value;
        const end = document.getElementById('analysis-end')?.value;
        if (!start || !end) return;

        try {
            const data = await AnalysisAPI.getSummary(start, end);
            this.setMetric('metric-total-hours', formatHours(data.total_hours));
            this.setMetric('metric-pto-hours', formatHours(data.pto_hours));
            this.setMetric('metric-overtime-hours', formatHours(data.overtime_hours));
            this.setMetric('metric-per-diem-requests', formatInt(data.per_diem_requests));
            this.setMetric('metric-road-scanning-days', formatInt(data.road_scanning_days));
            this.setMetric('metric-overnight-days', formatInt(data.overnight_days));
            this.setMetric('metric-total-miles', formatInt(data.total_miles));
            this.setMetric('metric-extra-expenses', formatMoney(data.extra_expenses));
        } catch (e) {
            console.error('Failed to load analysis summary', e);
        }
    },

    bindDetailClicks() {
        Object.keys(this.detailMetricConfig).forEach((id) => {
            const valueEl = document.getElementById(id);
            if (!valueEl) return;
            const card = valueEl.closest('.analysis-overlay-item');
            const target = card || valueEl;
            target.style.cursor = 'pointer';
            target.title = 'Click to view details';
            target.addEventListener('click', () => this.openDetail(id));
        });
    },

    async openDetail(metricElementId) {
        const cfg = this.detailMetricConfig[metricElementId];
        if (!cfg) return;

        const start = document.getElementById('analysis-start')?.value;
        const end = document.getElementById('analysis-end')?.value;
        if (!start || !end) return;

        try {
            const data = await AnalysisAPI.getDetail(cfg.metric, start, end);
            const items = this.formatDetailItems(data);
            await Popup.detailList(`${cfg.title} (${start} to ${end})`, items);
        } catch (e) {
            console.error('Failed to load analysis detail', e);
            await Popup.alert('Unable to load analysis detail right now.', 'Analysis Detail', 'error');
        }
    },

    formatDetailItems(data) {
        if (!data || !Array.isArray(data.items)) return [];
        if (data.type === 'jobs') {
            return data.items.map((item) => `${item.label}: ${Number(item.hours || 0).toFixed(2)}h`);
        }
        if (data.type === 'date_hours') {
            return data.items.map((item) => `${item.date}: ${Number(item.hours || 0).toFixed(2)}h`);
        }
        return data.items.map((d) => String(d));
    },

    setMetric(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }
};
