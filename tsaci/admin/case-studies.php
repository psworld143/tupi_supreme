<?php
require_once 'config.php';
requireLogin();

// CSRF guard: all admin POSTs must carry a valid token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken()) {
    http_response_code(403);
    exit('Invalid security token. Reload the page and try again.');
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Case study saved successfully!';
if ($status_param === 'deleted') $success = 'Case study deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $slug = generateSlug($title);
    $client_name = sanitizeInput($_POST['client_name'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $industry = sanitizeInput($_POST['industry'] ?? '');
    $challenge = $_POST['challenge'] ?? '';
    $solution = $_POST['solution'] ?? '';
    $results = $_POST['results'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO case_studies (title, slug, client_name, location, industry, challenge, solution, results, image_url, display_order, is_featured, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssiiii", $title, $slug, $client_name, $location, $industry, $challenge, $solution, $results, $image_url, $display_order, $is_featured, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'case_studies', $db->insert_id, "Created case study: {$title}");
                redirect('case-studies.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE case_studies SET title = ?, slug = ?, client_name = ?, location = ?, industry = ?, challenge = ?, solution = ?, results = ?, image_url = ?, display_order = ?, is_featured = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssssiiiii", $title, $slug, $client_name, $location, $industry, $challenge, $solution, $results, $image_url, $display_order, $is_featured, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'case_studies', $id, "Updated case study: {$title}");
                redirect('case-studies.php?status=saved');
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM case_studies WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'case_studies', $del_id, 'Deleted case study');
        redirect('case-studies.php?status=deleted');
    } else {
        $error = 'Error deleting case study: ' . $stmt->error;
    }
}

// Get case study for edit
$edit_case = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM case_studies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_case = $result->fetch_assoc();
    if (!$edit_case) {
        $error = 'Case study not found.';
        $action = 'list';
    }
}

// Get all case studies for list (with pagination + filtering + search)
$all_cases = [];
$total_cases = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_featured = $_GET['filter_featured'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_status === 'active') {
        $where[] = 'is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($filter_featured === '1') {
        $where[] = 'is_featured = 1';
    }
    if ($search_q !== '') {
        $where[] = '(title LIKE ? OR client_name LIKE ? OR location LIKE ? OR industry LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'ssss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM case_studies $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_cases = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_cases / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT * FROM case_studies $where_sql ORDER BY display_order, title LIMIT ? OFFSET ?";
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
            $all_cases[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(is_featured) featured FROM case_studies");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['featured'] = (int)$row['featured'];
}

// All case studies render in the main section on the public page
$view_url = '../case-studies.php#main';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Studies Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Lightweight inline HTML editor (replaces EOL CKEditor 4) -->
    <style>
        .qe-toolbar button { padding: 4px 8px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .qe-toolbar button:hover { background: #f3f4f6; }
        .qe-editor { min-height: 120px; }
        .qe-editor:focus { outline: none; border-color: #2c5530; }
        .qe-editor:empty:before { content: attr(data-placeholder); color: #9ca3af; }
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
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
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-folder-open text-primary"></i> Case Studies Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the success stories shown on the public Case Studies page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Case Study
                </a>
                <?php else: ?>
                <a href="case-studies.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Case Study</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Case studies appear as large cards on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Case Studies page</a>. The <strong>Results</strong> field is special — each line is parsed for a number + description (e.g., <code>30% reduction in operating costs</code>) and shown as a stat card. Lines without a leading number are shown as a checklist.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <?php echo csrfTokenField(); ?>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="title" id="title_input" required
                                           value="<?php echo htmlspecialchars($edit_case['title'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Municipal Water Treatment Plant Upgrade"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">A slug is auto-generated from the title.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Client Name</label>
                                        <input type="text" name="client_name" id="client_input"
                                               value="<?php echo htmlspecialchars($edit_case['client_name'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., City of Tupi Water District"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">The client or organization (optional).</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                        <input type="text" name="location" id="location_input"
                                               value="<?php echo htmlspecialchars($edit_case['location'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., South Cotabato"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Where the project took place (optional).</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                                        <input type="text" name="industry" id="industry_input"
                                               value="<?php echo htmlspecialchars($edit_case['industry'] ?? ''); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                               placeholder="e.g., Municipal Water"
                                               oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">The industry or sector (optional).</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Image <span class="text-gray-400 font-normal">(optional)</span></label>

                                    <!-- Image source mode selector -->
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                        <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter image URL</option>
                                        </select>
                                    </div>

                                    <!-- Image Preview -->
                                    <div id="image-preview-container" class="mb-3 <?php echo empty($edit_case['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($edit_case['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
                                        <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                            <i class="fas fa-times mr-1"></i>Remove Image
                                        </button>
                                    </div>

                                    <!-- File Picker (upload mode) -->
                                    <div id="file-picker-block" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                        <div class="text-center">
                                            <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                            <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                                <i class="fas fa-upload mr-2"></i>Choose Image File
                                            </label>
                                            <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB). Recommended: landscape images, ≥800px wide.</p>
                                        </div>
                                        <div id="upload-progress" class="hidden mt-2">
                                            <div class="bg-gray-200 rounded-full h-2">
                                                <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                            </div>
                                            <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                        </div>
                                    </div>

                                    <!-- URL Input (url mode) -->
                                    <div id="url-input-block" class="hidden mb-3">
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_case['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <!-- Hidden field: the actual value submitted with the form -->
                                    <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_case['image_url'] ?? ''); ?>">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_case['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first. Use 10, 20, 30…</p>
                                    </div>
                                    <div class="flex items-end">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_featured" value="1" id="featured_input"
                                                   <?php echo ($edit_case && $edit_case['is_featured']) ? 'checked' : ''; ?>
                                                   class="sr-only peer"
                                                   onchange="updatePreview()">
                                            <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                         peer-checked:bg-yellow-500 peer-focus-visible:ring-2 peer-focus-visible:ring-yellow-500/40 peer-focus-visible:ring-offset-2
                                                         after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                         after:bg-white after:rounded-full after:shadow
                                                         after:transition-transform after:duration-300 after:ease-in-out
                                                         peer-checked:after:translate-x-5
                                                         hover:after:scale-110 active:after:scale-95"></span>
                                            <span class="ml-3 text-sm text-gray-700">Featured <span class="text-gray-400">(highlighted with star)</span></span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Rich text editor fields (Challenge + Solution) -->
                                <?php
                                $rich_fields = [
                                    ['name' => 'challenge', 'label' => 'Challenge', 'hint' => 'What problem was the client facing? Supports formatting (bold, lists, links).', 'rows' => 5, 'placeholder' => 'The facility was experiencing declining water quality and increasing operational costs due to aging filtration media…'],
                                    ['name' => 'solution', 'label' => 'Solution', 'hint' => 'How did Tupi Supreme solve the problem? Supports formatting.', 'rows' => 5, 'placeholder' => 'We supplied premium granulated activated carbon (GAC) with a 2mm particle size specification, optimized for municipal water treatment…'],
                                ];
                                foreach ($rich_fields as $rf):
                                    $fid = $rf['name'] . '_html';
                                    $tid = $rf['name'];
                                ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo $rf['label']; ?></label>
                                    <div class="qe-toolbar flex flex-wrap gap-1 mb-2 p-2 bg-gray-50 rounded-t-md border border-b-0 border-gray-300" data-target="<?php echo $tid; ?>">
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
                                    <div id="<?php echo $fid; ?>" contenteditable="true" data-placeholder="<?php echo htmlspecialchars($rf['placeholder']); ?>"
                                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary qe-editor bg-white prose prose-sm max-w-none"
                                         oninput="document.getElementById('<?php echo $tid; ?>').value = this.innerHTML; updatePreview();"><?php echo htmlspecialchars_decode($edit_case[$rf['name']] ?? '', ENT_QUOTES); ?></div>
                                    <textarea name="<?php echo $rf['name']; ?>" id="<?php echo $tid; ?>" rows="<?php echo $rf['rows']; ?>" class="hidden"><?php echo htmlspecialchars_decode($edit_case[$rf['name']] ?? '', ENT_QUOTES); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo $rf['hint']; ?></p>
                                </div>
                                <?php endforeach; ?>

                                <!-- Results: Stat Cards + Checklist builder -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Results</label>
                                    <p class="text-xs text-gray-400 mb-3">Add stat cards (big numbers with a label) and checklist items. Stat cards show as highlighted metric boxes; checklist items show with a green checkmark.</p>

                                    <!-- Stat Cards section -->
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-sm font-medium text-gray-600 flex items-center gap-2"><i class="fas fa-chart-bar text-primary"></i> Stat Cards <span class="text-gray-400 font-normal">(max 4)</span></p>
                                            <button type="button" onclick="addStatCard()" class="text-sm text-primary hover:text-secondary inline-flex items-center gap-1"><i class="fas fa-plus"></i> Add Stat Card</button>
                                        </div>
                                        <div id="stat-cards-list" class="space-y-2"></div>
                                        <p id="stat-cards-empty" class="text-xs text-gray-400 italic mt-1">No stat cards yet. Click "Add Stat Card" to add one.</p>
                                    </div>

                                    <!-- Checklist Items section -->
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-sm font-medium text-gray-600 flex items-center gap-2"><i class="fas fa-check-circle text-primary"></i> Checklist Items</p>
                                            <button type="button" onclick="addChecklistItem()" class="text-sm text-primary hover:text-secondary inline-flex items-center gap-1"><i class="fas fa-plus"></i> Add Checklist Item</button>
                                        </div>
                                        <div id="checklist-list" class="space-y-2"></div>
                                        <p id="checklist-empty" class="text-xs text-gray-400 italic mt-1">No checklist items yet. Click "Add Checklist Item" to add one.</p>
                                    </div>

                                    <!-- Hidden field that gets submitted -->
                                    <textarea name="results" id="results" rows="5" class="hidden"><?php echo htmlspecialchars_decode($edit_case['results'] ?? '', ENT_QUOTES); ?></textarea>
                                </div>

                                <div>
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?php echo ($edit_case && $edit_case['is_active']) || !$edit_case ? 'checked' : ''; ?>
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

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Case Study' : 'Save Changes'; ?>
                                </button>
                                <a href="case-studies.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(case study card on the Case Studies page)</span>
                        </p>
                        <div class="case-study-card bg-white border border-[#e6ece8] rounded-2xl overflow-hidden">
                            <div class="p-8">
                                <div class="flex items-center gap-3 mb-5">
                                    <span id="preview_featured" class="eyebrow <?php echo ($edit_case && $edit_case['is_featured']) ? '' : 'hidden'; ?>">
                                        <i class="fas fa-star text-xs"></i> Featured
                                    </span>
                                    <span id="preview_industry" class="text-[#8a978f] text-sm font-medium"><?php echo htmlspecialchars($edit_case['industry'] ?? ''); ?></span>
                                </div>
                                <h3 id="preview_title" class="text-2xl font-bold text-[#23332c] mb-4 leading-tight"><?php echo htmlspecialchars($edit_case['title'] ?? 'Case study title'); ?></h3>
                                <p id="preview_client" class="text-[#8a978f] mb-5 text-sm flex items-center <?php echo empty($edit_case['client_name'] ?? '') ? 'hidden' : ''; ?>">
                                    <i class="fas fa-building text-[#3d7a66] mr-2"></i>
                                    <span id="preview_client_text"><?php echo htmlspecialchars($edit_case['client_name'] ?? ''); ?></span><?php if (!empty($edit_case['location'] ?? '')): ?> — <span id="preview_location"><?php echo htmlspecialchars($edit_case['location']); ?></span><?php endif; ?>
                                </p>

                                <!-- Results stat cards -->
                                <div id="preview_stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 <?php echo empty($edit_case['results'] ?? '') ? 'hidden' : ''; ?>"></div>

                                <!-- Challenge -->
                                <h4 id="preview_challenge_h" class="text-lg font-semibold text-[#23332c] mb-2 flex items-center <?php echo empty($edit_case['challenge'] ?? '') ? 'hidden' : ''; ?>">
                                    <i class="fas fa-exclamation-circle text-[#3d7a66] mr-2"></i> Challenge
                                </h4>
                                <div id="preview_challenge" class="text-[#5a6b62] mb-6 leading-relaxed text-sm prose prose-sm max-w-none <?php echo empty($edit_case['challenge'] ?? '') ? 'hidden' : ''; ?>"><?php echo $edit_case['challenge'] ?? ''; ?></div>

                                <!-- Solution -->
                                <h4 id="preview_solution_h" class="text-lg font-semibold text-[#23332c] mb-2 flex items-center <?php echo empty($edit_case['solution'] ?? '') ? 'hidden' : ''; ?>">
                                    <i class="fas fa-lightbulb text-[#3d7a66] mr-2"></i> Solution
                                </h4>
                                <div id="preview_solution" class="text-[#5a6b62] mb-6 leading-relaxed text-sm prose prose-sm max-w-none <?php echo empty($edit_case['solution'] ?? '') ? 'hidden' : ''; ?>"><?php echo $edit_case['solution'] ?? ''; ?></div>

                                <!-- Results -->
                                <h4 id="preview_results_h" class="text-lg font-semibold text-[#23332c] mb-3 flex items-center <?php echo empty($edit_case['results'] ?? '') ? 'hidden' : ''; ?>">
                                    <i class="fas fa-chart-line text-[#3d7a66] mr-2"></i> Results
                                </h4>
                                <ul id="preview_results" class="text-[#5a6b62] space-y-2 mb-6 <?php echo empty($edit_case['results'] ?? '') ? 'hidden' : ''; ?>"></ul>

                                <!-- Image -->
                                <div id="preview_image_wrap" class="mt-6 <?php echo empty($edit_case['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                    <img id="preview_image" src="<?php echo htmlspecialchars($edit_case['image_url'] ?? ''); ?>" alt="" class="w-full rounded-xl">
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The actual card on the public site may vary slightly. Results lines starting with a number become stat cards; others become checklist items.</p>
                    </div>
                </div>
            </div>

            <script>
            // Lightweight HTML editor toolbars
            (function () {
                document.querySelectorAll('.qe-toolbar[data-target]').forEach(function (toolbar) {
                    var targetId = toolbar.getAttribute('data-target');
                    var editor = document.getElementById(targetId + '_html');
                    if (!editor) return;
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
                        document.getElementById(targetId).value = editor.innerHTML;
                        updatePreview();
                    });
                });
                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    form.addEventListener('submit', function () {
                        ['challenge', 'solution'].forEach(function (id) {
                            var editor = document.getElementById(id + '_html');
                            var ta = document.getElementById(id);
                            if (editor && ta) ta.value = editor.innerHTML;
                        });
                        serializeResults();
                    });
                }
            })();

            // Live preview
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Case study title';
                var client = document.getElementById('client_input').value || '';
                var location = document.getElementById('location_input').value || '';
                var industry = document.getElementById('industry_input').value || '';
                var challengeHtml = document.getElementById('challenge_html').innerHTML || '';
                var solutionHtml = document.getElementById('solution_html').innerHTML || '';
                var featured = document.getElementById('featured_input').checked;
                var imgUrl = document.getElementById('image-url-input').value;

                document.getElementById('preview_title').textContent = title;
                document.getElementById('preview_industry').textContent = industry;
                document.getElementById('preview_featured').classList.toggle('hidden', !featured);

                // Client + location
                var clientEl = document.getElementById('preview_client');
                var clientText = document.getElementById('preview_client_text');
                var locEl = document.getElementById('preview_location');
                if (client.trim()) {
                    clientEl.classList.remove('hidden');
                    clientText.textContent = client;
                    if (location.trim()) {
                        if (!locEl) {
                            clientText.insertAdjacentHTML('afterend', ' — <span id="preview_location">' + escapeHtml(location) + '</span>');
                        } else {
                            locEl.textContent = location;
                        }
                    } else if (locEl) {
                        locEl.previousSibling && locEl.previousSibling.remove ? locEl.previousSibling.remove() : null;
                        locEl.remove();
                    }
                } else {
                    clientEl.classList.add('hidden');
                }

                // Challenge
                var chH = document.getElementById('preview_challenge_h');
                var ch = document.getElementById('preview_challenge');
                if (challengeHtml.trim() && challengeHtml.replace(/<[^>]*>/g,'').trim()) {
                    chH.classList.remove('hidden');
                    ch.classList.remove('hidden');
                    ch.innerHTML = challengeHtml;
                } else {
                    chH.classList.add('hidden');
                    ch.classList.add('hidden');
                }

                // Solution
                var soH = document.getElementById('preview_solution_h');
                var so = document.getElementById('preview_solution');
                if (solutionHtml.trim() && solutionHtml.replace(/<[^>]*>/g,'').trim()) {
                    soH.classList.remove('hidden');
                    so.classList.remove('hidden');
                    so.innerHTML = solutionHtml;
                } else {
                    soH.classList.add('hidden');
                    so.classList.add('hidden');
                }

                // Results: read from stat card + checklist builder
                serializeResults(); // make sure hidden textarea is current
                var statsBox = document.getElementById('preview_stats');
                var resH = document.getElementById('preview_results_h');
                var resList = document.getElementById('preview_results');

                // Gather stat cards from the builder
                var statCards = [];
                document.querySelectorAll('#stat-cards-list .stat-card-row').forEach(function(row) {
                    var val = row.querySelector('.stat-value').value.trim();
                    var label = row.querySelector('.stat-label').value.trim();
                    if (val && label) statCards.push({ value: val, label: label });
                });

                // Gather checklist items from the builder
                var checklistItems = [];
                document.querySelectorAll('#checklist-list .checklist-row').forEach(function(row) {
                    var text = row.querySelector('.checklist-text').value.trim();
                    if (text) checklistItems.push(text);
                });

                var hasResults = statCards.length > 0 || checklistItems.length > 0;

                if (hasResults) {
                    resH.classList.remove('hidden');
                    resList.classList.remove('hidden');

                    // Stat cards
                    if (statCards.length > 0) {
                        statsBox.classList.remove('hidden');
                        statsBox.innerHTML = statCards.map(function(s) {
                            return '<div class="result-stat bg-[#f5f7f5] border border-[#e6ece8] p-4 rounded-2xl text-center"><div class="text-3xl font-bold text-[#23332c] mb-1">' + escapeHtml(s.value) + '</div><div class="text-xs text-[#7d8b84]">' + escapeHtml(s.label) + '</div></div>';
                        }).join('');
                    } else {
                        statsBox.classList.add('hidden');
                    }

                    // Checklist: stat card lines + checklist items (matches public page behavior)
                    var allLines = statCards.map(function(s) { return s.value + ' ' + s.label; }).concat(checklistItems);
                    resList.innerHTML = allLines.map(function(l) {
                        return '<li class="flex items-start text-[#5a6b62] text-sm"><i class="fas fa-check text-[#3d7a66] mr-3 mt-1"></i>' + escapeHtml(l) + '</li>';
                    }).join('');
                } else {
                    statsBox.classList.add('hidden');
                    resH.classList.add('hidden');
                    resList.classList.add('hidden');
                }

                // Image
                var imgWrap = document.getElementById('preview_image_wrap');
                var img = document.getElementById('preview_image');
                if (imgUrl.trim()) {
                    img.src = imgUrl;
                    imgWrap.classList.remove('hidden');
                } else {
                    imgWrap.classList.add('hidden');
                }
            }

            function escapeHtml(s) {
                return s.replace(/[&<>"']/g, function(c) {
                    return {'&':'&','<':'<','>':'>','"':'"',"'":'&#39;'}[c];
                });
            }

            // ===== Results: Stat Card + Checklist Builder =====

            // Serialize the builder UI into the hidden results textarea
            // Format: stat cards as "{value} {label}" per line, then checklist items per line
            function serializeResults() {
                var lines = [];
                // Stat cards first
                document.querySelectorAll('#stat-cards-list .stat-card-row').forEach(function(row) {
                    var val = row.querySelector('.stat-value').value.trim();
                    var label = row.querySelector('.stat-label').value.trim();
                    if (val && label) {
                        lines.push(val + ' ' + label);
                    }
                });
                // Then checklist items
                document.querySelectorAll('#checklist-list .checklist-row').forEach(function(row) {
                    var text = row.querySelector('.checklist-text').value.trim();
                    if (text) {
                        lines.push(text);
                    }
                });
                document.getElementById('results').value = lines.join('\n');
            }

            // Parse existing results text into the builder UI (for edit mode)
            function parseResultsIntoBuilder() {
                var raw = document.getElementById('results').value || '';
                // Strip HTML tags, split on newlines/br
                var lines = raw.split(/\n|<br\s*\/?>|<\/li>|<\/p>/i)
                    .map(function(s) { return s.replace(/<[^>]*>/g, '').trim(); })
                    .filter(function(s) { return s.length > 0; });

                var statRegex = /^(\d+[%$]?[\.\d]*[M]?)\s+(.+)/i;
                lines.forEach(function(line) {
                    var m = line.match(statRegex);
                    if (m) {
                        addStatCard(m[1], m[2]);
                    } else {
                        addChecklistItem(line);
                    }
                });
                updateResultsEmptyStates();
                updatePreview();
            }

            function addStatCard(value, label) {
                var list = document.getElementById('stat-cards-list');
                var count = list.querySelectorAll('.stat-card-row').length;
                if (count >= 4) { alert('Maximum 4 stat cards.'); return; }
                var row = document.createElement('div');
                row.className = 'stat-card-row flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg p-2';
                row.innerHTML =
                    '<input type="text" class="stat-value w-24 px-2 py-1.5 border border-gray-300 rounded-md text-sm font-mono text-center" placeholder="30%" value="' + escapeAttr(value || '') + '" oninput="serializeResults(); updatePreview(); updateResultsEmptyStates();">' +
                    '<input type="text" class="stat-label flex-1 px-2 py-1.5 border border-gray-300 rounded-md text-sm" placeholder="improvement in filtration efficiency" value="' + escapeAttr(label || '') + '" oninput="serializeResults(); updatePreview(); updateResultsEmptyStates();">' +
                    '<button type="button" onclick="this.closest(\'.stat-card-row\').remove(); serializeResults(); updatePreview(); updateResultsEmptyStates();" class="text-red-500 hover:text-red-700 px-2"><i class="fas fa-trash-alt"></i></button>';
                list.appendChild(row);
                updateResultsEmptyStates();
            }

            function addChecklistItem(text) {
                var list = document.getElementById('checklist-list');
                var row = document.createElement('div');
                row.className = 'checklist-row flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg p-2';
                row.innerHTML =
                    '<i class="fas fa-check text-[#3d7a66] ml-1"></i>' +
                    '<input type="text" class="checklist-text flex-1 px-2 py-1.5 border border-gray-300 rounded-md text-sm" placeholder="Met all compliance standards" value="' + escapeAttr(text || '') + '" oninput="serializeResults(); updatePreview(); updateResultsEmptyStates();">' +
                    '<button type="button" onclick="this.closest(\'.checklist-row\').remove(); serializeResults(); updatePreview(); updateResultsEmptyStates();" class="text-red-500 hover:text-red-700 px-2"><i class="fas fa-trash-alt"></i></button>';
                list.appendChild(row);
                updateResultsEmptyStates();
            }

            function updateResultsEmptyStates() {
                var statCount = document.querySelectorAll('#stat-cards-list .stat-card-row').length;
                var checkCount = document.querySelectorAll('#checklist-list .checklist-row').length;
                document.getElementById('stat-cards-empty').classList.toggle('hidden', statCount > 0);
                document.getElementById('checklist-empty').classList.toggle('hidden', checkCount > 0);
            }

            function escapeAttr(s) {
                return s.replace(/&/g,'&').replace(/"/g,'"').replace(/</g,'<').replace(/>/g,'>');
            }

            // Initialize the builder from existing data on page load
            (function() {
                parseResultsIntoBuilder();
            })();
            </script>

            <script>
            // Image upload logic
            let selectedFile = null;
            let uploadState = 'idle';
            let imageMode = 'upload';

            function switchImageMode() {
                const mode = document.getElementById('image-source-mode').value;
                imageMode = mode;
                const fileBlock = document.getElementById('file-picker-block');
                const urlBlock = document.getElementById('url-input-block');
                if (mode === 'url') {
                    fileBlock.classList.add('hidden');
                    urlBlock.classList.remove('hidden');
                    document.getElementById('image-url-visible').value = document.getElementById('image-url-input').value;
                } else {
                    urlBlock.classList.add('hidden');
                    fileBlock.classList.remove('hidden');
                    if (uploadState === 'failed') {
                        uploadState = 'idle';
                        document.getElementById('upload-progress').classList.add('hidden');
                    }
                }
            }

            function syncUrlInput(value) {
                document.getElementById('image-url-input').value = value;
                const preview = document.getElementById('image-preview');
                if (value.trim()) {
                    preview.src = value;
                    preview.onerror = function() { this.style.display = 'none'; };
                    preview.onload = function() {
                        this.style.display = 'block';
                        document.getElementById('image-preview-container').classList.remove('hidden');
                    };
                } else {
                    document.getElementById('image-preview-container').classList.add('hidden');
                }
                updatePreview();
            }

            document.getElementById('image-file-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    selectedFile = file;
                    uploadState = 'idle';
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('image-preview');
                        preview.src = e.target.result;
                        document.getElementById('image-preview-container').classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                    uploadImage();
                }
            });

            function uploadImage() {
                if (!selectedFile) { alert('Please select an image file first'); return; }
                const formData = new FormData();
                formData.append('image', selectedFile);
                const progressContainer = document.getElementById('upload-progress');
                const progressBar = document.getElementById('upload-progress-bar');
                const statusText = document.getElementById('upload-status');
                const fileLabel = document.querySelector('label[for="image-file-input"]');
                uploadState = 'uploading';
                progressContainer.classList.remove('hidden');
                statusText.textContent = 'Uploading...';
                statusText.classList.remove('text-green-600', 'text-red-600');
                progressBar.classList.remove('bg-green-500');
                progressBar.style.width = '0%';
                fileLabel.style.pointerEvents = 'none';
                fileLabel.style.opacity = '0.6';
                const xhr = new XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) { progressBar.style.width = ((e.loaded / e.total) * 100) + '%'; }
                });
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            document.getElementById('image-url-input').value = response.url;
                            statusText.textContent = 'Upload successful!';
                            statusText.classList.add('text-green-600');
                            progressBar.classList.add('bg-green-500');
                            uploadState = 'success';
                            setTimeout(() => { progressContainer.classList.add('hidden'); }, 2000);
                            updatePreview();
                        } else {
                            statusText.textContent = 'Upload failed: ' + response.error;
                            statusText.classList.add('text-red-600');
                            uploadState = 'failed';
                        }
                    } else {
                        let errMsg = 'Server error';
                        try { errMsg = JSON.parse(xhr.responseText).error || errMsg; } catch (_) {}
                        statusText.textContent = 'Upload failed: ' + errMsg;
                        statusText.classList.add('text-red-600');
                        uploadState = 'failed';
                    }
                    fileLabel.style.pointerEvents = '';
                    fileLabel.style.opacity = '';
                });
                xhr.addEventListener('error', function() {
                    statusText.textContent = 'Upload failed: Network error';
                    statusText.classList.add('text-red-600');
                    uploadState = 'failed';
                    fileLabel.style.pointerEvents = '';
                    fileLabel.style.opacity = '';
                });
                xhr.open('POST', 'api/upload_image.php');
                xhr.send(formData);
            }

            function clearImagePreview() {
                document.getElementById('image-preview-container').classList.add('hidden');
                document.getElementById('image-url-input').value = '';
                document.getElementById('image-url-visible').value = '';
                document.getElementById('image-file-input').value = '';
                selectedFile = null;
                uploadState = 'idle';
                document.getElementById('upload-progress').classList.add('hidden');
                updatePreview();
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
                        <span class="px-3 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-star mr-1"></i><?php echo $stats['featured']; ?> featured</span>
                    </div>
                    <form method="GET" action="case-studies.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <select name="filter_featured" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All case studies</option>
                                <option value="1" <?php echo (($_GET['filter_featured'] ?? '') === '1') ? 'selected' : ''; ?>>Featured only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title, client, location, or industry…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_featured']) || !empty($_GET['q'])): ?>
                        <a href="case-studies.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_cases)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No case studies found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new case study</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_cases as $case): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($case['title']); ?></div>
                                        <?php if ($case['is_featured']): ?>
                                            <span class="text-xs text-yellow-600"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($case['client_name'] ?: '—'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($case['location'] ?: '—'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$case['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($case['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $case['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="case-studies.php" class="inline" onsubmit="return confirm('Delete this case study? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $case['id']; ?>">
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
                    'total_items'   => $total_cases,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Case Studies Page</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
