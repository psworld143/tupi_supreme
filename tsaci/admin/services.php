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
    $slug = generateSlug($title);
    $description = $_POST['description'] ?? '';
    $features = $_POST['features'] ?? '';
    $benefits = $_POST['benefits'] ?? '';
    $icon = sanitizeInput($_POST['icon'] ?? '');
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Service title is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO services (title, slug, description, features, benefits, icon, image_url, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssiii", $title, $slug, $description, $features, $benefits, $icon, $image_url, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'services', $db->insert_id, "Created service: {$title}");
                $success = 'Service added successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE services SET title = ?, slug = ?, description = ?, features = ?, benefits = ?, icon = ?, image_url = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssiiii", $title, $slug, $description, $features, $benefits, $icon, $image_url, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'services', $id, "Updated service: {$title}");
                $success = 'Service updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('delete', 'services', $id, 'Deleted service');
        $success = 'Service deleted successfully!';
    }
    $action = 'list';
}

$edit_service = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_service = $result->fetch_assoc();
    if (!$edit_service) {
        $error = 'Service not found.';
        $action = 'list';
    }
}

$all_services = [];
if ($action === 'list') {
    $result = $db->query("SELECT * FROM services ORDER BY display_order, title");
    while ($row = $result->fetch_assoc()) {
        $all_services[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - <?php echo SITE_NAME; ?></title>
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
    
    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">Services Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Service
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
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Service</h2>
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($edit_service['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icon (FontAwesome class)</label>
                            <input type="text" name="icon" value="<?php echo htmlspecialchars($edit_service['icon'] ?? ''); ?>" placeholder="fas fa-check" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                            <input type="url" name="image_url" value="<?php echo htmlspecialchars($edit_service['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" value="<?php echo htmlspecialchars($edit_service['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_service['description'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                        <textarea name="features" id="features" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_service['features'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Benefits</label>
                        <textarea name="benefits" id="benefits" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_service['benefits'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($edit_service && $edit_service['is_active']) || !$edit_service ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary">Save</button>
                        <a href="services.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
            <script>
                CKEDITOR.replace('description');
                CKEDITOR.replace('features');
                CKEDITOR.replace('benefits');
            </script>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_services)): ?>
                            <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No services found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_services as $service): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['title']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $service['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($service['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $service['id']; ?>" class="text-primary hover:text-secondary mr-3">Edit</a>
                                        <a href="?action=delete&id=<?php echo $service['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

