<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// Variables provided by the including file (sidebar.php):
// $header_title, $unread_messages, $recent_unread,
// $avatar_initial, $display_name, $role_label
?>
<!-- Top header bar — spans the content area (right of the sidebar on desktop) -->
<header id="admin-topbar" class="fixed top-0 left-0 right-0 lg:left-64 z-20 h-14 bg-white/95 backdrop-blur border-b border-zinc-200 flex items-center gap-3 pl-16 pr-4 lg:pl-4 lg:pr-8">
    <!-- Desktop sidebar collapse toggle -->
    <button id="sidebar-collapse" type="button" title="Expand sidebar" class="hidden w-8 h-8 rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 items-center justify-center transition-colors flex-shrink-0">
        <i class="fas fa-bars text-sm"></i>
    </button>
    <p class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-900"><?php echo htmlspecialchars($header_title); ?></p>

    <!-- Notifications -->
    <div class="relative flex-shrink-0">
        <button id="notif-toggle" type="button" title="Notifications" class="relative w-8 h-8 rounded-md flex items-center justify-center text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 transition-colors">
            <i class="fas fa-bell text-sm"></i>
            <?php if ($unread_messages > 0): ?>
                <span class="absolute -top-0.5 -right-0.5 min-w-[1rem] h-4 px-0.5 rounded-full bg-red-600 text-white text-[9px] font-medium flex items-center justify-center"><?php echo $unread_messages > 99 ? '99+' : $unread_messages; ?></span>
            <?php endif; ?>
        </button>
        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-md border border-zinc-200 shadow-lg overflow-hidden z-50">
            <div class="px-4 py-3 border-b border-zinc-200 flex items-center justify-between">
                <p class="text-xs font-medium text-zinc-900">Notifications</p>
                <?php if ($unread_messages > 0): ?>
                    <span class="text-[11px] text-zinc-500"><?php echo $unread_messages; ?> unread</span>
                <?php endif; ?>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <?php if (empty($recent_unread)): ?>
                    <div class="px-4 py-8 text-center">
                        <i class="far fa-bell-slash text-zinc-300 text-xl"></i>
                        <p class="text-xs text-zinc-500 mt-2">No new messages</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_unread as $n): ?>
                        <a href="messages.php?action=view&id=<?php echo (int)$n['id']; ?>" class="flex items-start gap-3 px-4 py-3 hover:bg-zinc-50 transition-colors border-b border-zinc-100 last:border-0">
                            <div class="w-8 h-8 rounded-full bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center text-xs font-medium flex-shrink-0"><?php echo htmlspecialchars(strtoupper(substr($n['name'], 0, 1))); ?></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-medium text-zinc-900 truncate"><?php echo htmlspecialchars($n['name']); ?></p>
                                <p class="text-xs text-zinc-500 truncate"><?php echo htmlspecialchars($n['subject']); ?></p>
                                <p class="text-[11px] text-zinc-400 mt-0.5"><?php echo formatDate($n['created_at'], 'M d, g:i A'); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="messages.php" class="block px-4 py-2.5 text-center text-xs font-medium text-[#2c5530] hover:bg-zinc-50 border-t border-zinc-200 transition-colors">View all messages</a>
        </div>
    </div>

    <a href="logout.php" title="Logout" class="w-8 h-8 rounded-md flex items-center justify-center text-zinc-500 hover:bg-red-50 hover:text-red-600 transition-colors flex-shrink-0">
        <i class="fas fa-sign-out-alt text-sm"></i>
    </a>

    <!-- User avatar (links to profile settings) -->
    <a href="settings.php" title="<?php echo htmlspecialchars($display_name . ' · ' . $role_label); ?>" class="relative flex-shrink-0 ml-1">
        <span class="w-8 h-8 rounded-full bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center text-xs font-medium overflow-hidden">
            <?php echo htmlspecialchars($avatar_initial); ?>
        </span>
        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white"></span>
    </a>
</header>

<style>
    /* Top header bar — slides in sync with the collapsing sidebar */
    #admin-topbar {
        transition: left 0.3s ease;
    }

    @media (min-width: 1024px) {
        body.sidebar-collapsed #admin-topbar {
            left: 4rem;
        }
        /* Topbar hamburger appears only while the sidebar is collapsed */
        body.sidebar-collapsed #sidebar-collapse {
            display: flex;
        }
    }
</style>

<script>
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
</script>
