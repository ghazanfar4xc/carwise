<?php
/** AutoPulse admin — car editor with tabs: General · Specs · Features · Images · SEO (Module 17). */
require __DIR__ . '/includes/bootstrap.php';
require_admin();

$id = get_int('id', 0);
$car = null;
$specs = [];
if ($id) {
    $st = db()->prepare('SELECT * FROM car_models WHERE id = ?');
    $st->execute([$id]);
    $car = $st->fetch();
    if (!$car) { flash_set('error', 'Car not found.'); redirect('admin/cars.php'); }
    $st = db()->prepare('SELECT * FROM car_specs WHERE car_id = ?');
    $st->execute([$id]);
    $specs = $st->fetch() ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brandId = (int)post('brand_id');
    $name = mb_substr(post('name'), 0, 120);
    $slug = slugify(post('slug') !== '' ? post('slug') : $name);

    // slug is unique per brand
    for ($i = 0; $i < 50; $i++) {
        $st = db()->prepare('SELECT COUNT(*) FROM car_models WHERE brand_id = ? AND slug = ?' . ($id ? ' AND id != ?' : ''));
        $st->execute($id ? [$brandId, $slug, $id] : [$brandId, $slug]);
        if ((int)$st->fetchColumn() === 0) break;
        $slug = rtrim($slug, '-0123456789') . '-' . ($i + 2);
    }

    $pros = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['pros'] ?? '')))));
    $cons = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['cons'] ?? '')))));
    $faq = [];
    foreach (($_POST['faq_q'] ?? []) as $i => $qq) {
        $qq = trim((string)$qq); $aa = trim((string)($_POST['faq_a'][$i] ?? ''));
        if ($qq !== '' && $aa !== '') $faq[] = ['q' => $qq, 'a' => $aa];
    }

    $data = [
        'brand_id'         => $brandId,
        'name'             => $name,
        'slug'             => $slug,
        'generation'       => mb_substr(post('generation'), 0, 60) ?: null,
        'year_start'       => (int)post('year_start') ?: null,
        'body_type'        => mb_substr(post('body_type'), 0, 50) ?: null,
        'segment'          => mb_substr(post('segment'), 0, 50) ?: null,
        'price'            => post('price') !== '' ? (float)preg_replace('/[^\d.]/', '', post('price')) : null,
        'price_note'       => mb_substr(post('price_note'), 0, 120) ?: null,
        'tagline'          => mb_substr(post('tagline'), 0, 190) ?: null,
        'overview'         => (string)($_POST['overview'] ?? ''),
        'pros'             => $pros ? json_encode($pros, JSON_UNESCAPED_UNICODE) : null,
        'cons'             => $cons ? json_encode($cons, JSON_UNESCAPED_UNICODE) : null,
        'faq'              => $faq ? json_encode($faq, JSON_UNESCAPED_UNICODE) : null,
        'main_image'       => post('main_image') ?: null,
        'status'           => in_array(post('status'), ['published', 'draft', 'upcoming'], true) ? post('status') : 'draft',
        'is_featured'      => post('is_featured') === '1' ? 1 : 0,
        'is_popular'       => post('is_popular') === '1' ? 1 : 0,
        'seo_title'        => mb_substr(post('seo_title'), 0, 150) ?: null,
        'meta_description' => mb_substr(post('meta_description'), 0, 300) ?: null,
    ];

    if ($id) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        db()->prepare("UPDATE car_models SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        flash_set('success', 'Car updated.');
    } else {
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        db()->prepare("INSERT INTO car_models ($cols) VALUES ($marks)")->execute(array_values($data));
        $id = (int)db()->lastInsertId();
        flash_set('success', 'Car created.');
    }

    // Specs (1:1 row)
    $specCols = ['engine','displacement_cc','fuel_type','power_hp','torque_nm','transmission','drive_type','acceleration_s','top_speed_kmh',
                 'fuel_tank_l','mileage_city_kml','mileage_highway_kml','battery_kwh','range_km','length_mm','width_mm','height_mm',
                 'wheelbase_mm','ground_clearance_mm','curb_weight_kg','boot_space_l','seating','doors'];
    $decimalCols = ['acceleration_s', 'mileage_city_kml', 'mileage_highway_kml', 'battery_kwh'];
    $specData = ['car_id' => $id];
    foreach ($specCols as $c) {
        if (in_array($c, ['engine', 'fuel_type', 'transmission', 'drive_type'], true)) continue; // text fields handled below
        $v = post($c);
        $specData[$c] = ($v !== '' && $v !== null) ? (in_array($c, $decimalCols, true) ? (float)$v : (int)$v) : null;
    }
    $specData['engine'] = mb_substr(post('engine'), 0, 120) ?: null;
    $specData['fuel_type'] = mb_substr(post('fuel_type'), 0, 40) ?: null;
    $specData['transmission'] = mb_substr(post('transmission'), 0, 80) ?: null;
    $specData['drive_type'] = mb_substr(post('drive_type'), 0, 30) ?: null;
    db()->prepare('DELETE FROM car_specs WHERE car_id = ?')->execute([$id]);
    $cols = implode(', ', array_keys($specData));
    $marks = implode(', ', array_fill(0, count($specData), '?'));
    db()->prepare("INSERT INTO car_specs ($cols) VALUES ($marks)")->execute(array_values($specData));

    // Features: textarea lines per group
    db()->prepare('DELETE FROM car_features WHERE car_id = ?')->execute([$id]);
    $stF = db()->prepare('INSERT INTO car_features (car_id, feature_group, item) VALUES (?, ?, ?)');
    foreach (['safety', 'technology', 'interior', 'exterior'] as $group) {
        foreach (explode("\n", (string)($_POST['feat_' . $group] ?? '')) as $item) {
            $item = trim(mb_substr($item, 0, 150));
            if ($item !== '') $stF->execute([$id, $group, $item]);
        }
    }

    // Categories (car-type)
    db()->prepare('DELETE FROM car_categories WHERE car_id = ?')->execute([$id]);
    $stC = db()->prepare('INSERT IGNORE INTO car_categories (car_id, category_id) VALUES (?, ?)');
    foreach ((array)($_POST['car_categories'] ?? []) as $cid) if (ctype_digit((string)$cid)) $stC->execute([$id, (int)$cid]);

    // Gallery images: keep existing (minus checked removals), add newly picked paths
    db()->prepare('DELETE FROM car_images WHERE car_id = ?')->execute([$id]);
    $stI = db()->prepare('INSERT INTO car_images (car_id, path, alt, sort_order, is_primary) VALUES (?, ?, ?, ?, 0)');
    $remove = array_map('trim', (array)($_POST['remove_images'] ?? []));
    $keepExisting = array_values(array_filter(
        array_map('trim', explode("\n", (string)($_POST['keep_images'] ?? ''))),
        fn($p) => $p !== '' && !in_array($p, $remove, true)
    ));
    $paths = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['gallery'] ?? ''))), fn($p) => $p !== ''));
    $galleryAlts = array_values(array_filter(array_map('trim', (array)($_POST['gallery_alt'] ?? []))));
    $i = 0;
    foreach ($keepExisting as $path) {
        $stI->execute([$id, $path, $galleryAlts[$i] ?? null, $i, ($path === post('main_image')) ? 1 : 0]);
        $i++;
    }
    foreach ($paths as $path) {
        $stI->execute([$id, $path, null, $i, ($path === post('main_image') && $i === 0) ? 1 : 0]);
        $i++;
    }

    cache_forget('sitemap');
    redirect('admin/car-edit.php?id=' . $id);
}

$brands = get_brands();
$carCats = get_categories('car');
$carCatIds = [];
if ($id) {
    $st = db()->prepare('SELECT category_id FROM car_categories WHERE car_id = ?');
    $st->execute([$id]);
    $carCatIds = $st->fetchAll(PDO::FETCH_COLUMN);
}
$features = $id ? get_car_features($id) : [];
$images = $id ? get_car_images($id) : [];
$faq = $car ? faq_items($car['faq']) : [];

function spec_val(string $key, array $specs): string
{
    return e((string)($_POST[$key] ?? $specs[$key] ?? ''));
}

$ADMIN_ACTIVE = 'cars';
$ADMIN_TITLE = $car ? ('Edit: ' . $car['name']) : 'New car';
include __DIR__ . '/includes/header.php';
?>
<form method="post" class="admin-form" novalidate>
    <?= csrf_field() ?>
    <div class="tabs sticky-tabs" role="tablist">
        <button type="button" class="tab active" data-tab="general">General</button>
        <button type="button" class="tab" data-tab="specs">Specifications</button>
        <button type="button" class="tab" data-tab="features">Features</button>
        <button type="button" class="tab" data-tab="images">Images</button>
        <button type="button" class="tab" data-tab="seo">SEO &amp; FAQ</button>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-left:auto"><?= $car ? 'Save changes' : 'Create car' ?></button>
    </div>

    <!-- General -->
    <div class="tab-panel" data-panel="general">
        <section class="panel">
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-brand">Brand *</label>
                    <select id="c-brand" name="brand_id" class="select" required>
                        <option value="">— select —</option>
                        <?php $curBrand = (int)($_POST['brand_id'] ?? $car['brand_id'] ?? 0); ?>
                        <?php foreach ($brands as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= $curBrand === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="c-name">Model name *</label>
                    <input type="text" id="c-name" name="name" class="input" required maxlength="120" value="<?= fv('name', $car['name'] ?? '') ?>" data-slug-source="#c-slug">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-slug">Slug</label>
                    <input type="text" id="c-slug" name="slug" class="input" value="<?= fv('slug', $car['slug'] ?? '') ?>">
                    <p class="hint">URL: /cars/&lt;brand&gt;/<span class="slug-preview"><?= e($car['slug'] ?? 'your-slug') ?></span></p>
                </div>
                <div class="form-field">
                    <label for="c-generation">Generation</label>
                    <input type="text" id="c-generation" name="generation" class="input" value="<?= fv('generation', $car['generation'] ?? '') ?>" placeholder="e.g. 12th Gen (E210)">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-year">Year</label>
                    <input type="number" id="c-year" name="year_start" class="input" min="1980" max="2100" value="<?= fv('year_start', $car['year_start'] ?? '') ?>">
                </div>
                <div class="form-field">
                    <label for="c-body">Body type</label>
                    <select id="c-body" name="body_type" class="select">
                        <?php $curBody = (string)($_POST['body_type'] ?? $car['body_type'] ?? 'Sedan'); ?>
                        <?php foreach (['Sedan', 'Hatchback', 'SUV', 'Crossover', 'Coupe', 'Pickup', 'Van'] as $bt): ?>
                            <option <?= $curBody === $bt ? 'selected' : '' ?>><?= $bt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-price">Price (<?= e(setting('currency', DEFAULT_CURRENCY)) ?>)</label>
                    <input type="number" id="c-price" name="price" class="input" step="1" min="0" value="<?= fv('price', $car['price'] ?? '') ?>">
                </div>
                <div class="form-field">
                    <label for="c-price-note">Price note</label>
                    <input type="text" id="c-price-note" name="price_note" class="input" value="<?= fv('price_note', $car['price_note'] ?? '') ?>" placeholder="e.g. ex-factory, import estimate">
                </div>
            </div>
            <div class="form-field">
                <label for="c-tagline">Tagline</label>
                <input type="text" id="c-tagline" name="tagline" class="input" maxlength="190" value="<?= fv('tagline', $car['tagline'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label for="c-overview">Overview (HTML allowed)</label>
                <textarea id="c-overview" name="overview" class="textarea" rows="8"><?= e($_POST['overview'] ?? $car['overview'] ?? '') ?></textarea>
            </div>
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-status">Status</label>
                    <select id="c-status" name="status" class="select">
                        <?php $curStatus = (string)($_POST['status'] ?? $car['status'] ?? 'published'); ?>
                        <?php foreach (['published' => 'Published', 'draft' => 'Draft', 'upcoming' => 'Upcoming'] as $sv => $sl): ?>
                            <option value="<?= $sv ?>" <?= $curStatus === $sv ? 'selected' : '' ?>><?= $sl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>Homepage</label>
                    <label class="check"><input type="checkbox" name="is_featured" value="1" <?= ($_POST['is_featured'] ?? $car['is_featured'] ?? 0) ? 'checked' : '' ?>> Featured</label>
                    <label class="check"><input type="checkbox" name="is_popular" value="1" <?= ($_POST['is_popular'] ?? $car['is_popular'] ?? 0) ? 'checked' : '' ?>> Popular badge</label>
                </div>
            </div>
            <div class="form-field">
                <label>Car categories</label>
                <?php foreach ($carCats as $c): ?>
                    <label class="check"><input type="checkbox" name="car_categories[]" value="<?= (int)$c['id'] ?>"
                        <?= in_array((string)$c['id'], array_map('strval', $carCatIds), true) ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- Specs -->
    <div class="tab-panel" data-panel="specs" hidden>
        <section class="panel">
            <div class="panel-head"><h2>Engine &amp; drivetrain</h2></div>
            <div class="form-row cols-2">
                <div class="form-field"><label for="s-engine">Engine</label><input type="text" id="s-engine" name="engine" class="input" value="<?= spec_val('engine', $specs) ?>" placeholder="e.g. 1.5L Turbo 4-cyl"></div>
                <div class="form-field"><label for="s-cc">Displacement (cc)</label><input type="number" id="s-cc" name="displacement_cc" class="input" min="0" value="<?= spec_val('displacement_cc', $specs) ?>"></div>
                <div class="form-field"><label for="s-fuel">Fuel type</label>
                    <select id="s-fuel" name="fuel_type" class="select">
                        <?php $curFuel = (string)($_POST['fuel_type'] ?? $specs['fuel_type'] ?? 'Petrol'); ?>
                        <?php foreach (['Petrol', 'Diesel', 'Hybrid', 'Electric', 'Plug-in Hybrid'] as $ft): ?>
                            <option <?= $curFuel === $ft ? 'selected' : '' ?>><?= $ft ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field"><label for="s-trans">Transmission</label><input type="text" id="s-trans" name="transmission" class="input" value="<?= spec_val('transmission', $specs) ?>" placeholder="e.g. CVT / 8-speed automatic"></div>
                <div class="form-field"><label for="s-drive">Drive type</label>
                    <select id="s-drive" name="drive_type" class="select">
                        <?php $curDrive = (string)($_POST['drive_type'] ?? $specs['drive_type'] ?? 'FWD'); ?>
                        <?php foreach (['FWD', 'RWD', 'AWD', '4WD'] as $dt): ?>
                            <option <?= $curDrive === $dt ? 'selected' : '' ?>><?= $dt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Performance &amp; economy</h2></div>
            <div class="form-row cols-3">
                <div class="form-field"><label for="s-hp">Power (hp)</label><input type="number" id="s-hp" name="power_hp" class="input" min="0" value="<?= spec_val('power_hp', $specs) ?>"></div>
                <div class="form-field"><label for="s-nm">Torque (Nm)</label><input type="number" id="s-nm" name="torque_nm" class="input" min="0" value="<?= spec_val('torque_nm', $specs) ?>"></div>
                <div class="form-field"><label for="s-acc">0–100 km/h (s)</label><input type="number" step="0.1" id="s-acc" name="acceleration_s" class="input" min="0" value="<?= spec_val('acceleration_s', $specs) ?>"></div>
                <div class="form-field"><label for="s-top">Top speed (km/h)</label><input type="number" id="s-top" name="top_speed_kmh" class="input" min="0" value="<?= spec_val('top_speed_kmh', $specs) ?>"></div>
                <div class="form-field"><label for="s-tank">Fuel tank (L)</label><input type="number" id="s-tank" name="fuel_tank_l" class="input" min="0" value="<?= spec_val('fuel_tank_l', $specs) ?>"></div>
                <div class="form-field"><label for="s-city">City economy (km/l)</label><input type="number" step="0.1" id="s-city" name="mileage_city_kml" class="input" min="0" value="<?= spec_val('mileage_city_kml', $specs) ?>"></div>
                <div class="form-field"><label for="s-hwy">Highway economy (km/l)</label><input type="number" step="0.1" id="s-hwy" name="mileage_highway_kml" class="input" min="0" value="<?= spec_val('mileage_highway_kml', $specs) ?>"></div>
                <div class="form-field"><label for="s-batt">Battery (kWh)</label><input type="number" step="0.1" id="s-batt" name="battery_kwh" class="input" min="0" value="<?= spec_val('battery_kwh', $specs) ?>"></div>
                <div class="form-field"><label for="s-range">Electric range (km)</label><input type="number" id="s-range" name="range_km" class="input" min="0" value="<?= spec_val('range_km', $specs) ?>"></div>
            </div>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Dimensions, weight &amp; capacity</h2></div>
            <div class="form-row cols-3">
                <div class="form-field"><label for="s-len">Length (mm)</label><input type="number" id="s-len" name="length_mm" class="input" min="0" value="<?= spec_val('length_mm', $specs) ?>"></div>
                <div class="form-field"><label for="s-wid">Width (mm)</label><input type="number" id="s-wid" name="width_mm" class="input" min="0" value="<?= spec_val('width_mm', $specs) ?>"></div>
                <div class="form-field"><label for="s-hei">Height (mm)</label><input type="number" id="s-hei" name="height_mm" class="input" min="0" value="<?= spec_val('height_mm', $specs) ?>"></div>
                <div class="form-field"><label for="s-wb">Wheelbase (mm)</label><input type="number" id="s-wb" name="wheelbase_mm" class="input" min="0" value="<?= spec_val('wheelbase_mm', $specs) ?>"></div>
                <div class="form-field"><label for="s-gc">Ground clearance (mm)</label><input type="number" id="s-gc" name="ground_clearance_mm" class="input" min="0" value="<?= spec_val('ground_clearance_mm', $specs) ?>"></div>
                <div class="form-field"><label for="s-weight">Curb weight (kg)</label><input type="number" id="s-weight" name="curb_weight_kg" class="input" min="0" value="<?= spec_val('curb_weight_kg', $specs) ?>"></div>
                <div class="form-field"><label for="s-boot">Boot space (L)</label><input type="number" id="s-boot" name="boot_space_l" class="input" min="0" value="<?= spec_val('boot_space_l', $specs) ?>"></div>
                <div class="form-field"><label for="s-seats">Seats</label><input type="number" id="s-seats" name="seating" class="input" min="1" max="9" value="<?= spec_val('seating', $specs) ?>"></div>
                <div class="form-field"><label for="s-doors">Doors</label><input type="number" id="s-doors" name="doors" class="input" min="1" max="8" value="<?= spec_val('doors', $specs) ?>"></div>
            </div>
        </section>
    </div>

    <!-- Features + pros/cons -->
    <div class="tab-panel" data-panel="features" hidden>
        <?php foreach (['safety' => 'Safety features', 'technology' => 'Technology', 'interior' => 'Interior & comfort', 'exterior' => 'Exterior'] as $gk => $gl): ?>
            <section class="panel">
                <div class="panel-head"><h2><?= $gl ?></h2></div>
                <div class="form-field">
                    <label for="feat-<?= $gk ?>">One feature per line</label>
                    <textarea id="feat-<?= $gk ?>" name="feat_<?= $gk ?>" class="textarea" rows="6"><?= e($_POST['feat_' . $gk] ?? implode("\n", $features[$gk] ?? [])) ?></textarea>
                </div>
            </section>
        <?php endforeach; ?>
        <section class="panel">
            <div class="panel-head"><h2>Pros &amp; cons</h2></div>
            <div class="form-row cols-2">
                <div class="form-field">
                    <label for="c-pros">Pros (one per line)</label>
                    <textarea id="c-pros" name="pros" class="textarea" rows="5"><?= e($_POST['pros'] ?? implode("\n", json_decode((string)($car['pros'] ?? '[]'), true) ?: [])) ?></textarea>
                </div>
                <div class="form-field">
                    <label for="c-cons">Cons (one per line)</label>
                    <textarea id="c-cons" name="cons" class="textarea" rows="5"><?= e($_POST['cons'] ?? implode("\n", json_decode((string)($car['cons'] ?? '[]'), true) ?: [])) ?></textarea>
                </div>
            </div>
        </section>
    </div>

    <!-- Images -->
    <div class="tab-panel" data-panel="images" hidden>
        <section class="panel">
            <div class="panel-head"><h2>Main image</h2></div>
            <div class="image-picker" data-input="main_image">
                <img class="picker-preview" src="<?= e(img_url($_POST['main_image'] ?? $car['main_image'] ?? '')) ?>" alt="">
                <input type="hidden" name="main_image" value="<?= fv('main_image', $car['main_image'] ?? '') ?>">
                <div class="picker-actions">
                    <button type="button" class="btn btn-sm btn-outline picker-open">Choose image</button>
                    <button type="button" class="btn btn-sm btn-ghost picker-clear">Clear</button>
                </div>
            </div>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>Gallery</h2></div>
            <?php if ($images): ?>
                <p class="hint">Existing gallery images — check the box to remove. The main image is used as the cover.</p>
                <div class="gallery-admin">
                    <?php foreach ($images as $im): ?>
                        <label class="gallery-admin-item">
                            <input type="checkbox" name="remove_images[]" value="<?= e($im['path']) ?>">
                            <img src="<?= e(img_url($im['path'])) ?>" alt="" loading="lazy">
                            <span>Remove</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <textarea name="keep_images" hidden><?= e(implode("\n", array_column($images, 'path'))) ?></textarea>
            <?php else: ?>
                <p class="hint">No gallery images yet.</p>
            <?php endif; ?>
            <div class="form-field">
                <label for="c-gallery">Add gallery images</label>
                <textarea id="c-gallery" name="gallery" class="textarea" rows="3" placeholder="Click “Choose images” to add paths (one per line)…"><?= e($_POST['gallery'] ?? '') ?></textarea>
                <button type="button" class="btn btn-sm btn-outline gallery-picker-open" data-target="c-gallery">Choose images</button>
            </div>
        </section>
    </div>

    <!-- SEO & FAQ -->
    <div class="tab-panel" data-panel="seo" hidden>
        <section class="panel">
            <div class="panel-head"><h2>SEO</h2></div>
            <div class="form-field">
                <label for="c-seo-title">SEO title</label>
                <input type="text" id="c-seo-title" name="seo_title" class="input" maxlength="150" value="<?= fv('seo_title', $car['seo_title'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label for="c-meta">Meta description</label>
                <textarea id="c-meta" name="meta_description" class="textarea" rows="2" maxlength="300"><?= fv('meta_description', $car['meta_description'] ?? '') ?></textarea>
            </div>
        </section>
        <section class="panel">
            <div class="panel-head"><h2>FAQ</h2><button type="button" class="btn btn-sm btn-outline faq-add">+ Add question</button></div>
            <div class="faq-rows">
                <?php foreach ($faq as $f): ?>
                    <div class="faq-row">
                        <input type="text" name="faq_q[]" class="input" placeholder="Question" value="<?= e($f['q']) ?>">
                        <textarea name="faq_a[]" class="textarea" rows="2" placeholder="Answer"><?= e($f['a']) ?></textarea>
                        <button type="button" class="btn btn-sm btn-ghost faq-remove">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
