<?php
/** AutoPulse — comparison data: GET /api/compare.php?ids=1,5,7 (max 3 cars). */
require dirname(__DIR__) . '/includes/api_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_out(['success' => false, 'message' => 'Method not allowed'], 405);

$ids = array_slice(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))), 0, 3);
if (count($ids) < 1) json_out(['success' => false, 'message' => 'No cars selected.']);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$st = db()->prepare("SELECT c.id, c.name, c.slug, c.price, c.price_note, c.main_image, c.body_type, c.year_start, c.pros, c.cons, c.overview,
                            b.name AS brand, b.slug AS brand_slug, s.*
                     FROM car_models c
                     JOIN brands b ON b.id = c.brand_id
                     LEFT JOIN car_specs s ON s.car_id = c.id
                     WHERE c.id IN ($placeholders) AND c.status = 'published'");
$st->execute($ids);
$cars = $st->fetchAll();

$out = [];
foreach ($cars as $c) {
    $out[] = [
        'id'     => (int)$c['id'],
        'name'   => $c['brand'] . ' ' . $c['name'],
        'year'   => $c['year_start'],
        'url'    => car_url($c),
        'image'  => img_url($c['main_image']),
        'price'  => format_price($c['price'], $c['price_note']),
        'specs'  => [
            'Body type'      => $c['body_type'],
            'Engine'         => $c['engine'],
            'Fuel type'      => $c['fuel_type'],
            'Power'          => $c['power_hp'] ? $c['power_hp'] . ' hp' : '',
            'Torque'         => $c['torque_nm'] ? $c['torque_nm'] . ' lb-ft' : '',
            'Transmission'   => $c['transmission'],
            'Drive type'     => $c['drive_type'],
            '0–60 mph'       => $c['acceleration_s'] ? spec_num($c['acceleration_s']) . ' s' : '',
            'Top speed'      => $c['top_speed_kmh'] ? $c['top_speed_kmh'] . ' mph' : '',
            'City economy'   => $c['mileage_city_kml'] ? spec_num($c['mileage_city_kml']) . ' mpg' : '',
            'Highway economy'=> $c['mileage_highway_kml'] ? $c['mileage_highway_kml'] . ' mpg' : '',
            'Battery'        => $c['battery_kwh'] ? $c['battery_kwh'] . ' kWh' : '',
            'Electric range' => $c['range_km'] ? $c['range_km'] . ' mi' : '',
            'Length'         => $c['length_mm'] ? $c['length_mm'] . ' in' : '',
            'Width'          => $c['width_mm'] ? $c['width_mm'] . ' in' : '',
            'Height'         => $c['height_mm'] ? $c['height_mm'] . ' in' : '',
            'Wheelbase'      => $c['wheelbase_mm'] ? $c['wheelbase_mm'] . ' in' : '',
            'Ground clearance' => $c['ground_clearance_mm'] ? $c['ground_clearance_mm'] . ' in' : '',
            'Curb weight'    => $c['curb_weight_kg'] ? $c['curb_weight_kg'] . ' lbs' : '',
            'Cargo space'    => $c['boot_space_l'] ? $c['boot_space_l'] . ' cu ft' : '',
            'Seating'        => $c['seating'] ? $c['seating'] . ' seats' : '',
        ],
        'safety' => get_car_features((int)$c['id'])['safety'] ?? [],
        'overview' => (string)$c['overview'], // admin-authored review HTML
    ];
}

json_out(['success' => true, 'data' => $out]);
