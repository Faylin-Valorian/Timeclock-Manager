export const Dialogs = {
    showError(msg) {
        let overlay = document.getElementById('stech-centered-error');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'stech-centered-error';
            overlay.innerHTML = `
                <div class="stech-error-content">
                    <h3>Access Denied</h3>
                    <p id="stech-error-msg-text"></p>
                    <button class="primary-button" id="btn-error-close">Close</button>
                </div>
            `;
            document.body.appendChild(overlay);
            
            document.getElementById('btn-error-close').addEventListener('click', () => {
                overlay.style.display = 'none';
            });
        }

        const p = document.getElementById('stech-error-msg-text');
        if (p) p.textContent = msg;
        
        overlay.style.display = 'flex';
    },

    confirmArchive(onConfirm) {
        this._createConfirmationOverlay(
            'stech-confirm-archive',
            '#e67e22',
            'Archive Record?',
            'Are you sure you want to archive this entry? It will be hidden from the main view.',
            'Yes, Archive It',
            onConfirm
        );
    },

    confirmRestore(onConfirm) {
        this._createConfirmationOverlay(
            'stech-confirm-restore',
            '#28a745',
            'Restore Record?',
            'This will move the record back to the active list.',
            'Yes, Restore It',
            onConfirm
        );
    },

    _createConfirmationOverlay(id, color, title, text, btnText, callback) {
        let overlay = document.getElementById(id);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = id;
            overlay.className = 'custom-confirm-overlay'; 
            document.body.appendChild(overlay);
        }

        overlay.innerHTML = `
            <div class="stech-error-content" style="border-top-color: ${color};">
                <h3 style="color: ${color};">${title}</h3>
                <p>${text}</p>
                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                    <button id="${id}-yes" class="primary-button" style="background-color: ${color};">${btnText}</button>
                    <button id="${id}-no" class="secondary-button">Cancel</button>
                </div>
            </div>
        `;

        document.getElementById(`${id}-yes`).onclick = () => {
            overlay.style.display = 'none';
            if (callback) callback();
        };
        document.getElementById(`${id}-no`).onclick = () => {
            overlay.style.display = 'none';
        };

        overlay.style.display = 'flex';
    }
};