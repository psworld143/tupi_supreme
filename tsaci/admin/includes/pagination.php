<?php
/**
 * Shared admin pagination component.
 *
 * Renders a styled pagination bar (First / Prev / numbers / Next / Last)
 * with an "Showing X–Y of N entries" summary. Pure PHP, no dependencies
 * beyond Tailwind + Font Awesome (already loaded on every admin page).
 *
 * Usage:
 *   require_once __DIR__ . '/includes/pagination.php';
 *   renderPagination([
 *       'current_page' => $current_page_num,
 *       'total_pages'   => $total_pages,
 *       'total_items'   => $total_content,
 *       'per_page'      => $per_page,
 *       'base_query'    => $_GET,   // existing query params to preserve (page is handled automatically)
 *   ]);
 *
 * The bar only renders when there is more than one page.
 */

if (!function_exists('renderPagination')) {
    function renderPagination(array $opts = []) {
        $current_page = max(1, intval($opts['current_page'] ?? 1));
        $total_pages  = max(1, intval($opts['total_pages'] ?? 1));
        $total_items  = intval($opts['total_items'] ?? 0);
        $per_page     = max(1, intval($opts['per_page'] ?? 10));
        $base_query   = $opts['base_query'] ?? [];

        // Nothing to paginate
        if ($total_pages <= 1) {
            return;
        }

        // Clamp current page
        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }

        $offset = ($current_page - 1) * $per_page;
        $showing_from = $offset + 1;
        $showing_to = min($offset + $per_page, $total_items);

        // Build base query string without the 'page' param
        unset($base_query['page']);
        $base_qs = http_build_query($base_query);
        $base_qs = $base_qs ? '&' . $base_qs : '';

        // Helper to build a page URL
        $page_url = function($p) use ($base_qs) {
            return '?' . http_build_query(['page' => $p]) . $base_qs;
        };

        // Numbered page window (show up to 5 pages around current)
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $start_page + 4);
        if ($end_page - $start_page < 4) {
            $start_page = max(1, $end_page - 4);
        }
        ?>
        <div class="px-6 py-4 bg-gray-50 border-t flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-sm text-gray-600">
                Showing <?php echo $showing_from; ?>–<?php echo $showing_to; ?>
                of <?php echo $total_items; ?> entries
            </p>
            <div class="flex items-center gap-1">
                <?php if ($current_page > 1): ?>
                    <a href="<?php echo $page_url(1); ?>" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-200 transition-colors" title="First page">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="<?php echo $page_url($current_page - 1); ?>" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-200 transition-colors" title="Previous page">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm border border-gray-200 rounded-md text-gray-400 cursor-not-allowed">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="px-3 py-1.5 text-sm border border-gray-200 rounded-md text-gray-400 cursor-not-allowed">
                        <i class="fas fa-angle-left"></i>
                    </span>
                <?php endif; ?>

                <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                    <?php if ($p == $current_page): ?>
                        <span class="px-3 py-1.5 text-sm border border-primary bg-primary text-white rounded-md font-medium"><?php echo $p; ?></span>
                    <?php else: ?>
                        <a href="<?php echo $page_url($p); ?>" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-200 transition-colors"><?php echo $p; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo $page_url($current_page + 1); ?>" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-200 transition-colors" title="Next page">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?php echo $page_url($total_pages); ?>" class="px-3 py-1.5 text-sm border border-gray-300 rounded-md hover:bg-gray-200 transition-colors" title="Last page">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-sm border border-gray-200 rounded-md text-gray-400 cursor-not-allowed">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="px-3 py-1.5 text-sm border border-gray-200 rounded-md text-gray-400 cursor-not-allowed">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
