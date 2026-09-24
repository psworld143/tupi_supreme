<?php
/**
 * Progressive login lockout — drop-in brute-force protection.
 *
 * Behaviour: MAX_LOGIN_ATTEMPTS consecutive failures (defined in admin
 * config.php) triggers a lockout that escalates through the steps below,
 * capped at the last step. A successful login resets everything.
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

/** Seconds left on the current lockout (0 = not locked). */
function loginLockoutRemaining(): int {
    return max(0, (int)($_SESSION['login_lockout_until'] ?? 0) - time());
}

/** Attempts left in the current round before the next lockout. */
function loginAttemptsRemaining(): int {
    return MAX_LOGIN_ATTEMPTS - (int)($_SESSION['login_attempts'] ?? 0);
}

/**
 * Record a failed login. Returns the lockout duration in seconds if this
 * failure triggered a lockout, or 0 if attempts remain.
 */
function registerFailedLogin(): int {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] < MAX_LOGIN_ATTEMPTS) {
        return 0;
    }
    $level = min((int)($_SESSION['login_lockout_level'] ?? 0), count(LOGIN_LOCKOUT_STEPS) - 1);
    $duration = LOGIN_LOCKOUT_STEPS[$level];
    $_SESSION['login_lockout_until'] = time() + $duration;
    $_SESSION['login_lockout_level'] = $level + 1;
    $_SESSION['login_attempts'] = 0;
    return $duration;
}

/** Clear all lockout state — call on successful login (after session_regenerate_id). */
function resetLoginLockout(): void {
    unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until'], $_SESSION['login_lockout_level']);
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
