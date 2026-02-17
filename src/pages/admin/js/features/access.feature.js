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

    render() {
        return '<div id="access-rules" class="admin-access-rules"></div>';
    },

    refresh(ctx) {
        this.renderAccessRules(ctx);
    },

    renderAccessRules(ctx) {
        const root = ctx.root.querySelector('#access-rules');
        if (!root) return;

        const availableFeatureIds = (ctx.overlay.features || []).map((f) => f.id);
        const activeRuleLabels = { ...ALL_RULE_LABELS };
        if (!availableFeatureIds.includes('holidays')) {
            delete activeRuleLabels.admin_holidays;
        }
        if (!availableFeatureIds.includes('users')) {
            delete activeRuleLabels.admin_users;
        }

        const rules = Object.keys(activeRuleLabels);
        root.innerHTML = rules.map((ruleKey) => {
            const selected = Array.isArray(ctx.overlay.data.access[ruleKey]) ? ctx.overlay.data.access[ruleKey] : [];
            const chips = (ctx.overlay.data.groups || []).map((gid) => {
                const isDeveloper = String(gid).toLowerCase() === DEVELOPER_GROUP.toLowerCase();
                const active = isDeveloper || selected.includes(gid);
                return `
                    <button type="button" class="access-chip ${active ? 'active' : ''}" data-rule="${ruleKey}" data-group="${gid}" ${isDeveloper ? 'disabled' : ''}>
                        ${gid}
                    </button>
                `;
            }).join('');

            return `
                <article class="access-rule-card">
                    <div class="access-rule-title">${activeRuleLabels[ruleKey]}</div>
                    <div class="access-chip-row">${chips}</div>
                </article>
            `;
        }).join('');

        root.querySelectorAll('.access-chip').forEach((chip) => {
            chip.addEventListener('click', async () => {
                if (chip.disabled) return;
                const rule = chip.dataset.rule;
                const group = chip.dataset.group;
                const current = Array.isArray(ctx.overlay.data.access[rule]) ? [...ctx.overlay.data.access[rule]] : [];
                const idx = current.indexOf(group);
                if (idx >= 0) current.splice(idx, 1);
                else current.push(group);
                ctx.overlay.data.access[rule] = current;
                await ctx.api.saveAccess(rule, current);
                this.renderAccessRules(ctx);
            });
        });
    }
};

export default AccessFeature;
