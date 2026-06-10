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
                $success = 'Case study added successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE case_studies SET title = ?, slug = ?, client_name = ?, location = ?, industry = ?, challenge = ?, solution = ?, results = ?, image_url = ?, display_order = ?, is_featured = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssssiiiii", $title, $slug, $client_name, $location, $industry, $challenge, $solution, $results, $image_url, $display_order, $is_featured, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'case_studies', $id, "Updated case study: {$title}");
                $success = 'Case study updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM case_studies WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('delete', 'case_studies', $id, 'Deleted case study');
        $success = 'Case study deleted successfully!';
    }
    $action = 'list';
}

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

$all_cases = [];
if ($action === 'list') {
    $result = $db->query("SELECT * FROM case_studies ORDER BY display_order, title");
    while ($row = $result->fetch_assoc()) {
        $all_cases[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Studies Management - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800">Case Studies Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Case Study
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
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Case Study</h2>
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($edit_case['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Name</label>
                            <input type="text" name="client_name" value="<?php echo htmlspecialchars($edit_case['client_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($edit_case['location'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                            <input type="text" name="industry" value="<?php echo htmlspecialchars($edit_case['industry'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                            <input type="url" name="image_url" value="<?php echo htmlspecialchars($edit_case['image_url'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" value="<?php echo htmlspecialchars($edit_case['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Challenge</label>
                        <textarea name="challenge" id="challenge" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_case['challenge'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Solution</label>
                        <textarea name="solution" id="solution" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_case['solution'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Results</label>
                        <textarea name="results" id="results" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars_decode($edit_case['results'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="mt-6 flex gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" <?php echo ($edit_case && $edit_case['is_featured']) ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-sm text-gray-700">Featured</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($edit_case && $edit_case['is_active']) || !$edit_case ? 'checked' : ''; ?> class="mr-2">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary">Save</button>
                        <a href="case-studies.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
            <script>
                CKEDITOR.replace('challenge');
                CKEDITOR.replace('solution');
                CKEDITOR.replace('results');
            </script>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_cases)): ?>
                            <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No case studies found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_cases as $case): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($case['title']); ?></div>
                                        <?php if ($case['is_featured']): ?>
                                            <span class="text-xs text-yellow-600"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($case['client_name'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($case['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $case['id']; ?>" class="text-primary hover:text-secondary mr-3">Edit</a>
                                        <a href="?action=delete&id=<?php echo $case['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600">Delete</a>
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

