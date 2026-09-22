<?php
/**
 * logo_pulse_loader.php — reusable logo-pulse loading animation (TSACI brand).
 *
 * Fullscreen page loader (auto-hides when the window finishes loading):
 *     include 'includes/logo_pulse_loader.php';
 *
 * Optional overrides — set BEFORE the include:
 *     $logo_pulse_overlay = true;                                   // render the overlay (default true)
 *     $logo_pulse_mode    = 'fixed';                                // 'fixed' covers the viewport; 'absolute' covers
 *                                                                     // the nearest positioned ancestor (e.g. main
 *                                                                     // content area, leaving a sidebar uncovered)
 *     $logo_pulse_text    = 'Loading...';                           // caption under the animation; '' hides it
 *     $logo_pulse_logo    = 'uploads/images/tupi_supreme_logo.png'; // logo path relative to the calling page
 *     $logo_pulse_color   = '#2c5530';                              // ring colour
 *     $logo_pulse_bg      = '#ffffff';                              // overlay background
 *
 * Inline loader — call anywhere after including this file once:
 *     echo logo_pulse_inline(['size' => 48, 'logo' => 'uploads/images/tupi_supreme_logo.png']);
 */

if (!isset($logo_pulse_overlay)) $logo_pulse_overlay = true;
if (!isset($logo_pulse_mode))    $logo_pulse_mode    = 'fixed';
if (!isset($logo_pulse_text))    $logo_pulse_text    = 'Loading...';
if (!isset($logo_pulse_logo))    $logo_pulse_logo    = 'uploads/images/tupi_supreme_logo.png';
if (!isset($logo_pulse_color))   $logo_pulse_color   = '#2c5530';
if (!isset($logo_pulse_bg))      $logo_pulse_bg      = '#ffffff';

if (!defined('LOGO_PULSE_STYLES')):
    define('LOGO_PULSE_STYLES', true);
?>
<style>
    .lpl-overlay{position:fixed;inset:0;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;background:#fff;transition:opacity .4s ease}
    .lpl-overlay--absolute{position:absolute}
    .lpl-overlay.lpl-hidden{opacity:0;pointer-events:none}
    .lpl-logo-wrap{position:relative;width:96px;height:96px;display:flex;align-items:center;justify-content:center}
    .lpl-logo{position:relative;z-index:1;width:70%;height:70%;object-fit:contain;animation:lpl-pulse 1.6s ease-in-out infinite}
    .lpl-ring{position:absolute;inset:0;border-radius:50%;border:3px solid rgba(44,85,48,.35);animation:lpl-ring 1.8s cubic-bezier(.2,.6,.4,1) infinite}
    .lpl-ring--delay{animation-delay:.9s}
    .lpl-text{font-family:'Poppins',sans-serif;font-size:.85rem;letter-spacing:.12em;text-transform:uppercase;color:#66746c}
    .lpl-inline{display:inline-flex;vertical-align:middle}
    @keyframes lpl-pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.12);opacity:.85}}
    @keyframes lpl-ring{0%{transform:scale(.6);opacity:.9}100%{transform:scale(1.5);opacity:0}}
    @media (prefers-reduced-motion:reduce){.lpl-logo,.lpl-ring{animation:none}}
</style>
<?php endif; ?>

<?php if ($logo_pulse_overlay && !defined('LOGO_PULSE_OVERLAY')): define('LOGO_PULSE_OVERLAY', true); ?>
<div id="logo-pulse-loader" class="lpl-overlay<?php echo $logo_pulse_mode === 'absolute' ? ' lpl-overlay--absolute' : ''; ?>" style="background:<?php echo htmlspecialchars($logo_pulse_bg, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="lpl-logo-wrap">
        <span class="lpl-ring" style="border-color:<?php echo htmlspecialchars($logo_pulse_color, ENT_QUOTES, 'UTF-8'); ?>"></span>
        <span class="lpl-ring lpl-ring--delay" style="border-color:<?php echo htmlspecialchars($logo_pulse_color, ENT_QUOTES, 'UTF-8'); ?>"></span>
        <img src="<?php echo htmlspecialchars($logo_pulse_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="lpl-logo" onerror="this.style.display='none'">
    </div>
    <?php if ($logo_pulse_text !== ''): ?>
    <div class="lpl-text"><?php echo htmlspecialchars($logo_pulse_text, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
</div>
<script>
    (function(){
        var el = document.getElementById('logo-pulse-loader');
        if (!el) return;
        function hide(){ el.classList.add('lpl-hidden'); setTimeout(function(){ el.remove(); }, 450); }
        if (document.readyState === 'complete') { hide(); }
        else { window.addEventListener('load', hide); setTimeout(hide, 5000); }
    })();
</script>
<?php endif; ?>

<?php
if (!function_exists('logo_pulse_inline')) {
    /**
     * Render a small inline logo-pulse loader.
     * Options: size (px, default 48), color (ring colour, default #2c5530),
     *          logo (image path, default uploads/images/tupi_supreme_logo.png).
     */
    function logo_pulse_inline(array $opts = []) {
        $size  = isset($opts['size'])  ? (int)$opts['size'] : 48;
        $color = isset($opts['color']) ? $opts['color']     : '#2c5530';
        $logo  = isset($opts['logo'])  ? $opts['logo']      : 'uploads/images/tupi_supreme_logo.png';
        $esc   = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
        return '<span class="lpl-logo-wrap lpl-inline" style="width:' . $size . 'px;height:' . $size . 'px">'
             . '<span class="lpl-ring" style="border-color:' . $esc($color) . '"></span>'
             . '<img src="' . $esc($logo) . '" alt="" class="lpl-logo" onerror="this.style.display=\'none\'">'
             . '</span>';
    }
}
