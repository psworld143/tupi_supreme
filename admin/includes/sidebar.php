<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}

// Bare-content mode for modal iframes (?embed=1): skip all chrome and
// neutralize the content wrapper's sidebar offset.
if (!empty($_GET['embed'])) {
    echo '<link rel="stylesheet" href="assets/admin-theme.css">';
    echo '<style>.lg\:ml-64{margin-left:0!important;padding:1.25rem!important}#admin-topbar,#sidebar,#sidebar-toggle,#sidebar-overlay,#admin-loader{display:none!important}a[href*="action=list"],a[href$="?status=saved"],a[href$="?status=deleted"]{display:none!important}.lg\:ml-64>div:has(h1){display:none!important}.lg\:ml-64>div.bg-white{border:0!important;border-radius:0!important;box-shadow:none!important;padding:0!important}body{background:#fff;overflow-y:auto}::-webkit-scrollbar{width:6px}::-webkit-scrollbar-thumb{background:#d4d4d8;border-radius:3px}</style>';
    return;
}

$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread messages count + recent items for the header notification dropdown
$db = getDB();
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$unread_messages = $result->fetch_assoc()['count'];

$recent_unread = [];
$recent_res = $db->query("SELECT id, name, subject, created_at FROM contact_messages WHERE is_read = 0 AND is_archived = 0 ORDER BY created_at DESC LIMIT 5");
if ($recent_res) {
    while ($row = $recent_res->fetch_assoc()) {
        $recent_unread[] = $row;
    }
}

$display_name = $current_user['full_name'] ?: $current_user['username'];
$avatar_initial = strtoupper(substr($display_name, 0, 1));
$role_label = ucwords(str_replace('_', ' ', $current_user['role'] ?? 'Admin'));
$site_short_name = 'Tupi Supreme';

$nav_groups = [
    'Overview' => [
        ['index.php',            'fa-home',           'Dashboard'],
        ['messages.php',         'fa-envelope',       'Messages'],
        ['broadcast.php',        'fa-bullhorn',       'Email Broadcast'],
    ],
    'Homepage' => [
        ['carousel.php',         'fa-images',         'Carousel'],
        ['homepage-features.php','fa-star',           'Homepage Features'],
        ['statistics.php',       'fa-chart-bar',      'Statistics'],
    ],
    'About Page' => [
        ['about.php',            'fa-info-circle',    'About Page'],
        ['team-members.php',     'fa-users',          'Team Members'],
        ['company-values.php',   'fa-gem',            'Company Values'],
        ['timeline.php',         'fa-history',        'Our Journey'],
        ['testimonials.php',     'fa-quote-left',     'Testimonials'],
        ['certifications.php',   'fa-certificate',    'Certifications'],
    ],
    'Products & Services' => [
        ['products.php',         'fa-cube',           'Products'],
        ['product-tabs.php',     'fa-folder',         'Product Tabs'],
        ['applications.php',     'fa-th-large',       'Applications'],
        ['services.php',         'fa-concierge-bell', 'Services'],
        ['service-items.php',    'fa-stream',         'Service Items'],
        ['case-studies.php',     'fa-book',           'Case Studies'],
    ],
    'Media & Content' => [
        ['pages.php',            'fa-file-alt',       'Pages'],
        ['gallery.php',          'fa-photo-video',    'Gallery'],
        ['resources.php',        'fa-file-download',  'Resources'],
        ['faqs.php',             'fa-question-circle','FAQs'],
    ],
    'Contact & Footer' => [
        ['contact-info.php',     'fa-address-book',   'Contact Info'],
        ['office-hours.php',     'fa-clock',          'Office Hours'],
        ['subject-options.php',  'fa-list-ul',        'Subject Options'],
        ['social-media.php',     'fa-share-alt',      'Social Media'],
        ['footer-links.php',     'fa-link',           'Footer Links'],
    ],
    'System' => [
        ['modules.php',          'fa-toggle-on',      'Site Modules'],
        ['site-settings.php',    'fa-sliders-h',      'Site Settings'],
        ['login-background.php', 'fa-sign-in-alt',    'Login Page'],
        ['settings.php',         'fa-cog',            'Settings'],
    ],
];  

// Friendly page title for the top header bar
$header_title = 'Admin';
foreach ($nav_groups as $items) {
    foreach ($items as $item) {
        if ($current_page === $item[0]) {
            $header_title = $item[2];
            break 2;
        }
    }
}
?>
<!-- Admin theme: Inter font + shadcn-style zinc design tokens -->
<link rel="stylesheet" href="assets/admin-theme.css?v=<?php echo @filemtime(__DIR__ . '/../assets/admin-theme.css'); ?>">

<!-- Page loader — covers only the content area (sidebar + topbar stay visible) -->
<div id="admin-loader" class="fixed top-14 left-0 right-0 bottom-0 lg:left-64 z-[35] bg-white flex flex-col items-center justify-center gap-8 transition-all duration-500">
    <div class="relative w-24 h-24 flex items-center justify-center">
        <span class="absolute inset-0 rounded-full border-2 border-[#2c5530]/30 animate-[alring_1.8s_cubic-bezier(.2,.6,.4,1)_infinite]"></span>
        <span class="absolute inset-0 rounded-full border-2 border-[#2c5530]/30 animate-[alring_1.8s_cubic-bezier(.2,.6,.4,1)_infinite] [animation-delay:.9s]"></span>
        <img src="../uploads/images/tupi_supreme_logo.png" alt="" class="w-14 h-14 object-contain animate-[alpulse_1.6s_ease-in-out_infinite]" onerror="this.style.display='none'">
    </div>
    <div class="flex flex-col items-center gap-3">
        <div class="w-40 h-[3px] rounded-full bg-zinc-100 overflow-hidden">
            <div class="h-full w-1/3 rounded-full bg-[#2c5530] animate-[albar_1.2s_ease-in-out_infinite]"></div>
        </div>
        <p class="text-[11px] font-medium tracking-[0.15em] uppercase text-zinc-400">Loading console</p>
    </div>
</div>
<style>
    @keyframes alring { 0% { transform: scale(.55); opacity: .9; } 100% { transform: scale(1.45); opacity: 0; } }
    @keyframes alpulse { 0%,100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: .85; } }
    @keyframes albar { 0% { transform: translateX(-120%); } 100% { transform: translateX(400%); } }
    @media (prefers-reduced-motion: reduce) { #admin-loader * { animation: none !important; } }
    /* Track the collapsed sidebar rail */
    @media (min-width: 1024px) {
        body.sidebar-collapsed #admin-loader { left: 4rem; }
    }
</style>
<script>
    // Keep the loader up for at least 1.4s so the UI never flashes,
    // then fade it out once the page (or the failsafe) is ready.
    (function () {
        var el = document.getElementById('admin-loader');
        if (!el) return;
        var shownAt = Date.now();
        var MIN_MS = 1400;
        function hide() {
            var wait = Math.max(0, MIN_MS - (Date.now() - shownAt));
            setTimeout(function () {
                el.style.opacity = '0';
                el.style.pointerEvents = 'none';
                setTimeout(function () { el.remove(); }, 500);
            }, wait);
        }
        if (document.readyState === 'complete') { hide(); }
        else { window.addEventListener('load', hide); setTimeout(hide, 5000); }
    })();
</script>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 bottom-0 left-0 z-40 w-64 transition-all duration-300 -translate-x-full lg:translate-x-0 bg-white text-zinc-700 border-r border-zinc-200 flex flex-col">
    <!-- Workspace header (pinned — stays put while the nav scrolls) -->
    <div class="sidebar-header flex items-center gap-2.5 px-4 pt-4 pb-3 flex-shrink-0">
        <img src="../uploads/images/tupi_supreme_logo.png" alt="" class="w-6 h-6 rounded-md object-contain flex-shrink-0">
        <p class="sidebar-label min-w-0 flex-1 font-medium text-sm text-zinc-900 truncate"><?php echo htmlspecialchars($site_short_name); ?></p>
        <!-- Desktop collapse button — shown while the sidebar is expanded -->
        <button type="button" title="Collapse sidebar" class="sidebar-collapse-alt hidden lg:flex w-7 h-7 rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 items-center justify-center transition-colors flex-shrink-0">
            <i class="fas fa-bars text-xs"></i>
        </button>
    </div>

    <!-- Command / nav filter bar -->
    <div class="sidebar-label px-3 pb-2 flex-shrink-0">
        <div class="flex items-center gap-2 h-8 px-2.5 rounded-md border border-zinc-200 bg-white focus-within:border-[#2c5530] focus-within:ring-2 focus-within:ring-[#2c5530]/15 transition-shadow">
            <i class="fas fa-search text-[11px] text-zinc-400 flex-shrink-0"></i>
            <input id="nav-filter" type="text" placeholder="Search pages" autocomplete="off"
                   class="min-w-0 flex-1 bg-transparent text-[13px] text-zinc-900 placeholder:text-zinc-400 focus:outline-none">
            <kbd class="hidden sm:inline-flex items-center gap-0.5 text-[10px] text-zinc-400 font-sans flex-shrink-0">⌘/</kbd>
        </div>
    </div>

    <div class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-5">
        <?php foreach ($nav_groups as $group_label => $items):
            $group_key = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $group_label));
            $group_has_active = false;
            foreach ($items as $it) {
                if ($it[0] === $current_page) { $group_has_active = true; break; }
            }
        ?>
            <div class="nav-group<?php echo $group_has_active ? ' has-active' : ''; ?>" data-group="<?php echo $group_key; ?>">
                <button type="button" class="nav-group-toggle section-label flex w-full items-center gap-1.5 px-2 pt-4 pb-1.5 text-[11px] font-medium uppercase tracking-wider text-zinc-400 hover:text-zinc-600 transition-colors" aria-expanded="true">
                    <i class="fas fa-chevron-down text-[8px] transition-transform duration-200"></i>
                    <span><?php echo $group_label; ?></span>
                </button>
                <nav class="nav-group-items space-y-0.5">
                    <?php foreach ($items as $item):
                        $is_active = $current_page === $item[0];
                    ?>
                        <a href="<?php echo $item[0]; ?>" title="<?php echo $item[2]; ?>" data-nav-item class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-md text-[13px] transition-colors <?php echo $is_active ? 'bg-[#e9f1ea] text-[#2c5530] font-medium' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'; ?>">
                            <i class="fas <?php echo $item[1]; ?> w-4 text-center text-[12px]"></i>
                            <span class="sidebar-label truncate"><?php echo $item[2]; ?></span>
                            <?php if ($item[0] === 'messages.php' && $unread_messages > 0): ?>
                                <span class="sidebar-badge ml-auto bg-[#2c5530] text-white text-[10px] font-medium rounded px-1.5 py-px"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        <?php endforeach; ?>
    </div>
</aside>

<!-- Top header bar — markup lives in includes/header.php -->
<?php include __DIR__ . '/header.php'; ?>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<!-- Mobile Menu Button -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 lg:hidden w-9 h-9 bg-white text-zinc-900 rounded-md border border-zinc-200 shadow-sm flex items-center justify-center">
    <i class="fas fa-bars"></i>
</button>

<style>
    /* Layout rules live here; all colors come from assets/admin-theme.css */

    /* Main content is a full-height white panel flush with the viewport edges */
    .lg\:ml-64 {
        background: #ffffff;
        margin: 0;
        min-height: 100vh;
        /* Clear the fixed top header (3.5rem bar + breathing room) */
        padding-top: 4.5rem !important;
        /* Animate its margin so it moves in sync with the collapsing sidebar */
        transition: margin-left 0.3s ease;
    }

    /* Dock the mobile menu button inside the top header bar */
    #sidebar-toggle {
        top: 0.375rem;
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1), top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Mobile drawer open: the toggle rides along with the sidebar and lands
       where the old close button sat — top-right of the sidebar header
       (16rem sidebar - 1.25rem header padding - 2.75rem button width) */
    @media (max-width: 1023.98px) {
        body.sidebar-open #sidebar-toggle {
            left: 12rem;
            top: 0.625rem;
        }
    }

    /* Sidebar scrollbar — blends into the white rail */
    #sidebar .sidebar-scroll {
        scrollbar-width: thin;
        scrollbar-color: #d4d4d8 #ffffff;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-track {
        background: #ffffff;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #d4d4d8;
        border-radius: 4px;
        border: 2px solid #ffffff;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #a1a1aa;
    }

    /* Collapsible nav groups — closed state hides items and rotates the chevron */
    .nav-group.closed .nav-group-items {
        display: none;
    }
    .nav-group.closed .nav-group-toggle .fa-chevron-down {
        transform: rotate(-90deg);
    }

    @media (min-width: 1024px) {
        /* Clear the full-height sidebar (16rem panel, flush edges) */
        .lg\:ml-64 {
            margin: 0 0 0 16rem !important;
            min-height: 100vh;
        }

        /* Collapsed (desktop-only) state: shrink sidebar to an icon rail */
        body.sidebar-collapsed #sidebar {
            width: 4rem;
        }
        body.sidebar-collapsed #sidebar .sidebar-label,
        body.sidebar-collapsed #sidebar .sidebar-badge,
        body.sidebar-collapsed #sidebar .section-label {
            display: none;
        }
        body.sidebar-collapsed #sidebar nav a {
            justify-content: center;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        body.sidebar-collapsed #sidebar .sidebar-header {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        /* In-sidebar hamburger disappears once collapsed — the topbar one takes over */
        body.sidebar-collapsed .sidebar-collapse-alt {
            display: none;
        }
        body.sidebar-collapsed #sidebar .sidebar-footer {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        /* Icon rail ignores group open/closed state — all icons stay reachable */
        body.sidebar-collapsed .nav-group.closed .nav-group-items {
            display: block;
        }
        body.sidebar-collapsed .nav-group + .nav-group {
            margin-top: 0.5rem;
        }
        body.sidebar-collapsed .lg\:ml-64 {
            margin-left: 4rem !important;
        }
    }
</style>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    // Two collapse buttons share one handler: inside the sidebar while
    // expanded, inside the topbar while collapsed.
    const collapseBtns = document.querySelectorAll('#sidebar-collapse, .sidebar-collapse-alt');

    const COLLAPSE_KEY = 'tsaci_sidebar_collapsed';

    function applyDesktopState() {
        const collapsed = localStorage.getItem(COLLAPSE_KEY) === '1';
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        collapseBtns.forEach(function (btn) {
            btn.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        });
    }

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.remove('hidden');
        document.body.classList.add('sidebar-open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        document.body.style.overflow = '';
    }

    function isSidebarOpen() {
        return !sidebar.classList.contains('-translate-x-full');
    }

    // Hamburger toggles open/close instead of only opening
    sidebarToggle?.addEventListener('click', function() {
        if (isSidebarOpen()) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // Escape closes the mobile sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isSidebarOpen() && window.innerWidth < 1024) {
            closeSidebar();
        }
    });

    // Desktop collapse/expand toggle (throttled to 350ms so rapid clicks can't
    // interrupt the width/margin transitions and leave the sidebar overlapping content)
    let isCollapsing = false;
    collapseBtns.forEach(function (btn) {
        btn.addEventListener('click', function() {
            if (isCollapsing) return;
            isCollapsing = true;
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem(COLLAPSE_KEY, isCollapsed ? '0' : '1');
            applyDesktopState();
            setTimeout(function() {
                isCollapsing = false;
            }, 350);
        });
    });

    // Apply persisted state on load (desktop only)
    if (window.innerWidth >= 1024) {
        applyDesktopState();
    }

    // Close sidebar on window resize if switching to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            closeSidebar();
        }
    });

    // Safety net: if the page is shown from the back/forward cache with the
    // mobile sidebar left open, clear the overlay + body scroll lock so the
    // page never ends up in an unclickable state.
    window.addEventListener('pageshow', function(e) {
        if (e.persisted && isSidebarOpen() && window.innerWidth < 1024) {
            closeSidebar();
        }
    });

    // Sidebar scroll position persistence — nav links trigger full page loads,
    // which would reset the scrollable nav to the top. Keep the current
    // scrollTop in sessionStorage and restore it after each navigation.
    const sidebarScroll = document.querySelector('#sidebar .sidebar-scroll');
    const SCROLL_KEY = 'tsaci_sidebar_scroll';

    function saveSidebarScroll() {
        if (sidebarScroll) {
            sessionStorage.setItem(SCROLL_KEY, String(sidebarScroll.scrollTop));
        }
    }

    function restoreSidebarScroll() {
        if (!sidebarScroll) return;
        const pos = parseInt(sessionStorage.getItem(SCROLL_KEY), 10);
        if (!isNaN(pos) && pos > 0) {
            sidebarScroll.scrollTop = pos;
        }
    }

    // Restore immediately (the sidebar markup is already parsed above this
    // script) and again on full load in case late-loading content changed
    // the scrollable height.
    restoreSidebarScroll();
    window.addEventListener('load', restoreSidebarScroll);

    // Keep the saved position current as the user scrolls.
    sidebarScroll?.addEventListener('scroll', saveSidebarScroll, { passive: true });

    // Capture the position right before navigation/unload as a safety net.
    sidebarScroll?.addEventListener('click', function(e) {
        if (e.target.closest('a')) saveSidebarScroll();
    });
    window.addEventListener('pagehide', saveSidebarScroll);

    // Collapsible nav groups — open by default; manually closed groups are
    // remembered in localStorage, except the group holding the current page
    // which always opens so the active link stays visible.
    const NAV_GROUPS_KEY = 'tsaci_sidebar_nav_groups';
    let navGroupState = {};
    try {
        navGroupState = JSON.parse(localStorage.getItem(NAV_GROUPS_KEY) || '{}') || {};
    } catch (e) {
        navGroupState = {};
    }

    document.querySelectorAll('#sidebar .nav-group').forEach(function(group) {
        const toggle = group.querySelector('.nav-group-toggle');
        if (!toggle) return;

        const open = group.classList.contains('has-active') || navGroupState[group.dataset.group] !== false;
        group.classList.toggle('closed', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        toggle.addEventListener('click', function() {
            const closed = group.classList.toggle('closed');
            navGroupState[group.dataset.group] = !closed;
            toggle.setAttribute('aria-expanded', closed ? 'false' : 'true');
            try {
                localStorage.setItem(NAV_GROUPS_KEY, JSON.stringify(navGroupState));
            } catch (e) {}
        });
    });

    // Nav filter ("Command" bar) — ⌘/ or Ctrl+K focuses it; typing filters
    // nav items by label and auto-expands groups that have matches.
    const navFilter = document.getElementById('nav-filter');

    document.addEventListener('keydown', function(e) {
        if ((e.key === '/' && (e.metaKey || e.ctrlKey)) || (e.key === 'k' && (e.metaKey || e.ctrlKey))) {
            e.preventDefault();
            navFilter?.focus();
            navFilter?.select();
        }
    });

    navFilter?.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#sidebar .nav-group').forEach(function(group) {
            let anyVisible = false;
            group.querySelectorAll('[data-nav-item]').forEach(function(item) {
                const match = !q || item.textContent.toLowerCase().includes(q);
                item.style.display = match ? '' : 'none';
                if (match) anyVisible = true;
            });
            group.style.display = anyVisible ? '' : 'none';
            if (q && anyVisible) {
                group.classList.remove('closed');
                group.querySelector('.nav-group-toggle')?.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Enter jumps to the first visible match
    navFilter?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const first = document.querySelector('#sidebar [data-nav-item]:not([style*="none"])');
            if (first) window.location.href = first.href;
        } else if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
            this.blur();
        }
    });
</script>

<!-- Global add/edit modal + List/Grid view toggle for admin list tables -->
<?php include __DIR__ . '/modal.php'; ?>
<?php include __DIR__ . '/filters.php'; ?>
<?php include __DIR__ . '/view-toggle.php'; ?>

<script>
// Normalize every admin page header to the messages.php style:
// compact icon chip + text-xl title + muted subtitle, no boxed card.
document.addEventListener('DOMContentLoaded', function () {
    var content = document.querySelector('.lg\:ml-64');
    if (!content) return;
    var h1 = content.querySelector('h1');
    if (!h1) return;
    var block = h1.parentElement;
    if (!block || (block.parentElement && block.parentElement.querySelector(':scope > .pg-icon'))) return;

    var onlyFa = function (cls) {
        return (cls || '').split(/\s+/).filter(function (c) { return /^fa[bsrltd]?$|^fa-/.test(c); }).join(' ');
    };

    // Flatten the old-style header card (bg-white rounded-lg shadow-md p-6)
    // so the header breathes like the messages page — keep only its margin.
    var card = h1.closest('.bg-white');
    if (card && card.parentElement === content) card.className = 'mb-6';

    // Compact title
    h1.className = 'text-xl font-semibold text-zinc-900';

    // Icon: reuse the h1's own inline icon when present, else this page's nav icon
    var iconClass = '';
    var inlineIcon = h1.querySelector('i');
    if (inlineIcon) {
        iconClass = onlyFa(inlineIcon.className);
        inlineIcon.remove();
    }
    if (!iconClass) {
        var page = location.pathname.split('/').pop();
        var navIcon = document.querySelector('#sidebar a[href="' + page + '"] i');
        iconClass = navIcon ? onlyFa(navIcon.className) : 'fas fa-file';
    }

    // Normalize subtitle styling
    var sub = block.querySelector('p');
    if (sub) sub.className = 'text-sm text-zinc-500 mt-0.5';

    var chip = document.createElement('span');
    chip.className = 'pg-icon w-10 h-10 rounded-lg bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center flex-shrink-0';
    chip.innerHTML = '<i class="' + iconClass + '"></i>';

    var wrap = document.createElement('div');
    wrap.className = 'flex items-center gap-3';
    block.parentNode.insertBefore(wrap, block);
    wrap.appendChild(chip);
    wrap.appendChild(block);
});
</script>
