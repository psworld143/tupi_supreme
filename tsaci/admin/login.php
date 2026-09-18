<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
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
                // Login successful
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Update last login
                $update_stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                logActivity('login', 'admin_users', $user['id'], 'User logged in');
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Login page background settings (managed in admin/login-background.php)
$login_bg = [
    'login_bg_enabled'  => '0',
    'login_bg_image'    => '',
    'login_bg_overlay'  => '80',
    'login_bg_gradient' => '1',
    'login_bg_color'    => '#f5f7f5',
];
$db = getDB();
$res = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('login_bg_enabled','login_bg_image','login_bg_overlay','login_bg_gradient','login_bg_color')");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $login_bg[$row['setting_key']] = $row['setting_value'];
    }
}
$login_bg_enabled = $login_bg['login_bg_enabled'] === '1' && trim($login_bg['login_bg_image']) !== '';
$login_bg_gradient = $login_bg['login_bg_gradient'] === '1';
$login_bg_alpha = number_format(max(0, min(100, (int) $login_bg['login_bg_overlay'])) / 100, 2);
$login_bg_url = str_replace(["'", "\\", "\n", "\r", "<", ">"], '', $login_bg['login_bg_image']);
$login_bg_color = preg_match('/^#[0-9a-fA-F]{6}$/', $login_bg['login_bg_color']) ? $login_bg['login_bg_color'] : '#f5f7f5';
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
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', ui-sans-serif, system-ui, sans-serif;
            <?php if ($login_bg_enabled): ?>
                <?php if ($login_bg_gradient): ?>
            background: linear-gradient(135deg, rgba(35, 51, 44, <?php echo $login_bg_alpha; ?>), rgba(61, 122, 102, <?php echo $login_bg_alpha; ?>)), url('<?php echo $login_bg_url; ?>') center/cover no-repeat fixed;
                <?php else: ?>
            background: <?php echo $login_bg_color; ?> url('<?php echo $login_bg_url; ?>') center/cover no-repeat fixed;
                <?php endif; ?>
            <?php else: ?>
                <?php if ($login_bg_gradient): ?>
            background: linear-gradient(135deg, #23332c, #3d7a66);
                <?php else: ?>
            background: <?php echo $login_bg_color; ?>;
                <?php endif; ?>
            <?php endif; ?>
            color: #23332c;
            min-height: 100vh;
        }

        /* Page grain pattern overlay */
        .page-pattern {
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        /* Floating gradient orbs for depth */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.35;
            pointer-events: none;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #8bc34a;
            top: -120px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .orb-2 {
            width: 320px;
            height: 320px;
            background: #3d7a66;
            bottom: -100px;
            left: -80px;
            animation: float 10s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, -30px); }
        }

        /* Eyebrow label */
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 1rem;
            background-color: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-radius: 9999px;
        }

        /* Login card */
        .login-card {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Form inputs */
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

        /* Icon container */
        .icon-container {
            background: linear-gradient(135deg, #3d7a66, #60796e);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Plain (no-gradient) mode — flattens every gradient/white-on-dark element */
        .plain-bg .icon-container {
            background: #3d7a66;
        }
        .plain-bg .eyebrow {
            background-color: rgba(61, 122, 102, 0.12);
            color: #3d7a66;
        }
        .login-card:hover .icon-container {
            transform: scale(1.06) rotate(-3deg);
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
            .orb {
                animation: none;
            }
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden<?php echo $login_bg_gradient ? '' : ' plain-bg'; ?>">
    <?php if ($login_bg_gradient): ?>
    <!-- Floating gradient orbs for depth -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="page-pattern absolute inset-0 opacity-30"></div>
    <?php endif; ?>

    <div class="max-w-md w-full login-card p-10 rounded-3xl shadow-2xl relative z-10 fade-in fade-in-delay-1">
        <div>
            <div class="flex justify-center fade-in fade-in-delay-2">
                <div class="icon-container w-20 h-20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-shield-alt text-3xl text-white"></i>
                </div>
            </div>
            <div class="text-center mt-6 fade-in fade-in-delay-3">
                <span class="eyebrow mb-3">
                    <i class="fas fa-lock text-xs"></i> Secure Access
                </span>
                <h2 class="text-2xl font-bold text-[#23332c] mt-3">
                    Admin Console Login
                </h2>
                <p class="mt-2 text-sm text-[#7d8b84]">
                    Tupi Supreme Activated Carbon, Inc.
                </p>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="mt-8 p-4 rounded-2xl flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 fade-in fade-in-delay-4" role="alert">
                <i class="fas fa-exclamation-circle text-red-500 text-lg mt-0.5"></i>
                <span class="text-sm"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mt-8 p-4 rounded-2xl flex items-start gap-3 bg-[#eef3f0] border border-[#c0ccc5] text-[#23332c] fade-in fade-in-delay-4" role="alert">
                <i class="fas fa-check-circle text-[#3d7a66] text-lg mt-0.5"></i>
                <span class="text-sm"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-5 fade-in fade-in-delay-4" method="POST" action="">
            <div>
                <label for="username" class="block text-sm font-medium text-[#23332c] mb-2">Username or Email</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-[#8a978f] text-sm"></i>
                    <input id="username" name="username" type="text" required 
                           class="form-input w-full pl-11 pr-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c] placeholder-[#8a978f]" 
                           placeholder="Username or Email" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-[#23332c] mb-2">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#8a978f] text-sm"></i>
                    <input id="password" name="password" type="password" required 
                           class="form-input w-full pl-11 pr-4 py-3 border border-[#d6ded9] rounded-xl bg-[#f7faf8] text-[#23332c] placeholder-[#8a978f]" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="w-full flex justify-center items-center gap-2 py-3 px-4 text-sm font-medium rounded-full text-white bg-[#23332c] hover:bg-[#3a4a41] transition-colors">
                    <i class="fas fa-sign-in-alt text-xs"></i>
                    Sign in
                </button>
            </div>
        </form>

        <div class="mt-8 text-center fade-in fade-in-delay-4">
            <a href="../index.php" class="inline-flex items-center gap-2 text-sm text-[#7d8b84] hover:text-[#3d7a66] transition-colors">
                <i class="fas fa-arrow-left text-xs"></i>
                Back to website
            </a>
        </div>
    </div>
</body>
</html>
