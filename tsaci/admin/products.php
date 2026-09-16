<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// Auto-add the category_id column to products if it doesn't exist yet (migration)
$col_check = $db->query("SHOW COLUMNS FROM `products` LIKE 'category_id'");
if ($col_check && $col_check->num_rows === 0) {
    $db->query("ALTER TABLE `products` ADD COLUMN `category_id` INT(11) DEFAULT NULL AFTER `image_url`");
    $db->query("ALTER TABLE `products` ADD KEY `category_id` (`category_id`)");
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Product saved successfully!';
if ($status_param === 'deleted') $success = 'Product deleted successfully!';

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $slug = generateSlug($name);
    $description = $_POST['description'] ?? '';
    $specifications = $_POST['specifications'] ?? '';
    $features = $_POST['features'] ?? '';
    $applications = $_POST['applications'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $category_id = $category_id > 0 ? $category_id : null;
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Product name is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO products (name, slug, description, specifications, features, applications, image_url, category_id, display_order, is_featured, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssiiiii", $name, $slug, $description, $specifications, $features, $applications, $image_url, $category_id, $display_order, $is_featured, $is_active, $_SESSION['admin_id']);

            if ($stmt->execute()) {
                logActivity('create', 'products', $db->insert_id, "Created product: {$name}");
                redirect('products.php?status=saved');
            } else {
                $error = 'Error adding product: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE products SET name = ?, slug = ?, description = ?, specifications = ?, features = ?, applications = ?, image_url = ?, category_id = ?, display_order = ?, is_featured = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssiiiiii", $name, $slug, $description, $specifications, $features, $applications, $image_url, $category_id, $display_order, $is_featured, $is_active, $_SESSION['admin_id'], $id);

            if ($stmt->execute()) {
                logActivity('update', 'products', $id, "Updated product: {$name}");
                redirect('products.php?status=saved');
            } else {
                $error = 'Error updating product: ' . $stmt->error;
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'products', $del_id, 'Deleted product');
        redirect('products.php?status=deleted');
    } else {
        $error = 'Error deleting product: ' . $stmt->error;
    }
}

// Get product for edit
$edit_product = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();

    if (!$edit_product) {
        $error = 'Product not found.';
        $action = 'list';
    }
}

// Fetch all product tabs (categories) for the dropdown — admin sees active + inactive
$product_categories = [];
$cat_res = $db->query("SELECT id, tab_key, label, icon, is_system FROM product_tabs ORDER BY display_order, label");
if ($cat_res) {
    while ($row = $cat_res->fetch_assoc()) {
        $product_categories[] = $row;
    }
}
// Build a quick lookup so the list view can show the category label
$categories_by_id = [];
foreach ($product_categories as $cat) {
    $categories_by_id[(int)$cat['id']] = $cat;
}

// Get all products for list (with pagination + filtering + search)
$all_products = [];
$total_products = 0;
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
        $where[] = 'p.is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'p.is_active = 0';
    }
    if ($filter_featured === '1') {
        $where[] = 'p.is_featured = 1';
    }
    if ($search_q !== '') {
        $where[] = '(p.name LIKE ? OR p.slug LIKE ? OR p.description LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM products p $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_products = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_products / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT p.*, au.username as updated_by_name FROM products p LEFT JOIN admin_users au ON p.updated_by = au.id $where_sql ORDER BY p.display_order, p.name LIMIT ? OFFSET ?";
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
            $all_products[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0, 'featured' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active, SUM(is_featured) featured FROM products");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
    $stats['featured'] = (int)$row['featured'];
}

// All products render in the main products section on the public page
$view_url = '../products.php#main';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-box text-primary"></i> Products Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage the product catalog shown on the public Products page.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Product
                </a>
                <?php else: ?>
                <a href="products.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
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
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Product</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Products appear in the catalog on the public <a href="<?php echo $view_url; ?>" target="_blank" class="underline hover:text-blue-900">Products page</a>, organized by category tabs. The <strong>Granulated Activated Carbon</strong> tab is the flagship — featured products also show on the homepage.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" id="name_input" required
                                           value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                           placeholder="e.g., Granulated Activated Carbon 2mm"
                                           oninput="updatePreview()">
                                    <p class="text-xs text-gray-400 mt-1">A slug is auto-generated from the name (e.g., "Granulated Activated Carbon" → "granulated-activated-carbon").</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <select name="category_id" id="category_input"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                                onchange="updatePreview()">
                                            <option value="0">— No category (keyword matching) —</option>
                                            <?php foreach ($product_categories as $cat): ?>
                                                <option value="<?php echo (int)$cat['id']; ?>"
                                                    <?php echo ($edit_product && (int)$edit_product['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['label']); ?>
                                                    <?php if ((int)$cat['is_system'] === 1): ?> (system)<?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Assign to a category tab. Manage tabs in <a href="product-tabs.php" class="text-primary hover:underline">Product Tabs</a>.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_product['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first in the catalog. Use 10, 20, 30…</p>
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
                                    <div id="image-preview-container" class="mb-3 <?php echo empty($edit_product['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                        <img id="image-preview" src="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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

                                    <!-- URL Input (url mode — visible text field for manual entry) -->
                                    <div id="url-input-block" class="hidden mb-3">
                                        <input type="url" id="image-url-visible" value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="https://example.com/image.jpg" oninput="syncUrlInput(this.value)">
                                        <p class="mt-1 text-xs text-gray-500">Paste a full image URL (https://...).</p>
                                    </div>

                                    <!-- Hidden field: the actual value submitted with the form -->
                                    <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>">
                                </div>

                                <!-- Rich text editor fields -->
                                <?php
                                $rich_fields = [
                                    ['name' => 'description', 'label' => 'Description', 'hint' => 'Main product description shown on the catalog. Supports formatting (bold, lists, links).', 'rows' => 5, 'placeholder' => 'Describe the product…'],
                                    ['name' => 'specifications', 'label' => 'Specifications', 'hint' => 'Technical specs (mesh size, iodine number, ash content). Tip: use bullet lists.', 'rows' => 5, 'placeholder' => 'e.g., • Mesh size: 8x30&#10;• Iodine number: ≥ 1000 mg/g&#10;• Ash content: ≤ 5%'],
                                    ['name' => 'features', 'label' => 'Features', 'hint' => 'Key features. The public site splits this on the • character to show as a checklist. Use one • per feature.', 'rows' => 5, 'placeholder' => '• High adsorption capacity&#10;• Low ash content&#10;• Fast filtration rate'],
                                    ['name' => 'applications', 'label' => 'Applications', 'hint' => 'Where this product can be used (e.g., municipal water treatment, air filtration).', 'rows' => 5, 'placeholder' => '• Municipal water treatment&#10;• Air filtration&#10;• Food & beverage'],
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
                                         oninput="document.getElementById('<?php echo $tid; ?>').value = this.innerHTML; updatePreview();"><?php echo htmlspecialchars_decode($edit_product[$rf['name']] ?? '', ENT_QUOTES); ?></div>
                                    <textarea name="<?php echo $rf['name']; ?>" id="<?php echo $tid; ?>" rows="<?php echo $rf['rows']; ?>" class="hidden"><?php echo htmlspecialchars_decode($edit_product[$rf['name']] ?? '', ENT_QUOTES); ?></textarea>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo $rf['hint']; ?></p>
                                </div>
                                <?php endforeach; ?>

                                <div class="flex flex-wrap gap-6">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_featured" value="1"
                                               <?php echo ($edit_product && $edit_product['is_featured']) ? 'checked' : ''; ?>
                                               class="sr-only peer">
                                        <span class="relative inline-flex items-center">
                                            <span class="w-11 h-6 bg-gray-300 peer-checked:bg-yellow-500 rounded-full transition-colors"></span>
                                            <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
                                        </span>
                                        <span class="ml-3 text-sm text-gray-700">Featured <span class="text-gray-400">(shown on homepage)</span></span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?php echo ($edit_product && $edit_product['is_active']) || !$edit_product ? 'checked' : ''; ?>
                                               class="sr-only peer">
                                        <span class="relative inline-flex items-center">
                                            <span class="w-11 h-6 bg-gray-300 peer-checked:bg-primary rounded-full transition-colors"></span>
                                            <span class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></span>
                                        </span>
                                        <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Product' : 'Save Changes'; ?>
                                </button>
                                <a href="products.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(product card on the Products page)</span>
                        </p>
                        <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white">
                            <div class="aspect-video bg-[#f7faf8] flex items-center justify-center overflow-hidden" id="preview_img_wrap">
                                <img id="preview_img" src="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" alt="" class="w-full h-full object-cover <?php echo empty($edit_product['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                <div id="preview_img_placeholder" class="aspect-video bg-gradient-to-br from-[#3d7a66] to-[#60796e] flex items-center justify-center w-full h-full <?php echo empty($edit_product['image_url'] ?? '') ? '' : 'hidden'; ?>">
                                    <i class="fas fa-box text-5xl text-white/80"></i>
                                </div>
                            </div>
                            <div class="p-6">
                                <h4 id="preview_name" class="text-xl font-semibold text-[#23332c] mb-2"><?php echo htmlspecialchars($edit_product['name'] ?? 'Product name'); ?></h4>
                                <div id="preview_desc" class="text-[#7d8b84] text-sm leading-relaxed mb-4 prose prose-sm max-w-none"><?php echo $edit_product['description'] ?? '<span class="text-gray-400">Product description appears here…</span>'; ?></div>
                                <ul id="preview_features" class="text-sm text-[#5a6b62] space-y-1 mb-4">
                                    <li class="text-gray-400">Features appear here when added (split on • )…</li>
                                </ul>
                                <div class="mt-auto pt-4">
                                    <span class="inline-flex items-center gap-2 bg-[#23332c] text-white font-medium py-2.5 px-5 rounded-full text-sm">
                                        <i class="fas fa-quote-left text-xs"></i>Request Quote
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The actual card on the public site may vary slightly. Features are split on the • character and shown as a checklist.</p>
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
                // Sync editors -> hidden textareas before submit
                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    form.addEventListener('submit', function () {
                        ['description', 'specifications', 'features', 'applications'].forEach(function (id) {
                            var editor = document.getElementById(id + '_html');
                            var ta = document.getElementById(id);
                            if (editor && ta) ta.value = editor.innerHTML;
                        });
                    });
                }
            })();

            // Live preview
            function updatePreview() {
                var name = document.getElementById('name_input').value || 'Product name';
                var descHtml = document.getElementById('description_html').innerHTML || '<span class="text-gray-400">Product description appears here…</span>';
                var featuresHtml = document.getElementById('features_html').innerHTML || '';
                var imgUrl = document.getElementById('image-url-input').value;

                document.getElementById('preview_name').textContent = name;
                document.getElementById('preview_desc').innerHTML = descHtml;

                // Parse features: split on • character
                var feats = featuresHtml.split('•').map(function(s){ return s.trim(); }).filter(function(s){ return s.length > 0 && s.replace(/<[^>]*>/g,'').trim().length > 0; });
                var ul = document.getElementById('preview_features');
                if (feats.length > 0) {
                    ul.innerHTML = feats.map(function(f) {
                        return '<li class="flex items-start"><i class="fas fa-check text-[#3d7a66] mt-1 mr-2 text-xs"></i>' + f + '</li>';
                    }).join('');
                } else {
                    ul.innerHTML = '<li class="text-gray-400">Features appear here when added (split on • )…</li>';
                }

                // Image
                var img = document.getElementById('preview_img');
                var placeholder = document.getElementById('preview_img_placeholder');
                if (imgUrl.trim()) {
                    img.src = imgUrl;
                    img.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                } else {
                    img.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                }
            }
            </script>

            <script>
            // Image upload logic (unchanged — kept as-is)
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
                    <form method="GET" action="products.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active only</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive only</option>
                            </select>
                            <select name="filter_featured" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All products</option>
                                <option value="1" <?php echo (($_GET['filter_featured'] ?? '') === '1') ? 'selected' : ''; ?>>Featured only</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search name or description…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_status']) || !empty($_GET['filter_featured']) || !empty($_GET['q'])): ?>
                        <a href="products.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_products)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No products found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new product</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_products as $product): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="text-xs text-yellow-600"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php
                                        $cid = (int)($product['category_id'] ?? 0);
                                        if ($cid > 0 && isset($categories_by_id[$cid])):
                                            $cat = $categories_by_id[$cid];
                                        ?>
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                                                <i class="fas <?php echo htmlspecialchars($cat['icon'] ?? 'fa-cube'); ?> mr-1"></i><?php echo htmlspecialchars($cat['label']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">keyword match</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($product['slug']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo (int)$product['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($product['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                        <a href="?action=edit&id=<?php echo $product['id']; ?>" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="products.php" class="inline" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
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
                    'total_items'   => $total_products,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="<?php echo $view_url; ?>" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Products Page</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
