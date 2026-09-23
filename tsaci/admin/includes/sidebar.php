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

$nav_items = [
    ['index.php',          'fa-home',           'Dashboard'],
    ['pages.php',          'fa-file-alt',       'Pages'],
    ['about.php',          'fa-info-circle',    'About Page'],
    ['team-members.php',   'fa-users',          'Team Members'],
    ['company-values.php', 'fa-gem',            'Company Values'],
    ['timeline.php',       'fa-history',        'Our Journey'],
    ['statistics.php',     'fa-chart-bar',      'Statistics'],
    ['homepage-features.php','fa-star',         'Homepage Features'],
    ['carousel.php',       'fa-images',         'Carousel'],
    ['products.php',       'fa-cube',           'Products'],
    ['product-tabs.php',   'fa-folder',         'Product Tabs'],
    ['applications.php',   'fa-th-large',       'Applications'],
    ['services.php',       'fa-concierge-bell', 'Services'],
    ['testimonials.php',   'fa-quote-left',     'Testimonials'],
    ['case-studies.php',   'fa-book',           'Case Studies'],
    ['gallery.php',        'fa-photo-video',    'Gallery'],
    ['resources.php',      'fa-file-download',  'Resources'],
    ['faqs.php',           'fa-question-circle','FAQs'],
    ['office-hours.php',   'fa-clock',          'Office Hours'],
    ['subject-options.php','fa-list-ul',        'Subject Options'],
    ['contact-info.php',   'fa-address-book',   'Contact Info'],
    ['social-media.php',   'fa-share-alt',      'Social Media'],
    ['footer-links.php',   'fa-link',           'Footer Links'],
    ['certifications.php', 'fa-certificate',    'Certifications'],
];

$account_items = [
    ['site-settings.php',    'fa-sliders-h',   'Site Settings'],
    ['login-background.php', 'fa-image',       'Login Background'],
    ['settings.php',         'fa-cog',         'Settings'],
];

// Friendly page title for the top header bar
$header_title = 'Admin';
foreach (array_merge($nav_items, [['messages.php', '', 'Messages']], $account_items) as $item) {
    if ($current_page === $item[0]) {
        $header_title = $item[2];
        break;
    }
}
?>
<!-- Poppins font + admin theme -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 bottom-0 left-0 z-40 w-64 transition-all duration-300 -translate-x-full lg:translate-x-0 bg-[#f5f7f5] text-[#45524b] lg:border-r lg:border-black/5 flex flex-col">
    <!-- Profile header (pinned — stays put while the nav scrolls) -->
    <div class="sidebar-header flex items-center gap-3 px-5 pt-5 pb-4 flex-shrink-0">
        <div class="w-11 h-11 rounded-full bg-[#23332c] text-white flex items-center justify-center font-semibold text-lg flex-shrink-0">
            <?php echo htmlspecialchars($avatar_initial); ?>
        </div>
        <div class="sidebar-label min-w-0 flex-1">
            <p class="font-semibold text-[15px] text-[#23332c] leading-tight truncate"><?php echo htmlspecialchars($display_name); ?></p>
            <p class="text-xs text-[#8a978f] truncate"><?php echo htmlspecialchars($role_label); ?></p>
        </div>
        <!-- Mobile close button -->
        <button id="sidebar-close" class="lg:hidden text-[#66746c] hover:text-[#23332c]">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-5">
        <!-- Navigation Menu -->
        <nav class="space-y-1">
            <?php foreach ($nav_items as $item):
                $is_active = $current_page === $item[0];
            ?>
                <a href="<?php echo $item[0]; ?>" title="<?php echo $item[2]; ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors <?php echo $is_active ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?>">
                    <i class="fas <?php echo $item[1]; ?> w-5 text-center"></i>
                    <span class="sidebar-label"><?php echo $item[2]; ?></span>
                </a>
            <?php endforeach; ?>

            <a href="messages.php" title="Messages" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors <?php echo $current_page == 'messages.php' ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'; ?>">
                <i class="fas fa-envelope w-5 text-center"></i>
                <span class="sidebar-label">Messages</span>
                <?php if ($unread_messages > 0): ?>
                    <span class="sidebar-badge ml-auto bg-[#23332c] text-white text-[10px] font-semibold rounded-full px-2 py-0.5"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
        </nav>

        <!-- Account section -->
        <p class="section-label px-3.5 pt-6 pb-2 text-[11px] font-semibold uppercase tracking-wider text-[#96a39b]">Account</p>
        <nav class="space-y-1">
            <?php foreach ($account_items as $item):
                $is_active = $current_page === $item[0];
                $is_logout = $item[0] === 'logout.php';
            ?>
                <a href="<?php echo $item[0]; ?>" title="<?php echo $item[2]; ?>" class="user-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm transition-colors <?php echo $is_logout ? 'text-red-500 hover:bg-red-50' : ($is_active ? 'bg-[#e2eae4] text-[#23332c] font-medium' : 'text-[#66746c] hover:bg-[#eaf0ec] hover:text-[#23332c]'); ?>">
                    <i class="fas <?php echo $item[1]; ?> w-5 text-center"></i>
                    <span class="sidebar-label"><?php echo $item[2]; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Bottom toggle (matches the dark round button in the reference) -->
    <div class="sidebar-footer p-4">
        <button id="sidebar-collapse" type="button" title="Collapse sidebar" class="hidden lg:flex w-10 h-10 rounded-full bg-[#23332c] text-white items-center justify-center hover:bg-[#3a4a41] transition-colors">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</aside>

<!-- Top header bar — spans the content area (right of the sidebar on desktop) -->
<header id="admin-topbar" class="fixed top-0 left-0 right-0 lg:left-64 z-20 h-14 bg-white/95 backdrop-blur border-b border-[#e6ece8] flex items-center gap-3 pl-16 pr-4 lg:pl-6 lg:pr-8">
    <p class="min-w-0 flex-1 truncate text-sm font-semibold text-[#23332c]"><?php echo htmlspecialchars($header_title); ?></p>

    <a href="../index.php" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-[#66746c] hover:text-[#23332c] transition-colors">
        <i class="fas fa-external-link-alt"></i> View Website
    </a>
    <span class="hidden sm:block w-px h-5 bg-[#e6ece8]"></span>

    <!-- Notifications -->
    <div class="relative flex-shrink-0">
        <button id="notif-toggle" type="button" title="Notifications" class="relative w-8 h-8 rounded-full flex items-center justify-center text-[#66746c] hover:bg-[#eff4f1] hover:text-[#23332c] transition-colors">
            <i class="fas fa-bell text-sm"></i>
            <?php if ($unread_messages > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 min-w-[1rem] h-4 px-0.5 rounded-full bg-red-500 text-white text-[9px] font-semibold flex items-center justify-center"><?php echo $unread_messages > 99 ? '99+' : $unread_messages; ?></span>
            <?php endif; ?>
        </button>
        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-2xl border border-[#e6ece8] shadow-lg overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-[#e6ece8] flex items-center justify-between">
                <p class="text-xs font-semibold text-[#23332c]">Notifications</p>
                <?php if ($unread_messages > 0): ?>
                    <span class="text-[10px] font-medium text-[#8a978f]"><?php echo $unread_messages; ?> unread</span>
                <?php endif; ?>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <?php if (empty($recent_unread)): ?>
                    <div class="px-4 py-8 text-center">
                        <i class="far fa-bell-slash text-[#c0ccc5] text-xl"></i>
                        <p class="text-xs text-[#8a978f] mt-2">No new messages</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_unread as $n): ?>
                        <a href="messages.php?action=view&id=<?php echo (int)$n['id']; ?>" class="flex items-start gap-3 px-4 py-3 hover:bg-[#f7faf8] transition-colors border-b border-[#f0f4f1] last:border-0">
                            <div class="w-8 h-8 rounded-full bg-[#eef3f0] text-[#3d7a66] flex items-center justify-center text-xs font-semibold flex-shrink-0"><?php echo htmlspecialchars(strtoupper(substr($n['name'], 0, 1))); ?></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-[#23332c] truncate"><?php echo htmlspecialchars($n['name']); ?></p>
                                <p class="text-xs text-[#66746c] truncate"><?php echo htmlspecialchars($n['subject']); ?></p>
                                <p class="text-[10px] text-[#8a978f] mt-0.5"><?php echo formatDate($n['created_at'], 'M d, g:i A'); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="messages.php" class="block px-4 py-2.5 text-center text-xs font-medium text-[#3d7a66] hover:bg-[#f7faf8] border-t border-[#e6ece8] transition-colors">View all messages</a>
        </div>
    </div>

    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-full bg-[#23332c] text-white flex items-center justify-center text-xs font-semibold flex-shrink-0"><?php echo htmlspecialchars($avatar_initial); ?></div>
        <div class="hidden sm:block leading-tight min-w-0">
            <p class="text-xs font-semibold text-[#23332c] truncate max-w-[10rem]"><?php echo htmlspecialchars($display_name); ?></p>
            <p class="text-[10px] text-[#8a978f] truncate"><?php echo htmlspecialchars($role_label); ?></p>
        </div>
    </div>

    <a href="logout.php" title="Logout" class="w-8 h-8 rounded-full flex items-center justify-center text-[#66746c] hover:bg-red-50 hover:text-red-500 transition-colors flex-shrink-0">
        <i class="fas fa-sign-out-alt text-sm"></i>
    </a>
</header>

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

    /* Top header bar — slides in sync with the collapsing sidebar */
    #admin-topbar {
        transition: left 0.3s ease;
    }

    /* Dock the mobile menu button inside the top header bar */
    #sidebar-toggle {
        top: 0.375rem;
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
            display: flex;
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        body.sidebar-collapsed .lg\:ml-64 {
            margin-left: 4rem !important;
        }
        body.sidebar-collapsed #admin-topbar {
            left: 4rem;
        }
    }
</style>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarClose = document.getElementById('sidebar-close');
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
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
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
    sidebarClose?.addEventListener('click', closeSidebar);
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

    // Notification dropdown
    const notifToggle = document.getElementById('notif-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');

    notifToggle?.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', function(e) {
        if (notifDropdown && !notifDropdown.classList.contains('hidden')
            && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && notifDropdown) {
            notifDropdown.classList.add('hidden');
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
</script>
