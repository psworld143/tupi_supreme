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

        // Signature with live contact details from the contact_info table
        $contact = function_exists('getMailContactDetails') ? getMailContactDetails() : ['email' => '', 'phone' => '', 'address' => ''];
        $contact_lines = [];
        if ($contact['email'] !== '')   $contact_lines[] = 'Email: ' . $contact['email'];
        if ($contact['phone'] !== '')   $contact_lines[] = 'Phone: ' . $contact['phone'];
        $signature = "Best regards,\n\n" . SMTP_FROM_NAME . "\nTupi Supreme"
            . (!empty($contact_lines) ? "\n" . implode(' | ', $contact_lines) : '');

        $reply_quoted = "\n\n--- Original message ---\n> " . str_replace("\n", "\n> ", trim($message['message']));
        $reply_templates = [
            'general'   => "Dear {$first_name},\n\nThank you for contacting Tupi Supreme regarding \"{$message['subject']}\". We appreciate you taking the time to reach out to us.\n\nI have personally reviewed your inquiry and wanted to respond promptly.\n\n\n\nPlease don't hesitate to reply to this email if there's anything further we can assist you with.\n\n{$signature}",
            'quotation' => "Dear {$first_name},\n\nThank you for your interest in our activated carbon products and for your inquiry about \"{$message['subject']}\".\n\nPlease find our pricing details below:\n\n\n\nA few notes:\n- Prices are valid for 30 days from the date of this email\n- Volume discounts are available for bulk orders\n- Formal quotations can be issued upon request\n\nShould you have any questions about the pricing or need a customized quote for your specific requirements, we're happy to assist.\n\n{$signature}",
            'specs'     => "Dear {$first_name},\n\nThank you for your inquiry about \"{$message['subject']}\".\n\nPlease find the technical specifications you requested below:\n\n\n\nAdditional documentation:\n- Complete product data sheets are available upon request\n- Our technical team can assist with application-specific recommendations\n\nIf you need further clarification on any specification or would like to discuss your application requirements, please let us know.\n\n{$signature}",
            'more_info' => "Dear {$first_name},\n\nThank you for reaching out to Tupi Supreme regarding \"{$message['subject']}\".\n\nTo ensure we provide you with the most accurate information and best possible solution, could you please provide a few more details:\n\n- \n- \n- \n\nOnce we receive this information, we'll respond promptly with our recommendations.\n\n{$signature}",
            'schedule'  => "Dear {$first_name},\n\nThank you for your inquiry about \"{$message['subject']}\".\n\nWe'd be happy to discuss your requirements in more detail. Would you be available for a call or meeting?\n\nPlease let us know:\n- Your preferred date and time\n- Whether you'd prefer a phone call, video call, or in-person meeting\n\nAlternatively, feel free to continue the conversation by email — whichever is most convenient for you.\n\n{$signature}",
            'follow_up' => "Dear {$first_name},\n\nI hope this message finds you well. I'm following up on your inquiry about \"{$message['subject']}\" to ensure your questions have been fully addressed.\n\nIf there's anything else we can clarify or if you're ready to move forward, please don't hesitate to let us know.\n\nWe look forward to the opportunity to work with you.\n\n{$signature}",
            'appreciation' => "Dear {$first_name},\n\nOn behalf of the entire Tupi Supreme team, thank you for reaching out to us.\n\nWe truly value your interest and the opportunity to assist you. Your inquiry about \"{$message['subject']}\" is important to us, and we're committed to ensuring you have the best possible experience.\n\nIf there's anything at all we can do to help — now or in the future — please know that we're just an email away.\n\n{$signature}",
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
$per_page = 5;
$current_page_num = 1;

if ($action === 'list') {
    $per_page = 5;
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
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
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
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #e4e4e7 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #e4e4e7; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #d4d4d8; }
        }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="flex items-center gap-3">
                <span class="pg-icon w-10 h-10 rounded-lg bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-envelope"></i>
                </span>
                <div>
                    <h1 class="text-xl font-semibold text-zinc-900">Contact Messages</h1>
                    <p class="text-sm text-zinc-500 mt-0.5">Messages submitted through the public contact form</p>
                </div>
            </div>
            <?php if ($action === 'view'): ?>
            <a href="messages.php" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                <i class="fas fa-arrow-left text-xs"></i> Back to List
            </a>
            <?php endif; ?>
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
            <div class="bg-white rounded-lg border border-zinc-200 p-6 mb-6 max-w-4xl">
                <div class="flex items-center gap-3 pb-5 mb-5 border-b border-zinc-200">
                    <div class="w-10 h-10 rounded-full bg-[#2c5530] flex items-center justify-center text-white font-medium text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($message['name'], 0, 1)); ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-zinc-900"><?php echo htmlspecialchars($message['subject']); ?></h2>
                            <?php if (!$message['is_read']): ?>
                                <span class="lv-badge lv-badge-amber">Unread</span>
                            <?php endif; ?>
                            <?php if ($message['is_archived']): ?>
                                <span class="lv-badge lv-badge-zinc"><i class="fas fa-box"></i> Archived</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-zinc-500 mt-0.5">Received <?php echo formatDate($message['created_at'], 'F d, Y \a\t g:i A'); ?></p>
                    </div>
                </div>

                <!-- Sender info -->
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 mb-6 px-4 py-3 bg-zinc-50 rounded-md border border-zinc-200">
                    <p class="text-sm font-medium text-zinc-900"><?php echo htmlspecialchars($message['name']); ?></p>
                    <?php if ($message['company']): ?>
                        <span class="text-sm text-zinc-500"><?php echo htmlspecialchars($message['company']); ?></span>
                    <?php endif; ?>
                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="text-sm text-[#2c5530] hover:underline inline-flex items-center gap-1.5">
                        <i class="fas fa-envelope text-[10px]"></i><?php echo htmlspecialchars($message['email']); ?>
                    </a>
                    <?php if ($message['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" class="text-sm text-[#2c5530] hover:underline inline-flex items-center gap-1.5">
                            <i class="fas fa-phone text-[10px]"></i><?php echo htmlspecialchars($message['phone']); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Message body -->
                <div class="mb-6">
                    <label class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 mb-2 block">Message</label>
                    <p class="text-sm text-zinc-700 whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($message['message']); ?></p>
                </div>

                <!-- Reply compose -->
                <div class="mb-6 p-5 bg-zinc-50 rounded-md border border-zinc-200">
                    <p class="text-sm font-medium text-zinc-900 mb-1.5 flex items-center gap-2"><i class="fas fa-reply text-xs text-[#2c5530]"></i> Reply</p>
                    <?php if (smtpRepliesEnabled()): ?>
                    <p class="text-xs text-zinc-500 mb-3">Sent from <strong class="text-zinc-700"><?php echo htmlspecialchars(SMTP_FROM_EMAIL !== '' ? SMTP_FROM_EMAIL : SMTP_USERNAME); ?></strong> via Gmail SMTP.</p>
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
                                <option value="schedule">Schedule call / meeting</option>
                                <option value="follow_up">Follow-up check-in</option>
                                <option value="appreciation">Thank you / appreciation</option>
                                <option value="blank">Blank</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Choosing a template replaces the draft below. Each includes your name, company signature, and contact details.</p>
                        </div>
                        <div class="mb-3">
                            <label for="reply_body" class="block text-xs font-medium text-gray-600 mb-1">Message</label>
                            <textarea id="reply_body" name="reply_body" rows="12" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($reply_template); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Edit the draft as needed — the quoted original at the bottom is included in the email.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                                <i class="fas fa-paper-plane text-xs"></i> Send Reply
                            </button>
                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo rawurlencode($message['subject']); ?>" class="text-zinc-500 hover:text-zinc-700 text-xs inline-flex items-center gap-1.5 ml-2">
                                <i class="fas fa-external-link-alt text-[10px]"></i>or open in mail client
                            </a>
                        </div>
                    </form>
                    <?php else: ?>
                    <p class="text-xs text-zinc-500 mb-3">SMTP replies are not configured. To send replies directly from here, set your Gmail address and app password in <code class="bg-zinc-200 px-1 rounded text-zinc-700">admin/mail_config.php</code>. Meanwhile, you can reply via your own mail client:</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo rawurlencode($message['subject']); ?>" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                            <i class="fas fa-reply text-xs"></i> Reply by Email
                        </a>
                        <?php if ($message['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-phone text-xs"></i> Call Sender
                        </a>
                        <?php endif; ?>
                        <button type="button" onclick="copyEmail('<?php echo htmlspecialchars($message['email'], ENT_QUOTES); ?>')" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-copy text-xs"></i> Copy Email
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap gap-2 pt-5 border-t border-zinc-200">
                    <?php if (!$message['is_read']): ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="read">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                            <i class="fas fa-check text-xs"></i> Mark as Read
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="unread">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-envelope text-xs"></i> Mark as Unread
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if (!$message['is_archived']): ?>
                    <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Archive this message? It can be restored from the Archived filter.');">
                        <input type="hidden" name="post_action" value="archive">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-box text-xs"></i> Archive
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" action="messages.php" class="inline">
                        <input type="hidden" name="post_action" value="unarchive">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-box-open text-xs"></i> Restore
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="messages.php" class="inline ml-auto" onsubmit="return confirm('Delete this message permanently? This cannot be undone.');">
                        <input type="hidden" name="post_action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                        <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-red-600 text-sm font-medium text-white hover:bg-red-700 transition-colors">
                            <i class="fas fa-trash text-xs"></i> Delete
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
            <div class="mb-4 px-4 py-3 rounded-md bg-zinc-50 border border-zinc-200 flex items-start gap-2.5">
                <i class="fas fa-info-circle text-zinc-400 mt-0.5 text-sm"></i>
                <p class="text-xs text-zinc-500 leading-relaxed">Messages submitted through the public <a href="../contact.php#contact-form" target="_blank" class="text-[#2c5530] font-medium hover:underline">contact form</a> appear here. Click a row to view the full message and reply by email. Unread rows are highlighted; archived messages are hidden from the active list.</p>
            </div>

            <!-- List View -->
            <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
                <!-- Stats + Filters -->
                <div class="px-4 py-3.5 border-b border-zinc-200">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600"><i class="fas fa-layer-group text-[9px]"></i><?php echo $stats['total']; ?> total</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e9f1ea] px-2.5 py-1 text-xs font-medium text-[#2c5530]"><i class="fas fa-envelope text-[9px]"></i><?php echo $stats['unread']; ?> unread</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600"><i class="fas fa-box text-[9px]"></i><?php echo $stats['archived']; ?> archived</span>
                    </div>
                    <form method="GET" action="messages.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter" class="px-3 py-2 border border-zinc-200 rounded-md text-sm text-zinc-900 focus:outline-none focus:ring-2 focus:ring-[#2c5530]/15 focus:border-[#2c5530]">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Active (not archived)</option>
                                <option value="unread" <?php echo $filter === 'unread' ? 'selected' : ''; ?>>Unread only</option>
                                <option value="archived" <?php echo $filter === 'archived' ? 'selected' : ''; ?>>Archived only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search name, email, subject, or message…"
                                       class="w-full pl-9 pr-3 py-2 border border-zinc-200 rounded-md text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#2c5530]/15 focus:border-[#2c5530]">
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                            <i class="fas fa-filter text-xs"></i> Filter
                        </button>
                        <?php if ($filter !== 'all' || !empty($_GET['q'])): ?>
                        <a href="messages.php" class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                            <i class="fas fa-times text-xs"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full">
                    <thead class="sticky top-0">
                        <tr>
                            <th class="text-left">Sender</th>
                            <th class="text-left">Subject & Preview</th>
                            <th class="text-left">Date</th>
                            <th class="text-left">Status</th>
                            <th class="text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_messages)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">No messages found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_messages as $msg): ?>
                                <tr class="<?php echo !$msg['is_read'] ? 'bg-[#f4f9f5]' : ''; ?> cursor-pointer" onclick="window.location='?action=view&id=<?php echo $msg['id']; ?>'">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full <?php echo !$msg['is_read'] ? 'bg-[#2c5530] text-white' : 'bg-zinc-100 text-zinc-600'; ?> flex items-center justify-center font-medium text-xs flex-shrink-0">
                                                <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm <?php echo !$msg['is_read'] ? 'font-semibold text-zinc-900' : 'font-medium text-zinc-700'; ?>">
                                                    <?php echo htmlspecialchars($msg['name']); ?>
                                                </div>
                                                <?php if ($msg['company']): ?>
                                                    <div class="text-xs text-zinc-500 truncate"><?php echo htmlspecialchars($msg['company']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="max-w-md">
                                        <div class="text-sm <?php echo !$msg['is_read'] ? 'font-semibold text-zinc-900' : 'text-zinc-700'; ?> truncate">
                                            <?php if (!$msg['is_read']): ?><i class="fas fa-circle text-[#2c5530] text-[7px] mr-1.5 align-middle"></i><?php endif; ?>
                                            <?php echo htmlspecialchars($msg['subject']); ?>
                                        </div>
                                        <div class="text-xs text-zinc-500 truncate mt-0.5"><?php echo htmlspecialchars(mb_strimwidth($msg['message'], 0, 100, '…')); ?></div>
                                    </td>
                                    <td class="text-sm text-zinc-500 whitespace-nowrap"><?php echo formatDate($msg['created_at'], 'M d, Y'); ?></td>
                                    <td>
                                        <?php if ($msg['is_archived']): ?>
                                            <span class="lv-badge lv-badge-zinc"><i class="fas fa-box"></i> Archived</span>
                                        <?php elseif (!$msg['is_read']): ?>
                                            <span class="lv-badge lv-badge-amber">Unread</span>
                                        <?php else: ?>
                                            <span class="lv-badge lv-badge-zinc">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="whitespace-nowrap" onclick="event.stopPropagation();">
                                        <a href="?action=view&id=<?php echo $msg['id']; ?>" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo rawurlencode($msg['subject']); ?>" title="Reply by email">
                                            <i class="fas fa-reply"></i>
                                        </a>
                                        <?php if (!$msg['is_archived']): ?>
                                        <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Archive this message?');">
                                            <input type="hidden" name="post_action" value="archive">
                                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" title="Archive">
                                                <i class="fas fa-box"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" action="messages.php" class="inline" onsubmit="return confirm('Delete this message permanently? This cannot be undone.');">
                                            <input type="hidden" name="post_action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                            <button type="submit" class="lv-del" title="Delete">
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
        <?php endif; ?>
    </div>
</body>
</html>
