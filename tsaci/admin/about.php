<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Available about page sections
$about_sections = [
    'page_header_title' => 'Page Header Title',
    'page_header_subtitle' => 'Page Header Subtitle',
    'company_story_title' => 'Company Story Title',
    'company_story_content' => 'Company Story Content',
    'company_story_icon' => 'Company Story Icon',
    'mission_title' => 'Mission Title',
    'mission_content' => 'Mission Content',
    'mission_icon' => 'Mission Icon',
    'vision_title' => 'Vision Title',
    'vision_content' => 'Vision Content',
    'vision_icon' => 'Vision Icon',
    'timeline_title' => 'Timeline Title',
    'timeline_subtitle' => 'Timeline Subtitle',
    'values_title' => 'Values Title',
    'values_subtitle' => 'Values Subtitle',
    'team_title' => 'Team Title',
    'team_subtitle' => 'Team Subtitle'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            if ($stmt->execute()) {
                logActivity('create', 'about_content', $db->insert_id, "Created about content for {$section_name}");
                $success = 'Content added successfully!';
                $action = 'list';
            } else {
                $error = 'Error adding content: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE about_content SET section_name = ?, title = ?, content = ?, image_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $section_name, $title, $content, $image_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
            
            if ($stmt->execute()) {
                logActivity('update', 'about_content', $id, "Updated about content for {$section_name}");
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
    $stmt = $db->prepare("DELETE FROM about_content WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity('delete', 'about_content', $id, 'Deleted about content');
        $success = 'Content deleted successfully!';
    } else {
        $error = 'Error deleting content: ' . $stmt->error;
    }
    $action = 'list';
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

// Get all content for list (with pagination)
$all_content = [];
$total_content = 0;
$total_pages = 1;
if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM about_content");
    $total_content = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_content / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT ac.*, au.username as updated_by_name FROM about_content ac LEFT JOIN admin_users au ON ac.updated_by = au.id ORDER BY ac.display_order, ac.section_name LIMIT ? OFFSET ?");
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
    <title>About Page Management - <?php echo SITE_NAME; ?></title>
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
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">About Page Management</h1>
                    <p class="text-gray-600 mt-1">Manage content for the About Us page</p>
                </div>
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
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> About Page Content</h2>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section Name</label>
                            <select name="section_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">Select Section</option>
                                <?php foreach ($about_sections as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($edit_content && $edit_content['section_name'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Or enter a custom section name</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" 
                                   value="<?php echo htmlspecialchars($edit_content['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" name="title" 
                               value="<?php echo htmlspecialchars($edit_content['title'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                               placeholder="Section title (if applicable)">
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea name="content" id="content" rows="10" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">For icons, use Font Awesome class names (e.g., fas fa-industry)</p>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image URL (Optional)</label>
                        <input type="url" name="image_url" 
                               value="<?php echo htmlspecialchars($edit_content['image_url'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"
                               placeholder="https://example.com/image.jpg">
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
                        <a href="about.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
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
                <div class="p-4 bg-gray-50 border-b">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-2"></i>
                        Manage all content sections for the About Us page. You can also manage general page content via <a href="pages.php" class="text-primary hover:underline">Page Content Management</a>.
                    </p>
                </div>
                <div class="overflow-auto" style="max-height: 60vh;">
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
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No content found. <a href="?action=add" class="text-primary hover:underline">Add new content</a></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_content as $content): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($about_sections[$content['section_name']] ?? $content['section_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo htmlspecialchars($content['title'] ?: '-'); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-500">
                                            <?php 
                                            $content_preview = $content['content'] ?? '';
                                            $content_preview = strip_tags($content_preview);
                                            echo htmlspecialchars(mb_substr($content_preview, 0, 80)) . '...'; 
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
            
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li><a href="../about.php" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View About Page</a></li>
                    <li><a href="pages.php?page_name=about" class="hover:underline"><i class="fas fa-cog mr-1"></i>Manage General About Page Content</a></li>
                    <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

