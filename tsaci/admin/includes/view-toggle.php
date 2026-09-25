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
    /* Segmented view toggle injected above each list table */
    .lv-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #fff;
        border-bottom: 1px solid #eef3f0;
    }
    .lv-toolbar .lv-view-label {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #8a978f;
        margin-right: 0.125rem;
    }
    .lv-seg {
        display: inline-flex;
        gap: 2px;
        padding: 2px;
        background: #f1f5f2;
        border: 1px solid #e6ece8;
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
        color: #66746c;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .lv-btn:hover {
        color: #23332c;
    }
    .lv-btn.lv-active {
        background: #23332c;
        color: #fff;
    }
    .lv-btn:focus-visible {
        outline: 2px solid #3d7a66;
        outline-offset: 1px;
    }

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
        box-shadow: 1px 0 0 0 #eef3f0;
    }
    .lv-list thead th:first-child {
        background-color: #f9fafb; /* matches thead bg-gray-50 */
        z-index: 11;
    }
    .lv-list tbody tr:hover td:first-child {
        background-color: #f9fafb; /* matches hover:bg-gray-50 */
    }

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
        background: rgba(35, 51, 44, 0.88);
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
        background: #f7faf8;
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
        border: 1px solid #e6ece8;
        border-radius: 0.875rem;
    }
    .lv-cards td {
        display: block;
        min-width: 0;
        padding: 0.4rem 0;
        border: 0;
        font-size: 0.8125rem;
        color: #45524b;
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
        color: #8a978f;
    }

    /* First column is the row title — slightly emphasized */
    .lv-cards td.lv-title {
        padding-top: 0;
        font-size: 0.9375rem;
    }
    .lv-cards td.lv-title > *:first-child {
        font-weight: 600;
        color: #23332c;
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
        border-top: 1px solid #eef3f0;
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

            // Tag each body cell with its column header text so cards can
            // render a label above every value.
            var headers = Array.prototype.map.call(
                table.querySelectorAll('thead th'),
                function (th) { return th.textContent.trim(); }
            );

            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var cells = tr.querySelectorAll('td');
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

            // Segmented List / Grid switch, inserted directly above the table.
            var bar = document.createElement('div');
            bar.className = 'lv-toolbar';
            bar.innerHTML =
                '<span class="lv-view-label">View</span>' +
                '<span class="lv-seg" role="group" aria-label="Table view">' +
                    '<button type="button" class="lv-btn" data-view="list" title="List view" aria-label="List view"><i class="fas fa-list"></i></button>' +
                    '<button type="button" class="lv-btn" data-view="grid" title="Grid view" aria-label="Grid view"><i class="fas fa-th-large"></i></button>' +
                '</span>';
            wrap.parentNode.insertBefore(bar, wrap);

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
