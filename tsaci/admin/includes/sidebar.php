<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
$current_user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread messages count
$db = getDB();
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$unread_messages = $result->fetch_assoc()['count'];

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
    ['logout.php',           'fa-sign-out-alt','Logout'],
];
?>
<!-- Poppins font + admin theme -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 bottom-0 left-0 lg:top-4 lg:bottom-4 lg:left-4 z-40 w-64 transition-all duration-300 -translate-x-full lg:translate-x-0 bg-[#f5f7f5] text-[#45524b] lg:rounded-2xl lg:shadow-lg lg:border lg:border-black/5 flex flex-col">
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

    /* Main content sits in a large white rounded card, like the reference */
    .lg\:ml-64 {
        background: #ffffff;
        border-radius: 1.5rem;
        margin: 0.75rem;
        min-height: calc(100vh - 1.5rem);
        /* Animate its margin so it moves in sync with the collapsing sidebar */
        transition: margin-left 0.3s ease;
    }

    /* Inner cards become flat, minimal panels */
    .lg\:ml-64 .shadow-md,
    .lg\:ml-64 .shadow-lg {
        box-shadow: none;
        border: 1px solid #e6ece8;
        border-radius: 1rem;
    }

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
        /* Clear the floating sidebar (1rem offset + 16rem panel + 1rem gap) */
        .lg\:ml-64 {
            margin: 1rem 1rem 1rem 18rem !important;
            min-height: calc(100vh - 2rem);
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
            margin-left: 6rem !important;
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

    // Safety net: if the page is shown from the back/forward cache with the
    // mobile sidebar left open, clear the overlay + body scroll lock so the
    // page never ends up in an unclickable state.
    window.addEventListener('pageshow', function(e) {
        if (e.persisted && isSidebarOpen() && window.innerWidth < 1024) {
            closeSidebar();
        }
    });
</script>
