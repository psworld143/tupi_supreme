<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $file_url = sanitizeInput($_POST['file_url'] ?? '');
    $file_type = sanitizeInput($_POST['file_type'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title) || empty($file_url)) {
        $error = 'Title and file URL are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO resources (title, description, file_url, file_type, category, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiii", $title, $description, $file_url, $file_type, $category, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'resources', $db->insert_id, "Added resource: {$title}");
                $success = 'Resource added successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE resources SET title = ?, description = ?, file_url = ?, file_type = ?, category = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssiiii", $title, $description, $file_url, $file_type, $category, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'resources', $id, "Updated resource: {$title}");
                $success = 'Resource updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('delete', 'resources', $id, 'Deleted resource');
        $success = 'Resource deleted successfully!';
    }
    $action = 'list';
}

$edit_resource = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_resource = $result->fetch_assoc();
    if (!$edit_resource) {
        $error = 'Resource not found.';
        $action = 'list';
    }
}

$all_resources = [];
$total_resources = 0;
$total_pages = 1;
if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM resources");
    $total_resources = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_resources / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT * FROM resources ORDER BY category, display_order, title LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_resources[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources Management - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800">Resources Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Resource
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Resource</h2>
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($edit_resource['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <input type="text" name="category" value="<?php echo htmlspecialchars($edit_resource['category'] ?? ''); ?>" placeholder="e.g., Technical Data, Brochures" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Type</label>
                            <input type="text" name="file_type" value="<?php echo htmlspecialchars($edit_resource['file_type'] ?? ''); ?>" placeholder="e.g., PDF, DOCX" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">File URL *</label>
                            <input type="url" name="file_url" required value="<?php echo htmlspecialchars($edit_resource['file_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" value="<?php echo htmlspecialchars($edit_resource['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($edit_resource['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($edit_resource && $edit_resource['is_active']) || !$edit_resource ? 'checked' : ''; ?> class="mr-2">
                                <span class="text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary">Save</button>
                        <a href="resources.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Downloads</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_resources)): ?>
                            <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No resources found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_resources as $resource): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($resource['title']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($resource['category'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($resource['file_type'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $resource['download_count']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($resource['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $resource['id']; ?>" class="text-primary hover:text-secondary mr-3">Edit</a>
                                        <a href="?action=delete&id=<?php echo $resource['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600">Delete</a>
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
                    'total_items'   => $total_resources,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

