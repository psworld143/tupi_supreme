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
    $name = sanitizeInput($_POST['name'] ?? '');
    $slug = generateSlug($name);
    $description = $_POST['description'] ?? '';
    $specifications = $_POST['specifications'] ?? '';
    $features = $_POST['features'] ?? '';
    $applications = $_POST['applications'] ?? '';
    $image_url = sanitizeInput($_POST['image_url'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($name)) {
        $error = 'Product name is required.';
    } else {
        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO products (name, slug, description, specifications, features, applications, image_url, display_order, is_featured, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssiiii", $name, $slug, $description, $specifications, $features, $applications, $image_url, $display_order, $is_featured, $is_active, $_SESSION['admin_id']);
            
            if ($stmt->execute()) {
                logActivity('create', 'products', $db->insert_id, "Created product: {$name}");
                $success = 'Product added successfully!';
                $action = 'list';
            } else {
                $error = 'Error adding product: ' . $stmt->error;
            }
        } elseif ($action === 'edit' && $id) {
            $stmt = $db->prepare("UPDATE products SET name = ?, slug = ?, description = ?, specifications = ?, features = ?, applications = ?, image_url = ?, display_order = ?, is_featured = ?, is_active = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sssssssiiiii", $name, $slug, $description, $specifications, $features, $applications, $image_url, $display_order, $is_featured, $is_active, $_SESSION['admin_id'], $id);
            
            if ($stmt->execute()) {
                logActivity('update', 'products', $id, "Updated product: {$name}");
                $success = 'Product updated successfully!';
                $action = 'list';
            } else {
                $error = 'Error updating product: ' . $stmt->error;
            }
        }
    }
}

// Handle delete
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity('delete', 'products', $id, 'Deleted product');
        $success = 'Product deleted successfully!';
    } else {
        $error = 'Error deleting product: ' . $stmt->error;
    }
    $action = 'list';
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

// Get all products for list (with pagination)
$all_products = [];
$total_products = 0;
$total_pages = 1;
if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $offset = ($current_page_num - 1) * $per_page;

    // Total count for pagination controls
    $count_result = $db->query("SELECT COUNT(*) as total FROM products");
    $total_products = $count_result->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_products / $per_page));
    // Clamp current page if out of range
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
        $offset = ($current_page_num - 1) * $per_page;
    }

    $stmt = $db->prepare("SELECT p.*, au.username as updated_by_name FROM products p LEFT JOIN admin_users au ON p.updated_by = au.id ORDER BY p.display_order, p.name LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - <?php echo SITE_NAME; ?></title>
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
                <h1 class="text-3xl font-bold text-gray-800">Products Management</h1>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add New Product
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
                <h2 class="text-2xl font-bold mb-4"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Product</h2>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="name" required 
                                   value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                            
                            <!-- Image Preview -->
                            <div id="image-preview-container" class="mb-3 <?php echo empty($edit_product['image_url'] ?? '') ? 'hidden' : ''; ?>">
                                <img id="image-preview" src="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" alt="Preview" class="max-w-full h-48 object-contain border border-gray-300 rounded-lg p-2 bg-gray-50">
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
                            
                            <!-- URL Input -->
                            <input type="url" name="image_url" id="image-url-input" 
                                   value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary" placeholder="Image URL or upload a file above">
                            <p class="mt-1 text-sm text-gray-500">Enter image URL manually or upload a file using the file picker above</p>
                            
                            <script>
                            let selectedFile = null;
                            
                            document.getElementById('image-file-input').addEventListener('change', function(e) {
                                const file = e.target.files[0];
                                if (file) {
                                    selectedFile = file;
                                    document.getElementById('upload-image-btn').classList.remove('hidden');
                                    
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
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                            <input type="number" name="display_order" 
                                   value="<?php echo htmlspecialchars($edit_product['display_order'] ?? 0); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="description" rows="5" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_product['description'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specifications</label>
                        <textarea name="specifications" id="specifications" rows="5" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_product['specifications'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                        <textarea name="features" id="features" rows="5" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_product['features'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Applications</label>
                        <textarea name="applications" id="applications" rows="5" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary"><?php echo htmlspecialchars_decode($edit_product['applications'] ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    
                    <div class="mt-6 flex gap-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" 
                                   <?php echo ($edit_product && $edit_product['is_featured']) ? 'checked' : ''; ?>
                                   class="mr-2">
                            <span class="text-sm text-gray-700">Featured</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo ($edit_product && $edit_product['is_active']) || !$edit_product ? 'checked' : ''; ?>
                                   class="mr-2">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-secondary transition-colors">
                            <i class="fas fa-save mr-2"></i>Save
                        </button>
                        <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">Cancel</a>
                    </div>
                </form>
            </div>
            
            <script>
                CKEDITOR.replace('description');
                CKEDITOR.replace('specifications');
                CKEDITOR.replace('features');
                CKEDITOR.replace('applications');
            </script>
            
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_products)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No products found. <a href="?action=add" class="text-primary hover:underline">Add new product</a></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="text-xs text-yellow-600"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($product['slug']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $product['display_order']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($product['is_active']): ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <a href="?action=edit&id=<?php echo $product['id']; ?>" class="text-primary hover:text-secondary mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                           onclick="return confirm('Are you sure?')"
                                           class="text-red-600 hover:text-red-800">
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
                    'total_items'   => $total_products,
                    'per_page'      => $per_page,
                    'base_query'    => $_GET,
                ]);
                ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

