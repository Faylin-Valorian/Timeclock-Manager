/**
 * Sidebar Logic
 * Handles module switching and date controls.
 */
export const Sidebar = {
    init(options = {}) {
        this.bindAdminNav(options.onModuleChange);
    },

    bindAdminNav(callback) {
        // Listen for clicks on any nav item with data-module
        const navItems = document.querySelectorAll('.nav-item[data-module]');
        
        navItems.forEach(item => {
            const link = item.querySelector('a');
            if (link) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update Active UI
                    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
                    link.classList.add('active');

                    // Trigger Callback with module key (e.g., 'payroll')
                    const moduleKey = item.getAttribute('data-module');
                    if (typeof callback === 'function') {
                        callback(moduleKey);
                    }
                });
            }
        });
    }
};