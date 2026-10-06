<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// Global modal — intercepts ?action=add / ?action=edit links inside the
// content area and opens them in an iframe dialog instead of navigating.
// The embedded page renders in "bare" mode (?embed=1, handled by
// sidebar.php). When the form's PRG redirect lands back on a URL with no
// action param, the modal closes and the parent page reloads.
?>
<div id="admin-modal" class="fixed inset-0 z-[60] hidden">
    <div class="am-overlay absolute inset-0 bg-zinc-950/40 backdrop-blur-[2px] opacity-0 transition-opacity duration-200"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-6 pointer-events-none">
        <div id="am-dialog" class="pointer-events-auto w-full max-w-4xl max-h-[85vh] flex flex-col bg-white rounded-lg border border-zinc-200 shadow-xl opacity-0 translate-y-3 transition-all duration-200 overflow-hidden" role="dialog" aria-modal="true">
            <div class="flex items-center gap-3 px-5 py-3.5 border-b border-zinc-200 flex-shrink-0">
                <span class="w-6 h-6 rounded-md bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center flex-shrink-0">
                    <i id="am-icon" class="fas fa-pen text-[10px]"></i>
                </span>
                <h2 id="am-title" class="min-w-0 flex-1 text-sm font-semibold text-zinc-900 truncate">Edit</h2>
                <button type="button" id="am-close" title="Close (Esc)" class="w-7 h-7 rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 flex items-center justify-center transition-colors flex-shrink-0">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            <div id="am-body" class="relative bg-white" style="height:360px; transition:height .25s ease;">
                <div id="am-spinner" class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-3 bg-white">
                    <i class="fas fa-circle-notch fa-spin text-[#2c5530] text-xl"></i>
                    <p class="text-xs text-zinc-400">Loading form…</p>
                </div>
                <iframe id="am-frame" class="w-full h-full border-0" title="Dialog content"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal   = document.getElementById('admin-modal');
    var overlay = modal.querySelector('.am-overlay');
    var dialog  = document.getElementById('am-dialog');
    var frame   = document.getElementById('am-frame');
    var spinner = document.getElementById('am-spinner');
    var titleEl = document.getElementById('am-title');
    var closeBtn = document.getElementById('am-close');
    var bodyEl   = document.getElementById('am-body');
    var resizeObs = null;

    // Fit the dialog to the embedded form's actual height (capped at 85vh
    // minus the title bar) so short forms look like a modal, not a screen.
    function fitHeight() {
        try {
            var doc = frame.contentWindow.document;
            var h = Math.max(doc.documentElement.scrollHeight, doc.body ? doc.body.scrollHeight : 0);
            var max = Math.floor(window.innerHeight * 0.85) - 52;
            bodyEl.style.height = Math.min(Math.max(h + 2, 240), max) + 'px';
        } catch (e) {}
    }

    function isFormAction(href) {
        return /[?&]action=(add|edit)\b/.test(href);
    }
    function hasEmbed(url) {
        return /[?&]embed=1\b/.test(url);
    }
    function withEmbed(url) {
        return url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
    }

    function openModal(url, title) {
        titleEl.textContent = title || 'Edit';
        document.getElementById('am-icon').className =
            'fas ' + (/[?&]action=add\b/.test(url) ? 'fa-plus' : 'fa-pen') + ' text-[10px]';
        frame.src = withEmbed(url);
        bodyEl.style.height = '360px';
        spinner.style.display = 'flex';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            overlay.classList.remove('opacity-0');
            dialog.classList.remove('opacity-0', 'translate-y-3');
        });
    }

    function closeModal() {
        overlay.classList.add('opacity-0');
        dialog.classList.add('opacity-0', 'translate-y-3');
        setTimeout(function () {
            modal.classList.add('hidden');
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }, 200);
    }

    // After the iframe's PRG redirect lands on a non-form URL, the save is
    // done — close and reload the parent so the list shows fresh data.
    frame.addEventListener('load', function () {
        spinner.style.display = 'none';
        fitHeight();
        // Re-fit if the form grows (validation errors, expanding fields).
        try {
            if (resizeObs) resizeObs.disconnect();
            resizeObs = new ResizeObserver(fitHeight);
            resizeObs.observe(frame.contentWindow.document.body);
        } catch (e) {}
        try {
            var href = frame.contentWindow.location.href;
            // The form's PRG redirect drops ?action (and ?embed) — landing on
            // a plain page URL inside the frame means the save completed.
            if (href.indexOf('.php') !== -1 && !isFormAction(href)) {
                closeModal();
                window.location.reload();
            }
        } catch (e) { /* cross-origin — leave open */ }
    });

    // Intercept add/edit links anywhere in the content area.
    // Opt out per-link with data-no-modal.
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('data-no-modal') || a.target === '_blank') return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^https?:/i.test(href)) return;
        if (!isFormAction(href)) return;
        e.preventDefault();
        var label = (a.textContent || '').trim().replace(/\s+/g, ' ');
        openModal(href, label.length > 2 && label.length < 60 ? label : null);
    });

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();
</script>
