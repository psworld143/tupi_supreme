<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
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
<!-- Poppins font + admin theme -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 bottom-0 left-0 z-40 w-64 transition-all duration-300 -translate-x-full lg:translate-x-0 bg-[#f5f7f5] text-[#45524b] lg:border-r lg:border-black/5 flex flex-col">
    <!-- Sidebar header (pinned — stays put while the nav scrolls) -->
    <div class="sidebar-header flex items-center gap-3 px-5 pt-5 pb-4 flex-shrink-0">
        <p class="sidebar-label min-w-0 flex-1 font-semibold text-[15px] text-[#23332c] truncate"><?php echo htmlspecialchars($site_short_name); ?></p>
        <!-- Desktop collapse button -->
        <button id="sidebar-collapse" type="button" title="Collapse sidebar" class="hidden lg:flex w-8 h-8 rounded-full text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c] items-center justify-center transition-colors flex-shrink-0">
            <i class="fas fa-bars text-sm"></i>
        </button>
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
                <button type="button" class="nav-group-toggle section-label flex w-full items-center justify-between px-3.5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-[#96a39b] hover:text-[#66746c] transition-colors" aria-expanded="true">
                    <span><?php echo $group_label; ?></span>
                    <i class="fas fa-chevron-down text-[9px] transition-transform duration-200"></i>
                </button>
                <nav class="nav-group-items space-y-1">
                    <?php foreach ($items as $item):
                        $is_active = $current_page === $item[0];
                    ?>
                        <a href="<?php echo $item[0]; ?>" title="<?php echo $item[2]; ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors <?php echo $is_active ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?>">
                            <i class="fas <?php echo $item[1]; ?> w-5 text-center"></i>
                            <span class="sidebar-label"><?php echo $item[2]; ?></span>
                            <?php if ($item[0] === 'messages.php' && $unread_messages > 0): ?>
                                <span class="sidebar-badge ml-auto bg-[#23332c] text-white text-[10px] font-semibold rounded-full px-2 py-0.5"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom profile block -->
    <div class="sidebar-footer flex items-center gap-3 px-5 py-4 border-t border-black/5 flex-shrink-0">
        <div class="w-9 h-9 rounded-full bg-[#23332c] text-white flex items-center justify-center text-xs font-semibold flex-shrink-0">
            <?php echo htmlspecialchars($avatar_initial); ?>
        </div>
        <div class="sidebar-label min-w-0 leading-tight">
            <p class="text-xs font-semibold text-[#23332c] truncate"><?php echo htmlspecialchars($display_name); ?></p>
            <p class="text-[10px] text-[#8a978f] truncate"><?php echo htmlspecialchars($role_label); ?></p>
        </div>
    </div>
</aside>

<!-- Top header bar — markup lives in includes/header.php -->
<?php include __DIR__ . '/header.php'; ?>

<!-- Sidebar Overlay (Mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<!-- Mobile Menu Button -->
<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 lg:hidden w-11 h-11 bg-white text-[#23332c] rounded-full shadow-lg flex items-center justify-center">
    <i class="fas fa-bars"></i>
</button>

<style>
    /* Global admin theme — Poppins + warm olive backdrop + white content card */
    body {
        font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        background-color: #60796e !important;
    }

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

    /* Inner cards become flat, minimal panels */
    .lg\:ml-64 .shadow-md,
    .lg\:ml-64 .shadow-lg {
        box-shadow: none;
        border: 1px solid #e6ece8;
        border-radius: 1rem;
    }

    /* Thin themed scrollbar for the page + inner scroll areas */
    html { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
    ::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }

    /* Sidebar scrollbar — matches the light sidebar background */
    #sidebar .sidebar-scroll {
        scrollbar-width: thin;
        scrollbar-color: #d2dcd5 #f5f7f5;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-track {
        background: #f5f7f5;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb {
        background-color: #d2dcd5;
        border-radius: 4px;
        border: 2px solid #f5f7f5;
    }
    #sidebar .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #c0ccc5;
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
    const sidebarCollapse = document.getElementById('sidebar-collapse');

    const COLLAPSE_KEY = 'tsaci_sidebar_collapsed';

    function applyDesktopState() {
        const collapsed = localStorage.getItem(COLLAPSE_KEY) === '1';
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (sidebarCollapse) {
            sidebarCollapse.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        }
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
    sidebarCollapse?.addEventListener('click', function() {
        if (isCollapsing) return;
        isCollapsing = true;
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem(COLLAPSE_KEY, isCollapsed ? '0' : '1');
        applyDesktopState();
        setTimeout(function() {
            isCollapsing = false;
        }, 350);
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
</script>

<!-- List/Grid view toggle for admin list tables -->
<?php include __DIR__ . '/view-toggle.php'; ?>
