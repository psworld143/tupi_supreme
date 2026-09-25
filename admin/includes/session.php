<?php
/**
 * Progressive login lockout — drop-in brute-force protection.
 *
 * Behaviour: MAX_LOGIN_ATTEMPTS consecutive failures (defined in admin
 * config.php) triggers a lockout that escalates through the steps below,
 * capped at the last step. A successful login resets everything.
 *
 * State is stored per client IP in the `login_attempts` table (auto-created
 * on first use), so clearing cookies or rotating session IDs does NOT reset
 * the counter.
 *
 * Usage in a login page (after config.php):
 *     require_once 'includes/session.php';
 *     $lockout_remaining = loginLockoutRemaining();
 *
 *     // in the POST handler, skip credential check while locked:
 *     if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockout_remaining <= 0) {
 *         // ... on bad credentials:
 *         if (registerFailedLogin() > 0) {
 *             $lockout_remaining = loginLockoutRemaining();   // now locked
 *         } else {
 *             $error = 'Invalid. ' . loginAttemptsRemaining() . ' attempt(s) left.';
 *         }
 *         // ... on success:
 *         session_regenerate_id(true);
 *         resetLoginLockout();
 *     }
 *
 *     // near </body>: emits the countdown script when locked, nothing otherwise
 *     loginLockoutCountdownScript($lockout_remaining);
 */

// Lockout durations in seconds, applied in order then capped at the last.
// 1 min -> 5 min -> 15 min. Edit to taste.
const LOGIN_LOCKOUT_STEPS = [60, 300, 900];

/** DB handle; lazily creates the login_attempts table once per request. */
function loginLockoutStore() {
    static $ready = false;
    $db = getDB();
    if (!$ready) {
        $db->query("CREATE TABLE IF NOT EXISTS `login_attempts` (
            `ip` VARCHAR(45) NOT NULL,
            `attempts` INT NOT NULL DEFAULT 0,
            `lockout_level` INT NOT NULL DEFAULT 0,
            `lockout_until` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $ready = true;
    }
    return $db;
}

function loginLockoutIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/** Current lockout row for this IP, or null. */
function loginLockoutRow(): ?array {
    $db = loginLockoutStore();
    $ip = loginLockoutIp();
    $stmt = $db->prepare("SELECT attempts, lockout_level, lockout_until FROM login_attempts WHERE ip = ?");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

/** Seconds left on the current lockout (0 = not locked). */
function loginLockoutRemaining(): int {
    $row = loginLockoutRow();
    return $row ? max(0, (int)$row['lockout_until'] - time()) : 0;
}

/** Attempts left in the current round before the next lockout. */
function loginAttemptsRemaining(): int {
    $row = loginLockoutRow();
    return MAX_LOGIN_ATTEMPTS - ($row ? (int)$row['attempts'] : 0);
}

/**
 * Record a failed login. Returns the lockout duration in seconds if this
 * failure triggered a lockout, or 0 if attempts remain.
 */
function registerFailedLogin(): int {
    $db = loginLockoutStore();
    $ip = loginLockoutIp();
    $row = loginLockoutRow();
    $attempts = ($row ? (int)$row['attempts'] : 0) + 1;

    if ($attempts < MAX_LOGIN_ATTEMPTS) {
        $stmt = $db->prepare("INSERT INTO login_attempts (ip, attempts) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE attempts = VALUES(attempts)");
        $stmt->bind_param('si', $ip, $attempts);
        $stmt->execute();
        return 0;
    }

    $level = min($row ? (int)$row['lockout_level'] : 0, count(LOGIN_LOCKOUT_STEPS) - 1);
    $duration = LOGIN_LOCKOUT_STEPS[$level];
    $until = time() + $duration;
    $next_level = $level + 1;
    $stmt = $db->prepare("INSERT INTO login_attempts (ip, attempts, lockout_level, lockout_until)
                          VALUES (?, 0, ?, ?)
                          ON DUPLICATE KEY UPDATE attempts = 0,
                              lockout_level = VALUES(lockout_level),
                              lockout_until = VALUES(lockout_until)");
    $stmt->bind_param('sii', $ip, $next_level, $until);
    $stmt->execute();
    return $duration;
}

/** Clear all lockout state for this IP — call on successful login (after session_regenerate_id). */
function resetLoginLockout(): void {
    $db = loginLockoutStore();
    $ip = loginLockoutIp();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
}

/**
 * Emit a live m:ss countdown into #lockout-countdown that reloads the page
 * when the lockout expires. Prints nothing when $remaining <= 0.
 */
function loginLockoutCountdownScript(int $remaining): void {
    if ($remaining <= 0) {
        return;
    }
    ?>
    <script>
        // Lockout countdown — reloads the page when the lockout expires
        (function () {
            var remaining = <?php echo (int) $remaining; ?>;
            var el = document.getElementById('lockout-countdown');
            function tick() {
                if (remaining <= 0) { location.reload(); return; }
                var m = Math.floor(remaining / 60);
                var s = remaining % 60;
                el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                remaining--;
                setTimeout(tick, 1000);
            }
            tick();
        })();
    </script>
    <?php
}
