const ALL_RULE_LABELS = {
    admin_access: 'Admin Panel Navigation',
    analysis_tab: 'Analysis Navigation',
    view_archive_toggle: 'Archive Filter Button',
    admin_holidays: 'Manage Holidays Section',
    admin_users: 'Manage Users Section'
};
const DEVELOPER_GROUP = 'Developer';

const AccessFeature = {
    id: 'access',
    label: 'Manage Access',
    order: 30,
    state: {
        selectedGroup: '',
        query: ''
    },

    render() {
        return `
            <div class="admin-toolbar">
                <input id="access-group-search" class="admin-control" type="text" placeholder="Search groups...">
            </div>
            <p class="admin-user-hint">Select a group to configure what features that group can access.</p>
            <div class="admin-access-layout">
                <div id="access-group-list" class="admin-user-list"></div>
                <div id="access-permissions-panel" class="admin-editor"></div>
            </div>
        `;
    },

    init(ctx) {
        ctx.root.querySelector('#access-group-search')?.addEventListener('input', (e) => {
            this.state.query = String(e.target.value || '').trim().toLowerCase();
            this.renderAccessPanel(ctx);
        });
    },

    refresh(ctx) {
        this.renderAccessPanel(ctx);
    },

    getActiveRuleLabels(ctx) {
        const availableFeatureIds = (ctx.overlay.features || []).map((f) => f.id);
        const activeRuleLabels = { ...ALL_RULE_LABELS };
        if (!availableFeatureIds.includes('holidays')) delete activeRuleLabels.admin_holidays;
        if (!availableFeatureIds.includes('users')) delete activeRuleLabels.admin_users;
        return activeRuleLabels;
    },

    renderAccessPanel(ctx) {
        const groupList = ctx.root.querySelector('#access-group-list');
        const panel = ctx.root.querySelector('#access-permissions-panel');
        if (!groupList || !panel) return;

        const groups = [...(ctx.overlay.data.groups || [])].sort((a, b) => String(a).localeCompare(String(b)));
        const filteredGroups = groups.filter((gid) => {
            if (!this.state.query) return true;
            return String(gid).toLowerCase().includes(this.state.query);
        });

        if (!this.state.selectedGroup || !groups.includes(this.state.selectedGroup)) {
            this.state.selectedGroup = groups[0] || '';
        }

        if (filteredGroups.length === 0) {
            groupList.innerHTML = '<div class="admin-list-empty">No groups found.</div>';
        } else {
            groupList.innerHTML = filteredGroups.map((gid) => `
                <button type="button" class="admin-user-card access-group-card ${this.state.selectedGroup === gid ? 'active' : ''}" data-group="${gid}">
                    <span class="admin-user-name">${gid}</span>
                    <span class="admin-user-id">Group</span>
                </button>
            `).join('');
        }

        groupList.querySelectorAll('.access-group-card').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.state.selectedGroup = btn.dataset.group || '';
                this.renderAccessPanel(ctx);
            });
        });

        if (!this.state.selectedGroup) {
            panel.innerHTML = '<div class="admin-list-empty">No groups available.</div>';
            return;
        }

        const activeRuleLabels = this.getActiveRuleLabels(ctx);
        const rules = Object.keys(activeRuleLabels);
        const isDeveloper = String(this.state.selectedGroup).toLowerCase() === DEVELOPER_GROUP.toLowerCase();

        panel.innerHTML = `
            <div class="access-panel-header">
                <div class="access-panel-title">${this.state.selectedGroup}</div>
                <div class="access-panel-subtitle">Feature Access</div>
            </div>
            <div class="admin-access-rules">
                ${rules.map((ruleKey) => {
                    const selected = Array.isArray(ctx.overlay.data.access[ruleKey]) ? ctx.overlay.data.access[ruleKey] : [];
                    const checked = isDeveloper || selected.includes(this.state.selectedGroup);
                    return `
                        <article class="access-rule-card">
                            <label class="access-permission-row">
                                <span class="access-rule-title">${activeRuleLabels[ruleKey]}</span>
                                <input
                                    type="checkbox"
                                    class="access-rule-toggle"
                                    data-rule="${ruleKey}"
                                    ${checked ? 'checked' : ''}
                                    ${isDeveloper ? 'disabled' : ''}
                                >
                            </label>
                        </article>
                    `;
                }).join('')}
            </div>
        `;

        panel.querySelectorAll('.access-rule-toggle').forEach((input) => {
            input.addEventListener('change', async () => {
                const rule = input.dataset.rule;
                const current = Array.isArray(ctx.overlay.data.access[rule]) ? [...ctx.overlay.data.access[rule]] : [];
                const group = this.state.selectedGroup;
                const hasGroup = current.includes(group);

                if (input.checked && !hasGroup) current.push(group);
                if (!input.checked && hasGroup) {
                    const idx = current.indexOf(group);
                    if (idx >= 0) current.splice(idx, 1);
                }

                ctx.overlay.data.access[rule] = current;
                await ctx.api.saveAccess(rule, current);
                this.renderAccessPanel(ctx);
            });
        });
    }
};

export default AccessFeature;
