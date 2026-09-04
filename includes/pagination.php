<?php
/**
 * AutoPulse — reusable pagination component.
 * $base = URL path, $query = existing GET params (page key managed here).
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function render_pagination(int $page, int $pages, string $base, array $query = []): void
{
    if ($pages <= 1) return;
    $make = function (int $p) use ($base, $query): string {
        $query['page'] = $p;
        return url($base) . '?' . http_build_query($query);
    };
    $numbers = [];
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === 1 || $i === $pages || abs($i - $page) <= 1) $numbers[] = $i;
        elseif (end($numbers) !== '…') $numbers[] = '…';
    }
    echo '<nav class="pagination" aria-label="Pagination"><ul>';
    if ($page > 1) echo '<li><a href="' . e($make($page - 1)) . '" aria-label="Previous page">‹</a></li>';
    foreach ($numbers as $n) {
        echo $n === '…'
            ? '<li class="ellipsis" aria-hidden="true">…</li>'
            : '<li><a href="' . e($make($n)) . '"' . ($n === $page ? ' class="active" aria-current="page"' : '') . '>' . $n . '</a></li>';
    }
    if ($page < $pages) echo '<li><a href="' . e($make($page + 1)) . '" aria-label="Next page">›</a></li>';
    echo '</ul></nav>';
}
