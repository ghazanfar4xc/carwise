<?php
/**
 * AutoPulse — data access queries (all prepared statements).
 * Every front-end/admin page gets its data through these functions.
 */

if (!defined('APP_RUNNING')) { http_response_code(403); exit('Forbidden'); }

/* ── Brands ────────────────────────────────────────────────────────── */

function get_brands(array $opts = []): array
{
    $sql = 'SELECT b.*, (SELECT COUNT(*) FROM car_models c WHERE c.brand_id = b.id AND c.status = "published") AS model_count
            FROM brands b WHERE b.status = 1';
    $sql .= isset($opts['featured']) && $opts['featured'] ? ' AND b.featured = 1' : '';
    $sql .= ' ORDER BY b.sort_order, b.name';
    return db()->query($sql)->fetchAll();
}

function get_brand(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM brands WHERE slug = ? AND status = 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

/* ── Categories & tags ─────────────────────────────────────────────── */

function get_categories(?string $type = null): array
{
    $sql = 'SELECT c.*, (SELECT COUNT(*) FROM article_categories ac WHERE ac.category_id = c.id) AS article_count
            FROM categories c WHERE c.status = 1';
    $params = [];
    if ($type !== null) { $sql .= ' AND c.type = ?'; $params[] = $type; }
    $sql .= ' ORDER BY c.sort_order, c.name';
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function get_category(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM categories WHERE slug = ? AND status = 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function article_tag_names(int $articleId): array
{
    $st = db()->prepare('SELECT t.name FROM tags t JOIN article_tags at_ ON at_.tag_id = t.id WHERE at_.article_id = ? ORDER BY t.name');
    $st->execute([$articleId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

/* ── Cars ──────────────────────────────────────────────────────────── */

function cars_query(array $f = []): array
{
    $where = ['c.status = "published"'];
    $params = [];
    if (!empty($f['brand'])) { $where[] = 'b.slug = ?'; $params[] = $f['brand']; }
    if (!empty($f['body']))  { $where[] = 'c.body_type = ?'; $params[] = $f['body']; }
    if (!empty($f['fuel']))  { $where[] = 's.fuel_type = ?'; $params[] = $f['fuel']; }
    if (!empty($f['q']))     { $where[] = '(c.name LIKE ? OR b.name LIKE ?)'; $params[] = "%{$f['q']}%"; $params[] = "%{$f['q']}%"; }
    if (!empty($f['ids']))   { $where[] = 'c.id IN (' . implode(',', array_map('intval', (array)$f['ids'])) . ')'; }
    if (!empty($f['category'])) {
        $where[] = 'c.id IN (SELECT cc.car_id FROM car_categories cc JOIN categories cat ON cat.id = cc.category_id WHERE cat.slug = ?)';
        $params[] = $f['category'];
    }
    $order = match ($f['sort'] ?? '') {
        'price_asc'  => 'c.price ASC',
        'price_desc' => 'c.price DESC',
        'popular'    => 'c.views DESC, c.id DESC',
        default      => 'c.year_start DESC, c.id DESC',
    };

    $whereSql = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM car_models c JOIN brands b ON b.id = c.brand_id LEFT JOIN car_specs s ON s.car_id = c.id WHERE $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $per = (int)($f['per'] ?? ITEMS_PER_PAGE);
    $page = max(1, (int)($f['page'] ?? 1));
    $offset = ($page - 1) * $per;

    $sql = "SELECT c.id, c.name, c.slug, c.body_type, c.segment, c.price, c.price_note, c.tagline,
                   c.main_image, c.is_popular, c.year_start, c.views, b.name AS brand, b.slug AS brand_slug,
                   s.power_hp, s.torque_nm, s.engine, s.fuel_type, s.transmission, s.drive_type, s.seating
            FROM car_models c
            JOIN brands b ON b.id = c.brand_id
            LEFT JOIN car_specs s ON s.car_id = c.id
            WHERE $whereSql ORDER BY $order LIMIT $per OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($params);
    return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per)];
}

function get_car(string $brandSlug, string $slug): ?array
{
    $st = db()->prepare('SELECT c.*, b.name AS brand, b.slug AS brand_slug, b.logo AS brand_logo
                         FROM car_models c JOIN brands b ON b.id = c.brand_id
                         WHERE b.slug = ? AND c.slug = ? AND c.status != "draft"');
    $st->execute([$brandSlug, $slug]);
    $car = $st->fetch();
    if (!$car) return null;
    $st = db()->prepare('SELECT * FROM car_specs WHERE car_id = ?');
    $st->execute([$car['id']]);
    $car['specs'] = $st->fetch() ?: [];
    $car['features'] = get_car_features($car['id']);
    $car['images'] = get_car_images($car['id']);
    $st = db()->prepare('SELECT cat.slug, cat.name FROM car_categories cc JOIN categories cat ON cat.id = cc.category_id WHERE cc.car_id = ?');
    $st->execute([$car['id']]);
    $car['categories'] = $st->fetchAll();
    return $car;
}

function get_car_features(int $carId): array
{
    $st = db()->prepare('SELECT feature_group, item FROM car_features WHERE car_id = ? ORDER BY id');
    $st->execute([$carId]);
    $out = [];
    foreach ($st->fetchAll() as $r) $out[$r['feature_group']][] = $r['item'];
    return $out;
}

function get_car_images(int $carId): array
{
    $st = db()->prepare('SELECT * FROM car_images WHERE car_id = ? ORDER BY is_primary DESC, sort_order, id');
    $st->execute([$carId]);
    return $st->fetchAll();
}

function similar_cars(array $car, int $limit = 3): array
{
    $st = db()->prepare('SELECT c.id, c.name, c.slug, c.price, c.price_note, c.main_image, c.body_type, c.year_start,
                                b.name AS brand, b.slug AS brand_slug, s.power_hp, s.fuel_type, s.transmission
                         FROM car_models c JOIN brands b ON b.id = c.brand_id LEFT JOIN car_specs s ON s.car_id = c.id
                         WHERE c.status = "published" AND c.id != ? AND (c.body_type = ? OR c.brand_id = ?)
                         ORDER BY (c.body_type = ?) DESC, c.is_popular DESC, c.views DESC LIMIT ' . (int)$limit);
    $st->execute([$car['id'], $car['body_type'], $car['brand_id'], $car['body_type']]);
    return $st->fetchAll();
}

function popular_cars(int $limit = 6): array
{
    return cars_query(['sort' => 'popular', 'per' => $limit])['items'];
}

/* ── Articles ──────────────────────────────────────────────────────── */

const ARTICLE_LIVE = '(a.status = "published" OR (a.status = "scheduled" AND a.published_at <= NOW()))';

function articles_query(array $f = []): array
{
    $where = [ARTICLE_LIVE];
    $params = [];
    if (!empty($f['category'])) {
        $where[] = 'a.id IN (SELECT ac.article_id FROM article_categories ac JOIN categories c2 ON c2.id = ac.category_id WHERE c2.slug = ?)';
        $params[] = $f['category'];
    }
    if (!empty($f['tag'])) {
        $where[] = 'a.id IN (SELECT at2.article_id FROM article_tags at2 JOIN tags t2 ON t2.id = at2.tag_id WHERE t2.slug = ?)';
        $params[] = $f['tag'];
    }
    if (!empty($f['brand_id'])) { $where[] = 'a.brand_id = ?'; $params[] = (int)$f['brand_id']; }
    if (!empty($f['brand_slug'])) { $where[] = 'a.brand_id = (SELECT id FROM brands WHERE slug = ?)'; $params[] = $f['brand_slug']; }
    if (!empty($f['q'])) { $where[] = '(a.title LIKE ? OR a.excerpt LIKE ?)'; $params[] = "%{$f['q']}%"; $params[] = "%{$f['q']}%"; }
    if (!empty($f['featured'])) { $where[] = 'a.is_featured = 1'; }
    if (!empty($f['author_id'])) { $where[] = 'a.user_id = ?'; $params[] = (int)$f['author_id']; }
    if (!empty($f['ids'])) { $where[] = 'a.id IN (' . implode(',', array_map('intval', (array)$f['ids'])) . ')'; }

    $order = ($f['sort'] ?? '') === 'popular' ? 'a.views DESC, a.id DESC' : 'a.published_at DESC, a.id DESC';
    $whereSql = implode(' AND ', $where);

    $count = db()->prepare("SELECT COUNT(*) FROM articles a WHERE $whereSql");
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $per = (int)($f['per'] ?? ITEMS_PER_PAGE);
    $page = max(1, (int)($f['page'] ?? 1));
    $offset = ($page - 1) * $per;

    $sql = "SELECT a.id, a.title, a.slug, a.excerpt, a.featured_image, a.published_at, a.views, a.is_featured, a.faq,
                   COALESCE(NULLIF(u.display_name, ''), u.username) AS author, u.slug AS author_slug, u.avatar AS author_avatar,
                   c.name AS category, c.slug AS category_slug, b.name AS brand, b.slug AS brand_slug
            FROM articles a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN categories c ON c.id = a.category_id
            LEFT JOIN brands b ON b.id = a.brand_id
            WHERE $whereSql ORDER BY $order LIMIT $per OFFSET $offset";
    $st = db()->prepare($sql);
    $st->execute($params);
    return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => (int)ceil($total / $per)];
}

function get_author(string $slug): ?array
{
    $st = db()->prepare('SELECT u.id, u.username, COALESCE(NULLIF(u.display_name, \'\'), u.username) AS name, u.slug, u.bio, u.avatar,
                                u.role, u.created_at,
                                (SELECT COUNT(*) FROM articles a WHERE a.user_id = u.id AND ' . ARTICLE_LIVE . ') AS article_count
                         FROM users u WHERE u.slug = ? AND u.status = 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function media_meta(string $path): array
{
    static $cache = [];
    if ($path === '') return ['alt' => '', 'caption' => '', 'description' => '', 'title' => '', 'width' => null, 'height' => null];
    if (!isset($cache[$path])) {
        $st = db()->prepare('SELECT alt, caption, description, title, width, height FROM media WHERE path = ?');
        $st->execute([$path]);
        $cache[$path] = $st->fetch() ?: ['alt' => '', 'caption' => '', 'description' => '', 'title' => '', 'width' => null, 'height' => null];
    }
    return $cache[$path];
}

function get_sources(string $type, int $id): array
{
    $st = db()->prepare('SELECT * FROM content_sources WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order, id');
    $st->execute([$type, $id]);
    return $st->fetchAll();
}

function get_answer_blocks(string $type, int $id): array
{
    $st = db()->prepare('SELECT * FROM answer_blocks WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order, id');
    $st->execute([$type, $id]);
    return $st->fetchAll();
}

function source_type_label(string $t): string
{
    return match ($t) {
        'manufacturer' => 'Manufacturer data',
        'government' => 'Government data',
        'regulator' => 'Regulator',
        'research' => 'Research',
        'official_documentation' => 'Official documentation',
        'independent_testing' => 'Independent testing',
        default => 'Reference',
    };
}

/**
 * Auto-301 when a slug changes: old URL -> new URL, chain-safe.
 * $urlPattern uses sprintf-style %s for the slug (e.g. 'articles/%s').
 */
function record_slug_redirect(string $oldSlug, string $newSlug, string $urlPattern, string $notes = 'Auto: slug changed'): void
{
    $old = '/' . ltrim(sprintf($urlPattern, $oldSlug), '/');
    $new = '/' . ltrim(sprintf($urlPattern, $newSlug), '/');
    if ($old === $new || $oldSlug === '' || $newSlug === '') return;
    // Resolve chains: if something already redirects FROM $new, point to its target instead
    $st = db()->prepare('SELECT new_path FROM redirects WHERE old_path = ?');
    $st->execute([$new]);
    if ($final = $st->fetchColumn()) $new = $final;
    // Never create a loop (new must not lead back to old)
    if ($new === $old) return;
    db()->prepare('INSERT INTO redirects (old_path, new_path, status_code, notes) VALUES (?, ?, 301, ?)
                   ON DUPLICATE KEY UPDATE new_path = VALUES(new_path), notes = VALUES(notes)')
        ->execute([$old, $new, $notes]);
    // Collapse chains: anything that pointed to the old URL now points to the final target
    db()->prepare('UPDATE redirects SET new_path = ? WHERE new_path = ?')->execute([$new, $old]);
}

function author_url(array $u): string { return url('author/' . $u['slug']); }

function get_article(string $slug): ?array
{
    $st = db()->prepare('SELECT a.*, COALESCE(NULLIF(u.display_name, \'\'), u.username) AS author, u.slug AS author_slug,
                                u.avatar AS author_avatar, u.bio AS author_bio, c.name AS category, c.slug AS category_slug,
                                b.name AS brand, b.slug AS brand_slug
                         FROM articles a
                         LEFT JOIN users u ON u.id = a.user_id
                         LEFT JOIN categories c ON c.id = a.category_id
                         LEFT JOIN brands b ON b.id = a.brand_id
                         WHERE a.slug = ? AND ' . ARTICLE_LIVE);
    $st->execute([$slug]);
    $a = $st->fetch();
    if (!$a) return null;
    $a['tags'] = article_tag_names((int)$a['id']);
    return $a;
}

function related_articles(array $a, int $limit = 3): array
{
    $st = db()->prepare('SELECT a.id, a.title, a.slug, a.featured_image, a.excerpt, a.published_at,
                                c.name AS category, c.slug AS category_slug
                         FROM articles a LEFT JOIN categories c ON c.id = a.category_id
                         WHERE ' . ARTICLE_LIVE . ' AND a.id != ?
                           AND (a.category_id = ? OR a.brand_id = ?)
                         ORDER BY a.published_at DESC LIMIT ' . (int)$limit);
    $st->execute([$a['id'], $a['category_id'], $a['brand_id']]);
    $items = $st->fetchAll();
    if (count($items) < $limit) { // top up with latest
        $ids = array_merge([$a['id']], array_column($items, 'id'));
        $st = db()->prepare('SELECT a.id, a.title, a.slug, a.featured_image, a.excerpt, a.published_at,
                                    c.name AS category, c.slug AS category_slug
                             FROM articles a LEFT JOIN categories c ON c.id = a.category_id
                             WHERE ' . ARTICLE_LIVE . ' AND a.id NOT IN (' . implode(',', array_map('intval', $ids)) . ')
                             ORDER BY a.published_at DESC LIMIT ' . ((int)$limit - count($items)));
        $st->execute();
        $items = array_merge($items, $st->fetchAll());
    }
    return $items;
}

/* ── Search (all content types) ────────────────────────────────────── */

function search_all(string $q, int $limit = 5): array
{
    $like = "%$q%";
    $st = db()->prepare('SELECT c.id, c.name, c.slug, c.price, c.price_note, c.main_image, c.body_type,
                                b.name AS brand, b.slug AS brand_slug
                         FROM car_models c JOIN brands b ON b.id = c.brand_id
                         WHERE c.status = "published" AND (c.name LIKE ? OR b.name LIKE ?)
                         ORDER BY c.is_popular DESC, c.views DESC LIMIT ' . (int)$limit);
    $st->execute([$like, $like]);
    $cars = $st->fetchAll();

    $st = db()->prepare('SELECT a.id, a.title, a.slug, a.featured_image FROM articles a
                         WHERE ' . ARTICLE_LIVE . ' AND a.title LIKE ? ORDER BY a.published_at DESC LIMIT ' . (int)$limit);
    $st->execute([$like]);
    $articles = $st->fetchAll();

    $st = db()->prepare('SELECT id, name, slug, logo FROM brands WHERE status = 1 AND name LIKE ? LIMIT ' . (int)$limit);
    $st->execute([$like]);
    $brands = $st->fetchAll();

    return ['cars' => $cars, 'articles' => $articles, 'brands' => $brands];
}

/* ── Pages, menus, comments ────────────────────────────────────────── */

function get_page(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM pages WHERE slug = ? AND status = "published"');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}

function footer_pages(): array
{
    $key = 'footer_pages';
    if (($c = cache_get($key, 600)) !== null) return $c;
    $rows = db()->query('SELECT id, title, slug FROM pages WHERE status = "published" AND show_in_footer = 1 ORDER BY sort_order, title')->fetchAll();
    cache_set($key, $rows);
    return $rows;
}

function menu_tree(string $menu = 'main'): array
{
    $key = 'menu_' . $menu;
    if (($c = cache_get($key, 600)) !== null) return $c;
    $st = db()->prepare('SELECT mi.id, mi.label, mi.url, mi.parent_id FROM menu_items mi JOIN menus m ON m.id = mi.menu_id
                         WHERE m.slug = ? AND mi.status = 1 ORDER BY mi.sort_order, mi.id');
    $st->execute([$menu]);
    $rows = $st->fetchAll();
    $tree = [];
    foreach ($rows as $r) {
        if ($r['parent_id']) continue;
        $r['children'] = array_values(array_filter($rows, fn($x) => (int)$x['parent_id'] === (int)$r['id']));
        $tree[] = $r;
    }
    cache_set($key, $tree);
    return $tree;
}

function get_comments(int $articleId): array
{
    $st = db()->prepare('SELECT id, author_name, body, created_at FROM comments
                         WHERE article_id = ? AND status = "approved" ORDER BY created_at DESC LIMIT 50');
    $st->execute([$articleId]);
    return $st->fetchAll();
}
