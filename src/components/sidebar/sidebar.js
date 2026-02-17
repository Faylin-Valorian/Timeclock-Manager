export const Sidebar = {
    init() {
        this.hideActiveLink();
        this.applyAccessVisibility();
    },

    hideActiveLink() {
        const nav = document.getElementById('app-navigation');
        if (!nav) return;

        const mode = nav.dataset.mode; // e.g. 'calendar', 'admin'
        let linkId = null;

        switch (mode) {
            case 'calendar':
                linkId = 'nav-link-calendar';
                break;
            case 'admin':
                linkId = 'nav-link-admin';
                break;
            case 'analysis':
                linkId = 'nav-link-analysis';
                break;
        }

        if (linkId) {
            const link = document.getElementById(linkId);
            // Hide the entire list item (li) that contains the link
            if (link && link.parentElement.tagName === 'LI') {
                link.parentElement.style.display = 'none';
            }
        }
    },

    async applyAccessVisibility() {
        try {
            const client = window.TimeclockManager?.Client;
            if (!client) return;
            const data = await client.request('GET', '/api/admin/access/me');

            const adminLink = document.getElementById('nav-link-admin')?.parentElement;
            const analysisLink = document.getElementById('nav-link-analysis')?.parentElement;
            const archiveBtn = document.getElementById('toggle-archive-view');

            if (adminLink) adminLink.style.display = data?.admin_nav ? '' : 'none';
            if (analysisLink) analysisLink.style.display = data?.analysis_nav ? '' : 'none';
            if (archiveBtn) archiveBtn.style.display = data?.archive_toggle ? '' : 'none';
        } catch (e) {
            // Keep default UI if permissions endpoint is unavailable.
        }
    }
};

// [NEW] Attach to Global Window so Webpack preserves the code
window.TimeclockManager = window.TimeclockManager || {};
window.TimeclockManager.Sidebar = Sidebar;

// [OPTIONAL] Auto-init if this script is loaded standalone (not via another module)
document.addEventListener('DOMContentLoaded', () => {
    // Check if we are not inside the Calendar app (which inits it manually)
    if (!window.TimeclockManager.CalendarInstance) {
        Sidebar.init();
    }
});
