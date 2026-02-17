import { AdminOverlay } from './js/overlay.js';

window.TimeclockManager = window.TimeclockManager || {};
window.TimeclockManager.Modules = window.TimeclockManager.Modules || {};

document.addEventListener('DOMContentLoaded', () => {
    window.TimeclockManager.Modules.AdminOverlay = AdminOverlay;
    AdminOverlay.init();
});

