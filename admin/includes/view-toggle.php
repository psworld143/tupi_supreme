<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// List/Grid view toggle for admin list tables.
// Finds every list table (`.min-w-full` inside an `.overflow-auto` /
// `.overflow-x-auto` scroller), tags each cell with its column header via
// data-label, and injects a segmented List/Grid switch above the table.
// In grid mode the same <table> is restyled into cards — no DOM cloning,
// so inline forms/links/JS keep working. Preference persists in
// localStorage; defaults to grid on small screens, list on desktop.
?>
<style>
    /* Card frame around the whole data-table block (toolbar + table + footer) */
    .lv-frame {
        background: #fff;
        border: 1px solid #e4e4e7;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .lv-frame.lv-frame-inner {
        border: 0;
        border-radius: 0;
    }
    .lv-records-host {
        margin-top: 1rem;
        border: 1px solid #e4e4e7 !important;
        border-radius: 0.625rem;
        background: #fff;
        box-shadow: none !important;
        overflow: hidden;
    }
    .lv-filter-panel {
        padding: 1.125rem 1.25rem !important;
        border: 1px solid #e4e4e7 !important;
        border-radius: 0.625rem;
        background: #fff !important;
        box-shadow: none !important;
    }
    .lv-records-host .lv-toolbar,
    .lv-records-host .lv-foot { padding-left: 1.25rem; padding-right: 1.25rem; }
    .lv-records-host > [class~="bg-gray-50"][class~="border-t"] {
        background: #fff;
        border-color: #f4f4f5;
        padding: 0.75rem 1.25rem;
    }
    .lv-records-host .lv-foot { border-top-color: #f4f4f5; }

    /* Toolbar injected above each list table: search left, view toggle right */
    .lv-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        min-height: 4rem;
        padding: 0.875rem 1.25rem;
        background: #fff;
        border-bottom: 1px solid #f4f4f5;
    }
    .lv-heading { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
    .lv-heading-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        flex: none;
        border-radius: 0.5rem;
        background: #e9f1ea;
        color: #2c5530;
        font-size: 0.75rem;
    }
    .lv-heading-title { font-size: 0.875rem; font-weight: 600; color: #18181b; }
    .lv-toolbar .lv-meta { font-size: 0.75rem; color: #71717a; }
    .lv-records-summary { display: flex; align-items: center; flex-wrap: wrap; gap: 0.25rem 0.75rem; margin: 0.125rem 0 0 !important; }
    .lv-records-summary > span {
        padding: 0 !important;
        border: 0 !important;
        background: none !important;
        color: #71717a !important;
        font-size: 0.6875rem !important;
        font-weight: 400 !important;
    }
    .lv-records-summary i { display: none; }
    .lv-toolbar .lv-search {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;
        max-width: 16rem;
        height: 2rem;
        padding: 0 0.625rem;
        border: 1px solid #e4e4e7;
        border-radius: 0.375rem;
        background: #fafafa;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
    }
    .lv-toolbar .lv-search:focus-within {
        background: #fff;
    }
    .lv-toolbar .lv-search:focus-within {
        border-color: #2c5530;
        box-shadow: 0 0 0 2px rgba(44, 85, 48, 0.15);
    }
    .lv-toolbar .lv-search i {
        font-size: 0.6875rem;
        color: #a1a1aa;
        flex-shrink: 0;
    }
    .lv-toolbar .lv-search input {
        min-width: 0;
        flex: 1;
        border: 0;
        background: transparent;
        font-size: 0.8125rem;
        color: #18181b;
    }
    .lv-toolbar .lv-search input:focus { outline: none; }
    .lv-toolbar .lv-search input::placeholder { color: #a1a1aa; }
    .lv-toolbar .lv-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    .lv-toolbar .lv-view-label {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #a1a1aa;
        margin-right: 0.125rem;
    }
    .lv-seg {
        display: inline-flex;
        gap: 2px;
        padding: 2px;
        background: #f4f4f5;
        border: 1px solid #e4e4e7;
        border-radius: 9999px;
    }
    .lv-btn {
        width: 1.75rem;
        height: 1.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 9999px;
        background: transparent;
        color: #71717a;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .lv-btn:hover {
        color: #2c5530;
    }
    .lv-btn.lv-active {
        background: #2c5530;
        color: #fff;
    }
    .lv-btn:focus-visible {
        outline: 2px solid #2c5530;
        outline-offset: 1px;
    }

    /* Selection checkbox + grip columns — fixed narrow widths, same
       vertical padding as data cells so rows stay on one rhythm */
    table.min-w-full th.lv-check,
    table.min-w-full td.lv-check {
        width: 2.75rem;
        padding-left: 1rem !important;
        padding-right: 0.25rem !important;
    }
    table.min-w-full th.lv-grip,
    table.min-w-full td.lv-grip {
        padding-left: 0.75rem !important;
        padding-right: 0 !important;
    }
    /* The cell right after the checkbox doesn't need its own left padding */
    table.min-w-full td.lv-check + td,
    table.min-w-full th.lv-check + th {
        padding-left: 0.5rem !important;
    }
    table.min-w-full td.lv-grip + td,
    table.min-w-full th.lv-grip + th {
        padding-left: 0.75rem !important;
    }
    table.min-w-full td.lv-check input[type="checkbox"],
    table.min-w-full th.lv-check input[type="checkbox"] {
        width: 0.875rem;
        height: 0.875rem;
        accent-color: #2c5530;
        cursor: pointer;
        vertical-align: middle;
    }
    .lv-cards td.lv-check { padding: 0 0 0.5rem 0 !important; }
    .lv-cards td.lv-check::before { content: none; }

    /* Selection/status footer injected below each table */
    .lv-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: #fff;
        border-top: 1px solid #e4e4e7;
        font-size: 0.75rem;
        color: #71717a;
    }
    .lv-foot .lv-clear {
        border: 0;
        background: transparent;
        color: #2c5530;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        padding: 0;
    }
    .lv-foot .lv-clear:hover { text-decoration: underline; }

    /* Status filter chips row (auto-built when the table has a Status column) */
    .lv-filters {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex-wrap: wrap;
        padding: 0.625rem 1rem;
        background: #fff;
        border-bottom: 1px solid #e4e4e7;
    }
    .lv-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        height: 1.75rem;
        padding: 0 0.75rem;
        border: 1px solid #e4e4e7;
        border-radius: 9999px;
        background: #fff;
        color: #52525b;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lv-chip:hover { border-color: #a1a1aa; color: #18181b; }
    .lv-chip .lv-chip-count {
        font-size: 0.6875rem;
        color: #a1a1aa;
        font-weight: 400;
    }
    .lv-chip.lv-chip-active {
        background: #2c5530;
        border-color: #2c5530;
        color: #fff;
    }
    .lv-chip.lv-chip-active .lv-chip-count { color: rgba(255,255,255,0.7); }

    /* Decorative drag-handle grip column — faint until the row is hovered */
    table.min-w-full th.lv-grip,
    table.min-w-full td.lv-grip {
        display: none;
        width: 1.5rem;
        color: #e4e4e7;
        cursor: grab;
        transition: color 0.15s ease;
    }
    table.min-w-full tbody tr:hover td.lv-grip { color: #a1a1aa; }
    table.min-w-full td.lv-grip i { font-size: 0.625rem; }
    .lv-cards td.lv-grip { display: none !important; }

    /* Auto status pills — cells whose text is a known status word get
       restyled into a shadcn badge with a colored dot */
    .lv-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        border-radius: 9999px;
        border: 1px solid transparent;
        padding: 0.125rem 0.625rem;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
    }
    .lv-badge::before {
        content: "";
        width: 0.375rem;
        height: 0.375rem;
        border-radius: 9999px;
        background: currentColor;
        flex-shrink: 0;
    }
    .lv-badge-green { background: #e9f1ea; color: #2c5530; }
    .lv-badge-red   { background: #fef2f2; color: #b91c1c; }
    .lv-badge-amber { background: #fffbeb; color: #b45309; }
    .lv-badge-zinc  { background: #f4f4f5; color: #52525b; }

    /* ── Enhanced list (table) mode ─────────────────────────── */

    /* Compact cells on phones so more columns fit before scrolling */
    @media (max-width: 639.98px) {
        .lv-list th,
        .lv-list td {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
            font-size: 0.8125rem;
        }
        .lv-list th {
            padding-top: 0.625rem;
            padding-bottom: 0.625rem;
        }
        .lv-list td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
    }

    /* Sticky first column keeps the row title visible while swiping sideways */
    .lv-list thead {
        z-index: 10;
    }
    .lv-list th:first-child,
    .lv-list td:first-child {
        position: sticky;
        left: 0;
        z-index: 6;
        background-color: #fff;
        box-shadow: 1px 0 0 0 #e4e4e7;
    }
    .lv-list thead th:first-child {
        background-color: #fafafa; /* matches thead bg-gray-50 */
        z-index: 11;
    }
    .lv-list tbody tr:hover td:first-child {
        background-color: #fafafa; /* matches hover:bg-gray-50 */
    }
    .lv-list tbody tr:has(.lv-row-check:checked) td:first-child { background-color: #f0f7f1; }

    /* Scroll affordance: right-edge fade + swipe hint while columns overflow */
    .lv-scrollwrap {
        position: relative;
    }
    .lv-scrollwrap::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 2.5rem;
        pointer-events: none;
        background: linear-gradient(to left, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 15;
    }
    .lv-scrollwrap.lv-scrollable::after {
        opacity: 1;
    }
    .lv-scrollwrap.lv-at-end::after {
        opacity: 0;
    }
    .lv-hint {
        position: absolute;
        top: 50%;
        right: 0.75rem;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.8rem;
        background: rgba(24, 24, 27, 0.9);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 500;
        border-radius: 9999px;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 20;
    }
    .lv-scrollwrap.lv-scrollable:not(.lv-scrolled):not(.lv-at-end) .lv-hint {
        opacity: 1;
    }

    /* Grid (card) mode — the scroller becomes a flat card grid container.
       `!important` is needed to beat the inline `style="max-height: 55vh"`. */
    .lv-cards {
        overflow: visible !important;
        max-height: none !important;
    }
    .lv-cards table.min-w-full {
        min-width: 0 !important;
    }
    .lv-cards thead {
        display: none;
    }
    .lv-cards table,
    .lv-cards tbody {
        display: block;
    }
    .lv-cards tbody {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        padding: 1rem;
        background: #fafafa;
    }
    @media (min-width: 640px) {
        .lv-cards tbody {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1440px) {
        .lv-cards tbody {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    /* Each row becomes a card (specificity beats the cell px-6/py-4 utils) */
    .lv-cards tbody tr {
        display: flex;
        flex-direction: column;
        min-width: 0;
        padding: 0.875rem 1rem;
        background: #fff;
        border: 1px solid #e4e4e7;
        border-radius: 0.875rem;
    }
    .lv-cards td {
        display: block;
        min-width: 0;
        padding: 0.4rem 0;
        border: 0;
        font-size: 0.8125rem;
        color: #3f3f46;
        overflow-wrap: anywhere;
    }
    .lv-cards td::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 0.2rem;
        font-size: 0.625rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #a1a1aa;
    }

    /* First column is the row title — slightly emphasized */
    .lv-cards td.lv-title {
        padding-top: 0;
        font-size: 0.9375rem;
    }
    .lv-cards td.lv-title > *:first-child {
        font-weight: 600;
        color: #2c5530;
    }

    /* Actions column becomes a button row pinned to the card bottom */
    .lv-cards td.lv-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: auto;
        padding-top: 0.75rem;
        padding-bottom: 0;
        border-top: 1px solid #e4e4e7;
    }
    .lv-cards td.lv-actions::before {
        content: none;
    }

    /* Empty-state rows (single colspan cell) span the whole grid */
    .lv-cards tr.lv-empty-row {
        grid-column: 1 / -1;
        padding: 2rem 1rem;
        text-align: center;
    }
    .lv-cards td.lv-empty {
        padding: 0;
    }
    .lv-cards td.lv-empty::before {
        content: none;
    }

    @media (max-width: 639px) {
        .lv-toolbar .lv-right { width: 100%; justify-content: flex-end; }
        .lv-toolbar .lv-search { max-width: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lv-btn {
            transition: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var LV_KEY = 'tsaci_admin_table_view';

        var tables = document.querySelectorAll(
            '.overflow-auto > table.min-w-full, .overflow-x-auto > table.min-w-full'
        );
        if (!tables.length) return;

        var instances = [];

        tables.forEach(function (table) {
            var scroller = table.parentElement;
            if (!scroller) return;
            var listCard = scroller.parentElement;

            // Tag each body cell with its column header text so cards can
            // render a label above every value.
            var statusIdx = -1;
            var headers = Array.prototype.map.call(
                table.querySelectorAll('thead th'),
                function (th, i) {
                    var text = th.textContent.trim();
                    if (/action/i.test(text)) {
                        th.classList.add('lv-actions-head');
                    }
                    if (/status/i.test(text)) {
                        statusIdx = i;
                    }
                    return text;
                }
            );

            // Leading grip + selection-checkbox columns (header = select all)
            var headRow = table.querySelector('thead tr');
            if (headRow) {
                var th = document.createElement('th');
                th.className = 'lv-check';
                th.innerHTML = '<input type="checkbox" class="lv-check-all" aria-label="Select all rows">';
                headRow.insertBefore(th, headRow.firstChild);
                var thGrip = document.createElement('th');
                thGrip.className = 'lv-grip';
                headRow.insertBefore(thGrip, th);
            }

            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var cells = tr.querySelectorAll('td');
                var isEmptyRow = cells.length === 1 && cells[0].hasAttribute('colspan');

                // Checkbox + grip cells first
                var checkTd = document.createElement('td');
                checkTd.className = 'lv-check';
                checkTd.setAttribute('data-label', '');
                var gripTd = document.createElement('td');
                gripTd.className = 'lv-grip';
                gripTd.setAttribute('data-label', '');
                if (isEmptyRow) {
                    checkTd.style.display = 'none';
                    gripTd.style.display = 'none';
                } else {
                    checkTd.innerHTML = '<input type="checkbox" class="lv-row-check" aria-label="Select row">';
                    gripTd.innerHTML = '<i class="fas fa-grip-vertical"></i>';
                }
                tr.insertBefore(checkTd, tr.firstChild);
                tr.insertBefore(gripTd, checkTd);

                var first = true;
                cells.forEach(function (td, i) {
                    if (td.hasAttribute('colspan')) {
                        td.classList.add('lv-empty');
                        tr.classList.add('lv-empty-row');
                        return;
                    }
                    var label = headers[i] || '';
                    td.setAttribute('data-label', label);
                    if (first) {
                        td.classList.add('lv-title');
                        first = false;
                    }
                    if (/action/i.test(label)) {
                        td.classList.add('lv-actions');
                    }
                    // Status pill — plain-text status words become badges,
                    // and the raw value is kept for the filter chips.
                    var txt = td.textContent.trim();
                    if (/status/i.test(label) && txt !== '' && txt.length < 30 && !td.children.length) {
                        var t = txt.toLowerCase();
                        tr._lvStatus = t;
                        var cls = 'lv-badge-zinc';
                        if (/^(active|published|done|yes|enabled|featured|verified|visible|show|paid|subscribed|read)\b/.test(t)) cls = 'lv-badge-green';
                        else if (/^(inactive|draft|no|disabled|hidden|overdue|unpaid|unsubscribed|deleted|archived)\b/.test(t)) cls = 'lv-badge-red';
                        else if (/pending|trial|process|review|unread|scheduled/.test(t)) cls = 'lv-badge-amber';
                        td.innerHTML = '<span class="lv-badge ' + cls + '">' + td.textContent + '</span>';
                    }
                });
            });

            // Wrapper pins the right-edge fade + swipe hint to the scroller's
            // visible edge (a pseudo-element inside the scroller would scroll
            // away with the table).
            var wrap = document.createElement('div');
            wrap.className = 'lv-scrollwrap';
            scroller.parentNode.insertBefore(wrap, scroller);
            wrap.appendChild(scroller);

            var hint = document.createElement('div');
            hint.className = 'lv-hint';
            hint.innerHTML = '<i class="fas fa-arrows-left-right"></i><span>Scroll for more</span>';
            wrap.appendChild(hint);

            var rowCount = table.querySelectorAll('tbody tr:not(.lv-empty-row)').length;

            // Status filter chips — built from the unique values found in the
            // Status column (skipped entirely if the table has none).
            var filtersEl = null;
            var statusCounts = {};
            var pageForm = listCard.querySelector('form.lv-filterbar');
            if (statusIdx >= 0 && !pageForm) {
                table.querySelectorAll('tbody tr').forEach(function (tr) {
                    if (tr._lvStatus) {
                        statusCounts[tr._lvStatus] = (statusCounts[tr._lvStatus] || 0) + 1;
                    }
                });
                var keys = Object.keys(statusCounts);
                if (keys.length) {
                    filtersEl = document.createElement('div');
                    filtersEl.className = 'lv-filters';
                    var chips = '<button type="button" class="lv-chip lv-chip-active" data-status="">All <span class="lv-chip-count">' + rowCount + '</span></button>';
                    keys.sort().forEach(function (k) {
                        var label = k.charAt(0).toUpperCase() + k.slice(1);
                        chips += '<button type="button" class="lv-chip" data-status="' + k + '">' + label + ' <span class="lv-chip-count">' + statusCounts[k] + '</span></button>';
                    });
                    filtersEl.innerHTML = chips;
                }
            }

            // Toolbar above the table: live search + count (left), List/Grid
            // (right). Skip the client-side search input when the page already
            // renders its own server-side search form — one search bar only.
            var filterPanel = pageForm && pageForm.parentElement !== listCard && pageForm.parentElement.parentElement === listCard
                ? pageForm.parentElement : null;
            if (filterPanel) {
                listCard.parentElement.insertBefore(filterPanel, listCard);
                filterPanel.classList.add('lv-filter-panel');
                listCard.classList.add('lv-records-host');
            }
            var hasPageSearch = !!(pageForm && pageForm.querySelector('input[name="q"], input[name="search"], input[type="search"], input[placeholder*="earch"]'));
            var bar = document.createElement('div');
            bar.className = 'lv-toolbar';
            bar.innerHTML =
                '<span class="lv-heading"><span class="lv-heading-icon"><i class="fas fa-layer-group"></i></span>' +
                    '<span><span class="lv-heading-title">Records</span><span class="lv-meta" style="display:block">' +
                    rowCount + (rowCount === 1 ? ' record' : ' records') + ' on this page</span></span></span>' +
                '<span class="lv-right">' +
                    (hasPageSearch ? '' : '<span class="lv-search"><i class="fas fa-search"></i><input type="text" placeholder="Search records" aria-label="Search table"></span>') +
                    '<span class="lv-view-label">View</span>' +
                    '<span class="lv-seg" role="group" aria-label="Table view">' +
                        '<button type="button" class="lv-btn" data-view="list" title="List view" aria-label="List view"><i class="fas fa-list"></i></button>' +
                        '<button type="button" class="lv-btn" data-view="grid" title="Grid view" aria-label="Grid view"><i class="fas fa-th-large"></i></button>' +
                    '</span>' +
                '</span>';
            var pageTitle = document.querySelector('h1');
            if (pageTitle) bar.querySelector('.lv-heading-title').textContent = pageTitle.textContent.trim().replace(/\s+Management$/i, '') || 'Records';
            wrap.parentNode.insertBefore(bar, wrap);
            if (filterPanel) {
                var summary = filterPanel.querySelector(':scope > div.flex');
                if (summary) {
                    summary.classList.add('lv-records-summary');
                    bar.querySelector('.lv-heading > span:last-child').appendChild(summary);
                }
            }

            // Selection footer below the table
            var foot = document.createElement('div');
            foot.className = 'lv-foot';
            foot.innerHTML =
                '<span class="lv-selcount">0 of ' + rowCount + ' row(s) selected.</span>' +
                '<button type="button" class="lv-clear" hidden>Clear selection</button>';
            wrap.parentNode.insertBefore(foot, wrap.nextSibling);

            // Frame: rounded bordered card wrapping toolbar + table + footer.
            // If the table already sits inside a bordered card, drop the
            // extra frame border so we don't double-outline.
            var frame = document.createElement('div');
            frame.className = 'lv-frame';
            if (scroller.closest('.rounded-lg, .rounded-xl, .rounded-2xl, .rounded-3xl, .shadow-md, .shadow-lg, .bg-white')) {
                frame.classList.add('lv-frame-inner');
            }
            bar.parentNode.insertBefore(frame, bar);
            if (filtersEl) frame.appendChild(filtersEl);
            frame.appendChild(bar);
            frame.appendChild(wrap);
            frame.appendChild(foot);

            var searchInput = bar.querySelector('.lv-search input');
            var checkAll = table.querySelector('.lv-check-all');
            var selCount = foot.querySelector('.lv-selcount');
            var clearBtn = foot.querySelector('.lv-clear');

            function visibleRowChecks() {
                return Array.prototype.filter.call(
                    table.querySelectorAll('.lv-row-check'),
                    function (c) { return c.closest('tr').style.display !== 'none'; }
                );
            }

            function updateSelection() {
                var checks = table.querySelectorAll('.lv-row-check:checked');
                selCount.textContent = checks.length + ' of ' + rowCount + ' row(s) selected.';
                clearBtn.hidden = checks.length === 0;
                if (checkAll) {
                    var vis = visibleRowChecks();
                    checkAll.checked = vis.length > 0 && vis.every(function (c) { return c.checked; });
                    checkAll.indeterminate = !checkAll.checked && vis.some(function (c) { return c.checked; });
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    visibleRowChecks().forEach(function (c) { c.checked = checkAll.checked; });
                    updateSelection();
                });
            }
            table.addEventListener('change', function (e) {
                if (e.target.classList.contains('lv-row-check')) updateSelection();
            });
            clearBtn.addEventListener('click', function () {
                table.querySelectorAll('.lv-row-check:checked').forEach(function (c) { c.checked = false; });
                updateSelection();
            });

            // Combined visibility: search query AND status chip
            var filterState = { q: '', status: '' };
            function applyFilters() {
                var q = filterState.q;
                var st = filterState.status;
                table.querySelectorAll('tbody tr').forEach(function (tr) {
                    var matchQ = !q || tr.textContent.toLowerCase().indexOf(q) !== -1;
                    var matchS = !st || tr._lvStatus === st;
                    tr.style.display = (matchQ && matchS) ? '' : 'none';
                });
                updateSelection();
                instances.forEach(updateScroll);
            }

            if (searchInput) searchInput.addEventListener('input', function () {
                filterState.q = this.value.trim().toLowerCase();
                applyFilters();
            });

            if (filtersEl) {
                filtersEl.addEventListener('click', function (e) {
                    var chip = e.target.closest('.lv-chip');
                    if (!chip) return;
                    filtersEl.querySelectorAll('.lv-chip').forEach(function (c) {
                        c.classList.remove('lv-chip-active');
                    });
                    chip.classList.add('lv-chip-active');
                    filterState.status = chip.dataset.status;
                    applyFilters();
                });
            }

            instances.push({
                scroller: scroller,
                wrap: wrap,
                buttons: bar.querySelectorAll('.lv-btn')
            });
        });

        // Show the edge fade + swipe hint only while horizontal room remains.
        function updateScroll(inst) {
            var s = inst.scroller;
            var scrollable = s.scrollWidth > s.clientWidth + 4;
            var atEnd = !scrollable || s.scrollLeft + s.clientWidth >= s.scrollWidth - 4;
            inst.wrap.classList.toggle('lv-scrollable', scrollable);
            inst.wrap.classList.toggle('lv-at-end', atEnd);
        }

        instances.forEach(function (inst) {
            inst.scroller.addEventListener('scroll', function () {
                inst.wrap.classList.add('lv-scrolled');
                updateScroll(inst);
            }, { passive: true });
        });

        function refreshScrollState() {
            instances.forEach(updateScroll);
        }
        window.addEventListener('resize', refreshScrollState);
        window.addEventListener('load', refreshScrollState);

        function applyView(view) {
            instances.forEach(function (inst) {
                inst.scroller.classList.toggle('lv-cards', view === 'grid');
                inst.scroller.classList.toggle('lv-list', view === 'list');
                inst.buttons.forEach(function (btn) {
                    var active = btn.dataset.view === view;
                    btn.classList.toggle('lv-active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
            refreshScrollState();
        }

        var stored = null;
        try { stored = localStorage.getItem(LV_KEY); } catch (e) {}
        applyView(stored || (window.innerWidth < 768 ? 'grid' : 'list'));

        instances.forEach(function (inst) {
            inst.buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyView(btn.dataset.view);
                    try { localStorage.setItem(LV_KEY, btn.dataset.view); } catch (e) {}
                });
            });
        });
    });
</script>
