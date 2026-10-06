<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// KPI counts
$stats = [];
$result = $db->query("SELECT COUNT(*) as count FROM page_content WHERE is_active = 1");
$stats['pages'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['products'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM services WHERE is_active = 1");
$stats['services'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM case_studies WHERE is_active = 1");
$stats['case_studies'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM gallery_images WHERE is_active = 1");
$stats['gallery'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM resources WHERE is_active = 1");
$stats['resources'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM certifications WHERE is_active = 1");
$stats['certifications'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$stats['messages'] = $result->fetch_assoc()['count'];

// This-month deltas for the trend chips
$this_month = [];
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$this_month['messages'] = $result->fetch_assoc()['count'];
$result = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$this_month['products'] = ($result && ($r = $result->fetch_assoc())) ? $r['count'] : 0;

// Chart data: admin activity per day over the selected range
$range = intval($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90], true)) $range = 30;
$daily = array_fill(0, $range, 0);
$res = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM activity_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL " . ($range - 1) . " DAY) GROUP BY DATE(created_at)");
if ($res) {
    $first = strtotime("-{$range} days +1 day");
    while ($row = $res->fetch_assoc()) {
        $idx = (int) floor((strtotime($row['d']) - $first) / 86400);
        if ($idx >= 0 && $idx < $range) $daily[$idx] = (int) $row['c'];
    }
}
$total_actions = array_sum($daily);

// Build a smooth SVG area chart from the daily series
$chartW = 800; $chartH = 220; $pad = 4;
$max = max(1, max($daily));
$stepX = ($chartW - $pad * 2) / max(1, $range - 1);
$pts = [];
foreach ($daily as $i => $v) {
    $pts[] = [$pad + $i * $stepX, $chartH - $pad - ($v / $max) * ($chartH - $pad * 2 - 20)];
}
$line = '';
foreach ($pts as $i => $p) {
    if ($i === 0) { $line = "M {$p[0]} {$p[1]}"; continue; }
    $prev = $pts[$i - 1];
    $cx = ($prev[0] + $p[0]) / 2;
    $line .= " C {$cx} {$prev[1]}, {$cx} {$p[1]}, {$p[0]} {$p[1]}";
}
$area = $line . " L " . ($pad + ($range - 1) * $stepX) . " {$chartH} L {$pad} {$chartH} Z";

// Recent activity table
$result = $db->query("SELECT al.*, au.username, au.full_name FROM activity_logs al LEFT JOIN admin_users au ON al.user_id = au.id ORDER BY al.created_at DESC LIMIT 10");
$recent_activity = [];
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

$cards = [
    ['Pages',         $stats['pages'],    'Content sections live on the site', 'fa-file-alt',       'pages.php'],
    ['Products',      $stats['products'], 'Active in the catalog',             'fa-cube',           'products.php'],
    ['Services',      $stats['services'], 'Published service offerings',       'fa-concierge-bell', 'services.php'],
    ['Unread Inbox',  $stats['messages'], 'Contact form messages',             'fa-envelope',       'messages.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="../uploads/images/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
<body class="bg-white">
    <?php include 'includes/sidebar.php'; ?>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { opacity: 0; animation: fadeInUp 0.5s ease-out forwards; }
        .fade-in-delay-1 { animation-delay: 0.05s; }
        .fade-in-delay-2 { animation-delay: 0.15s; }
        .fade-in-delay-3 { animation-delay: 0.25s; }
        .fade-in-delay-4 { animation-delay: 0.35s; }
        @media (prefers-reduced-motion: reduce) {
            .fade-in { opacity: 1; animation: none; }
        }
        .chart-tip { opacity: 0; transition: opacity .15s ease; }
        .chart-dot:hover + .chart-tip, .chart-hit:hover .chart-tip { opacity: 1; }
    </style>

    <!-- Main Content -->
    <div class="lg:ml-64 p-6 lg:p-8">
        <!-- Page heading row -->
        <div class="flex items-center justify-between mb-6 fade-in fade-in-delay-1">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900">Dashboard</h1>
                <p class="text-sm text-zinc-500 mt-0.5">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="../index.php" target="_blank" class="inline-flex items-center gap-2 h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                    <i class="fas fa-globe text-xs"></i> View Website
                </a>
                <a href="pages.php?action=add" class="inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors">
                    <i class="fas fa-plus text-xs"></i> Quick Create
                </a>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6 fade-in fade-in-delay-2">
            <?php foreach ($cards as $c): ?>
                <a href="<?php echo $c[4]; ?>" class="group rounded-lg border border-zinc-200 bg-white p-5 hover:border-zinc-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-zinc-500"><?php echo $c[0]; ?></p>
                        <span class="inline-flex items-center gap-1 rounded-md border border-zinc-200 px-1.5 py-0.5 text-[11px] font-medium text-zinc-600">
                            <i class="fas <?php echo $c[3]; ?> text-[9px]"></i>
                            <?php echo $c[0] === 'Unread Inbox' ? ($c[1] > 0 ? 'new' : 'clear') : 'active'; ?>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-zinc-900 tabular-nums"><?php echo number_format($c[1]); ?></p>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-xs text-zinc-500 truncate"><?php echo $c[2]; ?></p>
                        <i class="fas fa-arrow-trend-up text-[10px] text-zinc-300 group-hover:text-[#2c5530] transition-colors"></i>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Activity chart card -->
        <div class="rounded-lg border border-zinc-200 bg-white mb-6 fade-in fade-in-delay-3">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5 pb-4">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900">Admin Activity</h2>
                    <p class="text-xs text-zinc-500 mt-0.5"><?php echo number_format($total_actions); ?> actions in the last <?php echo $range; ?> days</p>
                </div>
                <div class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 p-1">
                    <?php foreach ([90 => 'Last 3 months', 30 => 'Last 30 days', 7 => 'Last 7 days'] as $r => $label): ?>
                        <a href="?range=<?php echo $r; ?>" class="px-3 py-1 rounded-md text-xs font-medium transition-colors <?php echo $range === $r ? 'bg-white text-zinc-900 shadow-sm border border-zinc-200' : 'text-zinc-500 hover:text-zinc-900'; ?>"><?php echo $label; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="px-5 pb-5">
                <svg viewBox="0 0 <?php echo $chartW; ?> <?php echo $chartH; ?>" class="w-full h-56" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2c5530" stop-opacity="0.18" />
                            <stop offset="100%" stop-color="#2c5530" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <?php for ($g = 1; $g <= 3; $g++): ?>
                        <line x1="0" y1="<?php echo $chartH * $g / 4; ?>" x2="<?php echo $chartW; ?>" y2="<?php echo $chartH * $g / 4; ?>" stroke="#f4f4f5" stroke-width="1" />
                    <?php endfor; ?>
                    <path d="<?php echo $area; ?>" fill="url(#areaFill)" />
                    <path d="<?php echo $line; ?>" fill="none" stroke="#2c5530" stroke-width="1.5" stroke-linecap="round" />
                    <?php foreach ($pts as $i => $p): ?>
                        <?php if ($daily[$i] > 0): ?>
                            <circle cx="<?php echo $p[0]; ?>" cy="<?php echo $p[1]; ?>" r="3" fill="#2c5530" opacity="0" class="chart-hit" />
                        <?php endif; ?>
                    <?php endforeach; ?>
                </svg>
                <div class="flex justify-between text-[10px] text-zinc-400 mt-1 px-1">
                    <span><?php echo date('M j', strtotime("-{$range} days +1 day")); ?></span>
                    <span><?php echo date('M j', strtotime('-' . floor($range / 2) . ' days')); ?></span>
                    <span><?php echo date('M j'); ?></span>
                </div>
            </div>
        </div>

        <!-- Recent activity table -->
        <div class="rounded-lg border border-zinc-200 bg-white overflow-hidden fade-in fade-in-delay-4">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-200">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900">Recent Activity</h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Latest actions across the admin console</p>
                </div>
                <a href="settings.php" class="inline-flex items-center gap-2 h-8 px-3 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-900 hover:bg-zinc-100 transition-colors">
                    <i class="fas fa-gear text-[10px]"></i> Customize
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="text-left">User</th>
                            <th class="text-left">Action</th>
                            <th class="text-left">Section</th>
                            <th class="text-left">Record</th>
                            <th class="text-left">When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_activity)): ?>
                            <tr><td colspan="5" class="text-center py-10">
                                <i class="far fa-clock text-zinc-300 text-2xl"></i>
                                <p class="text-sm text-zinc-500 mt-2">No activity recorded yet</p>
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-6 h-6 rounded-full bg-[#e9f1ea] text-[#2c5530] flex items-center justify-center text-[10px] font-medium flex-shrink-0">
                                                <?php echo strtoupper(substr($activity['username'] ?? '?', 0, 1)); ?>
                                            </span>
                                            <span class="font-medium text-zinc-900"><?php echo htmlspecialchars($activity['full_name'] ?: $activity['username']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </span>
                                    </td>
                                    <td class="text-zinc-500"><?php echo htmlspecialchars($activity['table_name'] ?: '—'); ?></td>
                                    <td class="text-zinc-500 tabular-nums"><?php echo $activity['record_id'] ? '#' . (int) $activity['record_id'] : '—'; ?></td>
                                    <td class="text-zinc-500 whitespace-nowrap"><?php echo formatDate($activity['created_at'], 'M d, g:i A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between px-5 py-3 border-t border-zinc-200">
                <p class="text-xs text-zinc-500">0 of <?php echo count($recent_activity); ?> row(s) selected.</p>
                <div class="flex items-center gap-4">
                    <span class="text-xs text-zinc-500">Rows per page <span class="font-medium text-zinc-900">10</span></span>
                    <span class="text-xs text-zinc-500">Page 1 of 1</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh messages count every 30 seconds
        setInterval(function() {
            fetch('api/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.messages !== undefined) {
                        const messagesEl = document.getElementById('stat-messages-count');
                        if (messagesEl) {
                            messagesEl.textContent = data.messages;
                        }
                    }
                })
                .catch(err => console.error('Error fetching stats:', err));
        }, 30000);
    </script>
</body>
</html>
