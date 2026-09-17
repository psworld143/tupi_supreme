<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Settings saved successfully!';
if ($status_param === 'added')   $success = 'Custom setting added successfully!';
if ($status_param === 'deleted') $success = 'Custom setting deleted successfully!';

// Known settings, grouped for the main form. key => [label, hint, input type]
$setting_groups = [
    'Company' => [
        'company_name'       => ['Company Name (Full)', 'Used in <title> tags and the footer copyright line.', 'text'],
        'company_short_name' => ['Company Short Name', 'Shown in the navbar and most page titles.', 'text'],
        'company_tagline'    => ['Tagline', 'Shown in the footer on every page.', 'textarea'],
    ],
    'Contact Details' => [
        'contact_address' => ['Address', 'Shown in the footer. Also used by the address lookup helper.', 'text'],
        'contact_phone'   => ['Phone', 'Shown in the footer.', 'text'],
        'contact_email'   => ['Email', 'Shown in the footer AND used as the contact form recipient.', 'email'],
    ],
    'SEO / Meta' => [
        'meta_description' => ['Meta Description', 'Default search-engine description for the homepage.', 'textarea'],
        'meta_keywords'    => ['Meta Keywords', 'Default keywords for the homepage (comma-separated).', 'textarea'],
    ],
    'Footer' => [
        'copyright_year' => ['Copyright Year', 'Shown in the footer copyright line.', 'text'],
    ],
];
$known_keys = [];
foreach ($setting_groups as $fields) {
    $known_keys = array_merge($known_keys, array_keys($fields));
}

// Load all settings into a key => row map
$settings = [];
$result = $db->query("SELECT * FROM site_settings ORDER BY setting_key");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row;
    }
}

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') === '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $post_action = $_POST['post_action'] ?? '';

        // -----------------------------------------------------------------
        // 1) Save grouped known settings
        // -----------------------------------------------------------------
        if ($post_action === 'save_settings') {
            $saved = 0;
            $upsert = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
            foreach ($known_keys as $key) {
                $value = $_POST['settings'][$key] ?? '';
                $value = is_string($value) ? trim($value) : '';
                $upsert->bind_param("ssi", $key, $value, $_SESSION['admin_id']);
                if ($upsert->execute()) {
                    $saved++;
                }
            }
            logActivity('update', 'site_settings', null, "Updated site settings ({$saved} keys)");
            redirect('site-settings.php?status=saved');
        }

        // -----------------------------------------------------------------
        // 2) Add a custom setting
        // -----------------------------------------------------------------
        if ($post_action === 'add_custom') {
            $key = strtolower(trim($_POST['new_key'] ?? ''));
            $value = trim($_POST['new_value'] ?? '');
            $description = trim($_POST['new_description'] ?? '');

            if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $key)) {
                $error = 'Setting key must be lowercase letters, numbers, and underscores (e.g., map_zoom_level).';
            } elseif (in_array($key, $known_keys, true)) {
                $error = "'{$key}' is a core setting — edit it in the form above.";
            } elseif (isset($settings[$key])) {
                $error = "A setting with the key '{$key}' already exists.";
            } else {
                $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_type, description, updated_by) VALUES (?, ?, 'text', ?, ?)");
                $stmt->bind_param("sssi", $key, $value, $description, $_SESSION['admin_id']);
                if ($stmt->execute()) {
                    logActivity('create', 'site_settings', $db->insert_id, "Added custom setting: {$key}");
                    redirect('site-settings.php?status=added#custom');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            }
        }

        // -----------------------------------------------------------------
        // 3) Update a custom setting's value/description
        // -----------------------------------------------------------------
        if ($post_action === 'update_custom') {
            $edit_id = intval($_POST['edit_id'] ?? 0);
            $value = trim($_POST['edit_value'] ?? '');
            $description = trim($_POST['edit_description'] ?? '');

            $stmt = $db->prepare("SELECT setting_key FROM site_settings WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                $error = 'Setting not found.';
            } elseif (in_array($row['setting_key'], $known_keys, true)) {
                $error = 'Core settings can only be edited in the main form above.';
            } else {
                $stmt = $db->prepare("UPDATE site_settings SET setting_value = ?, description = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssii", $value, $description, $_SESSION['admin_id'], $edit_id);
                if ($stmt->execute()) {
                    logActivity('update', 'site_settings', $edit_id, "Updated custom setting: {$row['setting_key']}");
                    redirect('site-settings.php?status=saved#custom');
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            }
        }
    }
}

// Handle delete (POST only — custom keys only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } else {
        $del_id = intval($_POST['delete_id']);
        $stmt = $db->prepare("SELECT setting_key FROM site_settings WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            $error = 'Setting not found.';
        } elseif (in_array($row['setting_key'], $known_keys, true)) {
            $error = "Core setting '{$row['setting_key']}' cannot be deleted — the website depends on it.";
        } else {
            $stmt = $db->prepare("DELETE FROM site_settings WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
                logActivity('delete', 'site_settings', $del_id, "Deleted custom setting: {$row['setting_key']}");
                redirect('site-settings.php?status=deleted#custom');
            } else {
                $error = 'Error deleting setting: ' . $stmt->error;
            }
        }
    }
}

// Reload settings after any POST handling
$settings = [];
$result = $db->query("SELECT * FROM site_settings ORDER BY setting_key");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row;
    }
}

// Split into known vs custom
$custom_settings = array_filter($settings, function($key) use ($known_keys) {
    return !in_array($key, $known_keys, true);
}, ARRAY_FILTER_USE_KEY);

// Edit target for custom settings (?edit_custom=<id>)
$edit_custom = null;
if (isset($_GET['edit_custom'])) {
    $eid = intval($_GET['edit_custom']);
    foreach ($settings as $row) {
        if ((int)$row['id'] === $eid && !in_array($row['setting_key'], $known_keys, true)) {
            $edit_custom = $row;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - <?php echo SITE_NAME; ?></title>
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
                        <i class="fas fa-sliders-h text-primary"></i> Site Settings
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Global values used across the whole website — company name, contact details, SEO defaults.</p>
                </div>
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

        <!-- Grouped Settings Form -->
        <form method="POST" action="">
            <?php echo csrfTokenField(); ?>
            <input type="hidden" name="post_action" value="save_settings">

            <?php foreach ($setting_groups as $group_label => $fields): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($group_label); ?></h2>
                    <div class="space-y-5 mt-4">
                        <?php foreach ($fields as $key => $meta):
                            $value = $settings[$key]['setting_value'] ?? '';
                        ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="setting_<?php echo $key; ?>">
                                    <?php echo htmlspecialchars($meta[0]); ?>
                                    <code class="ml-2 text-[11px] text-gray-400 font-normal"><?php echo $key; ?></code>
                                </label>
                                <?php if ($meta[2] === 'textarea'): ?>
                                    <textarea name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm"><?php echo htmlspecialchars($value); ?></textarea>
                                <?php else: ?>
                                    <input type="<?php echo $meta[2]; ?>" name="settings[<?php echo $key; ?>]" id="setting_<?php echo $key; ?>"
                                           value="<?php echo htmlspecialchars($value); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary text-sm">
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($meta[1]); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex items-center gap-3 mb-6">
                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>

        <!-- Custom Settings (advanced) -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6" id="custom">
            <h2 class="text-xl font-bold text-gray-800 mb-1 flex items-center gap-2">
                <i class="fas fa-wrench text-gray-400"></i> Custom Settings
            </h2>
            <p class="text-sm text-gray-500 mb-4">Additional key/value pairs for features not covered above. Core settings (listed in the form) can't be deleted or renamed.</p>

            <?php if (!empty($custom_settings)): ?>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($custom_settings as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900"><?php echo htmlspecialchars($row['setting_key']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($row['setting_value']); ?>"><?php echo htmlspecialchars(mb_strimwidth($row['setting_value'] ?? '', 0, 60, '…')); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo htmlspecialchars($row['description'] ?: '—'); ?></td>
                                    <td class="px-4 py-3 text-sm font-medium whitespace-nowrap">
                                        <a href="?edit_custom=<?php echo $row['id']; ?>#custom" class="text-primary hover:text-secondary mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="site-settings.php#custom" class="inline" onsubmit="return confirm('Delete the setting \'<?php echo htmlspecialchars($row['setting_key'], ENT_QUOTES); ?>\'? Any feature using it will fall back to its default.');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php if ($edit_custom && (int)$edit_custom['id'] === (int)$row['id']): ?>
                                    <tr class="bg-blue-50/50">
                                        <td colspan="4" class="px-4 py-4">
                                            <form method="POST" action="site-settings.php#custom" class="flex flex-col sm:flex-row gap-3 items-start">
                                                <?php echo csrfTokenField(); ?>
                                                <input type="hidden" name="post_action" value="update_custom">
                                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                                <div class="flex-1 w-full">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Value</label>
                                                    <input type="text" name="edit_value" value="<?php echo htmlspecialchars($row['setting_value'] ?? ''); ?>"
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                                </div>
                                                <div class="flex-1 w-full">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                                    <input type="text" name="edit_description" value="<?php echo htmlspecialchars($row['description'] ?? ''); ?>"
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                                </div>
                                                <div class="flex gap-2 pt-5">
                                                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary text-sm"><i class="fas fa-save mr-1"></i>Save</button>
                                                    <a href="site-settings.php#custom" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 text-sm">Cancel</a>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-sm text-gray-400 mb-6"><i class="fas fa-inbox mr-1"></i>No custom settings yet.</p>
            <?php endif; ?>

            <!-- Add custom setting -->
            <form method="POST" action="site-settings.php#custom" class="border-t pt-5">
                <?php echo csrfTokenField(); ?>
                <input type="hidden" name="post_action" value="add_custom">
                <p class="text-sm font-medium text-gray-700 mb-3">Add a custom setting</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <input type="text" name="new_key" required maxlength="100" pattern="[a-z][a-z0-9_]+"
                               placeholder="key_name" title="Lowercase letters, numbers, underscores"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono focus:outline-none focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-400 mt-1">lowercase_with_underscores</p>
                    </div>
                    <div>
                        <input type="text" name="new_value" placeholder="Value"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="new_description" placeholder="Description (optional)"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary text-sm whitespace-nowrap">
                            <i class="fas fa-plus mr-1"></i>Add
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Quick Links</h3>
            <ul class="text-sm text-blue-800 space-y-1">
                <li><a href="../index.php" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Homepage</a></li>
                <li><a href="../contact.php" target="_blank" class="hover:underline"><i class="fas fa-external-link-alt mr-1"></i>View Contact Page</a></li>
                <li><a href="index.php" class="hover:underline"><i class="fas fa-home mr-1"></i>Back to Admin Dashboard</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
