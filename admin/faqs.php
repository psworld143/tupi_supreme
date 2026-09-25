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
if ($status_param === 'saved')   $success = 'FAQ saved successfully!';
if ($status_param === 'deleted') $success = 'FAQ deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $question = sanitizeInput($_POST['question'] ?? '');
        $answer = $_POST['answer'] ?? '';
        $category = sanitizeInput($_POST['category'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($question) || trim($answer) === '') {
            $error = 'Question and answer are both required.';
        } else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO faqs (question, answer, category, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiii", $question, $answer, $category, $display_order, $is_active, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'faqs', $db->insert_id, "Added FAQ: {$question}");
                    redirect('faqs.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } elseif ($action === 'edit' && $id) {
                $stmt = $db->prepare("UPDATE faqs SET question = ?, answer = ?, category = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("sssiiii", $question, $answer, $category, $display_order, $is_active, $_SESSION['admin_id'], $id);
                if ($stmt->execute()) {
                    logActivity('update', 'faqs', $id, "Updated FAQ: {$question}");
                    redirect('faqs.php?status=saved');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $del_id = intval($_POST['delete_id']);
        $stmt = $db->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            logActivity('delete', 'faqs', $del_id, 'Deleted FAQ');
            redirect('faqs.php?status=deleted');
        } else {
            $error = 'Error deleting FAQ: ' . $stmt->error;
        }
    }
}

// Get FAQ for edit
$edit_faq = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_faq = $result->fetch_assoc();
    if (!$edit_faq) {
        $error = 'FAQ not found.';
        $action = 'list';
    }
}

// Get all FAQs for list (with pagination + filtering + search)
$all_faqs = [];
$total_faqs = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;
$categories = [];

if ($action === 'list') {
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_category = $_GET['filter_category'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Distinct categories for the filter dropdown
    $cat_result = $db->query("SELECT DISTINCT category FROM faqs WHERE category IS NOT NULL AND category <> '' ORDER BY category");
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_status === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($filter_category !== '') {
        $where[] = 'category = ?';
        $params[] = $filter_category;
        $types .= 's';
    }
    if ($search_q !== '') {
        $where[] = '(question LIKE ? OR answer LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM faqs $where_sql");
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_faqs = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_faqs / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_stmt = $db->prepare("SELECT * FROM faqs $where_sql ORDER BY display_order, id LIMIT ? OFFSET ?");
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_faqs[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM faqs");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// FAQs render in the accordion on the public Resources page (first 5 also on Contact)
$view_url = '../resources.php#faqs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs Management - <?php echo SITE_NAME; ?></title>
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
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-question-circle text-primary"></i> FAQs Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage frequently asked questions shown on the Resources and Contact pages.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New FAQ
                </a>
                <?php else: ?>
                <a href="faqs.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> FAQ</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">FAQs appear as an accordion on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Resources page</a>, and the first 5 active FAQs (by display order) also appear on the <a href="../contact.php" target="_blank" class="underline hover:text-blue-900">Contact page</a>.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Question <span class="text-red-500">*</span></label>
                                    <input type="text" name="question" id="question_input" required
                                           value="<?php echo htmlspecialchars($edit_faq['question'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., What is the particle size of your activated carbon?"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The question shown as the accordion header.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Answer <span class="text-red-500">*</span></label>
                                    <textarea name="answer" id="answer_input" rows="6" required
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                              placeholder="e.g., Our granulated activated carbon is specified at 2mm below..."
                                              oninput="updatePreview()"><?php echo htmlspecialchars($edit_faq['answer'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Line breaks are preserved on the public page.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="category" id="category_input"
                                           value="<?php echo htmlspecialchars($edit_faq['category'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., General"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">Used for grouping/filtering in the admin list.</p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <button type="button" class="quick-pick" onclick="setCategory('General')">General</button>
                                        <button type="button" class="quick-pick" onclick="setCategory('Products')">Products</button>
                                        <button type="button" class="quick-pick" onclick="setCategory('Services')">Services</button>
                                        <button type="button" class="quick-pick" onclick="setCategory('Ordering')">Ordering</button>
                                        <button type="button" class="quick-pick" onclick="setCategory('Technical')">Technical</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_faq['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first. Use 10, 20, 30…</p>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" value="1"
                                                   <?php echo ($edit_faq && $edit_faq['is_active']) || !$edit_faq ? 'checked' : ''; ?>
                                                   class="sr-only peer">
                                            <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                         peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                         after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                         after:bg-white after:rounded-full after:shadow
                                                         after:transition-transform after:duration-300 after:ease-in-out
                                                         peer-checked:after:translate-x-5
                                                         hover:after:scale-110 active:after:scale-95"></span>
                                            <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add FAQ' : 'Save Changes'; ?>
                                </button>
                                <a href="faqs.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(FAQ accordion item)</span>
                        </p>
                        <div class="rounded-2xl p-6" style="background: #23332c;">
                            <div class="rounded-2xl overflow-hidden border" style="background: rgba(255,255,255,0.08); border-color: rgba(139,195,74,0.45);">
                                <div class="w-full px-6 py-5 flex items-center justify-between gap-4">
                                    <span class="min-w-0">
                                        <span id="preview_category" class="text-[11px] font-medium uppercase tracking-wider rounded-full px-2.5 py-0.5 mb-2 <?php echo empty($edit_faq['category'] ?? '') ? 'hidden' : 'inline-block'; ?>" style="color: #8bc34a; background: rgba(139,195,74,0.15);"><?php echo htmlspecialchars($edit_faq['category'] ?? ''); ?></span>
                                        <span id="preview_question" class="block text-base font-semibold text-white pr-2"><?php echo htmlspecialchars($edit_faq['question'] ?? 'Your question here'); ?></span>
                                    </span>
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.1);">
                                        <i class="fas fa-chevron-down text-sm" style="color: #8bc34a; transform: rotate(180deg);"></i>
                                    </span>
                                </div>
                                <div class="px-6 pb-5">
                                    <p id="preview_answer" class="leading-relaxed text-sm" style="color: rgba(255,255,255,0.7);"><?php echo nl2br(htmlspecialchars($edit_faq['answer'] ?? 'The answer will appear here.')); ?></p>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The preview mirrors the dark FAQ accordion on the public Resources page.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helper
            function setCategory(name) {
                document.getElementById('category_input').value = name;
                updatePreview();
            }

            // Live preview
            function updatePreview() {
                var question = document.getElementById('question_input').value || 'Your question here';
                var answer = document.getElementById('answer_input').value || 'The answer will appear here.';
                var category = document.getElementById('category_input').value || '';
                var catEl = document.getElementById('preview_category');
                document.getElementById('preview_question').textContent = question;
                document.getElementById('preview_answer').innerText = answer;
                if (category.trim()) {
                    catEl.textContent = category;
                    catEl.classList.remove('hidden');
                    catEl.classList.add('inline-block');
                } else {
                    catEl.classList.add('hidden');
                    catEl.classList.remove('inline-block');
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
                    <form method="GET" action="faqs.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <select name="filter_category" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($_GET['filter_category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search questions or answers…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_category']) || !empty($_GET['q'])): ?>
                        <a href="faqs.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_faqs)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <i class="fas fa-question-circle text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No FAQs found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new FAQ</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_faqs as $faq): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm">
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($faq['question']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars(mb_strimwidth($faq['answer'], 0, 90, '…')); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php if ($faq['category']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700"><?php echo htmlspecialchars($faq['category']); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$faq['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($faq['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $faq['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="faqs.php" class="inline" onsubmit="return confirm('Delete this FAQ? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $faq['id']; ?>">
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
                    'total_items'   => $total_faqs,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View FAQs on Resources Page</a></li>
                    <li><a href="../contact.php" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View FAQs on Contact Page</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
