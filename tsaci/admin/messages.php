<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Mark as read
if ($action === 'read' && $id) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = 'Message marked as read.';
    $action = 'list';
}

// Archive message
if ($action === 'archive' && $id) {
    $stmt = $db->prepare("UPDATE contact_messages SET is_archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    logActivity('archive', 'contact_messages', $id, 'Archived message');
    $success = 'Message archived.';
    $action = 'list';
}

// Delete message
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    logActivity('delete', 'contact_messages', $id, 'Deleted message');
    $success = 'Message deleted.';
    $action = 'list';
}

// Get message for view
$message = null;
if ($action === 'view' && $id) {
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
    
    // Mark as read when viewing
    if ($message && !$message['is_read']) {
        $update_stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $id);
        $update_stmt->execute();
    }
}

// Get all messages (with pagination, respecting the active filter)
$filter = $_GET['filter'] ?? 'all';
$all_messages = [];
$total_messages = 0;
$total_pages = 1;
$per_page = 10;
$current_page_num = 1;
if ($action === 'list') {
    // Build the filter WHERE clause (filter is whitelisted, so this is safe)
    $where = " WHERE 1=1";
    if ($filter === 'unread') {
        $where .= " AND is_read = 0 AND is_archived = 0";
    } elseif ($filter === 'archived') {
        $where .= " AND is_archived = 1";
    } else {
        $where .= " AND is_archived = 0";
    }

    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM contact_messages" . $where);
    $total_messages = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_messages / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT * FROM contact_messages" . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2c5530',
                        secondary: '#4a7c59',
                        accent: '#8bc34a',
                        dark: '#1a1a1a',
                        light: '#f8f9fa'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Page-scoped alignment: keep the main content card the exact same
         height as the fixed sidebar so both panels align top/bottom. -->
    <style>
        @media (min-width: 1024px) {
            .lg\:ml-64 {
                height: calc(100vh - 2rem) !important;
                overflow-y: auto !important;
            }
            /* Thin, themed scrollbar for the in-card scroll area */
            .lg\:ml-64 {
                scrollbar-width: thin;
                scrollbar-color: #d2dcd5 transparent;
            }
            .lg\:ml-64::-webkit-scrollbar {
                width: 8px;
            }
            .lg\:ml-64::-webkit-scrollbar-track {
                background: transparent;
            }
            .lg\:ml-64::-webkit-scrollbar-thumb {
                background-color: #d2dcd5;
                border-radius: 4px;
                border: 2px solid transparent;
                background-clip: padding-box;
            }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover {
                background-color: #c0ccc5;
            }
        }
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">Contact Messages</h1>
                <div class="flex gap-2">
                    <a href="?filter=all" class="px-4 py-2 rounded-lg <?php echo $filter === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">All</a>
                    <a href="?filter=unread" class="px-4 py-2 rounded-lg <?php echo $filter === 'unread' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">Unread</a>
                    <a href="?filter=archived" class="px-4 py-2 rounded-lg <?php echo $filter === 'archived' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">Archived</a>
                </div>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'view' && $message): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold">Message Details</h2>
                    <a href="messages.php" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </a>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($message['name']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-lg text-gray-900">
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-primary hover:underline">
                                <?php echo htmlspecialchars($message['email']); ?>
                            </a>
                        </p>
                    </div>
                    <?php if ($message['phone']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($message['phone']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($message['company']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Company</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($message['company']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Subject</label>
                        <p class="text-lg text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Message</label>
                        <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                            <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($message['message']); ?></p>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Received</label>
                        <p class="text-gray-600"><?php echo formatDate($message['created_at'], 'F d, Y \a\t g:i A'); ?></p>
                    </div>
                </div>
                
                <div class="mt-6 flex gap-4">
                    <?php if (!$message['is_read']): ?>
                        <a href="?action=read&id=<?php echo $message['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Mark as Read</a>
                    <?php endif; ?>
                    <?php if (!$message['is_archived']): ?>
                        <a href="?action=archive&id=<?php echo $message['id']; ?>" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Archive</a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?php echo $message['id']; ?>" onclick="return confirm('Are you sure?')" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">Delete</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($all_messages)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No messages found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($all_messages as $msg): ?>
                            <tr class="<?php echo !$msg['is_read'] ? 'bg-blue-50' : ''; ?>">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($msg['name']); ?></div>
                                    <?php if ($msg['company']): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($msg['company']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($msg['email']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo formatDate($msg['created_at'], 'M d, Y'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Unread</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="?action=view&id=<?php echo $msg['id']; ?>" class="text-primary hover:text-secondary mr-3">View</a>
                                    <?php if (!$msg['is_archived']): ?>
                                        <a href="?action=archive&id=<?php echo $msg['id']; ?>" class="text-gray-600 hover:text-gray-800 mr-3">Archive</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $msg['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-800">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
            require_once __DIR__ . '/includes/pagination.php';
            renderPagination([
                'current_page' => $current_page_num,
                'total_pages'   => $total_pages,
                'total_items'   => $total_messages,
                'per_page'      => $per_page,
                'base_query'    => $_GET,
            ]);
            ?>
        </div>
    </div>
</body>
</html>

