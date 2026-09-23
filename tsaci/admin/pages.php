<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Status flash from PRG redirect (avoids form resubmission on refresh)
$status_param = $_GET['status'] ?? '';
if ($status_param === 'saved')   $success = 'Content saved successfully!';
if ($status_param === 'deleted') $success = 'Content deleted successfully!';

// Available pages (key => [label, public url])
$available_pages = [
    'index'          => ['Homepage',       '../index.php'],
    'about'          => ['About Us',       '../about.php'],
    'products'       => ['Products',       '../products.php'],
    'services'       => ['Services',       '../services.php'],
    'case-studies'   => ['Case Studies',   '../case-studies.php'],
    'gallery'        => ['Gallery',        '../gallery.php'],
    'resources'      => ['Resources',      '../resources.php'],
    'certifications' => ['Certifications', '../certifications.php'],
    'contact'        => ['Contact',        '../contact.php'],
];

// Maps section_name prefixes to the anchor IDs on the public pages.
// Longest matching prefix wins, so order doesn't matter.
$section_anchors = [
    'hero'            => 'hero',
    'features'        => 'features',
    'products'        => 'products',
    'cta'             => 'cta',
    'page_header'     => 'page-header',
    'section'         => 'main',
    'specifications'  => 'specifications',
    'applications'    => 'applications',
    'data_sheets'     => 'data-sheets',
    'catalogs'        => 'catalogs',
    'guides'          => 'guides',
    'faqs'            => 'faqs',
    'process'         => 'process',
    'testimonials'    => 'testimonials',
    'contact_section' => 'contact-section',
    'form'            => 'contact-form',
    'map'             => 'map',
    'office_hours'    => 'office-hours',
    'contact_info'    => 'contact-section',
    'iso'             => 'iso-certifications',
    'product_certs'   => 'product-certifications',
    'quality'         => 'quality-management',
    'compliance'      => 'industry-compliance',
    'testing'         => 'testing-validation',
];

// Derive the on-page anchor for a given section_name (longest-prefix match).
function getSectionAnchor($section_name, $anchors) {
    $best = null;
    $best_len = 0;
    foreach ($anchors as $prefix => $anchor) {
        $len = strlen($prefix);
        if (strpos($section_name, $prefix) === 0 && $len > $best_len) {
            $best = $anchor;
            $best_len = $len;
        }
    }
    return $best;
}

// Handle form submissions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name = sanitizeInput($_POST['page_name'] ?? '');
    $section_name = sanitizeInput($_POST['section_name'] ?? '');
    $content = $_POST['content'] ?? '';
    $content_type = sanitizeInput($_POST['content_type'] ?? 'text');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($page_name) || empty($section_name)) {
        $error = 'Page and section name are required.';
    } else {
        if ($action === 'add') {
            // Pre-check the UNIQUE(page_name, section_name) constraint so we can
            // show a friendly message instead of an uncaught mysqli_sql_exception.
            $check = $db->prepare("SELECT id FROM page_content WHERE page_name = ? AND section_name = ? LIMIT 1");
            $check->bind_param("ss", $page_name, $section_name);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "A content row for page '{$page_name}' with section '{$section_name}' already exists. Edit it instead of adding a new one.";
            } else {
                $stmt = $db->prepare("INSERT INTO page_content (page_name, section_name, content_type, content, display_order, is_active, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id']);

                try {
                    if ($stmt->execute()) {
                        logActivity('create', 'page_content', $db->insert_id, "Created content for {$page_name} - {$section_name}");
                        redirect('pages.php?status=saved');
                    } else {
                        $error = 'Error adding content: ' . $stmt->error;
                    }
                } catch (mysqli_sql_exception $e) {
                    // Safety net in case of a race or any other constraint violation.
                    if (strpos($e->getMessage(), 'unique_page_section') !== false || $e->getCode() === 1062) {
                        $error = "A content row for page '{$page_name}' with section '{$section_name}' already exists. Edit it instead of adding a new one.";
                    } else {
                        $error = 'Error adding content: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'edit' && $id) {
            // Pre-check the UNIQUE(page_name, section_name) constraint (excluding the current row).
            $check = $db->prepare("SELECT id FROM page_content WHERE page_name = ? AND section_name = ? AND id <> ? LIMIT 1");
            $check->bind_param("ssi", $page_name, $section_name, $id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "A content row for page '{$page_name}' with section '{$section_name}' already exists. Edit that one instead.";
            } else {
                $stmt = $db->prepare("UPDATE page_content SET page_name = ?, section_name = ?, content_type = ?, content = ?, display_order = ?, is_active = ?, updated_by = ? WHERE id = ?");
                $stmt->bind_param("ssssiiii", $page_name, $section_name, $content_type, $content, $display_order, $is_active, $_SESSION['admin_id'], $id);

                try {
                    if ($stmt->execute()) {
                        logActivity('update', 'page_content', $id, "Updated content for {$page_name} - {$section_name}");
                        redirect('pages.php?status=saved');
                    } else {
                        $error = 'Error updating content: ' . $stmt->error;
                    }
                } catch (mysqli_sql_exception $e) {
                    if (strpos($e->getMessage(), 'unique_page_section') !== false || $e->getCode() === 1062) {
                        $error = "A content row for page '{$page_name}' with section '{$section_name}' already exists. Edit that one instead.";
                    } else {
                        $error = 'Error updating content: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Handle delete (POST only — safer than GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['delete_id'] ?? '') !== '') {
    $del_id = intval($_POST['delete_id']);
    $stmt = $db->prepare("DELETE FROM page_content WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        logActivity('delete', 'page_content', $del_id, 'Deleted page content');
        redirect('pages.php?status=deleted');
    } else {
        $error = 'Error deleting content: ' . $stmt->error;
    }
}

// Handle bulk actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['bulk_action'] ?? '') !== '') {
    $bulk_action = sanitizeInput($_POST['bulk_action'] ?? '');
    $bulk_ids = $_POST['bulk_ids'] ?? [];
    $bulk_ids = array_map('intval', $bulk_ids);
    $bulk_ids = array_filter($bulk_ids);
    if (empty($bulk_ids)) {
        $error = 'No rows selected for bulk action.';
    } elseif (in_array($bulk_action, ['activate', 'deactivate', 'delete'], true)) {
        $in = implode(',', array_fill(0, count($bulk_ids), '?'));
        if ($bulk_action === 'delete') {
            $stmt = $db->prepare("DELETE FROM page_content WHERE id IN ($in)");
            $types = str_repeat('i', count($bulk_ids));
            $stmt->bind_param($types, ...$bulk_ids);
            if ($stmt->execute()) {
                foreach ($bulk_ids as $bid) {
                    logActivity('delete', 'page_content', $bid, 'Bulk deleted page content');
                }
                redirect('pages.php?status=deleted');
            } else {
                $error = 'Error bulk deleting: ' . $stmt->error;
            }
        } else {
            $is_active = ($bulk_action === 'activate') ? 1 : 0;
            $stmt = $db->prepare("UPDATE page_content SET is_active = ?, updated_by = ? WHERE id IN ($in)");
            $params = array_merge([$is_active], [$_SESSION['admin_id']], $bulk_ids);
            $types = 'ii' . str_repeat('i', count($bulk_ids));
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                logActivity('update', 'page_content', 0, 'Bulk ' . $bulk_action . 'd ' . count($bulk_ids) . ' rows');
                redirect('pages.php?status=saved');
            } else {
                $error = 'Error bulk updating: ' . $stmt->error;
            }
        }
    } else {
        $error = 'Unknown bulk action.';
    }
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

// Gather existing section names for the datalist suggestions
$section_suggestions = [];
$res = $db->query("SELECT DISTINCT section_name FROM page_content ORDER BY section_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $section_suggestions[] = $row['section_name'];
    }
}

// Known/available section names per page (not all may exist as rows yet).
// Surfacing these in the datalist makes it obvious which keys a page reads,
// so editors can create content for sections that aren't seeded yet.
$known_section_names = [
    'index' => [
        'hero_title', 'hero_description', 'hero_icon',
        'hero_cta_primary_text', 'hero_cta_primary_link',
        'hero_cta_secondary_text', 'hero_cta_secondary_link',
        'features_title', 'features_subtitle',
        'products_title', 'products_subtitle',
        'cta_title', 'cta_description',
        'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link',
        'cta_button_3_text', 'cta_button_3_link',
    ],
    // 'about' reads from the about_content table (managed in about.php), not page_content.
    'certifications' => [
        'page_header_title', 'page_header_subtitle',
        'iso_title', 'iso_subtitle',
        'product_certs_title', 'product_certs_subtitle',
        'quality_title', 'quality_subtitle',
        'compliance_title', 'compliance_subtitle',
        'testing_title', 'testing_subtitle', 'testing_note',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'products' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'tab_granulated_label', 'tab_husk_label', 'tab_custom_label',
        'specifications_title', 'specifications_subtitle', 'applications_title', 'applications_subtitle',
        'all_products_title', 'all_products_subtitle',
        'husk_banner_title', 'husk_banner_subtitle', 'husk_note_title', 'husk_note_text',
        'custom_banner_title', 'custom_banner_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link',
    ],
    'services' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'process_title', 'process_subtitle', 'features_title', 'features_subtitle',
        'testimonials_title', 'testimonials_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link',
    ],
    'case-studies' => [
        'page_header_title', 'page_header_subtitle', 'section_title', 'section_subtitle',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link',
    ],
    'gallery' => [
        'page_header_title', 'page_header_subtitle',
    ],
    'resources' => [
        'page_header_title', 'page_header_subtitle', 'data_sheets_title', 'data_sheets_subtitle',
        'catalogs_title', 'catalogs_subtitle', 'guides_title', 'guides_subtitle',
        'other_title', 'other_subtitle', 'faqs_title', 'faqs_subtitle',
        'download_button_text', 'download_guide_text',
        'cta_title', 'cta_description', 'cta_button_1_text', 'cta_button_1_link',
        'cta_button_2_text', 'cta_button_2_link', 'meta_description',
    ],
    'contact' => [
        'page_header_title', 'page_header_subtitle', 'contact_section_title', 'contact_section_subtitle',
        'form_title', 'form_subtitle', 'map_title', 'map_subtitle', 'map_embed_url',
        'office_hours_title', 'office_hours_subtitle', 'faqs_title', 'faqs_subtitle',
        'timezone_note', 'meta_description',
    ],
];
// Merge known names into the suggestions (deduped, sorted)
$section_suggestions = array_unique(array_merge($section_suggestions, ...array_values($known_section_names)));
sort($section_suggestions);

// Human-readable descriptions for common section keys (shown in the form when selected)
$section_descriptions = [
    // Generic prefixes
    'page_header_title'    => 'The large heading at the top of the page.',
    'page_header_subtitle' => 'The subtitle text below the page header heading.',
    'section_title'        => 'The main section heading on the page.',
    'section_subtitle'    => 'The subtitle below the main section heading.',
    'meta_description'     => 'The SEO meta description (used by search engines, not shown on the page). Only read on the Resources and Contact pages — the homepage uses Site Settings instead.',
    // Homepage
    'hero_title'               => 'The large headline in the homepage hero banner.',
    'hero_description'         => 'The supporting text below the hero title.',
    'hero_icon'                => 'Font Awesome icon class shown in the hero (e.g., fas fa-water).',
    'hero_cta_primary_text'    => 'Text for the primary hero button.',
    'hero_cta_primary_link'    => 'URL the primary hero button links to.',
    'hero_cta_secondary_text'  => 'Text for the secondary hero button.',
    'hero_cta_secondary_link'  => 'URL the secondary hero button links to.',
    'features_title'           => 'Heading for the features section.',
    'features_subtitle'        => 'Subtitle for the features section.',
    'products_title'           => 'Heading for the homepage products section.',
    'products_subtitle'        => 'Subtitle for the homepage products section.',
    // CTA (common to several pages)
    'cta_title'            => 'Heading for the call-to-action banner.',
    'cta_description'      => 'Body text for the call-to-action banner.',
    'cta_button_1_text'    => 'Text for the first CTA button.',
    'cta_button_1_link'    => 'URL the first CTA button links to.',
    'cta_button_2_text'    => 'Text for the second CTA button.',
    'cta_button_2_link'    => 'URL the second CTA button links to.',
    'cta_button_3_text'    => 'Text for the third CTA button (homepage only).',
    'cta_button_3_link'    => 'URL the third CTA button links to (homepage only).',
    // Products
    'tab_granulated_label' => 'Label for the Granulated Activated Carbon tab.',
    'tab_husk_label'       => 'Label for the Husk Products tab.',
    'tab_custom_label'     => 'Label for the Custom Products tab.',
    'specifications_title' => 'Heading for the specifications section.',
    'specifications_subtitle' => 'Subtitle for the specifications section.',
    'applications_title'   => 'Heading for the applications section.',
    'applications_subtitle' => 'Subtitle for the applications section.',
    'all_products_title'   => 'Heading for the all-products section.',
    'all_products_subtitle' => 'Subtitle for the all-products section.',
    'husk_banner_title'    => 'Banner heading inside the Coconut Husk Products tab.',
    'husk_banner_subtitle' => 'Banner subtitle inside the Coconut Husk Products tab.',
    'husk_note_title'      => 'Heading for the zero-waste note at the bottom of the husk tab.',
    'husk_note_text'       => 'Body text for the zero-waste note at the bottom of the husk tab.',
    'custom_banner_title'  => 'Banner heading inside the Custom Formulations tab.',
    'custom_banner_subtitle' => 'Banner subtitle inside the Custom Formulations tab.',
    // Services
    'process_title'       => 'Heading for the process steps section.',
    'process_subtitle'    => 'Subtitle for the process steps section.',
    'testimonials_title'  => 'Heading for the testimonials section.',
    'testimonials_subtitle' => 'Subtitle for the testimonials section.',
    // Resources
    'data_sheets_title'    => 'Heading for the Technical Data Sheets section.',
    'data_sheets_subtitle' => 'Subtitle for the Technical Data Sheets section.',
    'catalogs_title'       => 'Heading for the Product Catalogs section.',
    'catalogs_subtitle'    => 'Subtitle for the Product Catalogs section.',
    'guides_title'         => 'Heading for the Application Guides section.',
    'guides_subtitle'      => 'Subtitle for the Application Guides section.',
    'other_title'          => 'Heading for the Other Resources section.',
    'other_subtitle'       => 'Subtitle for the Other Resources section.',
    'faqs_title'           => 'Heading for the FAQs section.',
    'faqs_subtitle'        => 'Subtitle for the FAQs section.',
    'download_button_text' => 'Text on the download buttons (e.g., "Download").',
    'download_guide_text'  => 'Text on the guide download buttons.',
    // Certifications
    'iso_title'            => 'Heading for the ISO Certifications section.',
    'iso_subtitle'         => 'Subtitle for the ISO Certifications section.',
    'product_certs_title'    => 'Heading for the Product Certifications section.',
    'product_certs_subtitle' => 'Subtitle for the Product Certifications section.',
    'quality_title'        => 'Heading for the Quality Management Systems section.',
    'quality_subtitle'     => 'Subtitle for the Quality Management Systems section.',
    'compliance_title'     => 'Heading for the Industry Compliance section.',
    'compliance_subtitle'  => 'Subtitle for the Industry Compliance section.',
    'testing_title'        => 'Heading for the Testing & Validation section.',
    'testing_subtitle'     => 'Subtitle for the Testing & Validation section.',
    'testing_note'         => 'Footnote text below the testing specifications table.',
    // Contact
    'contact_section_title'    => 'Heading for the contact section.',
    'contact_section_subtitle' => 'Subtitle for the contact section.',
    'form_title'          => 'Heading above the contact form.',
    'form_subtitle'       => 'Subtitle above the contact form.',
    'map_title'           => 'Heading for the map section.',
    'map_subtitle'        => 'Subtitle for the map section.',
    'map_embed_url'       => 'Google Maps embed URL for the map.',
    'office_hours_title'  => 'Heading for the office hours section.',
    'office_hours_subtitle' => 'Subtitle for the office hours section.',
    'timezone_note'       => 'Small note about the timezone shown under office hours.',
];

// Content type metadata (label + badge color) — defined early so filters can use it
$content_types = [
    'text'      => ['Text',       'bg-blue-100 text-blue-800'],
    'html'      => ['HTML',       'bg-purple-100 text-purple-800'],
    'json'      => ['JSON',       'bg-amber-100 text-amber-800'],
    'image_url' => ['Image URL',  'bg-emerald-100 text-emerald-800'],
];

// Get all content for list (with pagination + filtering + search)
$all_content = [];
$total_content = 0;
$total_pages = 1;
$current_page_num = 1;
$per_page = 10;

if ($action === 'list') {
    $per_page = 10;
    $current_page_num = max(1, intval($_GET['page'] ?? 1));
    $filter_page = $_GET['filter_page'] ?? '';
    $filter_type = $_GET['filter_type'] ?? '';
    $filter_status = $_GET['filter_status'] ?? '';
    $search_q = trim($_GET['q'] ?? '');

    // Build dynamic WHERE
    $where = [];
    $params = [];
    $types = '';
    if ($filter_page !== '' && isset($available_pages[$filter_page])) {
        $where[] = 'pc.page_name = ?';
        $params[] = $filter_page;
        $types .= 's';
    }
    if ($filter_type !== '' && array_key_exists($filter_type, $content_types)) {
        $where[] = 'pc.content_type = ?';
        $params[] = $filter_type;
        $types .= 's';
    }
    if ($filter_status === 'active') {
        $where[] = 'pc.is_active = 1';
    } elseif ($filter_status === 'inactive') {
        $where[] = 'pc.is_active = 0';
    }
    if ($search_q !== '') {
        $where[] = '(pc.section_name LIKE ? OR pc.content LIKE ?)';
        $like = '%' . $search_q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $count_sql = "SELECT COUNT(*) as total FROM page_content pc $where_sql";
    $count_stmt = $db->prepare($count_sql);
    if ($types && $count_stmt) {
        $count_stmt->bind_param($types, ...$params);
    }
    if ($count_stmt) {
        $count_stmt->execute();
        $total_content = $count_stmt->get_result()->fetch_assoc()['total'];
    }
    $total_pages = max(1, ceil($total_content / $per_page));
    if ($current_page_num > $total_pages) {
        $current_page_num = $total_pages;
    }
    $offset = ($current_page_num - 1) * $per_page;

    // List query
    $list_sql = "SELECT pc.*, au.username as updated_by_name FROM page_content pc LEFT JOIN admin_users au ON pc.updated_by = au.id $where_sql ORDER BY pc.page_name, pc.display_order, pc.section_name LIMIT ? OFFSET ?";
    $list_stmt = $db->prepare($list_sql);
    $list_params = $params;
    $list_types = $types . 'ii';
    $list_params[] = $per_page;
    $list_params[] = $offset;
    if ($list_stmt) {
        $list_stmt->bind_param($list_types, ...$list_params);
        $list_stmt->execute();
        $result = $list_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_content[] = $row;
        }
    }
}

// Quick stats for the list header
$stats = ['total' => 0, 'active' => 0, 'inactive' => 0];
$st = $db->query("SELECT COUNT(*) total, SUM(is_active) active FROM page_content");
if ($st) {
    $row = $st->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['inactive'] = $stats['total'] - $stats['active'];
}

// Missing sections: for each page, which known section keys have no row in page_content.
// Helps editors spot gaps (e.g. a hero subtitle that silently falls back to the default).
// Also used by the Add/Edit form for live duplicate-section detection.
$existing_sections = [];
$res = $db->query("SELECT id, page_name, section_name FROM page_content");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $existing_sections[$row['page_name']][$row['section_name']] = true;
        $existing_sections[$row['page_name']][$row['section_name'] . '__id'] = (int)$row['id'];
    }
}
$missing_sections = []; // [page_key => [section_name, ...]]
$missing_total = 0;
foreach ($known_section_names as $page_key => $sections) {
    foreach ($sections as $sec) {
        if (empty($existing_sections[$page_key][$sec])) {
            $missing_sections[$page_key][] = $sec;
            $missing_total++;
        }
    }
}
$show_missing = ($_GET['show_missing'] ?? '0') === '1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Content Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Lightweight inline HTML editor (replaces EOL CKEditor 4) -->
    <style>
        .qe-toolbar button { padding: 4px 8px; border: 1px solid #d1d5db; background: #fff; border-radius: 4px; font-size: 13px; cursor: pointer; }
        .qe-toolbar button:hover { background: #f3f4f6; }
        .qe-toolbar button.active { background: #e5e7eb; }
        .qe-editor { min-height: 160px; }
        .qe-editor:focus { outline: none; border-color: #2c5530; }
    </style>
    <?php endif; ?>
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
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }
    </style>

    <!-- Main Content -->
    <div class="relative lg:ml-64 p-4 lg:p-8">
        <?php $logo_pulse_logo = '../uploads/images/tupi_supreme_logo.png'; $logo_pulse_mode = 'absolute'; include '../includes/logo_pulse_loader.php'; ?>

        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="fas fa-file-alt text-primary"></i> Page Content Management
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Edit the text, images, and sections shown across the public website.</p>
                </div>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Content
                </a>
                <?php else: ?>
                <a href="pages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors inline-flex items-center justify-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to List
                </a>
                <?php endif; ?>
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

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-1"><?php echo $action === 'add' ? 'Add New' : 'Edit'; ?> Page Content</h2>
                <p class="text-sm text-gray-500 mb-4">Fields marked <span class="text-red-500">*</span> are required.</p>

                <!-- Info banner -->
                <div class="mb-6 p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-start gap-2">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-800">Page content sections are reusable text/HTML/JSON/image values that the public pages read by their <strong>section name</strong> key. Pick the page and section to control exactly which part of the site this content fills in. The preview on the right shows how the content will render.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: form fields -->
                    <div>
                        <form method="POST" action="">
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Page <span class="text-red-500">*</span></label>
                                        <select name="page_name" id="page_name_input" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                            <option value="">Select a page…</option>
                                            <?php foreach ($available_pages as $key => $info): ?>
                                                <option value="<?php echo $key; ?>" <?php echo (($edit_content && $edit_content['page_name'] === $key) || (!$edit_content && ($_GET['preset_page'] ?? '') === $key)) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($info[0]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Which public page this content belongs to.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Section Name <span class="text-red-500">*</span></label>
                                        <input type="hidden" name="section_name" id="section_name_hidden" value="<?php echo htmlspecialchars($edit_content['section_name'] ?? $_GET['preset_section'] ?? ''); ?>">
                                        <select id="section_name_select"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary <?php echo ($edit_content && !empty($edit_content['section_name']) && !in_array($edit_content['section_name'], $known_section_names[$edit_content['page_name']] ?? [])) ? 'hidden' : ''; ?>">
                                            <option value="">Select a section…</option>
                                        </select>
                                        <input type="text" id="section_name_custom"
                                               value="<?php echo htmlspecialchars($edit_content['section_name'] ?? ''); ?>"
                                               placeholder="custom_section_name (snake_case)"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary mt-2 <?php echo ($edit_content && !empty($edit_content['section_name']) && !in_array($edit_content['section_name'], $known_section_names[$edit_content['page_name']] ?? [])) ? '' : 'hidden'; ?>">
                                        <label class="flex items-center gap-2 mt-2 text-xs text-gray-500 cursor-pointer">
                                            <input type="checkbox" id="use_custom_section" <?php echo ($edit_content && !empty($edit_content['section_name']) && !in_array($edit_content['section_name'], $known_section_names[$edit_content['page_name']] ?? [])) ? 'checked' : ''; ?>>
                                            Use a custom section name (advanced)
                                        </label>
                                        <p class="text-xs text-gray-400 mt-1">Pick from the list to match what the public page reads.</p>
                                    </div>
                                </div>

                                <!-- Section description banner -->
                                <div id="section_description" class="p-3 rounded-lg bg-green-50 border border-green-200 flex items-start gap-2 hidden">
                                    <i class="fas fa-tag text-green-600 mt-0.5"></i>
                                    <p class="text-sm text-green-800" id="section_description_text"></p>
                                </div>

                                <!-- Duplicate section warning banner -->
                                <div id="section_duplicate" class="p-3 rounded-lg bg-amber-50 border border-amber-300 flex items-start gap-2 hidden">
                                    <i class="fas fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                                    <div class="text-sm text-amber-900">
                                        <p id="section_duplicate_text"></p>
                                        <a id="section_duplicate_edit_link" href="#" class="inline-flex items-center gap-1 mt-1 text-amber-900 font-semibold hover:underline">
                                            <i class="fas fa-edit text-xs"></i>Edit the existing content instead
                                        </a>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                                        <select name="content_type" id="content_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                            <option value="text"      <?php echo ($edit_content && $edit_content['content_type'] === 'text') ? 'selected' : ''; ?>>Text</option>
                                            <option value="html"      <?php echo ($edit_content && $edit_content['content_type'] === 'html') ? 'selected' : ''; ?>>HTML (rich editor)</option>
                                            <option value="json"      <?php echo ($edit_content && $edit_content['content_type'] === 'json') ? 'selected' : ''; ?>>JSON</option>
                                            <option value="image_url" <?php echo ($edit_content && $edit_content['content_type'] === 'image_url') ? 'selected' : ''; ?>>Image URL</option>
                                        </select>
                                        <p class="text-xs text-gray-400 mt-1">Choose how this content should be stored and edited.</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                        <input type="number" name="display_order" min="0"
                                               value="<?php echo htmlspecialchars($edit_content['display_order'] ?? 0); ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary">
                                        <p class="text-xs text-gray-400 mt-1">Lower numbers appear first (0 = default).</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>

                                    <!-- Lightweight HTML editor toolbar (shown only for html content type) -->
                                    <div id="html_toolbar" class="qe-toolbar flex flex-wrap gap-1 mb-2 p-2 bg-gray-50 rounded-t-md border border-b-0 border-gray-300 hidden">
                                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                                        <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                                        <button type="button" data-cmd="insertUnorderedList" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                                        <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                                        <button type="button" data-cmd="formatBlock" data-val="h3" title="Heading">H3</button>
                                        <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph">P</button>
                                        <button type="button" data-cmd="createLink" title="Link"><i class="fas fa-link"></i></button>
                                        <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="fas fa-eraser"></i></button>
                                    </div>

                                    <!-- contenteditable rich surface for HTML; hidden textarea mirrors it for POST -->
                                    <div id="content_html" contenteditable="false"
                                         class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary qe-editor bg-white hidden prose max-w-none"></div>
                                    <textarea name="content" id="content" rows="10"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-primary focus:border-primary <?php echo (($edit_content['content_type'] ?? '') === 'json') ? 'font-mono' : ''; ?>"><?php echo htmlspecialchars_decode($edit_content['content'] ?? '', ENT_QUOTES); ?></textarea>

                                    <!-- Live image preview (only relevant for image_url) -->
                                    <div id="image_preview_wrap" class="mt-3 hidden">
                                        <p class="text-xs text-gray-500 mb-1">Image preview:</p>
                                        <img id="image_preview" src="" alt="Preview" class="max-h-40 rounded border border-gray-200 bg-gray-50 p-2" onerror="this.classList.add('hidden')">
                                    </div>

                                    <p id="content_hint" class="text-xs text-gray-400 mt-1"></p>
                                </div>

                                <div>
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="is_active" value="1"
                                               <?php echo ($edit_content && $edit_content['is_active']) || !$edit_content ? 'checked' : ''; ?>
                                               class="sr-only peer">
                                        <span class="relative w-11 h-6 rounded-full bg-gray-300 transition-colors duration-300 ease-in-out
                                                     peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2
                                                     after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                                                     after:bg-white after:rounded-full after:shadow
                                                     after:transition-transform after:duration-300 after:ease-in-out
                                                     peer-checked:after:translate-x-5
                                                     hover:after:scale-110 active:after:scale-95"></span>
                                        <span class="ml-3 text-sm text-gray-700">Active <span class="text-gray-400">(shown on the website)</span></span>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i><?php echo $action === 'add' ? 'Add Content' : 'Save Changes'; ?>
                                </button>
                                <a href="pages.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg transition-colors inline-flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Right: live preview -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-eye text-gray-400"></i> Live Preview
                            <span class="text-xs text-gray-400 font-normal">(how this content renders)</span>
                        </p>
                         <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Preview header showing page + section -->
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span id="preview_page_badge" class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800">—</span>
                                    <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                                    <span id="preview_section_badge" class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-800 font-mono">—</span>
                                    <span id="preview_type_badge" class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">text</span>
                                </div>
                            </div>
                            <!-- Preview body -->
                            <div class="p-6 bg-white min-h-[200px]">
                                <div id="preview_text" class="text-gray-800 whitespace-pre-wrap leading-relaxed"></div>
                                <div id="preview_html" class="prose prose-sm max-w-none text-gray-800 leading-relaxed hidden"></div>
                                <pre id="preview_json" class="text-xs font-mono text-gray-700 bg-gray-50 p-3 rounded border border-gray-200 overflow-auto hidden"></pre>
                                <div id="preview_image" class="hidden">
                                    <img id="preview_image_img" src="" alt="Preview" class="max-w-full max-h-64 rounded-lg border border-gray-200" onerror="this.parentElement.classList.add('hidden')">
                                </div>
                                <p id="preview_empty" class="text-gray-400 text-sm italic">Start typing to see a preview…</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">The preview shows how the content value renders. The actual placement on the public page depends on the page and section you choose.</p>
                    </div>
                </div>
            </div>

            <script>
            (function () {
                // Per-page known section names — populate the section <select> based on the chosen page.
                var knownSections = <?php echo json_encode($known_section_names); ?>;
                var sectionDescriptions = <?php echo json_encode($section_descriptions); ?>;
                var pageLabels = <?php echo json_encode(array_map(function($i) { return $i[0]; }, $available_pages)); ?>;
                // Existing (page_name, section_name) pairs already in the database — used for duplicate detection.
                var existingSections = <?php echo json_encode($existing_sections); ?>;
                // When editing, the current row's id is excluded from the duplicate check.
                var editId = <?php echo (int)($edit_content['id'] ?? 0); ?>;
                var pageSelect = document.querySelector('select[name="page_name"]');
                var sectionSelect = document.getElementById('section_name_select');
                var sectionCustom = document.getElementById('section_name_custom');
                var sectionHidden = document.getElementById('section_name_hidden');
                var useCustomChk = document.getElementById('use_custom_section');
                var form = document.querySelector('form[method="POST"]');
                var submitBtn = form ? form.querySelector('button[type="submit"]') : null;

                function rebuildSelect() {
                    var page = pageSelect.value;
                    var current = sectionHidden.value;
                    var names = knownSections[page] || [];
                    sectionSelect.innerHTML = '<option value="">Select a section…</option>';
                    var found = false;
                    names.forEach(function (name) {
                        var opt = document.createElement('option');
                        opt.value = name;
                        opt.textContent = name;
                        if (name === current) { opt.selected = true; found = true; }
                        sectionSelect.appendChild(opt);
                    });
                    // If the current value isn't a known key for this page, treat as custom.
                    if (current && !found) {
                        useCustomChk.checked = true;
                        sectionCustom.value = current;
                    }
                    syncCustomVisibility();
                    updateBadges();
                    updateSectionDescription();
                    checkDuplicate();
                }

                function syncCustomVisibility() {
                    var custom = useCustomChk.checked;
                    sectionCustom.classList.toggle('hidden', !custom);
                    sectionSelect.classList.toggle('hidden', custom);
                }

                function syncHidden() {
                    if (useCustomChk.checked) {
                        sectionHidden.value = sectionCustom.value.trim();
                    } else {
                        sectionHidden.value = sectionSelect.value;
                    }
                    updateBadges();
                    updateSectionDescription();
                    checkDuplicate();
                    if (typeof updateLivePreview === 'function') updateLivePreview();
                }

                function updateBadges() {
                    var pageEl = document.getElementById('preview_page_badge');
                    var sectionEl = document.getElementById('preview_section_badge');
                    if (pageEl) pageEl.textContent = pageLabels[pageSelect.value] || '—';
                    if (sectionEl) sectionEl.textContent = sectionHidden.value || '—';
                }

                function updateSectionDescription() {
                    var section = sectionHidden.value;
                    var descBox = document.getElementById('section_description');
                    var descText = document.getElementById('section_description_text');
                    if (!descBox || !descText) return;
                    var desc = sectionDescriptions[section];
                    if (desc) {
                        descText.textContent = desc;
                        descBox.classList.remove('hidden');
                    } else {
                        descBox.classList.add('hidden');
                    }
                }

                // Duplicate detection: warn (and block submit) when the chosen page+section already exists in another row.
                function checkDuplicate() {
                    var page = pageSelect.value;
                    var section = sectionHidden.value.trim();
                    var dupBox = document.getElementById('section_duplicate');
                    var dupText = document.getElementById('section_duplicate_text');
                    var dupLink = document.getElementById('section_duplicate_edit_link');
                    if (!dupBox) return;

                    if (!page || !section) {
                        dupBox.classList.add('hidden');
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }

                    // Editing the same row is fine — only flag a *different* existing row.
                    var taken = !!(existingSections[page] && existingSections[page][section]);
                    if (taken && editId && existingSections[page][section + '__id'] === editId) {
                        taken = false;
                    }

                    if (taken) {
                        dupText.textContent = 'A content row for page "' + (pageLabels[page] || page) + '" with section "' + section + '" already exists. Adding a duplicate would fail.';
                        if (dupLink) dupLink.href = 'pages.php?action=edit&id=' + (existingSections[page][section + '__id'] || '') + '&filter_page=' + encodeURIComponent(page);
                        dupBox.classList.remove('hidden');
                        if (submitBtn) { submitBtn.disabled = true; submitBtn.classList.add('opacity-50', 'cursor-not-allowed'); }
                    } else {
                        dupBox.classList.add('hidden');
                        if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('opacity-50', 'cursor-not-allowed'); }
                    }
                }

                if (pageSelect && sectionSelect) {
                    pageSelect.addEventListener('change', rebuildSelect);
                    useCustomChk.addEventListener('change', function () { syncCustomVisibility(); syncHidden(); });
                    sectionSelect.addEventListener('change', syncHidden);
                    sectionCustom.addEventListener('input', syncHidden);
                    rebuildSelect();
                    syncHidden();
                }
                if (form) {
                    form.addEventListener('submit', function (e) {
                        if (submitBtn && submitBtn.disabled) { e.preventDefault(); return false; }
                        syncHidden();
                    });
                }
            })();

            (function () {
                var typeSelect = document.getElementById('content_type');
                var textarea = document.getElementById('content');
                var hint = document.getElementById('content_hint');
                var previewWrap = document.getElementById('image_preview_wrap');
                var previewImg = document.getElementById('image_preview');
                var htmlBox = document.getElementById('content_html');
                var toolbar = document.getElementById('html_toolbar');

                var hints = {
                    text:      'Plain text. Line breaks are preserved on the website.',
                    html:      'Rich text editor. Use the toolbar for formatting.',
                    json:      'Structured JSON. Must be valid JSON.',
                    image_url: 'Paste a direct image URL (e.g. uploads/images/photo.jpg).'
                };

                function refreshPreview() {
                    if (typeSelect.value === 'image_url') {
                        previewWrap.classList.remove('hidden');
                        var url = textarea.value.trim();
                        previewImg.src = url;
                        previewImg.classList.toggle('hidden', !url);
                    } else {
                        previewWrap.classList.add('hidden');
                    }
                }

                // Live preview on the right column
                function updateLivePreview() {
                    var type = typeSelect.value;
                    var content = '';
                    if (type === 'html') {
                        content = htmlBox.getAttribute('contenteditable') === 'true' ? htmlBox.innerHTML : textarea.value;
                    } else {
                        content = textarea.value;
                    }

                    // Update type badge
                    var typeBadge = document.getElementById('preview_type_badge');
                    if (typeBadge) typeBadge.textContent = type;

                    var pText = document.getElementById('preview_text');
                    var pHtml = document.getElementById('preview_html');
                    var pJson = document.getElementById('preview_json');
                    var pImage = document.getElementById('preview_image');
                    var pEmpty = document.getElementById('preview_empty');

                    pText.classList.add('hidden');
                    pHtml.classList.add('hidden');
                    pJson.classList.add('hidden');
                    pImage.classList.add('hidden');
                    pEmpty.classList.add('hidden');

                    var trimmed = content.replace(/<[^>]*>/g, '').trim();
                    if (!trimmed && type !== 'image_url') {
                        pEmpty.classList.remove('hidden');
                        return;
                    }

                    if (type === 'text') {
                        pText.textContent = content;
                        pText.classList.remove('hidden');
                    } else if (type === 'html') {
                        pHtml.innerHTML = content;
                        pHtml.classList.remove('hidden');
                    } else if (type === 'json') {
                        try {
                            var parsed = JSON.parse(content);
                            pJson.textContent = JSON.stringify(parsed, null, 2);
                        } catch (e) {
                            pJson.textContent = content;
                        }
                        pJson.classList.remove('hidden');
                    } else if (type === 'image_url') {
                        var url = content.trim();
                        if (url) {
                            document.getElementById('preview_image_img').src = url;
                            pImage.classList.remove('hidden');
                        } else {
                            pEmpty.classList.remove('hidden');
                        }
                    }
                }

                function applyEditor() {
                    var type = typeSelect.value;
                    hint.textContent = hints[type] || '';

                    // Toggle monospace for JSON
                    textarea.classList.toggle('font-mono', type === 'json');

                    // Lightweight HTML editor only for html type
                    if (type === 'html') {
                        textarea.classList.add('hidden');
                        htmlBox.classList.remove('hidden');
                        toolbar.classList.remove('hidden');
                        htmlBox.setAttribute('contenteditable', 'true');
                        htmlBox.innerHTML = textarea.value;
                    } else {
                        // Sync any HTML edits back to the textarea before switching away
                        if (htmlBox.getAttribute('contenteditable') === 'true') {
                            textarea.value = htmlBox.innerHTML;
                        }
                        htmlBox.setAttribute('contenteditable', 'false');
                        htmlBox.classList.add('hidden');
                        toolbar.classList.add('hidden');
                        textarea.classList.remove('hidden');
                    }
                    refreshPreview();
                    updateLivePreview();
                }

                // Toolbar commands operate on the contenteditable surface
                if (toolbar) {
                    toolbar.addEventListener('mousedown', function (e) {
                        var btn = e.target.closest('button[data-cmd]');
                        if (!btn) return;
                        e.preventDefault(); // keep focus in the editor
                        htmlBox.focus();
                        var cmd = btn.getAttribute('data-cmd');
                        var val = btn.getAttribute('data-val');
                        if (cmd === 'createLink') {
                            var url = prompt('Link URL:', 'https://');
                            if (url) document.execCommand('createLink', false, url);
                        } else if (val) {
                            document.execCommand(cmd, false, val);
                        } else {
                            document.execCommand(cmd, false, null);
                        }
                    });
                    toolbar.addEventListener('mouseup', updateLivePreview);
                }

                // Mirror contenteditable -> textarea before submit
                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (typeSelect.value === 'html') {
                            textarea.value = htmlBox.innerHTML;
                        }
                    });
                }

                typeSelect.addEventListener('change', applyEditor);
                textarea.addEventListener('input', function() { refreshPreview(); updateLivePreview(); });
                if (htmlBox) htmlBox.addEventListener('input', updateLivePreview);

                applyEditor();
            })();
            </script>

        <?php else: ?>
            <!-- List View -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Stats + Filters -->
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs rounded-full bg-gray-200 text-gray-700"><i class="fas fa-layer-group mr-1"></i><?php echo $stats['total']; ?> total</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-check mr-1"></i><?php echo $stats['active']; ?> active</span>
                        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800"><i class="fas fa-pause mr-1"></i><?php echo $stats['inactive']; ?> inactive</span>
                        <?php if ($missing_total > 0): ?>
                        <a href="?show_missing=<?php echo $show_missing ? '0' : '1'; ?><?php echo !empty($filter_page) ? '&filter_page=' . urlencode($filter_page) : ''; ?><?php echo !empty($search_q) ? '&q=' . urlencode($search_q) : ''; ?>"
                           class="px-3 py-1 text-xs rounded-full <?php echo $show_missing ? 'bg-amber-200 text-amber-900' : 'bg-amber-100 text-amber-800'; ?> hover:bg-amber-200 transition-colors">
                            <i class="fas fa-triangle-exclamation mr-1"></i><?php echo $missing_total; ?> missing section<?php echo $missing_total === 1 ? '' : 's'; ?>
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800"><i class="fas fa-circle-check mr-1"></i>All known sections seeded</span>
                        <?php endif; ?>
                    </div>
                    <form method="GET" action="pages.php" class="flex flex-col sm:flex-row gap-2">
                        <div class="flex-1 flex flex-col sm:flex-row gap-2">
                            <select name="filter_page" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All pages</option>
                                <?php foreach ($available_pages as $key => $info): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (($_GET['filter_page'] ?? '') === $key) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($info[0]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="filter_type" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All types</option>
                                <?php foreach ($content_types as $tkey => $tmeta): ?>
                                    <option value="<?php echo $tkey; ?>" <?php echo (($_GET['filter_type'] ?? '') === $tkey) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tmeta[0]); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="filter_status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                                <option value="">All statuses</option>
                                <option value="active" <?php echo (($_GET['filter_status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($_GET['filter_status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <div class="relative flex-1">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                                       placeholder="Search section name or content…"
                                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        <?php if (!empty($_GET['filter_page']) || !empty($_GET['q']) || !empty($_GET['filter_type']) || !empty($_GET['filter_status'])): ?>
                        <a href="pages.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors text-sm inline-flex items-center justify-center">
                            <i class="fas fa-times mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </form>

                    <?php if ($show_missing && $missing_total > 0): ?>
                    <!-- Missing sections panel -->
                    <div class="mt-4 border border-amber-200 rounded-lg bg-amber-50 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-amber-900"><i class="fas fa-triangle-exclamation mr-1"></i>Missing sections (known keys with no content row)</h3>
                            <span class="text-xs text-amber-700">Click a key to create it</span>
                        </div>
                        <div class="space-y-2 max-h-60 overflow-auto">
                            <?php foreach ($missing_sections as $mpage => $msecs): ?>
                                <?php if (empty($msecs)) continue; ?>
                                <div>
                                    <div class="text-xs font-semibold text-gray-700 mb-1"><?php echo htmlspecialchars($available_pages[$mpage][0] ?? $mpage); ?></div>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($msecs as $msec): ?>
                                        <a href="?action=add&preset_page=<?php echo urlencode($mpage); ?>&preset_section=<?php echo urlencode($msec); ?>"
                                           class="px-2 py-1 text-xs rounded bg-white border border-amber-300 text-amber-800 hover:bg-amber-100 transition-colors font-mono">
                                            <i class="fas fa-plus mr-1"></i><?php echo htmlspecialchars($msec); ?>
                                        </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Bulk action bar -->
                <div class="px-4 py-2 bg-gray-50 border-b flex flex-wrap items-center gap-2">
                    <button type="button" id="bulk_select_toggle" onclick="toggleBulkMode()" class="px-3 py-1.5 text-xs rounded bg-primary text-white hover:bg-secondary transition-colors">
                        <i class="fas fa-list-check mr-1"></i>Select
                    </button>
                    <span id="bulk_actions" class="hidden flex-wrap items-center gap-2">
                        <span class="text-xs text-gray-500">With selected:</span>
                        <button type="button" onclick="bulkSubmit('activate')" class="px-3 py-1.5 text-xs rounded bg-green-100 text-green-800 hover:bg-green-200 transition-colors"><i class="fas fa-check mr-1"></i>Activate</button>
                        <button type="button" onclick="bulkSubmit('deactivate')" class="px-3 py-1.5 text-xs rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><i class="fas fa-pause mr-1"></i>Deactivate</button>
                        <button type="button" onclick="bulkSubmit('delete')" class="px-3 py-1.5 text-xs rounded bg-red-100 text-red-800 hover:bg-red-200 transition-colors"><i class="fas fa-trash mr-1"></i>Delete</button>
                        <button type="button" onclick="toggleBulkMode()" class="px-3 py-1.5 text-xs rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"><i class="fas fa-times mr-1"></i>Cancel</button>
                    </span>
                    <span id="bulk_count" class="ml-auto text-xs text-gray-400 hidden">0 selected</span>
                </div>

                <!-- Standalone bulk form (populated by JS from row checkboxes — avoids nesting forms) -->
                <form id="bulk_form" method="POST" action="pages.php">
                    <input type="hidden" name="bulk_action" id="bulk_action_field" value="">
                    <div id="bulk_ids_container"></div>
                </form>

                <div class="overflow-auto" style="max-height: 55vh;">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10 bulk-col hidden">
                                <input type="checkbox" id="select_all" class="rounded border-gray-300">
                            </th>
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
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500 mb-2">No content found.</p>
                                    <a href="?action=add" class="text-primary hover:underline text-sm"><i class="fas fa-plus mr-1"></i>Add new content</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_content as $content):
                                $page_label = $available_pages[$content['page_name']][0] ?? $content['page_name'];
                                $page_url   = $available_pages[$content['page_name']][1] ?? null;
                                $anchor     = getSectionAnchor($content['section_name'], $section_anchors);
                                $view_url   = $page_url ? $page_url . ($anchor ? '#' . $anchor : '') : null;
                                $ct_meta    = $content_types[$content['content_type']] ?? [$content['content_type'], 'bg-gray-100 text-gray-800'];
                                $is_img     = $content['content_type'] === 'image_url';
                                $raw        = strip_tags($content['content'] ?? '');
                                $preview    = mb_substr($raw, 0, 120);
                                $truncated  = mb_strlen($raw) > 120;
                            ?>
                                <tr class="hover:bg-gray-50" id="row-<?php echo (int)$content['id']; ?>">
                                    <td class="px-4 py-4 bulk-col hidden">
                                        <input type="checkbox" name="bulk_ids[]" value="<?php echo (int)$content['id']; ?>" class="row-check rounded border-gray-300">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($page_label); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-700 font-mono"><?php echo htmlspecialchars($content['section_name']); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $ct_meta[1]; ?>"><?php echo htmlspecialchars($ct_meta[0]); ?></span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <?php if ($is_img && trim($content['content'] ?? '') !== ''): ?>
                                            <div class="flex items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($content['content']); ?>" alt="" class="h-10 w-10 object-cover rounded border border-gray-200" onerror="this.style.display='none'">
                                                <span class="text-xs text-gray-500 truncate inline-block max-w-[8rem] align-middle font-mono"><?php echo htmlspecialchars(mb_substr($content['content'], 0, 40)); ?><?php echo mb_strlen($content['content']) > 40 ? '…' : ''; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500 truncate inline-block max-w-xs align-middle"><?php echo htmlspecialchars($preview); ?><?php echo $truncated ? '…' : ''; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900"><?php echo (int)$content['display_order']; ?></span>
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
                                        <?php if ($view_url): ?>
                                        <a href="<?php echo $view_url; ?>" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3" title="View on site">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        <form method="POST" action="pages.php" class="inline" onsubmit="return confirm('Delete this content? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $content['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

            <script>
            // Toggle bulk-select mode: shows/hides checkboxes + action buttons
            function toggleBulkMode() {
                var bulkCols = document.querySelectorAll('.bulk-col');
                var actions = document.getElementById('bulk_actions');
                var count = document.getElementById('bulk_count');
                var toggleBtn = document.getElementById('bulk_select_toggle');
                var active = toggleBtn.classList.toggle('bg-secondary');
                toggleBtn.classList.toggle('bg-primary', !active);
                bulkCols.forEach(function (el) { el.classList.toggle('hidden', !active); });
                actions.classList.toggle('hidden', !active);
                actions.classList.toggle('flex', active);
                count.classList.toggle('hidden', !active);
                if (!active) {
                    // Leaving select mode — clear all checks
                    document.querySelectorAll('input.row-check').forEach(function (cb) { cb.checked = false; });
                    var selectAll = document.getElementById('select_all');
                    if (selectAll) selectAll.checked = false;
                }
            }

            // Select-all + bulk count
            (function () {
                var selectAll = document.getElementById('select_all');
                var countEl = document.getElementById('bulk_count');
                function updateCount() {
                    var checks = document.querySelectorAll('input.row-check:checked');
                    countEl.textContent = checks.length + ' selected';
                }
                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        document.querySelectorAll('input.row-check').forEach(function (cb) { cb.checked = selectAll.checked; });
                        updateCount();
                    });
                }
                document.querySelectorAll('input.row-check').forEach(function (cb) {
                    cb.addEventListener('change', updateCount);
                });
            })();

            function bulkSubmit(action) {
                var checks = document.querySelectorAll('input.row-check:checked');
                if (checks.length === 0) { alert('Select at least one row first.'); return; }
                if (action === 'delete' && !confirm('Delete ' + checks.length + ' row(s)? This cannot be undone.')) return;
                var container = document.getElementById('bulk_ids_container');
                container.innerHTML = '';
                checks.forEach(function (cb) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'bulk_ids[]';
                    inp.value = cb.value;
                    container.appendChild(inp);
                });
                document.getElementById('bulk_action_field').value = action;
                document.getElementById('bulk_form').submit();
            }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
