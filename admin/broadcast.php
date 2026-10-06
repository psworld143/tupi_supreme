<?php
require_once 'config.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';
$send_report = null;

// Status flash + send report from PRG redirect
$status_param = $_GET['status'] ?? '';
if ($status_param === 'sent') $success = 'Broadcast sent.';

if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
if (!empty($_SESSION['broadcast_report'])) {
    $send_report = $_SESSION['broadcast_report'];
    unset($_SESSION['broadcast_report']);
}

// Distinct client emails collected from contact form submissions
$recipients = [];
$res = $db->query(
    "SELECT name, email, COUNT(*) AS msg_count, MAX(created_at) AS last_contact
     FROM contact_messages
     WHERE email <> ''
     GROUP BY email, name
     ORDER BY last_contact DESC"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $recipients[] = $row;
    }
}
$recipient_count = count($recipients);
$smtp_ready = smtpRepliesEnabled();

// Ready-made broadcast templates — picking one prefills the compose form
$email_templates = [
    'announcement' => [
        'label'    => 'General Announcement',
        'subject'  => 'An update from Tupi Supreme Activated Carbon, Inc.',
        'greeting' => 'Dear',
        'message'  => "We hope this message finds you well.\n\nWe are writing to share an important update from our team.\n\n[Write your announcement here — e.g. new services, schedule changes, or company news.]\n\nIf you have any questions, simply reply to this email and our team will be happy to assist you.\n\nThank you for your continued trust.",
    ],
    'product' => [
        'label'    => 'Product / Service News',
        'subject'  => 'Introducing our latest activated carbon solutions',
        'greeting' => 'Dear',
        'message'  => "We are excited to share our latest development with you.\n\n[Describe the new product or service — key benefits, applications, and availability.]\n\nOur team would be glad to walk you through the details or prepare a quotation tailored to your facility's needs.\n\nSimply reply to this email to get started.",
    ],
    'promo' => [
        'label'    => 'Promotion / Special Offer',
        'subject'  => 'A special offer for our valued clients',
        'greeting' => 'Dear',
        'message'  => "As one of our valued clients, we would like to extend a special offer to you.\n\n[Describe the promotion — discount, bundled service, or limited-time pricing, including dates and terms.]\n\nThis offer is available for a limited time. Reply to this email or contact our team to take advantage of it.\n\nThank you for choosing Tupi Supreme.",
    ],
    'maintenance' => [
        'label'    => 'Maintenance / Service Notice',
        'subject'  => 'Service notice from Tupi Supreme',
        'greeting' => 'Dear',
        'message'  => "We would like to inform you of a scheduled update to our operations.\n\n[Describe the schedule — dates, times, and any services affected.]\n\nWe apologize for any inconvenience and appreciate your understanding. For urgent concerns during this period, please reply to this email and we will assist you promptly.",
    ],
    'followup' => [
        'label'    => 'Follow-up / Check-in',
        'subject'  => 'Following up on your inquiry',
        'greeting' => 'Dear',
        'message'  => "Thank you for reaching out to Tupi Supreme Activated Carbon, Inc.\n\nWe are following up to make sure your questions have been fully answered and to see if there is anything else we can help you with.\n\nIf you are ready to proceed or would like a quotation, simply reply to this email and our team will assist you right away.",
    ],
    'appreciation' => [
        'label'    => 'Thank You / Appreciation',
        'subject'  => 'Thank you for your continued partnership',
        'greeting' => 'Dear',
        'message'  => "On behalf of the entire Tupi Supreme team, we would like to express our sincere gratitude for your trust and partnership.\n\nIt is clients like you who motivate us to keep improving our activated carbon solutions and service quality.\n\nWe look forward to continuing to serve your water treatment needs. If there is anything we can do better, we would love to hear from you — simply reply to this email.",
    ],
];

// Handle broadcast send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = !empty($_POST['ajax']); // modal sends via fetch; result returns as JSON
    $report = null;
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } elseif (!$smtp_ready) {
        $error = 'SMTP is not configured. Check admin/mail.ini before sending broadcasts.';
    } else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $greeting = trim($_POST['greeting'] ?? '');

        // Only send to clients the admin actually selected in the table
        $selected = $_POST['recipients'] ?? [];
        if (!is_array($selected)) $selected = [$selected];
        $selected = array_map('strtolower', array_map('trim', $selected));

        $targets = array_values(array_filter($recipients, function ($r) use ($selected) {
            return in_array(strtolower($r['email']), $selected, true);
        }));

        if ($subject === '' || $message === '') {
            $error = 'Subject and message are required.';
        } elseif (empty($targets)) {
            $error = 'Select at least one client to send to.';
        } else {
            set_time_limit(0);
            $report = ['sent' => 0, 'failed' => 0, 'failures' => [], 'subject' => $subject];

            foreach ($targets as $r) {
                $name = trim($r['name'] ?? '');
                $personal_body = ($greeting !== '' && $name !== '')
                    ? $greeting . " " . $name . ",\n\n" . $message
                    : $message;

                $result = sendMessageReply(
                    $r['email'],
                    $name,
                    $subject,
                    $personal_body,
                    'This announcement was sent by %s to clients who contacted us through our website.'
                );

                if ($result['success']) {
                    $report['sent']++;
                } else {
                    $report['failed']++;
                    $report['failures'][] = ['email' => $r['email'], 'error' => $result['error']];
                }
                usleep(300000); // ~3 emails/sec — stay friendly to SMTP rate limits
            }

            logActivity('broadcast', 'contact_messages', 0,
                "Broadcast '{$subject}' — {$report['sent']} sent, {$report['failed']} failed");
        }
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $report !== null, 'error' => $error, 'report' => $report]);
        exit;
    }

    if ($report !== null) {
        $_SESSION['broadcast_report'] = $report;
        if ($report['sent'] === 0 && $report['failed'] > 0) {
            $_SESSION['flash_error'] = 'Broadcast failed for all recipients. See the report below.';
        }
        redirect('broadcast.php?status=sent');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Broadcast - <?php echo SITE_NAME; ?></title>
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="relative lg:ml-64 p-4 lg:p-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900">Email Broadcast</h1>
                <p class="text-sm text-zinc-500 mt-0.5">Send one announcement to every client who contacted you</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 text-sm text-zinc-500">
                    <i class="fas fa-users text-zinc-400"></i>
                    <strong class="text-zinc-900" id="bc-selected-count">0</strong> of <?php echo $recipient_count; ?> selected
                </span>
                <button type="button" onclick="openBroadcastModal()" <?php echo (!$smtp_ready || $recipient_count === 0) ? 'disabled' : ''; ?>
                        class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-secondary transition-colors inline-flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-paper-plane"></i> Compose Broadcast
                </button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-3">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <span class="text-sm"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-start gap-3">
                <i class="fas fa-check-circle mt-0.5"></i>
                <span class="text-sm"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$smtp_ready): ?>
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg flex items-start gap-3">
                <i class="fas fa-exclamation-triangle mt-0.5"></i>
                <div class="text-sm">
                    <strong>SMTP is not configured.</strong> Broadcasts are disabled until <code class="bg-amber-100 px-1 rounded">admin/mail.ini</code> contains valid Gmail SMTP credentials.
                </div>
            </div>
        <?php endif; ?>

        <!-- Send report -->
        <?php if ($send_report): ?>
            <div class="bg-white rounded-lg border border-zinc-200 p-5 mb-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center <?php echo $send_report['failed'] > 0 ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'; ?>">
                        <i class="fas <?php echo $send_report['failed'] > 0 ? 'fa-exclamation' : 'fa-check'; ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900">Broadcast report — "<?php echo htmlspecialchars($send_report['subject']); ?>"</h2>
                        <p class="text-xs text-zinc-500"><?php echo $send_report['sent']; ?> delivered · <?php echo $send_report['failed']; ?> failed</p>
                    </div>
                </div>
                <?php if (!empty($send_report['failures'])): ?>
                    <div class="mt-3 border-t border-zinc-100 pt-3">
                        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">Failed deliveries</p>
                        <ul class="text-sm text-red-600 space-y-1">
                            <?php foreach ($send_report['failures'] as $f): ?>
                                <li><i class="fas fa-times-circle mr-1"></i><?php echo htmlspecialchars($f['email']); ?> — <?php echo htmlspecialchars($f['error']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Recipient list -->
        <div class="bg-white rounded-lg border border-zinc-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-900 flex items-center gap-2">
                    <i class="fas fa-address-book text-zinc-400"></i> Client Emails
                </h2>
                <span class="text-xs text-zinc-400">tick the clients you want to email, then Compose Broadcast</span>
            </div>

            <?php if ($recipient_count === 0): ?>
                <div class="p-10 text-center text-sm text-zinc-400">
                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                    No client emails yet — they appear here once someone submits the contact form.
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-zinc-100">
                                <th class="w-10 px-5 py-3">
                                    <input type="checkbox" id="bc-all" class="w-4 h-4 accent-[#2c5530] align-middle" title="Select all">
                                </th>
                                <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wide px-5 py-3">Client</th>
                                <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wide px-5 py-3">Email</th>
                                <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wide px-5 py-3">Messages</th>
                                <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wide px-5 py-3">Last Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recipients as $r): ?>
                                <tr class="border-b border-zinc-50 hover:bg-zinc-50/60 transition-colors cursor-pointer" onclick="if (event.target.type !== 'checkbox') { var cb = this.querySelector('.bc-check'); cb.checked = !cb.checked; } updateSelected();">
                                    <td class="px-5 py-3">
                                        <input type="checkbox" class="bc-check w-4 h-4 accent-[#2c5530] align-middle"
                                               name="recipients[]" value="<?php echo htmlspecialchars($r['email']); ?>"
                                               form="broadcast-form" onclick="event.stopPropagation()">
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">
                                                <?php echo strtoupper(substr($r['name'] !== '' ? $r['name'] : $r['email'], 0, 1)); ?>
                                            </div>
                                            <span class="text-sm font-medium text-zinc-800"><?php echo htmlspecialchars($r['name'] !== '' ? $r['name'] : '—'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-zinc-600"><?php echo htmlspecialchars($r['email']); ?></td>
                                    <td class="px-5 py-3 text-sm text-zinc-500"><?php echo (int)$r['msg_count']; ?></td>
                                    <td class="px-5 py-3 text-sm text-zinc-500"><?php echo date('M j, Y', strtotime($r['last_contact'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Compose modal -->
    <div id="bc-modal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-zinc-950/40 backdrop-blur-[2px] opacity-0 transition-opacity duration-200" id="bc-overlay"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 sm:p-6 pointer-events-none">
            <div id="bc-dialog" class="pointer-events-auto w-full max-w-xl max-h-[90vh] flex flex-col bg-white rounded-lg border border-zinc-200 shadow-xl opacity-0 translate-y-3 transition-all duration-200" role="dialog" aria-modal="true">
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-zinc-200 flex-shrink-0">
                    <span class="w-6 h-6 rounded-md bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-paper-plane text-[10px]"></i>
                    </span>
                    <h2 class="min-w-0 flex-1 text-sm font-semibold text-zinc-900 truncate">
                        Compose Broadcast <span class="text-zinc-400 font-normal">— <span id="bc-modal-count">0</span> selected</span>
                    </h2>
                    <button type="button" onclick="closeBroadcastModal()" title="Close (Esc)" class="w-7 h-7 rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-900 flex items-center justify-center transition-colors flex-shrink-0">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <form id="broadcast-form" method="POST" class="flex flex-col min-h-0">
                    <?php echo csrfTokenField(); ?>

                    <div class="p-5 overflow-y-auto min-h-0">
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Template</label>
                            <select id="tpl-select" onchange="applyTemplate(this.value)"
                                    class="w-full px-3 py-2 text-sm border border-zinc-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary bg-white">
                                <option value="">Start from scratch</option>
                                <?php foreach ($email_templates as $key => $tpl): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($tpl['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-zinc-400 mt-1">Picking a template fills the fields below — edit freely before sending.</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Subject</label>
                            <input type="text" name="subject" id="bc-subject" required maxlength="200"
                                   class="w-full px-3 py-2 text-sm border border-zinc-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                   placeholder="e.g. New product announcement">
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Greeting <span class="text-zinc-400 font-normal">(optional)</span></label>
                            <input type="text" name="greeting" id="bc-greeting" maxlength="60" value="Dear"
                                   class="w-full px-3 py-2 text-sm border border-zinc-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                   placeholder="Dear">
                            <p class="text-xs text-zinc-400 mt-1">When set, each client receives "Dear [their name]," above the message.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-600 mb-1">Message</label>
                            <textarea name="message" id="bc-message" rows="9" required
                                      class="w-full px-3 py-2 text-sm border border-zinc-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                      placeholder="Write your announcement here…"></textarea>
                            <p class="text-xs text-zinc-400 mt-1">Sent as a branded HTML email. Each client gets an individual copy — addresses are never shared.</p>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-t border-zinc-200 flex items-center justify-between gap-3 flex-shrink-0">
                        <p class="text-xs text-zinc-400">Sends one-by-one (~3/sec).</p>
                        <button type="submit" <?php echo !$smtp_ready ? 'disabled' : ''; ?>
                                class="bg-primary text-white px-5 py-2.5 rounded-md text-sm font-medium hover:bg-secondary transition-colors inline-flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fas fa-bullhorn"></i>
                            Send to <span id="bc-send-count">0</span> client(s)
                        </button>
                    </div>
                </form>

                <!-- Sending state -->
                <div id="bc-sending" style="display:none" class="flex-col items-center justify-center p-10 gap-3 text-center flex-1 min-h-0">
                    <i class="fas fa-circle-notch fa-spin text-[#2c5530] text-2xl"></i>
                    <p class="text-sm font-medium text-zinc-900">Sending to <span id="bc-sending-count">0</span> client(s)…</p>
                    <p class="text-xs text-zinc-400">Please keep this dialog open — emails go out one by one.</p>
                </div>

                <!-- Result state -->
                <div id="bc-result" style="display:none" class="flex-col flex-1 min-h-0">
                    <div class="p-6 text-center flex-1 overflow-y-auto">
                        <div id="bc-result-icon" class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center bg-emerald-100 text-emerald-600">
                            <i class="fas fa-check text-lg"></i>
                        </div>
                        <h3 id="bc-result-title" class="text-base font-semibold text-zinc-900 mb-1">Broadcast sent</h3>
                        <p id="bc-result-sub" class="text-sm text-zinc-500"></p>
                        <div id="bc-result-failures" class="hidden mt-4 text-left border-t border-zinc-100 pt-3">
                            <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide mb-2">Failed deliveries</p>
                            <ul id="bc-result-failure-list" class="text-sm text-red-600 space-y-1"></ul>
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-zinc-200 flex justify-end flex-shrink-0">
                        <button type="button" onclick="closeBroadcastModal()"
                                class="bg-zinc-900 text-white px-5 py-2.5 rounded-md text-sm font-medium hover:bg-zinc-800 transition-colors">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    var bcTemplates = <?php echo json_encode($email_templates); ?>;
    var bcModal   = document.getElementById('bc-modal');
    var bcOverlay = document.getElementById('bc-overlay');
    var bcDialog  = document.getElementById('bc-dialog');

    function applyTemplate(key) {
        var tpl = bcTemplates[key];
        document.getElementById('bc-subject').value  = tpl ? tpl.subject : '';
        document.getElementById('bc-greeting').value = tpl ? tpl.greeting : 'Dear';
        document.getElementById('bc-message').value  = tpl ? tpl.message : '';
    }

    function selectedCount() {
        return document.querySelectorAll('.bc-check:checked').length;
    }

    function updateSelected() {
        var n = selectedCount();
        document.getElementById('bc-selected-count').textContent = n;
        document.getElementById('bc-modal-count').textContent = n;
        document.getElementById('bc-send-count').textContent = n;
        var all = document.getElementById('bc-all');
        var total = document.querySelectorAll('.bc-check').length;
        all.checked = total > 0 && n === total;
        all.indeterminate = n > 0 && n < total;
    }

    document.getElementById('bc-all').addEventListener('change', function () {
        document.querySelectorAll('.bc-check').forEach(function (cb) { cb.checked = this.checked; }, this);
        updateSelected();
    });
    document.querySelectorAll('.bc-check').forEach(function (cb) {
        cb.addEventListener('change', updateSelected);
    });

    function bcShowPanel(id) {
        ['broadcast-form', 'bc-sending', 'bc-result'].forEach(function (pid) {
            document.getElementById(pid).style.display = (pid === id) ? 'flex' : 'none';
        });
    }

    function openBroadcastModal() {
        if (selectedCount() === 0) {
            alert('Select at least one client first — tick the checkboxes in the list.');
            return;
        }
        updateSelected();
        bcShowPanel('broadcast-form');
        bcModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            bcOverlay.classList.remove('opacity-0');
            bcDialog.classList.remove('opacity-0', 'translate-y-3');
        });
    }

    function showBroadcastResult(data) {
        var icon    = document.getElementById('bc-result-icon');
        var title   = document.getElementById('bc-result-title');
        var sub     = document.getElementById('bc-result-sub');
        var failBox = document.getElementById('bc-result-failures');
        var failList = document.getElementById('bc-result-failure-list');

        failBox.classList.add('hidden');
        failList.innerHTML = '';
        icon.className = 'w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center ';

        if (data.ok && data.report) {
            var allOk = data.report.failed === 0;
            icon.className += allOk ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600';
            icon.innerHTML = '<i class="fas ' + (allOk ? 'fa-check' : 'fa-exclamation') + ' text-lg"></i>';
            title.textContent = 'Broadcast "' + data.report.subject + '"';
            sub.textContent = data.report.sent + ' delivered · ' + data.report.failed + ' failed';

            if (data.report.failures && data.report.failures.length) {
                data.report.failures.forEach(function (f) {
                    var li = document.createElement('li');
                    li.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' +
                        f.email.replace(/[<>&]/g, '') + ' — ' + f.error.replace(/[<>&]/g, '');
                    failList.appendChild(li);
                });
                failBox.classList.remove('hidden');
            }
        } else {
            icon.className += 'bg-red-100 text-red-600';
            icon.innerHTML = '<i class="fas fa-times text-lg"></i>';
            title.textContent = 'Broadcast failed';
            sub.textContent = data.error || 'Something went wrong while sending.';
        }
        bcShowPanel('bc-result');
    }

    document.getElementById('broadcast-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var n = selectedCount();
        if (n === 0) { alert('Select at least one client first.'); return; }
        if (!confirm('Send this email to ' + n + ' selected client(s)?')) return;

        document.getElementById('bc-sending-count').textContent = n;
        bcShowPanel('bc-sending');

        var fd = new FormData(this);
        fd.append('ajax', '1');

        fetch(location.pathname, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(showBroadcastResult)
            .catch(function () {
                showBroadcastResult({ ok: false, error: 'Request failed — check your connection and try again.' });
            });
    });

    function closeBroadcastModal() {
        bcOverlay.classList.add('opacity-0');
        bcDialog.classList.add('opacity-0', 'translate-y-3');
        setTimeout(function () {
            bcModal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    }

    bcOverlay.addEventListener('click', closeBroadcastModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !bcModal.classList.contains('hidden')) closeBroadcastModal();
    });
    </script>
</body>
</html>
