import { Sidebar } from 'src/components/sidebar/sidebar.js';
import { AnalysisAPI } from './js/api.js';
import { Popup } from 'src/components/popup/popup.js';

const formatHours = (v) => `${Number(v || 0).toFixed(2)}h`;
const formatMoney = (v) => `$${Number(v || 0).toFixed(2)}`;
const formatInt = (v) => `${parseInt(v || 0, 10) || 0}`;

const AnalysisModule = {
    detailMetricConfig: {
        'metric-total-hours': { metric: 'total_hours', title: 'Total Hours by Job' },
        'metric-pto-hours': { metric: 'pto_hours', title: 'PTO Dates and Hours' },
        'metric-overtime-hours': { metric: 'overtime_hours', title: 'Overtime Hours by Job' },
        'metric-per-diem-requests': { metric: 'per_diem_requests', title: 'Per Diem Request Dates' },
        'metric-road-scanning-days': { metric: 'road_scanning_days', title: 'Road Scanning Dates' },
        'metric-overnight-days': { metric: 'overnight_days', title: 'Overnight Dates' }
    },

    state: {
        range: 'weekly',
        start: '',
        end: ''
    },

    init() {
        Sidebar.init();
        this.render();
        this.bindNavLinks();
        this.bindDblClickGuards();
        this.applyPresetRange(this.state.range);
        this.loadSummary();
    },

    bindNavLinks() {
        const links = {
            'nav-link-calendar': '/',
            'nav-link-analysis': '/analysis',
            'nav-link-admin': '/admin'
        };

        Object.keys(links).forEach((id) => {
            const link = document.getElementById(id);
            if (!link) return;
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const route = links[id];
                if (window.OC && window.OC.generateUrl) {
                    window.location.href = window.OC.generateUrl('/apps/timeclock-manager' + route);
                } else {
                    window.location.href = '/index.php/apps/timeclock-manager' + route;
                }
            });
        });
    },

    bindDblClickGuards() {
        const nav = document.getElementById('app-navigation');
        if (!nav) return;
        nav.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, true);
    },

    render() {
        const root = document.getElementById('analysis-root');
        if (!root) return;

        root.innerHTML = `
            <section class="analysis-shell">
                <header class="analysis-header">
                    <div>
                        <h1>Time Analysis</h1>
                        <p>Operational summary across hours, travel, and requests.</p>
                    </div>
                    <div class="analysis-filters">
                        <select id="analysis-range" class="analysis-control">
                            <option value="weekly">Weekly</option>
                            <option value="biweekly">Bi-Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="custom">Custom</option>
                        </select>
                        <input id="analysis-start" type="date" class="analysis-control" disabled>
                        <input id="analysis-end" type="date" class="analysis-control" disabled>
                    </div>
                </header>

                <div class="analysis-groups">
                    ${this.group('Hours', [
                        this.card('Total Hours', 'metric-total-hours'),
                        this.card('PTO Hours', 'metric-pto-hours'),
                        this.card('Overtime Hours', 'metric-overtime-hours')
                    ])}
                    ${this.group('Travel', [
                        this.card('Per Diem Requests', 'metric-per-diem-requests'),
                        this.card('Road Scanning Days', 'metric-road-scanning-days'),
                        this.card('Overnight Days', 'metric-overnight-days'),
                        this.card('Total Miles', 'metric-total-miles')
                    ])}
                    ${this.group('Extra Expenses', [
                        this.card('Extra Expenses', 'metric-extra-expenses')
                    ], true)}
                </div>
            </section>
        `;

        const range = root.querySelector('#analysis-range');
        const startEl = root.querySelector('#analysis-start');
        const endEl = root.querySelector('#analysis-end');
        range?.addEventListener('change', () => {
            this.state.range = range.value;
            this.applyPresetRange(this.state.range);
            this.loadSummary();
        });
        startEl?.addEventListener('change', () => this.loadSummary());
        endEl?.addEventListener('change', () => this.loadSummary());
        this.bindDetailClicks();
    },

    card(label, id) {
        return `
            <article class="analysis-card">
                <span class="analysis-label">${label}</span>
                <strong id="${id}" class="analysis-value">--</strong>
            </article>
        `;
    },

    group(title, cards, single = false) {
        return `
            <section class="analysis-group">
                <h3>${title}</h3>
                <div class="analysis-grid ${single ? 'analysis-grid-single' : ''}">
                    ${cards.join('')}
                </div>
            </section>
        `;
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
            const card = valueEl.closest('.analysis-card');
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

document.addEventListener('DOMContentLoaded', () => AnalysisModule.init());
