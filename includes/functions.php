<?php
/**
 * AutoPulse — shared helper functions (front + admin + api).
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

/* ── Output & URLs ─────────────────────────────────────────────────── */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function abs_url(string $path = ''): string
{
    $site = setting('site_url');
    if ($site !== '') return rtrim($site, '/') . url($path);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function asset(string $path): string
{
    $file = dirname(__DIR__) . '/assets/' . $path;
    $v = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '1';
    return url('assets/' . $path) . '?v=' . $v;
}

function redirect(string $path): void
{
    // keep preview sessions alive across admin redirects (development only)
    if (defined('PREVIEW_APT') && PREVIEW_APT !== '' && !str_contains($path, 'apt=')
        && str_starts_with($path, 'admin/') && !str_starts_with($path, 'admin/login')) {
        $path .= (str_contains($path, '?') ? '&' : '?') . 'apt=' . PREVIEW_APT;
    }
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/** URL helper for admin-area links. */
function admin_url(string $path = ''): string
{
    $u = url('admin/' . ltrim($path, '/'));
    // Preview sessions (development only) carry a token so admin works
    // inside sandboxed iframes where cookies are blocked.
    if (defined('PREVIEW_APT') && PREVIEW_APT !== '' && !str_contains($u, 'apt=')) {
        $u .= (str_contains($u, '?') ? '&' : '?') . 'apt=' . PREVIEW_APT;
    }
    return $u;
}

/* ── Preview access tokens (development only) ────────────────────────
 * Sandboxed preview iframes block cookies, which kills PHP sessions.
 * An ?apt= token pins the session id server-side so the admin panel
 * stays logged in without cookies. Inactive when APP_ENV=production. */

function apt_valid(string $t): bool
{
    if (!preg_match('/^[a-f0-9]{32}$/', $t)) return false;
    $f = dirname(__DIR__) . '/cache/apt_' . $t . '.php';
    if (!is_file($f)) return false;
    $data = @unserialize((string)@file_get_contents($f));
    return is_array($data) && ($data['expires'] ?? 0) > time();
}

function apt_create(int $userId): string
{
    $t = bin2hex(random_bytes(16));
    @file_put_contents(dirname(__DIR__) . '/cache/apt_' . $t . '.php',
        serialize(['user' => $userId, 'expires' => time() + 43200]));
    return $t;
}

function apt_user(string $t): int
{
    $f = dirname(__DIR__) . '/cache/apt_' . $t . '.php';
    $data = @unserialize((string)@file_get_contents($f));
    return is_array($data) ? (int)($data['user'] ?? 0) : 0;
}

function apt_destroy(string $t): void
{
    @unlink(dirname(__DIR__) . '/cache/apt_' . $t . '.php');
}

/* ── Theme customizer (Admin → Appearance) ─────────────────────────── */

/** Lighten/darken a hex colour by percentage. */
function adjust_brightness(string $hex, int $percent): string
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#d1252f';
    $parts = [];
    for ($i = 0; $i < 3; $i++) {
        $c = hexdec(substr($hex, $i * 2, 2));
        $c = max(0, min(255, (int)round($c + 255 * $percent / 100)));
        $parts[] = str_pad(dechex($c), 2, '0', STR_PAD_LEFT);
    }
    return '#' . implode('', $parts);
}

function normalize_hex(string $hex): string
{
    $hex = ltrim(trim($hex), '#');
    if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? '#' . strtolower($hex) : '';
}

/**
 * Builds the <style> override block from Appearance settings.
 * Empty string when the theme is stock.
 */
function theme_custom_css(): string
{
    $css = '';
    $p = normalize_hex(setting('theme_primary'));
    $s = normalize_hex(setting('theme_secondary'));
    if ($p !== '') {
        $css .= ':root{--color-primary:' . $p . ';--color-primary-dark:' . adjust_brightness($p, -25)
              . ';--color-primary-soft:' . $p . '22}'
              . 'html[data-theme="dark"]{--color-primary:' . adjust_brightness($p, 10) . ';--color-primary-dark:' . adjust_brightness($p, 25) . '}';
    }
    if ($s !== '') {
        $css .= ':root{--color-secondary:' . $s . ';--color-secondary-soft:' . adjust_brightness($s, 12) . '}'
              . 'html[data-theme="dark"]{--color-secondary:' . adjust_brightness($s, -12) . '}';
    }
    $radius = setting('theme_radius');
    if ($radius === 'sharp')    $css .= ':root{--radius-sm:2px;--radius-md:4px;--radius-lg:8px}';
    if ($radius === 'rounded')  $css .= ':root{--radius-sm:10px;--radius-md:16px;--radius-lg:22px}';
    if (setting('theme_chamfer', '1') === '0') $css .= '.btn-primary,.btn-dark{clip-path:none;border-radius:var(--radius-sm)}';
    if (setting('theme_font_scale') === 'compact') $css .= 'html{font-size:95%}';
    if (setting('theme_font_scale') === 'large')   $css .= 'html{font-size:106%}';
    return $css === '' ? '' : '<style id="theme-custom">' . $css . '</style>';
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text ?? '', '-') ?: 'item';
}

function unique_slug(PDO $db, string $table, string $slug, int $id = 0, string $extraWhere = '', array $extraParams = []): string
{
    $base = $slug;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = ?" . ($extraWhere ? " AND $extraWhere" : '') . ($id ? " AND id != ?" : '');
        $params = array_merge([$slug], $extraParams, $id ? [$id] : []);
        $st = $db->prepare($sql);
        $st->execute($params);
        if ((int)$st->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $i++;
    }
}

/* ── Settings (file-cached) ────────────────────────────────────────── */

function load_settings(): array
{
    if (($cached = cache_get('settings', 3600)) !== null) return $cached;
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
    $settings = [];
    foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
    cache_set('settings', $settings);
    return $settings;
}

function setting(string $key, string $default = ''): string
{
    $s = $GLOBALS['site_settings'] ?? load_settings();
    return $s[$key] ?? $default;
}

function save_settings(array $pairs): void
{
    $st = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($pairs as $k => $v) $st->execute([$k, (string)$v]);
    cache_forget('settings');
    $GLOBALS['site_settings'] = load_settings();
}

/* ── Tiny file cache (InfinityFree-safe) ───────────────────────────── */

function cache_path(string $key): string
{
    return dirname(__DIR__) . '/cache/' . preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)) . '.cache';
}

function cache_get(string $key, int $maxAge = 600)
{
    $f = cache_path($key);
    if (!is_file($f) || time() - filemtime($f) > $maxAge) return null;
    $data = @file_get_contents($f);
    return $data === false ? null : unserialize($data);
}

function cache_set(string $key, $data): void
{
    @file_put_contents(cache_path($key), serialize($data), LOCK_EX);
}

function cache_forget(string $key): void
{
    @unlink(cache_path($key));
}

function cache_clear_all(): int
{
    $n = 0;
    foreach (glob(dirname(__DIR__) . '/cache/*.cache') ?: [] as $f) { @unlink($f); $n++; }
    return $n;
}

/* ── CSRF ──────────────────────────────────────────────────────────── */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_require(): void
{
    if (!verify_csrf()) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            json_out(['success' => false, 'message' => 'Security token expired. Please reload the page and try again.']);
        }
        http_response_code(419);
        exit('Security token expired. Please go back and try again.');
    }
}

/* ── Flash messages → toasts ───────────────────────────────────────── */

function flash_set(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $msg];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) return '';
    $out = '<div id="flash-data" data-flash="' . e(json_encode($_SESSION['flash'], JSON_UNESCAPED_UNICODE)) . '"></div>';
    unset($_SESSION['flash']);
    return $out;
}

/* ── Request helpers ───────────────────────────────────────────────── */

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function rate_limit(string $name, int $max, int $windowSeconds = 600): bool
{
    $f = dirname(__DIR__) . '/cache/rl_' . md5($name . '|' . client_ip()) . '.php';
    $attempts = [];
    if (is_file($f)) {
        $raw = @file_get_contents($f);
        if ($raw !== false && str_starts_with($raw, '<?php die;')) {
            $attempts = unserialize(substr($raw, 10)) ?: [];
            $attempts = array_values(array_filter($attempts, fn($t) => time() - $t < $windowSeconds));
        }
    }
    if (count($attempts) >= $max) return false;
    $attempts[] = time();
    @file_put_contents($f, '<?php die;' . serialize($attempts), LOCK_EX);
    return true;
}

function post(string $key, string $default = ''): string
{
    return trim((string)($_POST[$key] ?? $default));
}

function get_int(string $key, int $default = 0): int
{
    return (int)($_GET[$key] ?? $default);
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ── Formatting ────────────────────────────────────────────────────── */

function format_price($val, ?string $note = ''): string
{
    $val = (float)$val;
    $note = (string)$note;
    if ($val <= 0) return $note !== '' ? e($note) : 'Price on request';
    return e(setting('currency', DEFAULT_CURRENCY)) . ' ' . number_format($val);
}

function format_date(?string $dt): string
{
    if (!$dt) return '';
    return date('d M Y', strtotime($dt));
}

function time_ago(?string $dt): string
{
    if (!$dt) return '';
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return format_date($dt);
}

function excerpt_text(string $html, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

function reading_time(string $html): int
{
    return max(1, (int)ceil(str_word_count(strip_tags($html)) / 200));
}

/* ── Content helpers ───────────────────────────────────────────────── */

/** Adds ids to h2/h3 headings and returns [html, toc[]]. */
function content_ids(string $html): array
{
    $toc = [];
    $html = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/i', function ($m) use (&$toc) {
        $title = trim(strip_tags($m[3]));
        $id = slugify($title);
        $final = $id;
        $i = 2;
        while (isset($toc[$final])) $final = $id . '-' . $i++;
        $toc[$final] = ['id' => $final, 'level' => (int)$m[1], 'title' => $title];
        return '<h' . $m[1] . $m[2] . ' id="' . $final . '">' . $m[3] . '</h' . $m[1] . '>';
    }, $html);
    return [$html ?? '', array_values($toc)];
}

function faq_items(?string $json): array
{
    if (!$json) return [];
    $items = json_decode($json, true);
    return is_array($items) ? array_values(array_filter($items, fn($i) => !empty($i['q']))) : [];
}

function img_url(?string $path, string $placeholder = 'assets/images/placeholder.svg'): string
{
    $path = $path ?? '';
    if ($path !== '' && is_file(dirname(__DIR__) . '/' . ltrim($path, '/'))) return url($path);
    return url($placeholder);
}

/* ── URL builders for content types ────────────────────────────────── */

function car_url(array $car): string
{
    return url('cars/' . ($car['brand_slug'] ?? $car['brand'] ?? '') . '/' . ($car['car_slug'] ?? $car['slug']));
}

function brand_url(array $b): string  { return url('brands/' . $b['slug']); }
function article_url(array $a): string { return url('articles/' . $a['slug']); }
function category_url(array $c): string { return url('category/' . $c['slug']); }

/* ── Secure image upload ───────────────────────────────────────────── */

/**
 * Validates and stores an uploaded image in uploads/<subdir>/.
 * Returns ['ok'=>true,'file'=>'uploads/..'] or ['ok'=>false,'error'=>'..'].
 */
function upload_image(array $file, string $subdir = 'media', int $maxWidth = 1920): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed or no file selected.'];
    }
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Image is larger than ' . round(UPLOAD_MAX_BYTES / 1048576) . ' MB.'];
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, WEBP and GIF images are allowed.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== $allowed[$ext]) {
        return ['ok' => false, 'error' => 'File content does not match its extension.'];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'File is not a valid image.'];
    }
    $dir = dirname(__DIR__) . '/uploads/' . preg_replace('/[^a-z0-9_\-]/', '', $subdir);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded file.'];
    }
    // Downscale very large originals with GD when available.
    if (function_exists('imagecreatetruecolor')) {
        $info = @getimagesize($dest);
        if ($info && (int)$info[0] > $maxWidth) {
            $img = match ($info[2]) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($dest),
                IMAGETYPE_PNG  => @imagecreatefrompng($dest),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($dest) : null,
                default        => null,
            };
            if ($img) {
                $h = (int)round($info[1] * $maxWidth / $info[0]);
                $out = imagecreatetruecolor($maxWidth, $h);
                if ($info[2] === IMAGETYPE_PNG) {
                    imagealphablending($out, false);
                    imagesavealpha($out, true);
                }
                imagecopyresampled($out, $img, 0, 0, 0, 0, $maxWidth, $h, (int)$info[0], (int)$info[1]);
                if ($info[2] === IMAGETYPE_JPEG) imagejpeg($out, $dest, 84);
                elseif ($info[2] === IMAGETYPE_PNG) imagepng($out, $dest, 6);
                elseif ($info[2] === IMAGETYPE_WEBP && function_exists('imagewebp')) imagewebp($out, $dest, 84);
                imagedestroy($img);
                imagedestroy($out);
            }
        }
    }
    return ['ok' => true, 'file' => 'uploads/' . basename($dir) . '/' . $name];
}

function human_size(int $bytes): string
{
    return $bytes > 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int)($bytes / 1024)) . ' KB';
}

/* ── Site logo ─────────────────────────────────────────────────────── */

/**
 * Default brand logo, inlined so `currentColor` adapts to light/dark themes.
 * If the admin uploaded a custom logo, render it as an <img> instead.
 */
function render_logo(string $class = 'logo-svg', int $height = 36): void
{
    $logo = setting('logo');
    if ($logo !== '' && $logo !== 'assets/images/logo.svg' && is_file(dirname(__DIR__) . '/' . ltrim($logo, '/'))) {
        echo '<img src="' . e(url($logo)) . '" alt="' . e(setting('site_name', 'AutoPulse')) . '" style="height:' . (int)$height . 'px;width:auto">';
        return;
    }
    // inline SVG (unique tachometer + pulse mark)
    $gid = 'apb' . preg_replace('/[^a-z0-9]/i', '', $class);
    echo '<svg class="' . e($class) . '" viewBox="0 0 188 52" role="img" aria-label="' . e(setting('site_name', 'AutoPulse')) . '">'
       . '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#16283e"/><stop offset="1" stop-color="#0b1524"/></linearGradient></defs>'
       . '<rect x="1" y="2" width="48" height="48" rx="13" fill="url(#' . $gid . ')"/>'
       . '<path d="M11 37a14.5 14.5 0 1 1 28 0" fill="none" stroke="#7f93aa" stroke-width="3" stroke-linecap="round" opacity=".9"/>'
       . '<path d="M35.8 24.9a14.5 14.5 0 0 1 3.2 12.1" fill="none" stroke="#e43849" stroke-width="3" stroke-linecap="round"/>'
       . '<line x1="25" y1="35" x2="36" y2="20.5" stroke="#e43849" stroke-width="3.4" stroke-linecap="round"/>'
       . '<circle cx="25" cy="35" r="3.1" fill="#fff"/>'
       . '<text x="60" y="33" font-family="\'Barlow Condensed\',\'Arial Narrow\',Arial,sans-serif" font-weight="700" font-size="27" letter-spacing="1.5" fill="currentColor">AUTO</text>'
       . '<text x="116" y="33" font-family="\'Barlow Condensed\',\'Arial Narrow\',Arial,sans-serif" font-weight="700" font-size="27" letter-spacing="1.5" fill="#d1252f">PULSE</text>'
       . '<path d="M61 42h22l5-7 7 13 5-10 4 5h68" fill="none" stroke="#d1252f" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" opacity=".9"/>'
       . '</svg>';
    unset($height);
}
