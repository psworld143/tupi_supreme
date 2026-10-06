<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$error = '';
$success = '';

$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved') $success = 'Module visibility saved successfully!';

// Module registry — mirrors getSiteModules() in includes/config.php.
// Setting keys: module_page_<slug> for pages, module_sec_<key> for homepage
// sections. Missing keys default to enabled.
$module_groups = [
    'Pages' => [
        // Homepage can't be disabled — it is the site.
        'page_about'          => ['About Us', 'about.php', 'fa-info-circle', 'Company story, mission/vision, timeline, team.'],
        'page_products'       => ['Products', 'products.php', 'fa-cube', 'Product catalog with tabbed categories.'],
        'page_services'       => ['Services', 'services.php', 'fa-concierge-bell', 'Service offerings, process steps, testimonials.'],
        'page_case-studies'   => ['Case Studies', 'case-studies.php', 'fa-book', 'Municipal water-treatment success stories.'],
        'page_gallery'        => ['Gallery', 'gallery.php', 'fa-photo-video', 'Image gallery by category.'],
        'page_resources'      => ['Resources', 'resources.php', 'fa-file-download', 'Data sheets, catalogs, FAQs.'],
        'page_certifications' => ['Certifications', 'certifications.php', 'fa-certificate', 'ISO certs and compliance info.'],
        'page_contact'        => ['Contact', 'contact.php', 'fa-envelope', 'Contact form and office details.'],
    ],
    'Homepage Sections' => [
        'sec_hero'     => ['Hero / Carousel', '', 'fa-images', 'Top banner with rotating slides.'],
        'sec_features' => ['Features', '', 'fa-star', '"Why Choose" feature cards.'],
        'sec_stats'    => ['Statistics', '', 'fa-chart-bar', 'Dark stat-counter band.'],
        'sec_products' => ['Products Preview', '', 'fa-cube', 'Featured product cards.'],
        'sec_cta'      => ['Call to Action', '', 'fa-comments', 'Bottom contact CTA band.'],
    ],
];
$all_module_keys = [];
foreach ($module_groups as $fields) {
    $all_module_keys = array_merge($all_module_keys, array_keys($fields));
}

// Load current flag values (stored as site_settings keys `module_<key>`)
$module_setting_keys = array_map(function($k) { return 'module_' . $k; }, $all_module_keys);
$module_flags = [];
$placeholders = implode(',', array_fill(0, count($module_setting_keys), '?'));
$stmt = $db->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($placeholders)");
$stmt->bind_param(str_repeat('s', count($module_setting_keys)), ...$module_setting_keys);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $module_flags[$row['setting_key']] = $row['setting_value'];
}
function moduleOn($flags, $key) {
    $skey = 'module_' . $key;
    return !isset($flags[$skey]) || $flags[$skey] !== '0';
}

// Save handler (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security token expired or invalid. Please reload the page and try again.';
    } elseif (($_POST['post_action'] ?? '') === 'save_modules') {
        $enabled = $_POST['modules'] ?? [];
        $upsert = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)");
        foreach ($all_module_keys as $key) {
            $value = in_array($key, $enabled, true) ? '1' : '0';
            $skey = 'module_' . $key;
            $upsert->bind_param("ssi", $skey, $value, $_SESSION['admin_id']);
            $upsert->execute();
        }
        logActivity('update', 'site_settings', null, 'Updated page/section visibility');
        redirect('modules.php?status=saved');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Modules - <?php echo SITE_NAME; ?></title>
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
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900">Site Modules</h1>
                <p class="text-sm text-zinc-500 mt-0.5">Control which pages and homepage sections are visible on the public site</p>
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

        <div class="mb-4 px-4 py-3 rounded-md bg-zinc-50 border border-zinc-200 flex items-start gap-2.5">
            <i class="fas fa-info-circle text-zinc-400 mt-0.5 text-sm"></i>
            <p class="text-xs text-zinc-500 leading-relaxed">Disabled pages are removed from the navigation and redirect visitors to the homepage. Disabled homepage sections are simply not rendered. Changes apply immediately after saving.</p>
        </div>

        <form method="POST" action="modules.php">
            <?php echo csrfTokenField(); ?>
            <input type="hidden" name="post_action" value="save_modules">

            <?php foreach ($module_groups as $group_label => $modules): ?>
                <div class="bg-white rounded-lg border border-zinc-200 mb-6 overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-200 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-zinc-900"><?php echo htmlspecialchars($group_label); ?></h2>
                        <span class="text-xs text-zinc-500"><?php echo count($modules); ?> modules</span>
                    </div>
                    <ul class="divide-y divide-zinc-100">
                        <?php foreach ($modules as $key => $meta):
                            $on = moduleOn($module_flags, $key);
                        ?>
                        <li class="flex items-center gap-4 px-5 py-3.5 hover:bg-zinc-50 transition-colors">
                            <div class="w-9 h-9 rounded-lg <?php echo $on ? 'bg-[#e9f1ea] text-[#2c5530]' : 'bg-zinc-100 text-zinc-400'; ?> flex items-center justify-center flex-shrink-0 transition-colors">
                                <i class="fas <?php echo $meta[2]; ?> text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-zinc-900">
                                    <?php echo htmlspecialchars($meta[0]); ?>
                                    <?php if ($meta[1]): ?>
                                        <code class="ml-1.5 text-[11px] text-zinc-400 font-normal"><?php echo $meta[1]; ?></code>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-zinc-500"><?php echo htmlspecialchars($meta[3]); ?></div>
                            </div>
                            <span class="lv-badge <?php echo $on ? 'lv-badge-green' : 'lv-badge-zinc'; ?>"><?php echo $on ? 'Enabled' : 'Disabled'; ?></span>
                            <label class="relative inline-flex cursor-pointer select-none items-center">
                                <input type="checkbox" name="modules[]" value="<?php echo $key; ?>" <?php echo $on ? 'checked' : ''; ?> class="sr-only peer">
                                <span class="block w-9 h-5 rounded-full bg-zinc-300 transition-colors peer-checked:bg-[#2c5530] peer-focus-visible:ring-2 peer-focus-visible:ring-[#2c5530]/30 peer-focus-visible:ring-offset-2 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:bg-white after:rounded-full after:shadow after:transition-transform peer-checked:after:translate-x-4"></span>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <div class="flex items-center gap-3 mb-6">
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                    <i class="fas fa-save text-xs"></i> Save Visibility
                </button>
            </div>
        </form>
    </div>
</body>
</html>
