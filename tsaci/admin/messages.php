<?php
require_once 'config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'read')     $success = 'Message marked as read.';
if ($status_param === 'archived') $success = 'Message archived.';
if ($status_param === 'deleted')  $success = 'Message deleted.';
if ($status_param === 'unarchived') $success = 'Message restored.';

// Session flashes (used by the reply action for detailed errors)
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Handle POST actions (mark read, archive, unarchive, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';
    $post_id = intval($_POST['id'] ?? 0);

    if ($post_id > 0) {
        if ($post_action === 'read') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            redirect('messages.php?status=read');
        } elseif ($post_action === 'unread') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            redirect('messages.php?status=read');
        } elseif ($post_action === 'archive') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_archived = 1 WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            logActivity('archive', 'contact_messages', $post_id, 'Archived message');
            redirect('messages.php?status=archived');
        } elseif ($post_action === 'unarchive') {
            $stmt = $db->prepare("UPDATE contact_messages SET is_archived = 0 WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            logActivity('unarchive', 'contact_messages', $post_id, 'Restored message');
            redirect('messages.php?status=unarchived');
        } elseif ($post_action === 'delete') {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            logActivity('delete', 'contact_messages', $post_id, 'Deleted message');
            redirect('messages.php?status=deleted');
        } elseif ($post_action === 'reply') {
            $back = 'messages.php?action=view&id=' . $post_id;
            if (!verifyCsrfToken()) {
                $_SESSION['flash_error'] = 'Invalid form token. Please try again.';
                redirect($back);
            }
            if (!smtpRepliesEnabled()) {
                $_SESSION['flash_error'] = 'SMTP replies are not configured. Set SMTP credentials in admin/mail_config.php.';
                redirect($back);
            }
            $reply_subject = trim($_POST['reply_subject'] ?? '');
            $reply_body = trim($_POST['reply_body'] ?? '');
            if ($reply_subject === '' || $reply_body === '') {
                $_SESSION['flash_error'] = 'Subject and message body are required.';
                redirect($back);
            }
            $stmt = $db->prepare("SELECT name, email FROM contact_messages WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            if (!$target) {
                $_SESSION['flash_error'] = 'Message not found.';
                redirect('messages.php');
            }
            $result = sendMessageReply($target['email'], $target['name'], $reply_subject, $reply_body);
            if ($result['success']) {
                logActivity('reply', 'contact_messages', $post_id, 'Sent email reply to ' . $target['email']);
                $_SESSION['flash_success'] = 'Reply sent to ' . $target['email'] . ' via Gmail SMTP.';
            } else {
                $_SESSION['flash_error'] = 'Failed to send reply: ' . $result['error'];
            }
            redirect($back);
        }
    }
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
        $message['is_read'] = 1;
    }

    if (!$message) {
        $error = 'Message not found.';
        $action = 'list';
    }

    // Pre-drafted reply templates (greeting + signature + quoted original)
    $reply_template = '';
    $reply_templates = [];
    $reply_quoted = '';
    if ($message) {
        $first_name = trim(explode(' ', trim($message['name']))[0]);
        $signature = "Best regards,\n" . SMTP_FROM_NAME . "\nTupi Supreme Activated Carbon, Inc.";
        $reply_quoted = "\n\n--- Original message ---\n> " . str_replace("\n", "\n> ", trim($message['message']));
        $reply_templates = [
            'general'   => "Dear {$first_name},\n\nThank you for contacting Tupi Supreme Activated Carbon, Inc. regarding \"{$message['subject']}\".\n\n\n\n{$signature}",
            'quotation' => "Dear {$first_name},\n\nThank you for your interest in our activated carbon products. Regarding your inquiry about \"{$message['subject']}\", please find our pricing details below:\n\n\n\nShould you need a formal quotation or bulk/volume pricing, just let us know.\n\n{$signature}",
            'specs'     => "Dear {$first_name},\n\nThank you for your inquiry about \"{$message['subject']}\". Here are the technical specifications you requested:\n\n\n\nIf you need our full product data sheets or have further technical questions, we're happy to help.\n\n{$signature}",
            'more_info' => "Dear {$first_name},\n\nThank you for reaching out to Tupi Supreme Activated Carbon, Inc. To assist you better regarding \"{$message['subject']}\", could you please provide a few more details:\n\n- \n- \n- \n\n{$signature}",
            'blank'     => "Dear {$first_name},\n\n\n\n{$signature}",
        ];
        $reply_template = $reply_templates['general'] . $reply_quoted;
    }
}

// Get all messages (with pagination + filtering + search)
$filter = $_GET['filter'] ?? 'all';
$all_messages = [];
$total_messages = 0;
$total_pages = 1;
$per_page = 10;
$current_page_num = 1;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $search_q = trim($_GET['q'] ?? '');

    // Build the filter WHERE clause (filter is whitelisted)
    $where = " WHERE 1=1";
    $params = [];
    $types = '';
    if ($filter === 'unread') {
        $where .= " AND is_read = 0 AND is_archived = 0";
    } elseif ($filter === 'archived') {
        $where .= " AND is_archived = 1";
    } else {
        $where .= " AND is_archived = 0";
    }
    if ($search_q !== '') {
        $where .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ? OR company LIKE ?)";
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sssss';
    }

    // Total count for pagination controls
    $count_sql = "SELECT COUNT(*) as total FROM contact_messages" . $where;
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_messages = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_messages / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    $list_sql = "SELECT * FROM contact_messages" . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $list_stmt = $db->prepare($list_sql);
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_messages[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'unread' => 0, 'archived' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(CASE WHEN is_read = 0 AND is_archived = 0 THEN 1 ELSE 0 END) unread, SUM(is_archived) archived FROM contact_messages");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['unread'] = (int)$row['unread'];
    $stats['archived'] = (int)$row['archived'];
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

    <style>
        @media (min-width: 1024px) {
            .lg\:ml-64 { height: calc(100vh - 2rem) !important; overflow-y: auto !important; }
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-envelope text-primary"></i> Contact Messages
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Messages submitted through the public contact form.</p>
                </div>
                <?php if ($action === 'view'): ?>
                <a href="messages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 flex items-start gap-2">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($action === 'view' && $message): ?>
            <!-- Message Detail View -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <i class="fas fa-envelope-open text-primary"></i> Message Details
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Received <?php echo formatDate($message['created_at'], 'F d, Y \a\t g:i A'); ?></p>
                    </div>
                    <a href="messages.php" class="text-gray-400 hover:text-gray-600" title="Back to list">
                        <i class="fas fa-arrow-left mr-1"></i>Back to List
                    </a>
                </div>

                <!-- Status badges -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <?php if (!$message['is_read']): ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-circle text-blue-500 text-[6px] mr-1"></i>Unread</span>
                    <?php else: ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-600"><i class="fas fa-check mr-1"></i>Read</span>
                    <?php endif; ?>
                    <?php if ($message['is_archived']): ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-box mr-1"></i>Archived</span>
                    <?php endif; ?>
                </div>

                <!-- Subject -->
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></h3>
                </div>

                <!-- Sender info card -->
                <div class="flex items-start gap-4 mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($message['name']); ?></p>
                            <?php if ($message['company']): ?>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($message['company']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 mt-1">
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-sm text-primary hover:underline inline-flex items-center gap-1">
                                <i class="fas fa-envelope text-xs"></i><?php echo htmlspecialchars($message['email']); ?>
                            </a>
                            <?php if ($message['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" class="text-sm text-primary hover:underline inline-flex items-center gap-1">
                                    <i class="fas fa-phone text-xs"></i><?php echo htmlspecialchars($message['phone']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Message body -->
                <div class="mb-6">
                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">Message</label>
                    <div class="p-4 bg-white rounded-lg border border-gray-200">
                        <p class="text-gray-900 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($message['message']); ?></p>
                    </div>
                </div>

                <!-- Reply compose -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm font-medium text-blue-900 mb-2 flex items-center gap-2"><i class="fas fa-reply"></i> Reply</p>
                    <?php if (smtpRepliesEnabled()): ?>
                    <p class="text-xs text-blue-700 mb-3">Send a reply directly from <strong><?php echo htmlspecialchars(SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME); ?></strong> via Gmail SMTP.</p>
                    <form method="POST" action="messages.php" onsubmit="return confirm('Send this reply to <?php echo htmlspecialchars($message['email'], ENT_QUOTES); ?>?');">
                        <?php echo csrfTokenField(); ?>
                        <input type="hidden" name="post_action" value="reply">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
                            <div class="text-sm text-gray-800 px-3 py-2 bg-white border border-gray-200 rounded-md">
                                <?php echo htmlspecialchars($message['name']); ?> &lt;<?php echo htmlspecialchars($message['email']); ?>&gt;
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reply_subject" class="block text-xs font-medium text-gray-600 mb-1">Subject</label>
                            <input type="text" id="reply_subject" name="reply_subject" required
                                   value="Re: <?php echo htmlspecialchars($message['subject']); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                        <div class="mb-3">
                            <label for="reply_preset" class="block text-xs font-medium text-gray-600 mb-1">Template</label>
                            <select id="reply_preset" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="general" selected>General response</option>
                                <option value="quotation">Quotation / pricing request</option>
                                <option value="specs">Product specs / technical inquiry</option>
                                <option value="more_info">Request more information</option>
                                <option value="blank">Blank</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Choosing a template replaces the draft below.</p>
                        </div>
                        <div class="mb-3">
                            <label for="reply_body" class="block text-xs font-medium text-gray-600 mb-1">Message</label>
                            <textarea id="reply_body" name="reply_body" rows="12" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($reply_template); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Edit the draft as needed — the quoted original at the bottom is included in the email.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center text-sm">
                                <i class="fas fa-paper-plane mr-2"></i>Send Reply
                            </button>
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo rawurlencode($message['subject']); ?>" class="text-gray-500 hover:text-gray-700 text-xs inline-flex items-center gap-1 ml-2">
                                <i class="fas fa-external-link-alt"></i>or open in mail client
                            </a>
                        </div>
                    </form>
                    <?php else: ?>
                    <p class="text-xs text-blue-700 mb-3">SMTP replies are not configured. To send replies directly from here, set your Gmail address and app password in <code class="bg-blue-100 px-1 rounded">admin/mail_config.php</code>. Meanwhile, you can reply via your own mail client:</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo rawurlencode($message['subject']); ?>" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-reply mr-2"></i>Reply by Email
                        </a>
                        <?php if ($message['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-phone mr-2"></i>Call Sender
                        </a>
                        <?php endif; ?>
                        <button type="button" onclick="copyEmail('<?php echo htmlspecialchars($message['email'], ENT_QUOTES); ?>')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-copy mr-2"></i>Copy Email
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                    <?php if (!$message['is_read']): ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="read">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-check mr-2"></i>Mark as Read
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="unread">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-envelope mr-2"></i>Mark as Unread
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (!$message['is_archived']): ?>
                    <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Archive this message? It can be restored from the Archived filter.');">
                        <input type="hidden" name="post_action" value="archive">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-box mr-2"></i>Archive
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="unarchive">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-box-open mr-2"></i>Restore
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Delete this message permanently? This cannot be undone.');">
                        <input type="hidden" name="post_action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors inline-flex items-center text-sm">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>

            <script>
            function copyEmail(email) {
                navigator.clipboard.writeText(email).then(function() {
                    alert('Email address copied to clipboard: ' + email);
                }, function() {
                    alert('Could not copy. Email: ' + email);
                });
            }

            var replyTemplates = <?php echo json_encode($reply_templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            var replyQuoted = <?php echo json_encode($reply_quoted, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            var presetSelect = document.getElementById('reply_preset');
            if (presetSelect) {
                presetSelect.addEventListener('change', function() {
                    var tpl = replyTemplates[this.value] || replyTemplates.general;
                    document.getElementById('reply_body').value = tpl + replyQuoted;
                });
            }
            </script>

        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Info banner -->
            <div class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <p class="text-sm text-blue-800">Messages submitted through the public <a href="../contact.php#contact-form" target="_blank" class="underline hover:text-blue-900">Contact form</a> appear here. Click a message to view full details and reply by email. Unread messages are highlighted in blue. Archived messages are kept but hidden from the active list.</p>
            </div>

            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-blue-100 text-blue-800"><i class="fas fa-envelope mr-1"></i><?php echo $stats['unread']; ?> unread</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-100 text-gray-700"><i class="fas fa-box mr-1"></i><?php echo $stats['archived']; ?> archived</span>
                    </div>
                    <form method="GET" action="messages.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Active (not archived)</option>
                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread only</option>
                                <option value="archived" <?php echo $filter === 'archived' ? 'selected' : ''; ?>>Archived only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search name, email, subject, or message…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if ($filter !== 'all' || !empty($_GET['q'])): ?>
                        <a href="messages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject & Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_messages)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">No messages found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_messages as $msg): ?>
                                <tr class="hover:bg-gray-50 <?php echo !$msg['is_read'] ? 'bg-blue-50' : ''; ?> cursor-pointer" onclick="window.location='?action=view&id=<?php echo $msg['id']; ?>'">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                                <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 <?php echo !$msg['is_read'] ? 'font-bold' : ''; ?>">
                                                    <?php echo htmlspecialchars($msg['name']); ?>
                                                </div>
                                                <?php if ($msg['company']): ?>
                                                    <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($msg['company']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 max-w-md">
                                        <div class="text-sm <?php echo !$msg['is_read'] ? 'font-semibold text-gray-900' : 'text-gray-700'; ?> truncate">
                                            <?php if (!$msg['is_read']): ?><i class="fas fa-circle text-blue-500 text-[6px] mr-1"></i><?php endif; ?>
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 truncate mt-0.5"><?php echo htmlspecialchars(mb_strimwidth($msg['message'], 0, 100, '…')); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"><?php echo formatDate($msg['created_at'], 'M d, Y'); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($msg['is_archived']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-box mr-1"></i>Archived</span>
                                        <?php elseif (!$msg['is_read']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">Unread</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap" onclick="event.stopPropagation();">
                                        <a href="?action=view&id=<?php echo $msg['id']; ?>" class="text-primary hover:text-secondary mr-3" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo rawurlencode($msg['subject']); ?>" class="text-gray-500 hover:text-gray-700 mr-3" title="Reply by email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <?php if (!$msg['is_archived']): ?>
                                        <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Archive this message?');">
                                            <input type="hidden" name="post_action" value="archive">
                                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="text-gray-500 hover:text-gray-700 mr-3" title="Archive">
                                                <i class="fas fa-box"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Delete this message permanently? This cannot be undone.');">
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
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

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="../contact.php#contact-form" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Public Contact Form</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
