const escapeHtml = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const UsersFeature = {
    id: 'users',
    label: 'Manage User',
    order: 10,

    render() {
        return `
            <div class="admin-toolbar">
                <input id="admin-user-search" class="admin-control" type="text" placeholder="Search users...">
            </div>
            <div id="admin-user-list" class="admin-user-list"></div>
        `;
    },

    init(ctx) {
        ctx.root.querySelector('#admin-user-search')?.addEventListener('input', () => this.renderUsers(ctx));
    },

    refresh(ctx) {
        this.renderUsers(ctx);
    },

    renderUsers(ctx) {
        const root = ctx.root.querySelector('#admin-user-list');
        if (!root) return;

        const query = (ctx.root.querySelector('#admin-user-search')?.value || '').trim().toLowerCase();
        const users = (ctx.overlay.data.users || []).filter((u) => {
            const name = String(u.display_name || '').toLowerCase();
            const uid = String(u.uid || '').toLowerCase();
            if (!query) return true;
            return name.includes(query) || uid.includes(query);
        });

        if (users.length === 0) {
            root.innerHTML = '<div class="admin-list-empty">No users found.</div>';
            return;
        }

        root.innerHTML = users.map((u) => `
            <button type="button" class="admin-user-card" data-uid="${escapeHtml(u.uid)}" data-name="${escapeHtml(u.display_name || '')}">
                <span class="admin-user-name">${escapeHtml(u.display_name || u.uid)}</span>
                <span class="admin-user-id">${escapeHtml(u.uid)}</span>
            </button>
        `).join('');

        root.querySelectorAll('.admin-user-card').forEach((btn) => {
            btn.addEventListener('click', () => {
                const uid = btn.dataset.uid || '';
                const name = btn.dataset.name || uid;
                if (!uid) return;
                window.TimeclockManager?.Impersonation?.set?.(uid, name);
                ctx.close();
                ctx.refetchCalendar();
            });
        });
    }
};

export default UsersFeature;

