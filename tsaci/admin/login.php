<?php
require_once 'config.php';
require_once 'includes/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Progressive login lockout (see includes/session.php)
$lockout_remaining = loginLockoutRemaining();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockout_remaining <= 0) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $failed = false;

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, email, password_hash, full_name, role, is_active FROM admin_users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (!$user['is_active']) {
                $error = 'Your account has been deactivated.';
            } elseif (password_verify($password, $user['password_hash'])) {
                // Login successful — new session ID (fixation defence) + reset lockout state
                session_regenerate_id(true);
                resetLoginLockout();

                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Update last login
                $update_stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                logActivity('login', 'admin_users', $user['id'], 'User logged in');

                // "Remember me" — keep this session alive for 30 days instead
                // of the default 8-hour cookie lifetime.
                if (!empty($_POST['remember'])) {
                    setcookie(session_name(), session_id(), [
                        'expires'  => time() + 30 * 24 * 3600,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }

                header('Location: index.php');
                exit;
            } else {
                $failed = true;
            }
        } else {
            $failed = true;
        }

        if ($failed) {
            if (registerFailedLogin() > 0) {
                $lockout_remaining = loginLockoutRemaining();
            } else {
                $error = 'Invalid username or password. ' . loginAttemptsRemaining() . ' attempt(s) remaining.';
            }
        }
    }
}

if ($lockout_remaining > 0) {
    $error = 'Too many failed attempts. You can try again in ';
    $lockout_active = true;
}

// Login page content & appearance (managed in admin/login-background.php)
$login_defaults = [
    'login_bg_enabled'         => '0',
    'login_bg_image'           => '',
    'login_bg_overlay'         => '80',
    'login_bg_color'           => '#152e1e',
    'login_bg_gradient_from'   => '#2c5530',
    'login_bg_gradient_to'     => '#152e1e',
    'login_logo'               => '../uploads/images/tupi_supreme_logo.png',
    'login_logo_mode'          => 'both',
    'login_logo_alt'           => '../uploads/images/tupi_supreme_logo.png',
    'login_accent_color'       => '#8bc34a',
    'login_form_bg_color'      => '#f3f8f4',
    'login_button_color'       => '#2c5530',
    'login_brand_line'         => 'Official website of Tupi Supreme Activated Carbon, Inc.',
    'login_brand_eyebrow'      => 'TSACI Admin Portal',
    'login_brand_title'        => 'One sign-in for',
    'login_brand_title_accent' => 'your whole website.',
    'login_brand_description'  => 'Pages, products, services, gallery, messages, and settings — manage the entire TSACI website from a single admin console.',
    'login_pill_1_icon'        => 'fa-file-alt',
    'login_pill_1_text'        => 'Content & Pages',
    'login_pill_2_icon'        => 'fa-cube',
    'login_pill_2_text'        => 'Products & Services',
    'login_pill_3_icon'        => 'fa-envelope',
    'login_pill_3_text'        => 'Messages',
    'login_brand_quote'        => 'Premium activated carbon solutions for cleaner water and a greener tomorrow.',
    'login_brand_footer'       => 'Tupi Supreme Activated Carbon, Inc. · Admin Console',
    'login_form_eyebrow'       => 'Admin Access',
    'login_form_heading'       => 'Welcome back,',
    'login_form_subtext'       => 'Sign in to your admin dashboard',
    'login_button_text'        => 'Sign in to Dashboard',
    'login_help_text'          => 'Trouble signing in?',
    'login_help_link_text'     => 'Contact support',
    'login_help_link_url'      => '../contact.php',
];
$L = $login_defaults;
$db = getDB();
$res = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'login\_%'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (array_key_exists($row['setting_key'], $login_defaults)) {
            $L[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// Sanitized/derived values for CSS & attributes
$hex6 = function ($v, $fallback) {
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? $v : $fallback;
};
$strip_bad = function ($v) {
    return str_replace(["'", "\\", "\n", "\r", "<", ">"], '', (string) $v);
};

$login_bg_enabled = $L['login_bg_enabled'] === '1' && trim($L['login_bg_image']) !== '';
$login_bg_alpha   = number_format(max(0, min(100, (int) $L['login_bg_overlay'])) / 100, 2);
$login_bg_url     = $strip_bad($L['login_bg_image']);
$login_bg_color   = $hex6($L['login_bg_color'], $login_defaults['login_bg_color']);
$login_grad_from  = $hex6($L['login_bg_gradient_from'], $login_defaults['login_bg_gradient_from']);
$login_grad_to    = $hex6($L['login_bg_gradient_to'], $login_defaults['login_bg_gradient_to']);
$login_accent     = $hex6($L['login_accent_color'], $login_defaults['login_accent_color']);
$login_form_bg    = $hex6($L['login_form_bg_color'], $login_defaults['login_form_bg_color']);
$login_btn_color  = $hex6($L['login_button_color'], $login_defaults['login_button_color']);

$login_logo = trim($strip_bad($L['login_logo']));
if ($login_logo === '') {
    $login_logo = $login_defaults['login_logo'];
}

// Sign-in panel logo — falls back to the shared logo unless "separate" mode is on
$login_logo_form = $login_logo;
if ($L['login_logo_mode'] === 'separate') {
    $alt = trim($strip_bad($L['login_logo_alt']));
    if ($alt !== '') {
        $login_logo_form = $alt;
    }
}

$login_help_url = trim($strip_bad($L['login_help_link_url']));
if ($login_help_url === '' || stripos($login_help_url, 'javascript:') === 0 || stripos($login_help_url, 'data:') === 0) {
    $login_help_url = '#';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2c5530',
                        secondary: '#4a7c59',
                        accent: '#8bc34a'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            color: #23332c;
        }

        /* Left brand panel — custom image w/ dark overlay when enabled, else gradient */
        .brand-panel {
            <?php if ($login_bg_enabled): ?>
            background-image: linear-gradient(rgba(21, 46, 30, <?php echo $login_bg_alpha; ?>), rgba(21, 46, 30, <?php echo $login_bg_alpha; ?>)), url('<?php echo $login_bg_url; ?>');
            background-color: <?php echo $login_bg_color; ?>;
            background-size: cover;
            background-position: center;
            <?php else: ?>
            background-color: <?php echo $login_bg_color; ?>;
            background-image: linear-gradient(155deg, <?php echo $login_grad_from; ?> 0%, <?php echo $login_grad_to; ?> 100%);
            <?php endif; ?>
        }

        /* Sign-in button — color from settings, darker on hover */
        .login-btn {
            background-color: <?php echo $login_btn_color; ?>;
            transition: filter 0.3s ease, background-color 0.3s ease;
        }
        .login-btn:hover {
            filter: brightness(0.82);
        }

        /* Form inputs */
        .form-input {
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }
        .form-input:focus {
            border-color: <?php echo $login_btn_color; ?>;
            box-shadow: 0 0 0 3px rgba(44, 85, 48, 0.12);
            outline: none;
        }
        .form-input:hover:not(:focus) {
            border-color: #c0ccc5;
        }

        /* Fade-in animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }
        .fade-in-delay-1 { animation-delay: 0.05s; }
        .fade-in-delay-2 { animation-delay: 0.15s; }
        .fade-in-delay-3 { animation-delay: 0.25s; }
        .fade-in-delay-4 { animation-delay: 0.35s; }

        /* Respect reduced-motion preference */
        @media (prefers-reduced-motion: reduce) {
            .fade-in {
                opacity: 1;
                animation: none;
                transform: none;
            }
        }
    </style>
</head>
<body class="min-h-screen" style="background-color: <?php echo $login_form_bg; ?>">

    <div class="flex min-h-screen">

        <!-- Left brand panel (desktop only) -->
        <div class="brand-panel hidden lg:flex w-[62%] xl:w-[66%] relative flex-col p-12 xl:p-16 text-white overflow-hidden">
            <p class="relative z-10 text-sm font-semibold tracking-wide text-white/90"><?php echo htmlspecialchars($L['login_brand_line']); ?></p>

            <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center max-w-xl mx-auto">
                <div class="w-24 h-24 rounded-full bg-white/95 flex items-center justify-center shadow-xl ring-4 ring-white/15 fade-in fade-in-delay-1">
                    <img src="<?php echo htmlspecialchars($login_logo); ?>" alt="Logo" class="w-16 h-16 object-contain">
                </div>
                <?php if (trim($L['login_brand_eyebrow']) !== ''): ?>
                    <p class="mt-8 text-[11px] font-semibold uppercase tracking-[0.25em] text-[#9fd4a8] fade-in fade-in-delay-2"><?php echo htmlspecialchars($L['login_brand_eyebrow']); ?></p>
                <?php endif; ?>
                <h1 class="mt-4 text-4xl xl:text-5xl font-bold leading-tight fade-in fade-in-delay-2">
                    <?php echo htmlspecialchars($L['login_brand_title']); ?><br><span style="color: <?php echo $login_accent; ?>"><?php echo htmlspecialchars($L['login_brand_title_accent']); ?></span>
                </h1>
                <?php if (trim($L['login_brand_description']) !== ''): ?>
                    <p class="mt-5 text-sm xl:text-base text-white/75 leading-relaxed max-w-md fade-in fade-in-delay-3"><?php echo htmlspecialchars($L['login_brand_description']); ?></p>
                <?php endif; ?>
                <?php
                $pills = [];
                for ($i = 1; $i <= 3; $i++) {
                    $pill_text = trim($L["login_pill_{$i}_text"]);
                    if ($pill_text === '') continue;
                    $pill_icon = preg_match('/^fa[a-z0-9-]+$/', $L["login_pill_{$i}_icon"]) ? $L["login_pill_{$i}_icon"] : 'fa-circle';
                    $pills[] = [$pill_icon, $pill_text];
                }
                ?>
                <?php if (!empty($pills)): ?>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3 fade-in fade-in-delay-4">
                        <?php foreach ($pills as $pill): ?>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/25 bg-white/10 text-xs font-medium">
                                <i class="fas <?php echo $pill[0]; ?>" style="color: <?php echo $login_accent; ?>"></i> <?php echo htmlspecialchars($pill[1]); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="relative z-10 text-center">
                <?php if (trim($L['login_brand_quote']) !== ''): ?>
                    <p class="text-sm italic text-white/60">&ldquo;<?php echo htmlspecialchars($L['login_brand_quote']); ?>&rdquo;</p>
                <?php endif; ?>
                <?php if (trim($L['login_brand_footer']) !== ''): ?>
                    <p class="mt-3 text-xs text-white/50"><?php echo htmlspecialchars($L['login_brand_footer']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="flex-1 flex flex-col relative" style="background-color: <?php echo $login_form_bg; ?>">
            <a href="../index.php" title="Back to website" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-white border border-[#e6ece8] flex items-center justify-center text-[#66746c] hover:text-[#23332c] hover:border-[#c0ccc5] transition-colors z-10">
                <i class="fas fa-arrow-left text-xs"></i>
            </a>

            <div class="flex-1 flex items-center justify-center px-6 sm:px-10 py-14">
                <div class="w-full max-w-sm">
                    <div class="text-center fade-in fade-in-delay-1">
                        <div class="w-20 h-20 mx-auto rounded-full bg-white flex items-center justify-center shadow-lg ring-4 ring-[#e2eae4]">
                            <img src="<?php echo htmlspecialchars($login_logo_form); ?>" alt="Logo" class="w-14 h-14 object-contain">
                        </div>
                        <?php if (trim($L['login_form_eyebrow']) !== ''): ?>
                            <p class="mt-6 text-[11px] font-semibold uppercase tracking-[0.25em] text-[#3d7a66]"><?php echo htmlspecialchars($L['login_form_eyebrow']); ?></p>
                        <?php endif; ?>
                        <h2 class="mt-2 text-3xl font-bold text-[#23332c]"><?php echo htmlspecialchars($L['login_form_heading']); ?></h2>
                        <?php if (trim($L['login_form_subtext']) !== ''): ?>
                            <p class="mt-1 text-sm text-[#7d8b84]"><?php echo htmlspecialchars($L['login_form_subtext']); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if ($error): ?>
                        <div class="mt-8 p-4 rounded-2xl flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 fade-in fade-in-delay-2" role="alert">
                            <i class="fas fa-exclamation-circle text-red-500 text-lg mt-0.5"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($error); ?><?php if (!empty($lockout_active)): ?><span id="lockout-countdown" class="font-semibold"></span><?php endif; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mt-8 p-4 rounded-2xl flex items-start gap-3 bg-[#eef3f0] border border-[#c0ccc5] text-[#23332c] fade-in fade-in-delay-2" role="alert">
                            <i class="fas fa-check-circle text-[#3d7a66] text-lg mt-0.5"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="mt-8 space-y-5 fade-in fade-in-delay-3" method="POST" action="">
                        <div>
                            <label for="username" class="block text-xs font-semibold text-[#23332c] mb-1.5">Username or Email</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-[#a8b3ac] text-sm"></i>
                                <input id="username" name="username" type="text" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>
                                       class="form-input w-full pl-11 pr-4 py-3 border border-[#e2eae4] rounded-xl bg-white text-sm text-[#23332c] placeholder-[#a8b3ac] disabled:opacity-60 disabled:cursor-not-allowed"
                                       placeholder="Enter your username or email" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                        </div>
                        <div>
                            <label for="password" class="block text-xs font-semibold text-[#23332c] mb-1.5">Password</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#a8b3ac] text-sm"></i>
                                <input id="password" name="password" type="password" required <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>
                                       class="form-input w-full pl-11 pr-11 py-3 border border-[#e2eae4] rounded-xl bg-white text-sm text-[#23332c] placeholder-[#a8b3ac] disabled:opacity-60 disabled:cursor-not-allowed"
                                       placeholder="Enter your password">
                                <button type="button" id="toggle-password" title="Show password" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#a8b3ac] hover:text-[#66746c] transition-colors">
                                    <i class="fas fa-eye text-sm"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" name="remember" value="1" class="w-4 h-4 rounded accent-[#2c5530]">
                                <span class="text-xs text-[#66746c]">Remember me</span>
                            </label>
                            <?php if (trim($L['login_help_link_text']) !== ''): ?>
                                <a href="<?php echo htmlspecialchars($login_help_url); ?>" class="text-xs font-medium hover:underline" style="color: <?php echo $login_btn_color; ?>"><?php echo htmlspecialchars($L['login_help_link_text']); ?></a>
                            <?php endif; ?>
                        </div>

                        <div>
                            <button type="submit" <?php echo $lockout_remaining > 0 ? 'disabled' : ''; ?>
                                    class="login-btn w-full flex justify-center items-center gap-2 py-3.5 px-4 text-sm font-semibold rounded-xl text-white shadow-md disabled:opacity-60 disabled:cursor-not-allowed">
                                <i class="fas fa-sign-in-alt text-xs"></i>
                                <?php echo htmlspecialchars($L['login_button_text']); ?>
                            </button>
                        </div>
                    </form>

                    <?php if (trim($L['login_help_text']) !== ''): ?>
                        <p class="mt-8 text-center text-xs text-[#8a978f] fade-in fade-in-delay-4">
                            <?php echo htmlspecialchars($L['login_help_text']); ?>
                            <?php if (trim($L['login_help_link_text']) !== ''): ?>
                                <a href="<?php echo htmlspecialchars($login_help_url); ?>" class="font-medium hover:underline" style="color: <?php echo $login_btn_color; ?>"><?php echo htmlspecialchars($L['login_help_link_text']); ?></a>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (trim($L['login_brand_footer']) !== ''): ?>
                <p class="pb-5 text-center text-[11px] text-[#a8b3ac]">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($L['login_brand_footer']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php loginLockoutCountdownScript($lockout_remaining); ?>

    <script>
        // Password visibility toggle
        const pwInput = document.getElementById('password');
        const pwToggle = document.getElementById('toggle-password');
        pwToggle?.addEventListener('click', function() {
            const show = pwInput.type === 'password';
            pwInput.type = show ? 'text' : 'password';
            this.title = show ? 'Hide password' : 'Show password';
            this.innerHTML = '<i class="fas ' + (show ? 'fa-eye-slash' : 'fa-eye') + ' text-sm"></i>';
        });
    </script>
</body>
</html>
