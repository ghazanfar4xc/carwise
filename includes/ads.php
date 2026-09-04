<?php
/**
 * AutoPulse — advertisement slots (Module 28).
 * Ad code lives ONLY in the ad_slots table; nothing is hardcoded.
 */
if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

function ad_slot(string $position, string $class = ''): void
{
    static $slots = null;
    if ($slots === null) {
        if (($cached = cache_get('ad_slots', 600)) !== null) {
            $slots = $cached;
        } else {
            $slots = [];
            foreach (db()->query('SELECT position, code, enabled FROM ad_slots')->fetchAll() as $s) {
                $slots[$s['position']] = $s;
            }
            cache_set('ad_slots', $slots);
        }
    }
    $slot = $slots[$position] ?? null;
    if (!$slot || !(int)$slot['enabled'] || trim((string)$slot['code']) === '') return;
    echo '<div class="ad-slot ' . e($class) . '" aria-label="Advertisement">' . $slot['code'] . '</div>';
}
