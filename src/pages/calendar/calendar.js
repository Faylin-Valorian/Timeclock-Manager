// Import TmClient
import { TmClient } from 'src/api/api.js'; 
import { CalendarModule } from './js/fullcalendar.js';
import { TabsModule } from './js/tabs.js';
import { TimesheetModule } from '../timesheet/timesheet.js';
import { Popup } from 'src/components/popup/popup.js';
import { AnalysisOverlay } from '../analysis/js/overlay.js';

// Global Namespace
window.TimeclockManager = window.TimeclockManager || {};
window.TimeclockManager.Client = TmClient; 
window.TimeclockManager.CalendarInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    const Impersonation = {
        storageKey: 'tm_impersonation',
        state: { uid: '', displayName: '' },

        init() {
            try {
                const raw = window.sessionStorage.getItem(this.storageKey);
                if (raw) {
                    const parsed = JSON.parse(raw);
                    this.state.uid = String(parsed?.uid || '');
                    this.state.displayName = String(parsed?.displayName || '');
                }
            } catch (e) {
            }
            this.bindBanner();
            this.renderBanner();
        },

        bindBanner() {
            document.getElementById('impersonation-clear')?.addEventListener('click', () => this.clear());
        },

        set(uid, displayName = '') {
            this.state.uid = String(uid || '');
            this.state.displayName = String(displayName || uid || '');
            try {
                window.sessionStorage.setItem(this.storageKey, JSON.stringify(this.state));
            } catch (e) {
            }
            this.renderBanner();
        },

        clear() {
            this.state.uid = '';
            this.state.displayName = '';
            try {
                window.sessionStorage.removeItem(this.storageKey);
            } catch (e) {
            }
            this.renderBanner();
            window.TimeclockManager.CalendarInstance?.refetchEvents?.();
        },

        getTargetUid() {
            return this.state.uid || '';
        },

        renderBanner() {
            const banner = document.getElementById('impersonation-banner');
            const label = document.getElementById('impersonation-text');
            if (!banner || !label) return;

            if (!this.state.uid) {
                banner.style.display = 'none';
                return;
            }

            label.textContent = `Impersonating: ${this.state.displayName || this.state.uid} (${this.state.uid})`;
            banner.style.display = 'flex';
        }
    };

    window.TimeclockManager.Impersonation = Impersonation;
    Impersonation.init();

    const lastInteractionByKey = {};
    const isRapidInteraction = (key, thresholdMs = 350) => {
        const now = Date.now();
        const prev = lastInteractionByKey[key] || 0;
        lastInteractionByKey[key] = now;
        return (now - prev) < thresholdMs;
    };

    const eventDateKey = (eventObj) => {
        if (!eventObj?.start) return '';
        const d = eventObj.start;
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const isIncompleteEvent = (eventObj) => {
        const id = String(eventObj?.id || '');
        return id.endsWith('-active') || id.endsWith('-missing');
    };

    const isNoSignInEvent = (eventObj) => {
        const id = String(eventObj?.id || '');
        return id.endsWith('-no-time-in');
    };

    const isPerDiemNoTimeEvent = (eventObj) => {
        const id = String(eventObj?.id || '');
        if (!id.endsWith('-pd')) return false;
        return parseInt(eventObj?.extendedProps?.per_diem_no_time, 10) === 1;
    };

    const isPerDiemEvent = (eventObj) => {
        const id = String(eventObj?.id || '');
        return id.endsWith('-pd');
    };

    const getOpenCandidateForDate = (dateStr, mode = 'dateClick') => {
        const cal = CalendarModule.instance;
        if (!cal) return null;

        const dayEvents = cal.getEvents().filter((ev) => {
            if (!ev || ev.display === 'background') return false;
            if (!ev.extendedProps || !ev.extendedProps.timesheet_id) return false;
            return eventDateKey(ev) === dateStr;
        });

        if (dayEvents.length === 0) return null;

        // Date click behavior: prioritize unfinished records.
        const incomplete = dayEvents.find((ev) => isIncompleteEvent(ev));
        if (incomplete) return incomplete;

        // Also treat these as unresolved records for day-click behavior.
        const perDiemNoTime = dayEvents.find((ev) => isPerDiemNoTimeEvent(ev));
        if (perDiemNoTime) return perDiemNoTime;

        const noSignIn = dayEvents.find((ev) => isNoSignInEvent(ev));
        if (noSignIn) return noSignIn;

        if (mode === 'todayButton') {
            // Today button: open per diem only when it is the unresolved per-diem-only case.
            const perDiemOnly = dayEvents.find((ev) => isPerDiemEvent(ev))
                && dayEvents.every((ev) => isPerDiemEvent(ev));
            if (perDiemOnly) {
                return dayEvents.find((ev) => isPerDiemEvent(ev));
            }
        }

        return null;
    };
    
    // 1. Initialize Modules
    TabsModule.init();
    window.TimeclockManager.Modules = window.TimeclockManager.Modules || {};
    const timesheetModule = window.TimeclockManager.Modules.Timesheet || null;
    timesheetModule?.init?.();
    window.TimeclockManager.Modules.AnalysisOverlay = AnalysisOverlay;
    AnalysisOverlay.init();

    // 2. Initialize Calendar
    const calContainer = document.getElementById('calendar');
    if (calContainer) {
        calContainer.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();
        }, true);

        CalendarModule.init(calContainer);
        
        // 3. Link Calendar Events to Module
        CalendarModule.onEventClick = async (info) => {
            const eventKey = `event:${info?.event?.id || ''}`;
            if (isRapidInteraction(eventKey)) return;

            // [FIX] Use the real DB ID stored in extendedProps
            const props = info.event.extendedProps;
            const id = props && props.timesheet_id ? props.timesheet_id : info.event.id;
            const isArchived = !!(props && parseInt(props.archive, 10) === 1);
            
            // Extract date (YYYY-MM-DD)
            const date = info.event.startStr.split('T')[0];

            const canEditArchived = !!timesheetModule?.permissions?.can_edit_archived;
            if (isArchived && !canEditArchived) {
                await Popup.alert(
                    'This is an archived record. Non-admin users can view only.',
                    'Archived Record',
                    'info'
                );
            }

            timesheetModule?.open?.(date, id, { archive: isArchived });
        };

        CalendarModule.onDateClick = (info) => {
            const dayKey = `date:${info?.dateStr || ''}`;
            if (isRapidInteraction(dayKey)) return;
            if (CalendarModule.archiveMode === 1) return;

            const mode = info?.source === 'todayButton' ? 'todayButton' : 'dateClick';
            const candidate = getOpenCandidateForDate(info.dateStr, mode);
            if (candidate) {
                const id = candidate.extendedProps.timesheet_id;
                timesheetModule?.open?.(info.dateStr, id);
                return;
            }

            timesheetModule?.open?.(info.dateStr, null);
        };
    }
});
