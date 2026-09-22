<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['admin_id'];

// ---------------------------------------------------------------------------
// Lightweight auto-migration: add profile_picture column to admin_users if the
// column doesn't exist yet. Idempotent and safe to run on every request.
// ---------------------------------------------------------------------------
$col_check = $db->query("SHOW COLUMNS FROM admin_users LIKE 'profile_picture'");
if ($col_check && $col_check->num_rows === 0) {
    $db->query("ALTER TABLE admin_users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER full_name");
}

// Load the full current user record (including profile_picture + dates)
$stmt = $db->prepare("SELECT id, username, email, full_name, profile_picture, role, is_active, last_login, created_at, updated_at FROM admin_users WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    // Account vanished mid-session — bail out cleanly.
    redirect('logout.php');
}

$error = '';
$success = '';
$active_tab = $_GET['tab'] ?? 'profile';
if (!in_array($active_tab, ['profile', 'security', 'account'], true)) {
    $active_tab = 'profile';
}

// ---------------------------------------------------------------------------
// Handle POST actions
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['post_action'] ?? '';

    // -----------------------------------------------------------------------
    // 1) Update profile (full name, username, email, profile picture)
    // -----------------------------------------------------------------------
    if ($post_action === 'update_profile') {
        $active_tab = 'profile';
        $full_name = trim($_POST['full_name'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        // Basic validation
        if ($full_name === '' || $username === '' || $email === '') {
            $error = 'Full name, username, and email are all required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Uniqueness checks (exclude self) for username + email
            $check = $db->prepare("SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1");
            $check->bind_param("ssi", $username, $email, $user_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = 'That username or email is already in use by another account.';
            }
            $check->close();

            // Handle optional profile picture upload
            $profile_picture_value = $user['profile_picture'];
            if ($error === '' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                if ($file['size'] > MAX_FILE_SIZE) {
                    $error = 'Profile picture is larger than the 10 MB limit.';
                } elseif (!in_array($file['type'], ALLOWED_IMAGE_TYPES, true)) {
                    $error = 'Profile picture must be a JPEG, PNG, GIF, or WebP image.';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $safe_ext = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $ext : 'png';
                    $unique_name = 'profile_' . $user_id . '_' . uniqid('', true) . '.' . $safe_ext;
                    $upload_path = UPLOAD_DIR . 'images/' . $unique_name;

                    if (!is_dir(UPLOAD_DIR . 'images/')) {
                        @mkdir(UPLOAD_DIR . 'images/', 0755, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Remove the previous picture file (if it lived in our uploads dir)
                        if ($profile_picture_value) {
                            $old_abs = realpath(__DIR__ . '/../') . preg_replace('#^/tupi_supreme/tsaci#', '', $profile_picture_value);
                            if ($old_abs && is_file($old_abs) && strpos($old_abs, realpath(UPLOAD_DIR)) === 0) {
                                @unlink($old_abs);
                            }
                        }
                        $profile_picture_value = '/tupi_supreme/tsaci/uploads/images/' . $unique_name;
                    } else {
                        $error = 'Failed to save the uploaded profile picture.';
                    }
                }
            }

            // Persist updates
            if ($error === '') {
                $update = $db->prepare("UPDATE admin_users SET full_name = ?, username = ?, email = ?, profile_picture = ? WHERE id = ?");
                $update->bind_param("ssssi", $full_name, $username, $email, $profile_picture_value, $user_id);
                if ($update->execute()) {
                    // Keep the session username in sync (login.php stores it there)
                    $_SESSION['admin_username'] = $username;
                    logActivity('update_profile', 'admin_users', $user_id, 'Updated own profile');
                    // Refresh local $user so the form reflects saved values immediately
                    $user['full_name']       = $full_name;
                    $user['username']        = $username;
                    $user['email']           = $email;
                    $user['profile_picture'] = $profile_picture_value;
                    $success = 'Profile updated successfully.';
                } else {
                    $error = 'Could not save your profile. Please try again.';
                }
                $update->close();
            }
        }
    }

    // -----------------------------------------------------------------------
    // 2) Change password
    // -----------------------------------------------------------------------
    if ($post_action === 'change_password') {
        $active_tab = 'security';
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Fetch the stored hash fresh (don't trust the cached $user row)
        $pw_stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $pw_stmt->bind_param("i", $user_id);
        $pw_stmt->execute();
        $stored_hash = $pw_stmt->get_result()->fetch_assoc()['password_hash'] ?? '';
        $pw_stmt->close();

        if ($current === '' || $new === '' || $confirm === '') {
            $error = 'All three password fields are required.';
        } elseif (!password_verify($current, $stored_hash)) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($new) < PASSWORD_MIN_LENGTH) {
            $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif ($new === $current) {
            $error = 'New password must be different from your current password.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $update->bind_param("si", $new_hash, $user_id);
            if ($update->execute()) {
                logActivity('change_password', 'admin_users', $user_id, 'Changed own password');
                $success = 'Password changed successfully.';
            } else {
                $error = 'Could not change your password. Please try again.';
            }
            $update->close();
        }
    }
}

// Display helpers
$display_name    = $user['full_name'] ?: $user['username'];
$avatar_initial  = strtoupper(substr($display_name, 0, 1));
$role_label      = ucwords(str_replace('_', ' ', $user['role'] ?? 'Admin'));
$profile_picture = $user['profile_picture'];
$created_display = $user['created_at']  ? formatDate($user['created_at'],  'F d, Y \a\t g:i A') : '—';
$last_login_disp = $user['last_login']  ? formatDate($user['last_login'],  'F d, Y \a\t g:i A') : 'Never';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
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
            .lg\:ml-64 { scrollbar-width: thin; scrollbar-color: #d2dcd5 transparent; }
            .lg\:ml-64::-webkit-scrollbar { width: 8px; }
            .lg\:ml-64::-webkit-scrollbar-track { background: transparent; }
            .lg\:ml-64::-webkit-scrollbar-thumb { background-color: #d2dcd5; border-radius: 4px; border: 2px solid transparent; background-clip: padding-box; }
            .lg\:ml-64::-webkit-scrollbar-thumb:hover { background-color: #c0ccc5; }
        }

        /* Tab cards */
        .settings-tab {
            transition: all 0.2s ease;
        }
        .settings-tab[aria-selected="true"] {
            background-color: #23332c;
            color: #ffffff;
            border-color: #23332c;
            box-shadow: 0 4px 14px -4px rgba(35, 51, 44, 0.35);
        }
        .settings-tab[aria-selected="true"] .tab-icon {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }
        .settings-tab[aria-selected="false"]:hover {
            border-color: #c0ccc5;
            background-color: #f7faf8;
        }

        /* Form inputs (match login.php aesthetic) */
        .form-input {
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }
        .form-input:focus {
            border-color: #3d7a66;
            box-shadow: 0 0 0 3px rgba(61, 122, 102, 0.15);
            outline: none;
        }
        .form-input:hover:not(:focus) {
            border-color: #c0ccc5;
        }

        /* Read-only field styling */
        .form-input:read-only {
            background-color: #f5f7f5;
            color: #45524b;
            cursor: default;
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
                        <i class="fas fa-cog text-primary"></i> Settings
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Manage your profile, security, and account details.</p>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start gap-2">
                <i class="fas fa-exclamation-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-start gap-2">
                <i class="fas fa-check-circle mt-0.5"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Tab cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" role="tablist" aria-label="Settings sections">
            <button type="button" role="tab" id="tab-profile" aria-selected="<?php echo $active_tab === 'profile' ? 'true' : 'false'; ?>"
                    data-tab="profile"
                    class="settings-tab flex items-center gap-3 p-4 rounded-xl border bg-white text-left">
                <span class="tab-icon w-10 h-10 rounded-full bg-[#eaf0ec] text-[#23332c] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user"></i>
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-[15px] leading-tight">Profile</span>
                    <span class="block text-xs opacity-80 truncate">Name, username, email, photo</span>
                </span>
            </button>

            <button type="button" role="tab" id="tab-security" aria-selected="<?php echo $active_tab === 'security' ? 'true' : 'false'; ?>"
                    data-tab="security"
                    class="settings-tab flex items-center gap-3 p-4 rounded-xl border bg-white text-left">
                <span class="tab-icon w-10 h-10 rounded-full bg-[#eaf0ec] text-[#23332c] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt"></i>
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-[15px] leading-tight">Security</span>
                    <span class="block text-xs opacity-80 truncate">Change your password</span>
                </span>
            </button>

            <button type="button" role="tab" id="tab-account" aria-selected="<?php echo $active_tab === 'account' ? 'true' : 'false'; ?>"
                    data-tab="account"
                    class="settings-tab flex items-center gap-3 p-4 rounded-xl border bg-white text-left">
                <span class="tab-icon w-10 h-10 rounded-full bg-[#eaf0ec] text-[#23332c] flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-card"></i>
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-[15px] leading-tight">Account Information</span>
                    <span class="block text-xs opacity-80 truncate">ID, role, dates (read-only)</span>
                </span>
            </button>
        </div>

        <!-- Tab panels -->
        <div class="bg-white rounded-lg shadow-md p-6">

            <!-- ============================ PROFILE ============================ -->
            <section role="tabpanel" aria-labelledby="tab-profile" id="panel-profile" class="tab-panel <?php echo $active_tab !== 'profile' ? 'hidden' : ''; ?>">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-1">
                    <i class="fas fa-user text-primary"></i> Profile
                </h2>
                <p class="text-sm text-gray-500 mb-6">Update how your account appears across the admin console.</p>

                <form method="POST" action="settings.php?tab=profile" enctype="multipart/form-data" class="max-w-2xl">
                    <input type="hidden" name="post_action" value="update_profile">

                    <!-- Profile picture -->
                    <div class="flex items-center gap-5 mb-6">
                        <div class="relative flex-shrink-0">
                            <?php if ($profile_picture): ?>
                                <img id="profile-preview" src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile picture"
                                     class="w-20 h-20 rounded-full object-cover border-2 border-[#e2eae4]">
                            <?php else: ?>
                                <div id="profile-preview" class="w-20 h-20 rounded-full bg-[#23332c] text-white flex items-center justify-center font-semibold text-2xl border-2 border-[#e2eae4]">
                                    <?php echo htmlspecialchars($avatar_initial); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <label for="profile_picture" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#eaf0ec] text-[#23332c] text-sm font-medium hover:bg-[#dde7e1] transition-colors cursor-pointer">
                                <i class="fas fa-upload"></i> Change Photo
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden">
                            <p class="text-xs text-gray-500 mt-2">JPEG, PNG, GIF, or WebP. Max 10 MB.</p>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-5">
                        <label for="full_name" class="block text-sm font-medium text-[#23332c] mb-2">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                    </div>

                    <!-- Username -->
                    <div class="mb-5">
                        <label for="username" class="block text-sm font-medium text-[#23332c] mb-2">Username</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($user['username']); ?>"
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                    </div>

                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-[#23332c] mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="bg-[#23332c] text-white px-5 py-2.5 rounded-xl hover:bg-[#3a4a41] transition-colors inline-flex items-center gap-2 font-medium">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="settings.php?tab=profile" class="px-5 py-2.5 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </form>
            </section>

            <!-- ============================ SECURITY ============================ -->
            <section role="tabpanel" aria-labelledby="tab-security" id="panel-security" class="tab-panel <?php echo $active_tab !== 'security' ? 'hidden' : ''; ?>">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-1">
                    <i class="fas fa-shield-alt text-primary"></i> Security
                </h2>
                <p class="text-sm text-gray-500 mb-6">Choose a strong password of at least <?php echo PASSWORD_MIN_LENGTH; ?> characters.</p>

                <form method="POST" action="settings.php?tab=security" class="max-w-2xl">
                    <input type="hidden" name="post_action" value="change_password">

                    <!-- Current Password -->
                    <div class="mb-5">
                        <label for="current_password" class="block text-sm font-medium text-[#23332c] mb-2">Current Password</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#8a978f] text-sm"></i>
                            <input type="password" id="current_password" name="current_password" required
                                   class="form-input w-full pl-11 pr-11 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-[#8a978f] hover:text-[#23332c]" data-target="current_password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-5">
                        <label for="new_password" class="block text-sm font-medium text-[#23332c] mb-2">New Password</label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-[#8a978f] text-sm"></i>
                            <input type="password" id="new_password" name="new_password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                   class="form-input w-full pl-11 pr-11 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-[#8a978f] hover:text-[#23332c]" data-target="new_password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-[#23332c] mb-2">Confirm New Password</label>
                        <div class="relative">
                            <i class="fas fa-check-double absolute left-4 top-1/2 -translate-y-1/2 text-[#8a978f] text-sm"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                                   class="form-input w-full pl-11 pr-11 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c]">
                            <button type="button" class="toggle-password absolute right-3 top-1/2 -translate-y-1/2 text-[#8a978f] hover:text-[#23332c]" data-target="confirm_password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="bg-[#23332c] text-white px-5 py-2.5 rounded-xl hover:bg-[#3a4a41] transition-colors inline-flex items-center gap-2 font-medium">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <a href="settings.php?tab=security" class="px-5 py-2.5 rounded-xl text-gray-700 hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </form>
            </section>

            <!-- ======================== ACCOUNT INFORMATION ======================== -->
            <section role="tabpanel" aria-labelledby="tab-account" id="panel-account" class="tab-panel <?php echo $active_tab !== 'account' ? 'hidden' : ''; ?>">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2 mb-1">
                    <i class="fas fa-id-card text-primary"></i> Account Information
                </h2>
                <p class="text-sm text-gray-500 mb-6">These details are managed by the system and shown for your reference only.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-2xl">
                    <div>
                        <label for="account_id" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Account ID</label>
                        <input type="text" id="account_id" value="#<?php echo htmlspecialchars($user['id']); ?>" readonly
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f5f7f5] text-[#23332c] font-medium">
                    </div>

                    <div>
                        <label for="account_role" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Role</label>
                        <input type="text" id="account_role" value="<?php echo htmlspecialchars($role_label); ?>" readonly
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f5f7f5] text-[#23332c] font-medium">
                    </div>

                    <div>
                        <label for="account_created" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Account Created</label>
                        <input type="text" id="account_created" value="<?php echo htmlspecialchars($created_display); ?>" readonly
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f5f7f5] text-[#23332c] font-medium">
                    </div>

                    <div>
                        <label for="account_last_login" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Last Login</label>
                        <input type="text" id="account_last_login" value="<?php echo htmlspecialchars($last_login_disp); ?>" readonly
                               class="form-input w-full px-4 py-2.5 border border-[#d6ded9] rounded-xl bg-[#f5f7f5] text-[#23332c] font-medium">
                    </div>
                </div>

                <div class="mt-6 p-4 bg-[#f7faf8] border border-[#e2eae4] rounded-xl flex items-start gap-3 max-w-2xl">
                    <i class="fas fa-info-circle text-[#3d7a66] mt-0.5"></i>
                    <p class="text-sm text-[#45524b]">
                        Need to change your role or account status? Contact another Super Admin. These fields are read-only for security reasons.
                    </p>
                </div>
            </section>

        </div>
    </div>

    <script>
        // Tab switching — no page reload, just toggles panels + active styling.
        (function () {
            var tabs = document.querySelectorAll('.settings-tab');
            var panels = document.querySelectorAll('.tab-panel');

            function activateTab(name) {
                tabs.forEach(function (tab) {
                    var selected = tab.getAttribute('data-tab') === name;
                    tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.id !== 'panel-' + name);
                });
                // Keep the URL in sync without reloading (so a refresh keeps the tab)
                if (history.replaceState) {
                    var url = 'settings.php?tab=' + encodeURIComponent(name);
                    history.replaceState(null, '', url);
                }
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activateTab(tab.getAttribute('data-tab'));
                });
            });

            // Show/hide password toggles
            document.querySelectorAll('.toggle-password').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = document.getElementById(btn.getAttribute('data-target'));
                    if (!input) return;
                    var icon = btn.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) icon.className = 'fas fa-eye-slash';
                    } else {
                        input.type = 'password';
                        if (icon) icon.className = 'fas fa-eye';
                    }
                });
            });

            // Live preview of a newly-selected profile picture
            var fileInput = document.getElementById('profile_picture');
            var preview = document.getElementById('profile-preview');
            if (fileInput && preview) {
                fileInput.addEventListener('change', function () {
                    if (fileInput.files && fileInput.files[0]) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            // Replace the avatar block with an <img> showing the chosen file
                            var img = document.createElement('img');
                            img.src = e.target.result;
                            img.alt = 'Profile picture preview';
                            img.className = 'w-20 h-20 rounded-full object-cover border-2 border-[#e2eae4]';
                            img.id = 'profile-preview';
                            preview.replaceWith(img);
                        };
                        reader.readAsDataURL(fileInput.files[0]);
                    }
                });
            }
        })();
    </script>
</body>
</html>
