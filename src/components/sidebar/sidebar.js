export const Sidebar = {
    init() {
        this.hideActiveLink();
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