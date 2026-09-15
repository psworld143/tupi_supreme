<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Available pages
$available_pages = [
    'index' => 'Homepage',
    'about' => 'About Us',
    'products' => 'Products',
    'services' => 'Services',
    'case-studies' => 'Case Studies',
    'gallery' => 'Gallery',
    'resources' => 'Resources',
    'certifications' => 'Certifications',
    'contact' => 'Contact'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name = sanitizeInput($_POST['page_name'] ?? '');
    $section_name = sanitizeInput($_POST['section_name'] ?? '');
    $content = $_POST['content'] ?? '';
    $content_type = sanitizeInput($_POST['content_type'] ?? 'text');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($page_name) || empty($section_name)) {
        $error = 'Page name and section name are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id']);
            
            if ($stmt->execute()) {
                logActivity('create', 'page_content', $db->insert_id, "Created content for {$page_name} - {$section_name}");
                $success = 'Content added successfully!';
                $action = 'list';
            } else {
                $error = 'Error adding content: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE page_content SET page_name = ?, section_name = ?, content_type = ?, content = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id'], $id);
            
            if ($stmt->execute()) {
                logActivity('update', 'page_content', $id, "Updated content for {$page_name} - {$section_name}");
                $success = 'Content updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error updating content: ' . $stmt->error;
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM page_content WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity('delete', 'page_content', $id, 'Deleted page content');
        $success = 'Content deleted successfully!';
    } else {
        $error = 'Error deleting content: ' . $stmt->error;
    }
    $action = 'list';
}

// Get content for edit
$edit_content = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM page_content WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_content = $result->fetch_assoc();
    
    if (!$edit_content) {
        $error = 'Content not found.';
        $action = 'list';
    }
}

// Get all content for list (with pagination)
$all_content = [];
$total_content = 0;
$total_pages = 1;
if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM page_content");
    $total_content = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_content / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT pc.*, au.username as updated_by_name FROM page_content pc LEFT JOIN admin_users au ON pc.updated_by = au.id ORDER BY pc.page_name, pc.display_order, pc.section_name LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_content[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Content Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
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
                <h1 class="text-3xl font-bold text-gray-800">Page Content Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Content
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Page Content</h2>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Page</label>
                            <select name="page_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">Select Page</option>
                                <?php foreach ($available_pages as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($edit_content && $edit_content['page_name'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Name</label>
                            <input type="text" name="section_name" required 
                                   value="<?php echo htmlspecialchars($edit_content['section_name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                                   placeholder="e.g., hero_title, about_description">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                            <select name="content_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="text" <?php echo ($edit_content && $edit_content['content_type'] === 'text') ? 'selected' : ''; ?>>Text</option>
                                <option value="html" <?php echo ($edit_content && $edit_content['content_type'] === 'html') ? 'selected' : ''; ?>>HTML</option>
                                <option value="json" <?php echo ($edit_content && $edit_content['content_type'] === 'json') ? 'selected' : ''; ?>>JSON</option>
                                <option value="image_url" <?php echo ($edit_content && $edit_content['content_type'] === 'image_url') ? 'selected' : ''; ?>>Image URL</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" 
                                   value="<?php echo htmlspecialchars($edit_content['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="content" id="content" rows="10" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo ($edit_content && $edit_content['is_active']) || !$edit_content ? 'checked' : ''; ?>
                                   class="mr-2">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="fas fa-save mr-2"></i>Save
                        </button>
                        <a href="pages.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
            
            <script>
                CKEDITOR.replace('content');
            </script>
            
        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">All Page Content</h2>
                        <p class="text-sm text-gray-600 mt-1">Manage content for all pages</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="about.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors text-sm">
                            <i class="fas fa-info-circle mr-2"></i>Manage About Page
                        </a>
                        <a href="../about.php" target="_blank" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                            <i class="fas fa-external-link-alt mr-2"></i>View About Page
                        </a>
                    </div>
                </div>
                <div class="overflow-auto" style="max-height: 60vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_content)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No content found. <a href="?action=add" class="text-primary hover:underline">Add new content</a></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_content as $content): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($available_pages[$content['page_name']] ?? $content['page_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($content['section_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800"><?php echo htmlspecialchars($content['content_type']); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-500">
                                            <?php 
                                            $content_preview = $content['content'] ?? '';
                                            $content_preview = strip_tags($content_preview);
                                            echo htmlspecialchars(mb_substr($content_preview, 0, 50)) . '...'; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo $content['display_order']; ?></span>
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
                                        <a href="?action=delete&id=<?php echo $content['id']; ?>"
                                           onclick="return confirm('Are you sure you want to delete this content?')"
                                           class="text-red-600 hover:text-red-800" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
        <?php endif; ?>
    </div>
</body>
</html>

