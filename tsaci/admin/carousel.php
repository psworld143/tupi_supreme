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
if ($status_param === 'saved')   $success = 'Carousel slide saved successfully!';
if ($status_param === 'deleted') $success = 'Carousel slide deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $overlay_opacity = isset($_POST['overlay_opacity']) ? max(0, min(100, intval($_POST['overlay_opacity']))) : 92;
    $button_text = sanitizeInput($_POST['button_text'] ?? '');
    $button_link = sanitizeInput($_POST['button_link'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($image_url)) {
        $error = 'Image is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO carousel_slides (title, description, image_url, overlay_opacity, button_text, button_link, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssissiii", $title, $description, $image_url, $overlay_opacity, $button_text, $button_link, $display_order, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'carousel_slides', $db->insert_id, "Created carousel slide: {$title}");
                redirect('carousel.php?status=saved');
            } else {
                $error = 'Error adding carousel slide: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE carousel_slides SET title = ?, description = ?, image_url = ?, overlay_opacity = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssissiiii", $title, $description, $image_url, $overlay_opacity, $button_text, $button_link, $display_order, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'carousel_slides', $id, "Updated carousel slide: {$title}");
                redirect('carousel.php?status=saved');
            } else {
                $error = 'Error updating carousel slide: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM carousel_slides WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'carousel_slides', $del_id, 'Deleted carousel slide');
        redirect('carousel.php?status=deleted');
    } else {
        $error = 'Error deleting carousel slide: ' . $stmt->error;
    }
}

// Get slide for editing
$slide = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM carousel_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $slide = $result->fetch_assoc();

    if (!$slide) {
        $error = 'Carousel slide not found.';
        $action = 'list';
    }
}

// Get slides for listing (with pagination + filtering + search)
$slides = [];
$total_slides = 0;
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
        $where[] = 'is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(title LIKE ? OR description LIKE ? OR button_text LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM carousel_slides $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_slides = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_slides / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT * FROM carousel_slides $where_sql ORDER BY display_order, id LIMIT ? OFFSET ?";
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
            $slides[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM carousel_slides");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// All carousel slides render in the hero section on the homepage
$view_url = '../index.php#hero';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Management - <?php echo SITE_NAME; ?></title>
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
        /* Quick-pick suggestion buttons */
        .quick-pick { padding: 3px 8px; font-size: 11px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; cursor: pointer; color: #4b5563; }
        .quick-pick:hover { background: #f3f4f6; border-color: #9ca3af; }
        /* Hero preview */
        .hero-preview { background-size: cover; background-position: center; }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-images text-primary"></i> Carousel Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the rotating hero slides shown at the top of the homepage.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Slide
                </a>
                <?php else: ?>
                <a href="carousel.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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

        <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                    </div>
                    <form method="GET" action="carousel.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search title, description, or button text…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['q'])): ?>
                        <a href="carousel.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Button</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($slides)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-images text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No carousel slides found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add a new slide</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($slides as $s): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo (int)$s['display_order']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($s['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($s['image_url']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" class="h-16 w-24 object-cover rounded">
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($s['title'] ?? 'Untitled'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php if ($s['button_text']): ?>
                                            <?php echo htmlspecialchars($s['button_text']); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($s['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $s['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="carousel.php" class="inline" onsubmit="return confirm('Delete this carousel slide? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
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
                    'total_items'   => $total_slides,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Homepage (hero carousel)</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Carousel Slide</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Slides rotate in the <strong>hero section</strong> at the top of the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">homepage</a>. The image is shown as a full-width background with a dark green gradient overlay. Keep titles short and punchy — they render very large.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>" onsubmit="return validateImageUpload()">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input type="text" name="title" id="title_input" value="<?php echo htmlspecialchars($slide['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="e.g., Premium Activated Carbon" oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">The headline shown on the slide. Renders as a large <strong>h1</strong> — keep under ~50 characters for best fit.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <textarea name="description" id="desc_input" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="A short tagline or supporting text…" oninput="updatePreview()"><?php echo htmlspecialchars($slide['description'] ?? ''); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Supports basic HTML. Renders as a paragraph below the title — keep under ~150 characters.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Image <span class="text-red-500">*</span></label>

                                    <!-- Image source mode selector -->
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Image source</label>
                                        <select id="image-source-mode" onchange="switchImageMode()" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                            <option value="upload">Upload from file</option>
                                            <option value="url">Enter image URL</option>
                                        </select>
                                    </div>

                                    <!-- Image Preview -->
                                    <div id="image-preview-container" class="mb-3 <?php echo empty($slide['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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
                                            <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — file uploads automatically when selected. Recommended: wide landscape images (≥1600px).</p>
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
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <!-- Hidden field: the actual value submitted with the form -->
                                    <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>">
                                </div>

                                <!-- Image visibility: overlay opacity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Image Visibility <span class="text-gray-400 font-normal">(overlay opacity)</span>
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="range" name="overlay_opacity" id="overlay_opacity" min="0" max="100" step="1"
                                               value="<?php echo htmlspecialchars($slide['overlay_opacity'] ?? 92); ?>"
                                               oninput="updateOpacityDisplay(this.value)"
                                               class="w-full max-w-xs accent-primary">
                                        <span id="overlay_opacity_value" class="text-sm font-medium text-gray-700 w-12 text-right"><?php echo htmlspecialchars($slide['overlay_opacity'] ?? 92); ?>%</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">Controls how strongly the dark green gradient covers the image. <strong>0%</strong> = image fully visible, <strong>100%</strong> = image fully hidden behind the green overlay. Default 92%.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Button Text <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <input type="text" name="button_text" id="btn_text_input" value="<?php echo htmlspecialchars($slide['button_text'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="e.g., Learn More" oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Text for the call-to-action button. Leave blank to hide the button.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Button Link <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <input type="text" name="button_link" id="btn_link_input" value="<?php echo htmlspecialchars($slide['button_link'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="e.g., contact.php" oninput="updatePreview()">
                                        <p class="text-xs text-gray-400 mt-1">Page the button links to.</p>
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            <button type="button" class="quick-pick" onclick="setLink('contact.php')">contact.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('products.php')">products.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('about.php')">about.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('services.php')">services.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('case-studies.php')">case-studies.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('resources.php')">resources.php</button>
                                            <button type="button" class="quick-pick" onclick="setLink('certifications.php')">certifications.php</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0" value="<?php echo htmlspecialchars($slide['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first in the rotation. Use 10, 20, 30… for insert room.</p>
                                    </div>

                                    <div class="flex items-center">
                                        <label class="flex items-center cursor-pointer select-none">
                                            <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo (!isset($slide) || $slide['is_active']) ? 'checked' : ''; ?> class="sr-only peer">
                                            <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                         peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                         after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                         after:bg-white after:rounded-full after:shadow
                                                         after:transition-transform after:duration-300 after:ease-in-out
                                                         peer-checked:after:translate-x-5
                                                         hover:after:scale-110 active:after:scale-95"></span>
                                            <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the homepage)</span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Slide' : 'Save Changes'; ?>
                                </button>
                                <a href="carousel.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how this slide looks in the hero)</span>
                        </p>
                        <!-- Approximation of the hero carousel slide on index.php -->
                        <div class="rounded-lg overflow-hidden border border-gray-200" style="min-height: 300px;">
                            <div id="hero_preview" class="hero-preview relative h-72 flex items-center" style="background-image: linear-gradient(rgba(35, 51, 44, <?php echo number_format(($slide['overlay_opacity'] ?? 92) / 100, 2); ?>), rgba(35, 51, 44, <?php echo number_format(($slide['overlay_opacity'] ?? 92) / 100, 2); ?>)), url('<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>');">
                                <div class="absolute inset-0 bg-black/10"></div>
                                <div class="px-8 relative z-10 w-full">
                                    <span class="eyebrow bg-white/15 text-white/90 mb-3 inline-block px-2 py-0.5 text-xs rounded-full">
                                        <i class="fas fa-water text-xs"></i> Municipal Water Treatment
                                    </span>
                                    <h1 id="preview_title" class="text-2xl lg:text-3xl font-bold mb-3 leading-tight mt-2 text-white"><?php echo htmlspecialchars($slide['title'] ?? 'Slide title'); ?></h1>
                                    <p id="preview_desc" class="text-sm mb-4 text-white/85 max-w-md"><?php echo htmlspecialchars($slide['description'] ?? 'Slide description appears here…'); ?></p>
                                    <div id="preview_btn_wrap" class="<?php echo empty($slide['button_text'] ?? '') ? 'hidden' : ''; ?>">
                                        <span id="preview_btn" class="bg-white text-[#23332c] font-medium py-2 px-5 rounded-full text-sm inline-flex items-center gap-2">
                                            <span id="preview_btn_text"><?php echo htmlspecialchars($slide['button_text'] ?? ''); ?></span>
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The actual hero is full-width and taller. Text sizes are scaled down here for preview. The dark green gradient overlay is always applied on top of your image.</p>
                    </div>
                </div>
            </div>

            <script>
            // Quick-pick helper for button link
            function setLink(l) { document.getElementById('btn_link_input').value = l; updatePreview(); }

            // Live preview update
            function updatePreview() {
                var title = document.getElementById('title_input').value || 'Slide title';
                var desc = document.getElementById('desc_input').value || 'Slide description appears here…';
                var btnText = document.getElementById('btn_text_input').value;
                var imgUrl = document.getElementById('image-url-input').value;
                var opacity = document.getElementById('overlay_opacity').value;
                var alpha = (parseInt(opacity, 10) / 100).toFixed(2);

                document.getElementById('preview_title').textContent = title;
                document.getElementById('preview_desc').textContent = desc;

                var btnWrap = document.getElementById('preview_btn_wrap');
                if (btnText.trim()) {
                    document.getElementById('preview_btn_text').textContent = btnText;
                    btnWrap.classList.remove('hidden');
                } else {
                    btnWrap.classList.add('hidden');
                }

                // Update background image + overlay opacity if changed
                if (imgUrl.trim()) {
                    document.getElementById('hero_preview').style.backgroundImage =
                        "linear-gradient(rgba(35, 51, 44, " + alpha + "), rgba(35, 51, 44, " + alpha + ")), url('" + imgUrl + "')";
                }
            }

            // Slider readout + live preview refresh
            function updateOpacityDisplay(value) {
                document.getElementById('overlay_opacity_value').textContent = value + '%';
                updatePreview();
            }
            </script>

            <script>
            // Image upload logic (unchanged — kept as-is)
            let selectedFile = null;
            let uploadState = 'idle';
            let imageMode = 'upload';
            const originalImageUrl = document.getElementById('image-url-input').value;

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

            // Keep the hidden submitted field in sync with the visible URL text box
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
                        let response = null;
                        try { response = JSON.parse(xhr.responseText); } catch (_) {}
                        if (response && response.success) {
                            document.getElementById('image-url-input').value = response.url;
                            statusText.textContent = 'Upload successful!';
                            statusText.classList.add('text-green-600');
                            progressBar.classList.add('bg-green-500');
                            uploadState = 'success';
                            setTimeout(() => { progressContainer.classList.add('hidden'); }, 2000);
                            updatePreview();
                        } else {
                            statusText.textContent = 'Upload failed: ' + (response && response.error ? response.error : 'Unexpected server response');
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

            function validateImageUpload() {
                const urlInput = document.getElementById('image-url-input');
                if (imageMode === 'url') {
                    if (!urlInput.value.trim()) { alert('Please enter an image URL.'); return false; }
                    return true;
                }
                if (uploadState === 'uploading') { alert('Please wait for the image upload to finish before saving.'); return false; }
                if (selectedFile && uploadState === 'failed') { alert('The image upload failed. Please try again or pick a different file.'); return false; }
                if (!urlInput.value.trim()) { alert('Please choose and upload an image file first.'); return false; }
                return true;
            }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
