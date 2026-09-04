<?php
/**
 * AutoPulse — reusable breadcrumbs (with JSON-LD handled by seo.php).
 * $items = [['name' => 'Home', 'url' => ''], ['name' => 'Corolla']]
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function render_breadcrumbs(array $items): void
{
    if (count($items) < 2) return;
    breadcrumb_schema($items);
    echo '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        if ($i < $last && !empty($item['url'])) {
            echo '<li><a href="' . e(url($item['url'])) . '">' . e($item['name']) . '</a></li>';
        } else {
            echo '<li aria-current="page">' . e($item['name']) . '</li>';
        }
    }
    echo '</ol></nav>';
}
