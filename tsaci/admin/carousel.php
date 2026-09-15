<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $button_text = sanitizeInput($_POST['button_text'] ?? '');
    $button_link = sanitizeInput($_POST['button_link'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($image_url)) {
        $error = 'Image URL is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO carousel_slides (title, description, image_url, button_text, button_link, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiii", $title, $description, $image_url, $button_text, $button_link, $display_order, $is_active, $_SESSION['admin_id']);
            
            if ($stmt->execute()) {
                logActivity('create', 'carousel_slides', $db->insert_id, "Created carousel slide: {$title}");
                $success = 'Carousel slide added successfully!';
                $action = 'list';
            } else {
                $error = 'Error adding carousel slide: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE carousel_slides SET title = ?, description = ?, image_url = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssiiii", $title, $description, $image_url, $button_text, $button_link, $display_order, $is_active, $_SESSION['admin_id'], $id);
            
            if ($stmt->execute()) {
                logActivity('update', 'carousel_slides', $id, "Updated carousel slide: {$title}");
                $success = 'Carousel slide updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error updating carousel slide: ' . $stmt->error;
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM carousel_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity('delete', 'carousel_slides', $id, "Deleted carousel slide");
        $success = 'Carousel slide deleted successfully!';
        $action = 'list';
    } else {
        $error = 'Error deleting carousel slide: ' . $stmt->error;
    }
}

// Get slides for listing (with pagination)
$slides = [];
$total_slides = 0;
$total_pages = 1;
if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM carousel_slides");
    $total_slides = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_slides / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT * FROM carousel_slides ORDER BY display_order, id LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $slides[] = $row;
    }
}

// Get slide for editing
$slide = null;
if (($action === 'edit' || $action === 'delete') && $id) {
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
                <h1 class="text-3xl font-bold text-gray-800">Carousel Management</h1>
                <?php if ($action === 'list'): ?>
                    <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add New Slide
                    </a>
                <?php else: ?>
                    <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                <?php endif; ?>
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
        
        <?php if ($action === 'list'): ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
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
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No carousel slides found. <a href="?action=add" class="text-primary hover:underline">Add one now</a>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($slides as $s): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($s['display_order']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($s['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($s['image_url']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" class="h-16 w-24 object-cover rounded">
                                        <?php else: ?>
                                            <span class="text-gray-400">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($s['title'] ?? 'Untitled'); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php if ($s['button_text']): ?>
                                            <?php echo htmlspecialchars($s['button_text']); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">No button</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $s['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $s['id']; ?>" class="text-primary hover:text-secondary mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?action=delete&id=<?php echo $s['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this slide?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
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
                    'total_items'   => $total_slides,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold mb-6"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Carousel Slide</h2>
                
                <form method="POST" action="?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($slide['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($slide['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image <span class="text-red-500">*</span></label>
                            
                            <!-- Image Preview -->
                            <div id="image-preview-container" class="mb-3 <?php echo empty($slide['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                <img id="image-preview" src="<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
                                <button type="button" onclick="clearImagePreview()" class="mt-2 text-sm text-red-600 hover:text-red-800">
                                    <i class="fas fa-times mr-1"></i>Remove Image
                                </button>
                            </div>
                            
                            <!-- File Picker -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-3">
                                <div class="text-center">
                                    <input type="file" id="image-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                                    <label for="image-file-input" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                                        <i class="fas fa-upload mr-2"></i>Choose Image File
                                    </label>
                                    <button type="button" id="upload-image-btn" onclick="uploadImage()" class="ml-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors hidden">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>Upload
                                    </button>
                                    <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB)</p>
                                </div>
                                <div id="upload-progress" class="hidden mt-2">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                    </div>
                                    <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                            </div>
                            
                            <!-- URL Input (for manual entry or after upload) -->
                            <input type="url" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($slide['image_url'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="Image URL or upload a file above">
                            <p class="mt-1 text-sm text-gray-500">Enter image URL manually or upload a file using the file picker above</p>
                        </div>
                        
                        <script>
                        let selectedFile = null;
                        
                        document.getElementById('image-file-input').addEventListener('change', function(e) {
                            const file = e.target.files[0];
                            if (file) {
                                selectedFile = file;
                                document.getElementById('upload-image-btn').classList.remove('hidden');
                                
                                // Show preview of selected file
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const preview = document.getElementById('image-preview');
                                    preview.src = e.target.result;
                                    document.getElementById('image-preview-container').classList.remove('hidden');
                                };
                                reader.readAsDataURL(file);
                            }
                        });
                        
                        function uploadImage() {
                            if (!selectedFile) {
                                alert('Please select an image file first');
                                return;
                            }
                            
                            const formData = new FormData();
                            formData.append('image', selectedFile);
                            
                            const uploadBtn = document.getElementById('upload-image-btn');
                            const progressContainer = document.getElementById('upload-progress');
                            const progressBar = document.getElementById('upload-progress-bar');
                            const statusText = document.getElementById('upload-status');
                            
                            uploadBtn.disabled = true;
                            progressContainer.classList.remove('hidden');
                            statusText.textContent = 'Uploading...';
                            
                            const xhr = new XMLHttpRequest();
                            
                            xhr.upload.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    const percentComplete = (e.loaded / e.total) * 100;
                                    progressBar.style.width = percentComplete + '%';
                                }
                            });
                            
                            xhr.addEventListener('load', function() {
                                if (xhr.status === 200) {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        document.getElementById('image-url-input').value = response.url;
                                        statusText.textContent = 'Upload successful!';
                                        statusText.classList.add('text-green-600');
                                        progressBar.classList.add('bg-green-500');
                                        setTimeout(() => {
                                            progressContainer.classList.add('hidden');
                                            uploadBtn.classList.add('hidden');
                                        }, 2000);
                                    } else {
                                        alert('Upload failed: ' + response.error);
                                        progressContainer.classList.add('hidden');
                                    }
                                } else {
                                    const response = JSON.parse(xhr.responseText);
                                    alert('Upload failed: ' + (response.error || 'Server error'));
                                    progressContainer.classList.add('hidden');
                                }
                                uploadBtn.disabled = false;
                            });
                            
                            xhr.addEventListener('error', function() {
                                alert('Upload failed: Network error');
                                progressContainer.classList.add('hidden');
                                uploadBtn.disabled = false;
                            });
                            
                            xhr.open('POST', 'api/upload_image.php');
                            xhr.send(formData);
                        }
                        
                        function clearImagePreview() {
                            document.getElementById('image-preview-container').classList.add('hidden');
                            document.getElementById('image-url-input').value = '';
                            document.getElementById('image-file-input').value = '';
                            selectedFile = null;
                            document.getElementById('upload-image-btn').classList.add('hidden');
                        }
                        
                        // Update preview when URL is manually entered
                        document.getElementById('image-url-input').addEventListener('input', function(e) {
                            const url = e.target.value;
                            if (url) {
                                const preview = document.getElementById('image-preview');
                                preview.src = url;
                                preview.onerror = function() {
                                    this.style.display = 'none';
                                };
                                preview.onload = function() {
                                    this.style.display = 'block';
                                    document.getElementById('image-preview-container').classList.remove('hidden');
                                };
                            } else {
                                document.getElementById('image-preview-container').classList.add('hidden');
                            }
                        });
                        </script>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
                            <input type="text" name="button_text" value="<?php echo htmlspecialchars($slide['button_text'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Button Link</label>
                            <input type="text" name="button_link" value="<?php echo htmlspecialchars($slide['button_link'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="mt-1 text-sm text-gray-500">e.g., contact.php, products.php, about.php</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" value="<?php echo htmlspecialchars($slide['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                            <p class="mt-1 text-sm text-gray-500">Lower numbers appear first</p>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo (!isset($slide) || $slide['is_active']) ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-4">
                        <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">Cancel</a>
                        <button type="submit" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>Save Slide
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

