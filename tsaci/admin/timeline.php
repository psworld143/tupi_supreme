<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Timeline event saved successfully!';
if ($status_param === 'deleted') $success = 'Timeline event deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $year = intval($_POST['year'] ?? 0);
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title) || $year <= 0) {
        $error = 'Year and title are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO timeline_events (year, title, description, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiii", $year, $title, $description, $display_order, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'timeline_events', $db->insert_id, "Created timeline event: {$year} - {$title}");
                redirect('timeline.php?status=saved');
            } else {
                $error = 'Error adding timeline event: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE timeline_events SET year = ?, title = ?, description = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("issiiii", $year, $title, $description, $display_order, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'timeline_events', $id, "Updated timeline event: {$year} - {$title}");
                redirect('timeline.php?status=saved');
            } else {
                $error = 'Error updating timeline event: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM timeline_events WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'timeline_events', $del_id, 'Deleted timeline event');
        redirect('timeline.php?status=deleted');
    } else {
        $error = 'Error deleting timeline event: ' . $stmt->error;
    }
}

// Get event for edit
$edit_event = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM timeline_events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_event = $result->fetch_assoc();

    if (!$edit_event) {
        $error = 'Timeline event not found.';
        $action = 'list';
    }
}

// Get all events for list (with pagination + filtering + search)
$all_events = [];
$total_events = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_status === 'active') {
        $where[] = 'te.is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'te.is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(te.title LIKE ? OR te.description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM timeline_events te $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_events = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_events / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT te.*, au.username as updated_by_name FROM timeline_events te LEFT JOIN admin_users au ON te.updated_by = au.id $where_sql ORDER BY te.year DESC, te.display_order, te.title LIMIT ? OFFSET ?";
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
            $all_events[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM timeline_events");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// All timeline events render in the same "Our Journey" section on about.php
$view_url = '../about.php#timeline';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline Events (Our Journey) Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Lightweight inline HTML editor (replaces EOL CKEditor 4) -->
    <style>
        .qe-toolbar button { padding: 4px 8px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .qe-toolbar button:hover { background: #f3f4f6; }
        .qe-editor { min-height: 140px; }
        .qe-editor:focus { outline: none; border-color: #2c5530; }
        .qe-editor:empty:before { content: attr(data-placeholder); color: #9ca3af; }
    </style>
    <?php endif; ?>
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
                        <i class="fas fa-history text-primary"></i> Timeline Events Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage "Our Journey" timeline events shown on the About page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Event
                </a>
                <?php else: ?>
                <a href="timeline.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Timeline Event</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Timeline events appear in the <strong>"Our Journey"</strong> section on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">About page</a>. They're sorted by year (ascending), then by display order.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year <span class="text-red-500">*</span></label>
                                    <input type="number" name="year" id="year_input" required min="1900" max="2100"
                                           value="<?php echo htmlspecialchars($edit_event['year'] ?? date('Y')); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., 1999"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The year this milestone occurred. Events are sorted oldest → newest.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                    <input type="number" name="display_order" id="order_input" min="0"
                                           value="<?php echo htmlspecialchars($edit_event['display_order'] ?? 0); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Tiebreaker for events in the same year. Lower = first. <button type="button" onclick="suggestOrder()" class="text-primary hover:underline">Auto-suggest from year</button>.</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                <input type="text" name="title" id="title_input" required
                                       value="<?php echo htmlspecialchars($edit_event['title'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                       placeholder="e.g., Company Founded"
                                       oninput="updatePreview()">
                                <p class="text-xs text-gray-400 mt-1">A short label for this milestone (shown as the heading).</p>
                            </div>

                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <div id="html_toolbar" class="qe-toolbar flex flex-wrap gap-1 mb-2 p-2 bg-gray-50 rounded-t-md border border-b-0 border-gray-300">
                                    <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                                    <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                                    <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                    <button type="button" data-cmd="insertUnorderedList" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                                    <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                                    <button type="button" data-cmd="formatBlock" data-val="h4" title="Small heading">H4</button>
                                    <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>
                                    <button type="button" data-cmd="createLink" title="Link"><i class="fas fa-link"></i></button>
                                    <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="fas fa-eraser"></i></button>
                                </div>
                                <div id="desc_html" contenteditable="true" data-placeholder="Describe this milestone…"
                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary qe-editor bg-white prose max-w-none"
                                     oninput="document.getElementById('description').value = this.innerHTML; updatePreview();"><?php echo htmlspecialchars_decode($edit_event['description'] ?? '', ENT_QUOTES); ?></div>
                                <textarea name="description" id="description" rows="5" class="hidden"><?php echo htmlspecialchars_decode($edit_event['description'] ?? '', ENT_QUOTES); ?></textarea>
                                <p class="text-xs text-gray-400 mt-1">A brief description of the event. Supports formatting (bold, lists, links).</p>
                            </div>

                            <div class="mt-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1"
                                           <?php echo ($edit_event && $edit_event['is_active']) || !$edit_event ? 'checked' : ''; ?>
                                           class="sr-only peer">
                                    <span class="relative inline-flex items-center">
                                        <span class="w-11 h-6 bg-gray-300 peer-checked:bg-primary rounded-full transition-colors"></span>
                                        <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
                                    </span>
                                    <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the About page)</span></span>
                                </label>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Event' : 'Save Changes'; ?>
                                </button>
                                <a href="timeline.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how this event looks on the About page)</span>
                        </p>
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="timeline-item relative mb-2">
                                <div class="timeline-dot absolute -left-2 top-2 w-3 h-3 rounded-full bg-primary border-2 border-white shadow"></div>
                                <div class="timeline-content bg-white border border-[#e6ece8] p-6 rounded-2xl relative ml-4">
                                    <span class="eyebrow mb-3 inline-block px-2 py-0.5 text-xs rounded-full bg-primary/10 text-primary font-semibold" id="preview_year"><?php echo htmlspecialchars($edit_event['year'] ?? date('Y')); ?></span>
                                    <h4 class="text-xl font-semibold text-[#23332c] mb-2 mt-2" id="preview_title"><?php echo htmlspecialchars($edit_event['title'] ?? 'Event title'); ?></h4>
                                    <div class="text-[#7d8b84] leading-relaxed text-sm" id="preview_desc"><?php echo $edit_event['description'] ?? '<span class="text-gray-400">Event description appears here…</span>'; ?></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The actual styling on the public page may differ slightly. This is an approximation.</p>
                    </div>
                </div>
            </div>

            <script>
            // Lightweight HTML editor toolbar
            (function () {
                var toolbar = document.getElementById('html_toolbar');
                var editor = document.getElementById('desc_html');
                if (toolbar && editor) {
                    toolbar.addEventListener('mousedown', function (e) {
                        var btn = e.target.closest('button[data-cmd]');
                        if (!btn) return;
                        e.preventDefault();
                        editor.focus();
                        var cmd = btn.getAttribute('data-cmd');
                        var val = btn.getAttribute('data-val');
                        if (cmd === 'createLink') {
                            var url = prompt('Link URL:', 'https://');
                            if (url) document.execCommand('createLink', false, url);
                        } else if (val) {
                            document.execCommand(cmd, false, val);
                        } else {
                            document.execCommand(cmd, false, null);
                        }
                        document.getElementById('description').value = editor.innerHTML;
                        updatePreview();
                    });
                }
                // Sync editor -> hidden textarea before submit
                var form = document.querySelector('form[method="POST"]');
                if (form && editor) {
                    form.addEventListener('submit', function () {
                        document.getElementById('description').value = editor.innerHTML;
                    });
                }
            })();

            // Live preview
            function updatePreview() {
                var year = document.getElementById('year_input').value || 'Year';
                var title = document.getElementById('title_input').value || 'Event title';
                var desc = document.getElementById('desc_html').innerHTML || '<span class="text-gray-400">Event description appears here…</span>';
                document.getElementById('preview_year').textContent = year;
                document.getElementById('preview_title').textContent = title;
                document.getElementById('preview_desc').innerHTML = desc;
            }

            // Auto-suggest display order: use the last two digits of the year
            function suggestOrder() {
                var year = parseInt(document.getElementById('year_input').value, 10);
                if (year > 0) {
                    document.getElementById('order_input').value = year % 100;
                } else {
                    alert('Enter a year first.');
                }
            }
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="timeline.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title or description…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="timeline.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_events)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No timeline events found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new event</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_events as $event):
                                $preview = mb_substr(strip_tags($event['description'] ?? ''), 0, 60);
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($event['year']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['title']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($preview); ?><?php echo mb_strlen(strip_tags($event['description'] ?? '')) > 60 ? '…' : ''; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$event['display_order']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($event['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $event['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="timeline.php" class="inline" onsubmit="return confirm('Delete this timeline event? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $event['id']; ?>">
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
                    'total_items'   => $total_events,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View About Page (Our Journey)</a></li>
                    <li><a href="about.php" class="hover:underline"><i class="fas fa-cog mr-1"></i>Manage About Page Content</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
