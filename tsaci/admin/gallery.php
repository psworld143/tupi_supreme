<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

$categories = ['facilities', 'products', 'process', 'installations', 'team'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = $_POST['description'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? 'facilities');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title) || empty($image_url)) {
        $error = 'Title and image URL are required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO gallery_images (title, description, image_url, category, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiii", $title, $description, $image_url, $category, $display_order, $is_active, $_SESSION['admin_id']);
            if ($stmt->execute()) {
                logActivity('create', 'gallery_images', $db->insert_id, "Added gallery image: {$title}");
                $success = 'Image added successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE gallery_images SET title = ?, description = ?, image_url = ?, category = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("ssssiiii", $title, $description, $image_url, $category, $display_order, $is_active, $_SESSION['admin_id'], $id);
            if ($stmt->execute()) {
                logActivity('update', 'gallery_images', $id, "Updated gallery image: {$title}");
                $success = 'Image updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error: ' . $stmt->error;
            }
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM gallery_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity('delete', 'gallery_images', $id, 'Deleted gallery image');
        $success = 'Image deleted successfully!';
    }
    $action = 'list';
}

$edit_image = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM gallery_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_image = $result->fetch_assoc();
    if (!$edit_image) {
        $error = 'Image not found.';
        $action = 'list';
    }
}

$all_images = [];
if ($action === 'list') {
    $result = $db->query("SELECT * FROM gallery_images ORDER BY category, display_order, title");
    while ($row = $result->fetch_assoc()) {
        $all_images[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - <?php echo SITE_NAME; ?></title>
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
    
    <!-- Main Content -->
    <div class="lg:ml-64 p-4 lg:p-8">
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">Gallery Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Image
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
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Gallery Image</h2>
                <form method="POST" action="" onsubmit="return validateImageUpload()">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($edit_image['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo ($edit_image && $edit_image['category'] === $cat) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" value="<?php echo htmlspecialchars($edit_image['display_order'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image <span class="text-red-500">*</span></label>
                            
                            <!-- Image Preview -->
                            <div id="image-preview-container" class="mb-3 <?php echo empty($edit_image['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                <img id="image-preview" src="<?php echo htmlspecialchars($edit_image['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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
                                    <p class="mt-2 text-xs text-gray-500">JPEG, PNG, GIF, or WebP (Max 10MB) — file uploads automatically when selected</p>
                                </div>
                                <div id="upload-progress" class="hidden mt-2">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div id="upload-progress-bar" class="bg-primary h-2 rounded-full transition-all" style="width: 0%"></div>
                                    </div>
                                    <p id="upload-status" class="text-sm text-gray-600 mt-1"></p>
                                </div>
                            </div>
                            
                            <!-- URL Input (hidden, auto-filled by upload) -->
                            <input type="hidden" name="image_url" id="image-url-input" value="<?php echo htmlspecialchars($edit_image['image_url'] ?? ''); ?>">
                            
                            <script>
                            let selectedFile = null;
                            // Upload state: 'idle' | 'uploading' | 'success' | 'failed'
                            let uploadState = 'idle';
                            // Remember the original URL when editing so we know if the user picked a new file
                            const originalImageUrl = document.getElementById('image-url-input').value;
                            
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
                                    
                                    // Auto-upload immediately after selection
                                    uploadImage();
                                }
                            });
                            
                            function uploadImage() {
                                if (!selectedFile) {
                                    alert('Please select an image file first');
                                    return;
                                }
                                
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
                                            uploadState = 'success';
                                            setTimeout(() => {
                                                progressContainer.classList.add('hidden');
                                            }, 2000);
                                        } else {
                                            statusText.textContent = 'Upload failed: ' + response.error;
                                            statusText.classList.add('text-red-600');
                                            progressContainer.classList.remove('hidden');
                                            uploadState = 'failed';
                                        }
                                    } else {
                                        let errMsg = 'Server error';
                                        try { errMsg = JSON.parse(xhr.responseText).error || errMsg; } catch (_) {}
                                        statusText.textContent = 'Upload failed: ' + errMsg;
                                        statusText.classList.add('text-red-600');
                                        progressContainer.classList.remove('hidden');
                                        uploadState = 'failed';
                                    }
                                    fileLabel.style.pointerEvents = '';
                                    fileLabel.style.opacity = '';
                                });
                                
                                xhr.addEventListener('error', function() {
                                    statusText.textContent = 'Upload failed: Network error';
                                    statusText.classList.add('text-red-600');
                                    progressContainer.classList.remove('hidden');
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
                                document.getElementById('image-file-input').value = '';
                                selectedFile = null;
                                uploadState = 'idle';
                                document.getElementById('upload-progress').classList.add('hidden');
                            }
                            
                            function validateImageUpload() {
                                const urlInput = document.getElementById('image-url-input');
                                
                                // Block save while an upload is still in progress
                                if (uploadState === 'uploading') {
                                    alert('Please wait for the image upload to finish before saving.');
                                    return false;
                                }
                                
                                // User picked a new file but the upload failed
                                if (selectedFile && uploadState === 'failed') {
                                    alert('The image upload failed. Please try again or pick a different file.');
                                    return false;
                                }
                                
                                // No image at all (new record with no upload, or image was cleared)
                                if (!urlInput.value.trim()) {
                                    alert('Please choose and upload an image file first.');
                                    return false;
                                }
                                
                                return true;
                            }
                            </script>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($edit_image['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($edit_image && $edit_image['is_active']) || !$edit_image ? 'checked' : ''; ?> class="mr-2">
                                <span class="text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary">Save</button>
                        <a href="gallery.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_images)): ?>
                            <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No images found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_images as $img): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <?php if ($img['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($img['image_url']); ?>" alt="<?php echo htmlspecialchars($img['title']); ?>" class="w-20 h-20 object-cover rounded">
                                        <?php else: ?>
                                            <div class="w-20 h-20 bg-gray-200 rounded"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($img['title']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo ucfirst($img['category']); ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($img['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $img['id']; ?>" class="text-primary hover:text-secondary mr-3">Edit</a>
                                        <a href="?action=delete&id=<?php echo $img['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600">Delete</a>
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

