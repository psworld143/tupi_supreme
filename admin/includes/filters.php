<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// Filter bar component — included by sidebar.php before view-toggle.php.
// Upgrades any server-side GET form that contains a <select> or a search
// input into a modern labeled filter panel: custom chevron selects, icon
// search field, primary/ghost button hierarchy, and a "Filters applied"
// indicator. Pure progressive enhancement — forms still submit normally.
?>
<style>
    .lv-filterbar {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        align-items: end;
        gap: 0.625rem 0.75rem;
        padding: 1.125rem 1.25rem;
        background: #fff;
        border: 1px solid #e4e4e7;
        border-radius: 0.625rem;
    }
    .lv-filter-panel .lv-filterbar { padding: 0; border: 0; border-radius: 0; }
    .lv-filterbar div:not(.lv-filter-field):not(.lv-filter-actions):not(.lv-filter-head) {
        display: contents;
    }
    .lv-filter-head {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.125rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #18181b;
    }
    .lv-filter-head i { font-size: 0.6875rem; color: #2c5530; }
    .lv-filter-field {
        grid-column: span 4;
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
        min-width: 0;
    }
    .lv-filter-field > label {
        font-size: 0.6875rem;
        font-weight: 500;
        color: #52525b;
    }
    .lv-filter-field .lv-select,
    .lv-filter-field .lv-field { width: 100%; flex: none; }
    .lv-filter-field .lv-select select { width: 100%; min-width: 0; }
    .lv-filter-actions {
        grid-column: span 2;
        display: flex;
        align-items: flex-end;
        align-self: end;
        gap: 0.5rem;
        min-width: 0;
    }
    .lv-filterbar select,
    .lv-filterbar input[type="text"],
    .lv-filterbar input[type="search"],
    .lv-filterbar input[type="number"],
    .lv-filterbar input[type="date"],
    .lv-filterbar input[type="month"] {
        height: 2.25rem !important;
        padding: 0 0.625rem !important;
        border: 1px solid #e4e4e7 !important;
        border-radius: 0.375rem !important;
        background-color: #fff !important;
        font-size: 0.8125rem !important;
        color: #3f3f46;
        box-shadow: none !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        width: auto;
        min-width: 0;
    }
    .lv-filterbar select:focus,
    .lv-filterbar input:focus {
        border-color: #2c5530 !important;
        box-shadow: 0 0 0 2px rgba(44, 85, 48, 0.12) !important;
        outline: none;
    }
    .lv-filterbar .lv-select { position: relative; display: inline-flex; }
    .lv-filterbar .lv-select select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 1.875rem !important;
        min-width: 9rem;
        cursor: pointer;
    }
    .lv-filterbar .lv-select::after {
        content: "\f078";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.5625rem;
        color: #a1a1aa;
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }
    .lv-filterbar .lv-field {
        position: relative;
        display: inline-flex;
        align-items: center;
        min-width: 11rem;
    }
    .lv-filterbar .lv-field > i {
        position: absolute;
        left: 0.75rem;
        font-size: 0.6875rem;
        color: #a1a1aa;
        pointer-events: none;
    }
    .lv-filterbar .lv-field input {
        width: 100%;
        padding-left: 2rem !important;
    }
    .lv-filterbar .lv-fbtn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        height: 2.25rem;
        padding: 0 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .lv-filterbar .lv-fbtn-primary {
        background: #2c5530;
        border: 1px solid #2c5530;
        color: #fff !important;
    }
    .lv-filterbar .lv-fbtn-primary:hover { background: #22402a; border-color: #22402a; }
    .lv-filterbar .lv-fbtn-ghost {
        background: #fff;
        border: 1px solid #e4e4e7;
        color: #3f3f46 !important;
    }
    .lv-filterbar .lv-fbtn-ghost:hover { background: #f4f4f5; color: #18181b !important; }
    .lv-filterbar .lv-filtered {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        height: 1.625rem;
        padding: 0 0.625rem;
        border-radius: 9999px;
        background: #e9f1ea;
        color: #2c5530;
        font-size: 0.6875rem;
        font-weight: 500;
    }
    .lv-filterbar .lv-filtered i { font-size: 0.5625rem; }
    @media (max-width: 639px) {
        .lv-filter-field { grid-column: 1 / -1 !important; }
        .lv-filterbar .lv-filter-actions { grid-column: 1 / -1 !important; flex-wrap: wrap; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(function (form) {
            if ((form.method || 'get').toLowerCase() !== 'get') return;
            if (form.closest('.lv-filterbar')) return;
            var selects = form.querySelectorAll('select');
            var searchInputs = form.querySelectorAll(
                'input[name="q"], input[name="search"], input[type="search"], input[placeholder*="earch"]'
            );
            if (!selects.length && !searchInputs.length) return;

            form.classList.add('lv-filterbar');
            form.style.display = 'grid';
            form.style.gridTemplateColumns = 'repeat(12, minmax(0, 1fr))';
            form.style.alignItems = 'end';
            form.style.gap = '0.625rem 0.75rem';

            var head = document.createElement('div');
            head.className = 'lv-filter-head';
            head.style.gridColumn = '1 / -1';
            head.innerHTML = '<i class="fas fa-filter"></i> Filters';
            form.insertBefore(head, form.firstChild);

            function fieldLabel(el) {
                var raw = el.name || el.id || '';
                if (/^(q|search|query)$/i.test(raw)) return 'Search';
                var pretty = raw.replace(/^filter[_-]?/i, '').replace(/[_-]+/g, ' ').trim()
                    || raw.replace(/[_-]+/g, ' ').trim();
                if (pretty) {
                    pretty = pretty.charAt(0).toUpperCase() + pretty.slice(1);
                    return pretty;
                }
                if (el.tagName === 'SELECT' && el.options.length) {
                    return el.options[0].textContent.trim();
                }
                return (el.placeholder || 'Search').trim();
            }

            function wrapField(controlWrap, labelText) {
                var f = document.createElement('div');
                f.className = 'lv-filter-field';
                f.style.gridColumn = 'span 4';
                var lb = document.createElement('label');
                lb.textContent = labelText;
                var input = controlWrap.matches('input, select') ? controlWrap : controlWrap.querySelector('input, select');
                if (input) {
                    if (!input.id) input.id = 'lv-filter-' + Math.random().toString(36).slice(2);
                    lb.htmlFor = input.id;
                }
                controlWrap.parentNode.insertBefore(f, controlWrap);
                f.appendChild(lb);
                f.appendChild(controlWrap);
            }

            selects.forEach(function (sel) {
                var w = document.createElement('span');
                w.className = 'lv-select';
                sel.parentNode.insertBefore(w, sel);
                w.appendChild(sel);
                wrapField(w, fieldLabel(sel));
            });

            searchInputs.forEach(function (inp) {
                if (inp.closest('.lv-field, .lv-search')) return;
                var icon = null;
                var par = inp.parentElement;
                if (par) {
                    var cand = par.querySelector('i.fas, i.fa');
                    if (cand && cand !== inp) icon = cand;
                }
                var w = document.createElement('span');
                w.className = 'lv-field';
                inp.parentNode.insertBefore(w, inp);
                w.appendChild(inp);
                if (icon) {
                    w.insertBefore(icon, inp);
                } else {
                    var ic = document.createElement('i');
                    ic.className = 'fas fa-search';
                    w.insertBefore(ic, inp);
                }
                wrapField(w, fieldLabel(inp));
            });

            form.querySelectorAll('input').forEach(function (el) {
                if (el.type === 'hidden' || el.type === 'submit') return;
                if (el.closest('.lv-filter-field, .lv-field, .lv-search')) return;
                wrapField(el, fieldLabel(el));
            });

            var actionEls = [];
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
                btn.classList.add('lv-fbtn', 'lv-fbtn-primary');
                actionEls.push(btn);
            });
            form.querySelectorAll('a, button').forEach(function (b) {
                if (b.type === 'submit') return;
                if (/clear|reset/i.test(b.textContent)) {
                    b.classList.add('lv-fbtn', 'lv-fbtn-ghost');
                    actionEls.push(b);
                }
            });

            // Distribute fields across the row, leaving a slot for the
            // Filter/Clear buttons so they sit inline with the inputs.
            var fields = form.querySelectorAll('.lv-filter-field');
            var actionsSpan = actionEls.length ? 2 : 0;
            var span = Math.max(2, Math.floor((12 - actionsSpan) / Math.max(1, fields.length)));
            fields.forEach(function (f) { f.style.gridColumn = 'span ' + span; });

            if (actionEls.length) {
                var actions = document.createElement('div');
                actions.className = 'lv-filter-actions';
                actions.style.gridColumn = 'span ' + actionsSpan;
                actions.style.alignSelf = 'end';
                actionEls.forEach(function (el) { actions.appendChild(el); });
                form.appendChild(actions);
            }

            var isFiltered = false;
            form.querySelectorAll('input, select').forEach(function (el) {
                if (el.type === 'hidden' || el.type === 'submit') return;
                if (el.tagName === 'SELECT') {
                    if (el.selectedIndex > 0) isFiltered = true;
                } else if (el.value && el.value.trim() !== '') {
                    isFiltered = true;
                }
            });
            if (isFiltered) {
                var tag = document.createElement('span');
                tag.className = 'lv-filtered';
                tag.innerHTML = '<i class="fas fa-filter"></i> Filters applied';
                (actionEls.length ? actions : form).appendChild(tag);
            }
        });
    });
</script>
