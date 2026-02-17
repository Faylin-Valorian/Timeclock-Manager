export const Popup = {
    rootId: 'tm-popup-root',

    ensureRoot() {
        let root = document.getElementById(this.rootId);
        if (!root) {
            root = document.createElement('div');
            root.id = this.rootId;
            document.body.appendChild(root);
        }
        return root;
    },

    close(overlay) {
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    },

    alert(message, title = 'Notice', kind = 'info') {
        return new Promise((resolve) => {
            const root = this.ensureRoot();
            const overlay = document.createElement('div');
            overlay.className = 'tm-popup-overlay';

            const card = document.createElement('div');
            card.className = `tm-popup-card tm-popup-${kind}`;
            const titleEl = document.createElement('h3');
            titleEl.className = 'tm-popup-title';
            titleEl.textContent = title;

            const messageEl = document.createElement('p');
            messageEl.className = 'tm-popup-message';
            messageEl.textContent = message;

            const actions = document.createElement('div');
            actions.className = 'tm-popup-actions';

            const okBtn = document.createElement('button');
            okBtn.type = 'button';
            okBtn.className = 'tm-popup-btn tm-popup-btn-primary';
            okBtn.textContent = 'OK';

            actions.appendChild(okBtn);
            card.appendChild(titleEl);
            card.appendChild(messageEl);
            card.appendChild(actions);
            const onClose = () => {
                this.close(overlay);
                resolve(true);
            };

            okBtn.addEventListener('click', onClose);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) onClose();
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') onClose();
            });

            overlay.appendChild(card);
            root.appendChild(overlay);
            okBtn.focus();
        });
    },

    confirm(message, title = 'Please Confirm', confirmLabel = 'Delete') {
        return new Promise((resolve) => {
            const root = this.ensureRoot();
            const overlay = document.createElement('div');
            overlay.className = 'tm-popup-overlay';

            const card = document.createElement('div');
            card.className = 'tm-popup-card tm-popup-warning';
            const titleEl = document.createElement('h3');
            titleEl.className = 'tm-popup-title';
            titleEl.textContent = title;

            const messageEl = document.createElement('p');
            messageEl.className = 'tm-popup-message';
            messageEl.textContent = message;

            const actions = document.createElement('div');
            actions.className = 'tm-popup-actions';

            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'tm-popup-btn tm-popup-btn-secondary';
            cancelBtn.textContent = 'Cancel';

            const confirmBtn = document.createElement('button');
            confirmBtn.type = 'button';
            confirmBtn.className = 'tm-popup-btn tm-popup-btn-danger';
            confirmBtn.textContent = confirmLabel;

            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);
            card.appendChild(titleEl);
            card.appendChild(messageEl);
            card.appendChild(actions);

            const onCancel = () => {
                this.close(overlay);
                resolve(false);
            };
            const onConfirm = () => {
                this.close(overlay);
                resolve(true);
            };

            cancelBtn.addEventListener('click', onCancel);
            confirmBtn.addEventListener('click', onConfirm);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) onCancel();
            });

            card.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') onCancel();
            });

            overlay.appendChild(card);
            root.appendChild(overlay);
            confirmBtn.focus();
        });
    },

    confirmSaveBeforeClose(message, title = 'Unsaved Changes') {
        return new Promise((resolve) => {
            const root = this.ensureRoot();
            const overlay = document.createElement('div');
            overlay.className = 'tm-popup-overlay';

            const card = document.createElement('div');
            card.className = 'tm-popup-card tm-popup-warning';
            const titleEl = document.createElement('h3');
            titleEl.className = 'tm-popup-title';
            titleEl.textContent = title;

            const messageEl = document.createElement('p');
            messageEl.className = 'tm-popup-message';
            messageEl.textContent = message;

            const actions = document.createElement('div');
            actions.className = 'tm-popup-actions';

            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.className = 'tm-popup-btn tm-popup-btn-secondary';
            cancelBtn.textContent = 'Cancel';

            const discardBtn = document.createElement('button');
            discardBtn.type = 'button';
            discardBtn.className = 'tm-popup-btn tm-popup-btn-secondary';
            discardBtn.textContent = "Don't Save";

            const saveBtn = document.createElement('button');
            saveBtn.type = 'button';
            saveBtn.className = 'tm-popup-btn tm-popup-btn-primary';
            saveBtn.textContent = 'Save';

            actions.appendChild(cancelBtn);
            actions.appendChild(discardBtn);
            actions.appendChild(saveBtn);
            card.appendChild(titleEl);
            card.appendChild(messageEl);
            card.appendChild(actions);

            const onCancel = () => {
                this.close(overlay);
                resolve('cancel');
            };
            const onDiscard = () => {
                this.close(overlay);
                resolve('discard');
            };
            const onSave = () => {
                this.close(overlay);
                resolve('save');
            };

            cancelBtn.addEventListener('click', onCancel);
            discardBtn.addEventListener('click', onDiscard);
            saveBtn.addEventListener('click', onSave);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) onCancel();
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') onCancel();
            });

            overlay.appendChild(card);
            root.appendChild(overlay);
            saveBtn.focus();
        });
    },

    detailList(title, items = [], emptyMessage = 'No records found.') {
        return new Promise((resolve) => {
            const root = this.ensureRoot();
            const overlay = document.createElement('div');
            overlay.className = 'tm-popup-overlay';

            const card = document.createElement('div');
            card.className = 'tm-popup-card tm-popup-info';
            const titleEl = document.createElement('h3');
            titleEl.className = 'tm-popup-title';
            titleEl.textContent = title;

            const body = document.createElement('div');
            body.className = 'tm-popup-message';

            if (!Array.isArray(items) || items.length === 0) {
                const p = document.createElement('p');
                p.textContent = emptyMessage;
                body.appendChild(p);
            } else {
                const ul = document.createElement('ul');
                ul.style.margin = '0';
                ul.style.paddingLeft = '18px';
                items.forEach((line) => {
                    const li = document.createElement('li');
                    li.textContent = String(line);
                    ul.appendChild(li);
                });
                body.appendChild(ul);
            }

            const actions = document.createElement('div');
            actions.className = 'tm-popup-actions';
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'tm-popup-btn tm-popup-btn-primary';
            closeBtn.textContent = 'Close';
            actions.appendChild(closeBtn);

            const onClose = () => {
                this.close(overlay);
                resolve(true);
            };

            closeBtn.addEventListener('click', onClose);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) onClose();
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') onClose();
            });

            card.appendChild(titleEl);
            card.appendChild(body);
            card.appendChild(actions);
            overlay.appendChild(card);
            root.appendChild(overlay);
            closeBtn.focus();
        });
    }
};
