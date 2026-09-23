<?php
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not allowed');
}
// Variables provided by the including file (sidebar.php):
// $header_title, $unread_messages, $recent_unread,
// $avatar_initial, $display_name, $role_label
?>
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

<style>
    /* Top header bar — slides in sync with the collapsing sidebar */
    #admin-topbar {
        transition: left 0.3s ease;
    }

    @media (min-width: 1024px) {
        body.sidebar-collapsed #admin-topbar {
            left: 4rem;
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
