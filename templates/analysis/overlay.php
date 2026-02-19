<div id="analysis-overlay" class="analysis-overlay" style="display:none;">
    <div class="analysis-overlay-card">
        <div class="analysis-overlay-header">
            <div class="analysis-overlay-header-spacer"></div>
            <h2>Time Analysis</h2>
            <button type="button" id="analysis-overlay-close" class="analysis-overlay-close" aria-label="Close analysis">×</button>
        </div>

        <div class="analysis-overlay-filters">
            <select id="analysis-range" class="analysis-overlay-control">
                <option value="weekly">Weekly</option>
                <option value="biweekly">Bi-Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="custom">Custom</option>
            </select>
            <input id="analysis-start" type="date" class="analysis-overlay-control" disabled>
            <input id="analysis-end" type="date" class="analysis-overlay-control" disabled>
        </div>

        <div class="analysis-overlay-groups">
            <p class="analysis-overlay-detail-hint">Click a box to get a break down of hours or dates for the selected time frame.</p>
            <section class="analysis-group">
                <h3>Hours</h3>
                <div class="analysis-overlay-grid">
                    <article class="analysis-overlay-item"><span>Total Hours</span><strong id="metric-total-hours">--</strong></article>
                    <article class="analysis-overlay-item"><span>PTO Hours</span><strong id="metric-pto-hours">--</strong></article>
                    <article class="analysis-overlay-item"><span>Overtime Hours</span><strong id="metric-overtime-hours">--</strong></article>
                </div>
            </section>

            <section class="analysis-group">
                <h3>Travel</h3>
                <div class="analysis-overlay-grid">
                    <article class="analysis-overlay-item"><span>Per Diem Requests</span><strong id="metric-per-diem-requests">--</strong></article>
                    <article class="analysis-overlay-item"><span>Road Scanning Days</span><strong id="metric-road-scanning-days">--</strong></article>
                    <article class="analysis-overlay-item"><span>Overnight Days</span><strong id="metric-overnight-days">--</strong></article>
                    <article class="analysis-overlay-item"><span>Total Miles</span><strong id="metric-total-miles">--</strong></article>
                </div>
            </section>

            <section class="analysis-group">
                <h3>Extra Expenses</h3>
                <div class="analysis-overlay-grid analysis-overlay-grid-single">
                    <article class="analysis-overlay-item"><span>Extra Expenses</span><strong id="metric-extra-expenses">--</strong></article>
                </div>
            </section>
        </div>
    </div>
</div>
