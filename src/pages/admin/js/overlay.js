import { AdminAPI } from './api.js';
import { ADMIN_FEATURES } from './features/index.js';

export const AdminOverlay = {
    isOpen: false,
    data: {
        holidays: [],
        access: {},
        groups: [],
        users: []
    },
    features: [],
    featureContexts: {},
    activeFeatureId: '',

    init() {
        const overlay = document.getElementById('admin-overlay');
        if (!overlay) return;

        this.features = [...ADMIN_FEATURES].sort((a, b) => (a.order || 999) - (b.order || 999));
        this.renderFeatureShell();
        this.initFeatureModules();

        document.getElementById('admin-overlay-close')?.addEventListener('click', () => this.close());
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.close();
        });
        overlay.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    },

    renderFeatureShell() {
        const menuRoot = document.getElementById('admin-menu-root');
        const sectionsRoot = document.getElementById('admin-sections-root');
        if (!menuRoot || !sectionsRoot) return;

        if (!this.features.length) {
            menuRoot.innerHTML = '';
            sectionsRoot.innerHTML = '<div class="admin-placeholder">No admin features are installed.</div>';
            return;
        }

        menuRoot.innerHTML = this.features.map((feature, index) => `
            <button type="button" class="admin-menu-btn ${index === 0 ? 'active' : ''}" data-admin-section="${feature.id}">${feature.label}</button>
        `).join('');

        sectionsRoot.innerHTML = this.features.map((feature, index) => `
            <section id="admin-section-${feature.id}" class="admin-section" style="${index === 0 ? '' : 'display:none;'}"></section>
        `).join('');

        this.activeFeatureId = this.features[0].id;

        menuRoot.querySelectorAll('.admin-menu-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const sectionId = btn.dataset.adminSection;
                this.activateFeature(sectionId);
            });
        });
    },

    initFeatureModules() {
        this.featureContexts = {};
        this.features.forEach((feature) => {
            const root = document.getElementById(`admin-section-${feature.id}`);
            if (!root) return;
            if (typeof feature.render === 'function') {
                root.innerHTML = feature.render();
            }

            const ctx = {
                overlay: this,
                root,
                api: AdminAPI,
                close: () => this.close(),
                requestReload: async () => this.reload(),
                refetchCalendar: () => window.TimeclockManager?.CalendarInstance?.refetchEvents?.()
            };
            this.featureContexts[feature.id] = ctx;

            if (typeof feature.init === 'function') {
                feature.init(ctx);
            }
        });
    },

    activateFeature(featureId) {
        if (!featureId) return;
        this.activeFeatureId = featureId;
        document.querySelectorAll('#admin-menu-root .admin-menu-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.adminSection === featureId);
        });
        document.querySelectorAll('#admin-sections-root .admin-section').forEach((section) => {
            section.style.display = section.id === `admin-section-${featureId}` ? '' : 'none';
        });

        const feature = this.features.find((f) => f.id === featureId);
        const ctx = this.featureContexts[featureId];
        if (feature && ctx && typeof feature.onActivate === 'function') {
            feature.onActivate(ctx);
        }
    },

    async open() {
        const overlay = document.getElementById('admin-overlay');
        if (!overlay) return;

        const analysisOverlay = window.TimeclockManager?.Modules?.AnalysisOverlay;
        analysisOverlay?.close?.();

        overlay.style.display = 'flex';
        this.isOpen = true;
        this.setSidebarActive(true);
        await this.reload();
    },

    close() {
        const overlay = document.getElementById('admin-overlay');
        if (!overlay) return;
        overlay.style.display = 'none';
        this.isOpen = false;
        this.setSidebarActive(false);
    },

    toggle() {
        if (this.isOpen) this.close();
        else this.open();
    },

    setSidebarActive(active) {
        const link = document.getElementById('nav-link-admin');
        if (link) link.classList.toggle('active', !!active);
    },

    async reload() {
        try {
            const data = await AdminAPI.getBootstrap();
            this.data = {
                holidays: Array.isArray(data?.holidays) ? data.holidays : [],
                access: data?.access || {},
                groups: Array.isArray(data?.groups) ? data.groups : [],
                users: Array.isArray(data?.users) ? data.users : []
            };

            this.features.forEach((feature) => {
                const ctx = this.featureContexts[feature.id];
                if (!ctx || typeof feature.refresh !== 'function') return;
                feature.refresh(ctx);
            });
        } catch (e) {
            console.error('Failed to load admin overlay data', e);
        }
    }
};
