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
if ($status_param === 'saved')   $success = 'Content saved successfully!';
if ($status_param === 'deleted') $success = 'Content deleted successfully!';
if ($status_param === 'seeded')  $success = 'Default sections seeded successfully!';

// Available about page sections (key => [label, anchor on public page])
$about_sections = [
    'page_header_title'       => ['Page Header Title',       'page-header'],
    'page_header_subtitle'    => ['Page Header Subtitle',    'page-header'],
    'company_story_title'     => ['Company Story Title',      'company-story'],
    'company_story_content'   => ['Company Story Content',   'company-story'],
    'company_story_icon'      => ['Company Story Icon',       'company-story'],
    'mission_title'           => ['Mission Title',            'mission-vision'],
    'mission_content'         => ['Mission Content',          'mission-vision'],
    'mission_icon'            => ['Mission Icon',             'mission-vision'],
    'vision_title'            => ['Vision Title',             'mission-vision'],
    'vision_content'          => ['Vision Content',           'mission-vision'],
    'vision_icon'             => ['Vision Icon',              'mission-vision'],
    'timeline_title'          => ['Timeline Title',           'timeline'],
    'timeline_subtitle'       => ['Timeline Subtitle',        'timeline'],
    'values_title'            => ['Values Title',             'values'],
    'values_subtitle'         => ['Values Subtitle',          'values'],
    'team_title'              => ['Team Title',               'team'],
    'team_subtitle'           => ['Team Subtitle',            'team'],
];

// Section type metadata: type => [fields to show, description, hint]
// Types: 'title' (short text), 'subtitle' (short text), 'content' (rich HTML), 'icon' (FA class)
$section_types = [
    'page_header_title'       => ['type' => 'title',   'desc' => 'The large heading at the very top of the About page.'],
    'page_header_subtitle'    => ['type' => 'subtitle', 'desc' => 'A short tagline below the page header title.'],
    'company_story_title'     => ['type' => 'title',   'desc' => 'Heading for the "Our Story" section.'],
    'company_story_content'   => ['type' => 'content', 'desc' => 'The main paragraph(s) telling the company\'s story. Supports rich formatting.'],
    'company_story_icon'      => ['type' => 'icon',    'desc' => 'A Font Awesome icon class shown next to the story section.'],
    'mission_title'           => ['type' => 'title',   'desc' => 'Heading for the Mission card.'],
    'mission_content'         => ['type' => 'content', 'desc' => 'The mission statement text. Supports rich formatting.'],
    'mission_icon'            => ['type' => 'icon',    'desc' => 'A Font Awesome icon class for the Mission card.'],
    'vision_title'            => ['type' => 'title',   'desc' => 'Heading for the Vision card.'],
    'vision_content'          => ['type' => 'content', 'desc' => 'The vision statement text. Supports rich formatting.'],
    'vision_icon'             => ['type' => 'icon',    'desc' => 'A Font Awesome icon class for the Vision card.'],
    'timeline_title'          => ['type' => 'title',   'desc' => 'Heading above the company timeline.'],
    'timeline_subtitle'       => ['type' => 'subtitle', 'desc' => 'A short description below the timeline heading.'],
    'values_title'            => ['type' => 'title',   'desc' => 'Heading for the Core Values section.'],
    'values_subtitle'         => ['type' => 'subtitle', 'desc' => 'A short description below the values heading.'],
    'team_title'              => ['type' => 'title',   'desc' => 'Heading for the Team section.'],
    'team_subtitle'           => ['type' => 'subtitle', 'desc' => 'A short description below the team heading.'],
];

// Group sections by area for the list-view filter dropdown
$section_groups = [
    'Page Header'    => ['page_header_title', 'page_header_subtitle'],
    'Company Story'  => ['company_story_title', 'company_story_content', 'company_story_icon'],
    'Mission/Vision' => ['mission_title', 'mission_content', 'mission_icon', 'vision_title', 'vision_content', 'vision_icon'],
    'Timeline'       => ['timeline_title', 'timeline_subtitle'],
    'Values'         => ['values_title', 'values_subtitle'],
    'Team'           => ['team_title', 'team_subtitle'],
];

// Handle form submissions (POST) — add/edit (excludes delete and seed actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '' && ($_POST['seed_defaults'] ?? '') === '') {
    // CSRF check
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
    $section_name = sanitizeInput($_POST['section_name'] ?? '');
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($section_name)) {
        $error = 'Section name is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO about_content (section_name, title, content, image_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $section_name, $title, $content, $image_url, $display_order, $is_active, $_SESSION['admin_id']);

            try {
                if ($stmt->execute()) {
                    logActivity('create', 'about_content', $db->insert_id, "Created about content for {$section_name}");
                    redirect('about.php?status=saved');
                } else {
                    $error = 'Error adding content: ' . $stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $error = "A section named '" . htmlspecialchars($section_name) . "' already exists. Each section name must be unique — please edit the existing one instead of adding a duplicate.";
                } else {
                    $error = 'Error adding content: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE about_content SET section_name = ?, title = ?, content = ?, image_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $section_name, $title, $content, $image_url, $display_order, $is_active, $_SESSION['admin_id'], $id);

            try {
                if ($stmt->execute()) {
                    logActivity('update', 'about_content', $id, "Updated about content for {$section_name}");
                    redirect('about.php?status=saved');
                } else {
                    $error = 'Error updating content: ' . $stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $error = "A section named '" . htmlspecialchars($section_name) . "' already exists. Each section name must be unique — please choose a different section name.";
                } else {
                    $error = 'Error updating content: ' . $e->getMessage();
                }
            }
        }
    }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    // CSRF check
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM about_content WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'about_content', $del_id, 'Deleted about content');
        redirect('about.php?status=deleted');
    } else {
        $error = 'Error deleting content: ' . $stmt->error;
    }
    }
}

// Handle seed default sections (POST only, CSRF-protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['seed_defaults'] ?? '') !== '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        // Insert every predefined section key that doesn't already exist (UNIQUE on section_name).
        $existing = [];
        $res = $db->query("SELECT section_name FROM about_content");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[$row['section_name']] = true;
            }
        }
        $inserted = 0;
        $order = 1;
        $stmt = $db->prepare("INSERT INTO about_content (section_name, title, content, image_url, display_order, is_active, updated_by) VALUES (?, NULL, NULL, NULL, ?, 1, ?)");
        foreach ($about_sections as $key => $meta) {
            if (!isset($existing[$key])) {
                $stmt->bind_param("sii", $key, $order, $_SESSION['admin_id']);
                $stmt->execute();
                $inserted++;
            }
            $order++;
        }
        if ($inserted > 0) {
            logActivity('create', 'about_content', null, "Seeded {$inserted} default about content sections");
        }
        redirect('about.php?status=seeded');
    }
}

// Get content for edit
$edit_content = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM about_content WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_content = $result->fetch_assoc();

    if (!$edit_content) {
        $error = 'Content not found.';
        $action = 'list';
    }
}

// Get all content for list (with pagination + filtering + search)
$all_content = [];
$total_content = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 5;

// Section names already in use (used to disable duplicates in the Add dropdown)
$used_section_names = [];
$used_res = $db->query("SELECT section_name FROM about_content");
if ($used_res) {
    while ($row = $used_res->fetch_assoc()) {
        $used_section_names[$row['section_name']] = true;
    }
}

if ($action === 'list') {
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_group = $_GET['filter_group'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_group !== '' && isset($section_groups[$filter_group])) {
        $placeholders = implode(',', array_fill(0, count($section_groups[$filter_group]), '?'));
        $where[] = "ac.section_name IN ($placeholders)";
        foreach ($section_groups[$filter_group] as $sn) {
            $params[] = $sn;
            $types .= 's';
        }
    }
    if ($search_q !== '') {
        $where[] = '(ac.section_name LIKE ? OR ac.title LIKE ? OR ac.content LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM about_content ac $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_content = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_content / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT ac.*, au.username as updated_by_name FROM about_content ac LEFT JOIN admin_users au ON ac.updated_by = au.id $where_sql ORDER BY ac.display_order, ac.section_name LIMIT ? OFFSET ?";
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
            $all_content[] = $row;
        }
    }
}

// Quick stats for the list header (only needed in list view)
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
if ($action === 'list') {
    $st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM about_content");
    if ($st) {
        $row = $st->fetch_assoc();
        $stats['total'] = (int)$row['total'];
        $stats['active'] = (int)$row['active'];
        $stats['inactive'] = $stats['total'] - $stats['active'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Page Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Lightweight inline HTML editor (replaces EOL CKEditor 4) -->
    <style>
        .qe-toolbar button { padding: 4px 8px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .qe-toolbar button:hover { background: #f3f4f6; }
        .qe-editor { min-height: 160px; }
        .qe-editor:focus { outline: none; border-color: #2c5530; }
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
                        <i class="fas fa-info-circle text-primary"></i> About Page Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the content sections shown on the public About Us page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Content
                </a>
                <?php else: ?>
                <a href="about.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> About Page Content</h2>
                <p class="text-sm text-gray-500 mb-6">Fields marked <span class="text-red-500">*</span> are required.</p>

                <form method="POST" action="">
                    <?php echo csrfTokenField(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Name <span class="text-red-500">*</span></label>
                            <?php if ($action === 'edit' && $edit_content): ?>
                                <!-- On edit, the section name is the row's identity — lock it to prevent UNIQUE collisions -->
                                <input type="hidden" name="section_name" value="<?php echo htmlspecialchars($edit_content['section_name']); ?>">
                                <input type="text" value="<?php echo htmlspecialchars($about_sections[$edit_content['section_name']][0] ?? $edit_content['section_name']); ?>"
                                       disabled class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed">
                                <p class="text-xs text-gray-400 mt-1">Section name cannot be changed after creation.</p>
                            <?php else: ?>
                                <select name="section_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                    <option value="">Select a section…</option>
                                    <?php foreach ($section_groups as $group => $keys): ?>
                                        <optgroup label="<?php echo htmlspecialchars($group); ?>">
                                            <?php foreach ($keys as $key): ?>
                                                <?php $is_used = isset($used_section_names[$key]); ?>
                                                <option value="<?php echo $key; ?>" <?php echo $is_used ? 'disabled' : ''; ?>>
                                                    <?php echo htmlspecialchars($about_sections[$key][0]); ?><?php echo $is_used ? ' (already exists)' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-400 mt-1">Choose which About page section this content belongs to. Greyed-out options already exist — edit them instead.</p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" min="0"
                                   value="<?php echo htmlspecialchars($edit_content['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-400 mt-1">Lower numbers appear first (0 = default).</p>
                        </div>
                    </div>

                    <!-- Section description banner (context-aware, updated by JS) -->
                    <div id="section_desc" class="mt-4 mb-2 p-3 rounded-lg bg-blue-50 border border-blue-200 hidden">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div>
                                <p id="section_desc_text" class="text-sm text-blue-800"></p>
                                <p id="section_desc_hint" class="text-xs text-blue-600 mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Title field (shown for title-type sections) -->
                    <div class="mt-6" id="title_field_wrap">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-gray-400 font-normal">(the heading text)</span></label>
                        <input type="text" name="title"
                               value="<?php echo htmlspecialchars($edit_content['title'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                               placeholder="Enter the heading text…">
                        <p class="text-xs text-gray-400 mt-1">This text appears as the heading on the public About page.</p>
                    </div>

                    <!-- Content field: rich HTML editor (for content-type sections) -->
                    <div class="mt-6" id="content_rich_wrap">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <div id="html_toolbar" class="qe-toolbar flex flex-wrap gap-1 mb-2 p-2 bg-gray-50 rounded-t-md border border-b-0 border-gray-300">
                            <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                            <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                            <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                            <button type="button" data-cmd="insertUnorderedList" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                            <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                            <button type="button" data-cmd="formatBlock" data-val="h3" title="Heading">H3</button>
                            <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>
                            <button type="button" data-cmd="createLink" title="Link"><i class="fas fa-link"></i></button>
                            <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="fas fa-eraser"></i></button>
                        </div>
                        <div id="content_html" contenteditable="false"
                             class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary qe-editor bg-white prose max-w-none"></div>
                        <textarea name="content" id="content" rows="10"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">Write the paragraph(s) for this section. Use the toolbar for formatting.</p>
                    </div>

                    <!-- Content field: plain text (for subtitle-type sections) -->
                    <div class="mt-6 hidden" id="content_plain_wrap">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle Text</label>
                        <textarea name="content_plain" id="content_plain" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                  placeholder="Enter the subtitle text…"
                                  oninput="document.getElementById('content').value = this.value;"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">A short line of text shown below the section heading.</p>
                    </div>

                    <!-- Content field: icon picker (for icon-type sections) -->
                    <div class="mt-6 hidden" id="content_icon_wrap">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Awesome Icon Class</label>
                        <div class="flex items-center gap-3">
                            <input type="text" id="content_icon" 
                                   value="<?php echo htmlspecialchars($edit_content['content'] ?? ''); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary font-mono"
                                   placeholder="fas fa-industry"
                                   oninput="updateIconPreview(this.value); document.getElementById('content').value = this.value;">
                            <div id="icon_preview" class="flex items-center justify-center w-14 h-14 border border-gray-300 rounded-lg bg-gray-50 text-2xl text-primary">
                                <i class="fas fa-industry"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">Enter a Font Awesome icon class. <a href="https://fontawesome.com/v6/search?o=r&m=free" target="_blank" class="text-primary hover:underline">Browse icons</a>. Common choices: <code class="bg-gray-100 px-1 rounded cursor-pointer" onclick="document.getElementById('content_icon').value='fas fa-industry';updateIconPreview('fas fa-industry')">fas fa-industry</code>, <code class="bg-gray-100 px-1 rounded cursor-pointer" onclick="document.getElementById('content_icon').value='fas fa-bullseye';updateIconPreview('fas fa-bullseye')">fas fa-bullseye</code>, <code class="bg-gray-100 px-1 rounded cursor-pointer" onclick="document.getElementById('content_icon').value='fas fa-eye';updateIconPreview('fas fa-eye')">fas fa-eye</code>, <code class="bg-gray-100 px-1 rounded cursor-pointer" onclick="document.getElementById('content_icon').value='fas fa-leaf';updateIconPreview('fas fa-leaf')">fas fa-leaf</code>, <code class="bg-gray-100 px-1 rounded cursor-pointer" onclick="document.getElementById('content_icon').value='fas fa-globe';updateIconPreview('fas fa-globe')">fas fa-globe</code>.</p>
                    </div>

                    <!-- Image field (only shown for content-type sections that may use images) -->
                    <div class="mt-6 hidden" id="image_field_wrap">
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
                        <div id="image-preview-container" class="mb-3 <?php echo empty($edit_content['image_url'] ?? '') ? 'hidden' : ''; ?>">
                            <img id="image-preview" src="<?php echo htmlspecialchars($edit_content['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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
                                <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — file uploads automatically when selected</p>
                            </div>
                            <div id="upload-progress" class="hidden mt-2">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                </div>
                                <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                            </div>
                        </div>

                        <!-- URL Input (url mode — visible text field for manual entry) -->
                        <div id="url-input-block" class="hidden mb-3">
                            <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_content['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                            <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                        </div>

                        <!-- Hidden field: the actual value submitted with the form -->
                        <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_content['image_url'] ?? ''); ?>">
                    </div>

                    <div class="mt-6">
                        <label class="flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo ($edit_content && $edit_content['is_active']) || !$edit_content ? 'checked' : ''; ?>
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

                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Content' : 'Save Changes'; ?>
                        </button>
                        <a href="about.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>

            <script>
            // Section type metadata for context-aware form
            var sectionTypes = <?php echo json_encode($section_types); ?>;
            var editSection = <?php echo json_encode($edit_content['section_name'] ?? ''); ?>;

            // Show/hide fields based on section type
            function updateFormForSection(sectionKey) {
                var meta = sectionTypes[sectionKey];
                var descWrap = document.getElementById('section_desc');
                var descText = document.getElementById('section_desc_text');
                var descHint = document.getElementById('section_desc_hint');
                var titleWrap = document.getElementById('title_field_wrap');
                var richWrap = document.getElementById('content_rich_wrap');
                var plainWrap = document.getElementById('content_plain_wrap');
                var iconWrap = document.getElementById('content_icon_wrap');
                var imageWrap = document.getElementById('image_field_wrap');

                if (!meta) {
                    descWrap.classList.add('hidden');
                    titleWrap.classList.remove('hidden');
                    richWrap.classList.remove('hidden');
                    plainWrap.classList.add('hidden');
                    iconWrap.classList.add('hidden');
                    imageWrap.classList.remove('hidden');
                    return;
                }

                // Show description banner
                descWrap.classList.remove('hidden');
                descText.textContent = meta.desc;

                var type = meta.type;
                if (type === 'title') {
                    descHint.textContent = 'Use the Title field below. Content and Image are not needed.';
                    titleWrap.classList.remove('hidden');
                    richWrap.classList.add('hidden');
                    plainWrap.classList.add('hidden');
                    iconWrap.classList.add('hidden');
                    imageWrap.classList.add('hidden');
                } else if (type === 'subtitle') {
                    descHint.textContent = 'Use the Subtitle Text field below. Title and Image are not needed.';
                    titleWrap.classList.add('hidden');
                    richWrap.classList.add('hidden');
                    plainWrap.classList.remove('hidden');
                    iconWrap.classList.add('hidden');
                    imageWrap.classList.add('hidden');
                    // Sync plain -> hidden content textarea
                    var plain = document.getElementById('content_plain');
                    document.getElementById('content').value = plain.value;
                } else if (type === 'content') {
                    descHint.textContent = 'Use the rich text editor below. Supports formatting (bold, lists, links, etc.).';
                    titleWrap.classList.add('hidden');
                    richWrap.classList.remove('hidden');
                    plainWrap.classList.add('hidden');
                    iconWrap.classList.add('hidden');
                    imageWrap.classList.remove('hidden');
                } else if (type === 'icon') {
                    descHint.textContent = 'Enter a Font Awesome icon class. A live preview is shown next to the input.';
                    titleWrap.classList.add('hidden');
                    richWrap.classList.add('hidden');
                    plainWrap.classList.add('hidden');
                    iconWrap.classList.remove('hidden');
                    imageWrap.classList.add('hidden');
                    // Sync icon -> hidden content textarea
                    var iconInput = document.getElementById('content_icon');
                    document.getElementById('content').value = iconInput.value;
                    updateIconPreview(iconInput.value);
                }
            }

            // Live icon preview
            function updateIconPreview(iconClass) {
                var preview = document.getElementById('icon_preview');
                preview.innerHTML = '<i class="' + iconClass + '"></i>';
            }

            // Lightweight HTML editor
            (function () {
                var textarea = document.getElementById('content');
                var htmlBox = document.getElementById('content_html');
                var toolbar = document.getElementById('html_toolbar');

                function applyEditor() {
                    var richWrap = document.getElementById('content_rich_wrap');
                    if (richWrap.classList.contains('hidden')) {
                        textarea.classList.remove('hidden');
                        htmlBox.classList.add('hidden');
                        toolbar.classList.add('hidden');
                        htmlBox.setAttribute('contenteditable', 'false');
                        return;
                    }
                    textarea.classList.add('hidden');
                    htmlBox.classList.remove('hidden');
                    toolbar.classList.remove('hidden');
                    htmlBox.setAttribute('contenteditable', 'true');
                    htmlBox.innerHTML = textarea.value;
                }

                if (toolbar) {
                    toolbar.addEventListener('mousedown', function (e) {
                        var btn = e.target.closest('button[data-cmd]');
                        if (!btn) return;
                        e.preventDefault();
                        htmlBox.focus();
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
                    });
                }

                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (!document.getElementById('content_rich_wrap').classList.contains('hidden')) {
                            textarea.value = htmlBox.innerHTML;
                        }
                    });
                }

                // Initialize editor after section type is applied
                setTimeout(applyEditor, 50);
            })();

            // Initialize form for the current section (edit mode or add mode)
            (function () {
                var sectionSelect = document.querySelector('select[name="section_name"]');
                if (sectionSelect) {
                    sectionSelect.addEventListener('change', function () { updateFormForSection(sectionSelect.value); });
                }
                // On edit, the section is locked — use the hidden value
                var initialSection = editSection || (sectionSelect ? sectionSelect.value : '');
                if (initialSection) {
                    updateFormForSection(initialSection);
                }
            })();
            </script>

            <script>
            // Image upload logic (unchanged — only visible for content-type sections)
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
                        <?php if ($stats['total'] < count($about_sections)): ?>
                        <form method="POST" action="about.php" class="inline" onsubmit="return confirm('Create any missing default About page sections as empty rows? Existing sections are left untouched.');">
                            <?php echo csrfTokenField(); ?>
                            <input type="hidden" name="seed_defaults" value="1">
                            <button type="submit" class="px-3 py-1 text-xs rounded-full bg-secondary text-white hover:bg-primary transition-colors" title="Add any missing default sections as empty rows">
                                <i class="fas fa-magic mr-1"></i>Seed missing sections
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <form method="GET" action="about.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_group" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All sections</option>
                                <?php foreach ($section_groups as $group => $keys): ?>
                                    <option value="<?php echo htmlspecialchars($group); ?>" <?php echo (($_GET['filter_group'] ?? '') === $group) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($group); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search section, title, or content…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_group']) || !empty($_GET['q'])): ?>
                        <a href="about.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_content)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No content found.</p>
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new content</a>
                                        <span class="text-gray-300">|</span>
                                        <form method="POST" action="about.php" class="inline" onsubmit="return confirm('Create all default About page sections as empty rows? You can edit them afterwards.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="seed_defaults" value="1">
                                            <button type="submit" class="text-secondary hover:underline text-sm"><i class="fas fa-magic mr-1"></i>Seed all default sections</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_content as $content):
                                $section_label = $about_sections[$content['section_name']][0] ?? $content['section_name'];
                                $anchor        = $about_sections[$content['section_name']][1] ?? null;
                                $view_url      = $anchor ? '../about.php#' . $anchor : '../about.php';
                                $preview       = mb_substr(strip_tags($content['content'] ?? ''), 0, 60);
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $content['id']; ?>" class="text-sm font-medium text-gray-900 hover:text-primary hover:underline"><?php echo htmlspecialchars($section_label); ?></a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($content['title'] ?: '—'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($preview); ?><?php echo mb_strlen(strip_tags($content['content'] ?? '')) > 60 ? '…' : ''; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$content['display_order']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($content['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $content['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="about.php" class="inline" onsubmit="return confirm('Delete this content? This cannot be undone.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $content['id']; ?>">
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
                    'total_items'   => $total_content,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="../about.php" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View About Page</a></li>
                    <li><a href="pages.php" class="hover:underline"><i class="fas fa-file-alt mr-1"></i>Manage General Page Content</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
