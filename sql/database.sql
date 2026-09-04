-- ============================================================
-- AutoPulse — MySQL database schema + demo data
-- Import via phpMyAdmin (InfinityFree) or: mysql -u root -p < database.sql
-- NOTE: CREATE DATABASE/USER lines are for local setup only — on
-- InfinityFree the database already exists; import the tables only.
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS autopulse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE autopulse;

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── Users (admins & editors) ─────────────────────────────────
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  status TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ⚠ TEMPORARY DEVELOPMENT CREDENTIALS — change immediately after first login
-- admin / Admin@123 · editor / Editor@123
INSERT INTO users (username, email, password_hash, role) VALUES
('admin',  'admin@autopulse.test',  '$2y$12$hAXZf3bj9A4884NwxfDgYOXsQZgNgl3CD2TL5gMtlLY3/FwJXKnny', 'admin'),
('editor', 'editor@autopulse.test', '$2y$12$oEPR9Y7FZDI0B9HV/nQUMOp/7SQKPNaOibt8cOXM7q1I1Hv7B6wmS', 'editor');

-- ── Site settings ────────────────────────────────────────────
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value LONGTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'AutoPulse'),
('tagline', 'Cars · Specs · Prices · Reviews'),
('site_url', ''),
('logo', 'assets/images/logo.svg'),
('favicon', 'assets/images/favicon.svg'),
('currency', 'PKR'),
('contact_email', 'hello@autopulse.test'),
('contact_phone', '+92 300 0000000'),
('contact_address', 'Faisalabad, Punjab, Pakistan'),
('social_facebook', ''),
('social_twitter', ''),
('social_instagram', ''),
('social_youtube', ''),
('meta_description', 'AutoPulse — car specifications, prices, honest reviews, comparisons and automotive news for the Pakistani market and beyond.'),
('og_image', 'uploads/general/hero.jpg'),
('hero_image', 'uploads/general/hero.jpg'),
('google_analytics_id', ''),
('google_verification', ''),
('bing_verification', ''),
('header_scripts', ''),
('footer_scripts', ''),
('custom_css', ''),
('copyright_text', 'All rights reserved.'),
('footer_about', 'AutoPulse is an independent automotive publication covering car specifications, prices, reviews and news — built for people who research before they buy.'),
('maintenance_mode', '0'),
('comments_enabled', '1'),
('homepage_sections', '[{"key":"hero","label":"Hero","enabled":1,"head":""},{"key":"latest_articles","label":"Latest Articles","enabled":1,"head":"Latest Articles"},{"key":"latest_cars","label":"Latest Cars","enabled":1,"head":"Latest Cars"},{"key":"popular_cars","label":"Popular Cars","enabled":1,"head":"Popular Cars"},{"key":"brands","label":"Brands","enabled":1,"head":"Browse by Brand"},{"key":"compare_cta","label":"Compare CTA","enabled":1,"head":""},{"key":"news","label":"News","enabled":1,"head":"Latest Automotive News"},{"key":"guides","label":"Buying Guides","enabled":1,"head":"Buying Guides"},{"key":"newsletter","label":"Newsletter","enabled":1,"head":""}]');

-- ── Categories (article + car types) ─────────────────────────
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  type ENUM('article','car') NOT NULL DEFAULT 'article',
  description VARCHAR(500) NULL,
  seo_title VARCHAR(150) NULL,
  meta_description VARCHAR(300) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, slug, type, description, sort_order) VALUES
('Car News', 'car-news', 'article', 'The latest launches, price updates and industry news from the automotive world.', 1),
('Car Reviews', 'car-reviews', 'article', 'In-depth reviews: how cars actually drive, ride and cost to run.', 2),
('Buying Guides', 'buying-guides', 'article', 'Practical advice for choosing, checking and negotiating your next car.', 3),
('Maintenance', 'maintenance', 'article', 'Service intervals, DIY checks and tips to keep your car healthy.', 4),
('EV & Hybrid', 'ev-hybrid', 'article', 'Electric and hybrid cars: technology, charging and ownership costs.', 5),
('SUVs', 'suv', 'car', 'Sport utility vehicles — high seating, practical cabins and bad-road capability.', 1),
('Sedans', 'sedan', 'car', 'Classic three-box saloons focused on comfort and refinement.', 2),
('Hatchbacks', 'hatchback', 'car', 'Compact, city-friendly cars with practical boots.', 3),
('Electric Cars', 'electric', 'car', 'Fully electric vehicles available or expected in the region.', 4);

-- ── Tags ─────────────────────────────────────────────────────
DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tags (name, slug) VALUES
('Toyota','toyota'),('Honda','honda'),('Suzuki','suzuki'),('Kia','kia'),('Hyundai','hyundai'),
('MG','mg'),('Tesla','tesla'),('BMW','bmw'),('Changan','changan'),('SUV','suv'),('Sedan','sedan'),
('Electric','electric'),('Price Watch','price-watch'),('Fuel Economy','fuel-economy'),('Used Cars','used-cars');

-- ── Brands ───────────────────────────────────────────────────
DROP TABLE IF EXISTS brands;
CREATE TABLE brands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  country VARCHAR(60) NULL,
  founded_year SMALLINT UNSIGNED NULL,
  logo VARCHAR(255) NULL,
  description MEDIUMTEXT NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  seo_title VARCHAR(150) NULL,
  meta_description VARCHAR(300) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO brands (name, slug, country, founded_year, logo, description, featured, sort_order) VALUES
('Toyota','toyota','Japan',1937,'assets/images/brands/toyota.svg','<p>Toyota is the world''s largest automaker, famous for reliability and strong resale value. Its local line-up spans the budget Yaris to the Land Cruiser, with the Corolla an institution on Pakistani roads.</p>',1,1),
('Honda','honda','Japan',1948,'assets/images/brands/honda.svg','<p>Honda builds some of the best engines in the business and pairs them with sharp chassis tuning. The Civic and City are among Pakistan''s most desired sedans.</p>',1,2),
('Suzuki','suzuki','Japan',1909,'assets/images/brands/suzuki.svg','<p>Suzuki dominates Pakistan''s entry-level market with the Alto, Cultus and Ravi — small, simple cars that are cheap to buy, run and fix.</p>',1,3),
('Kia','kia','South Korea',1944,'assets/images/brands/kia.svg','<p>Kia returned to Pakistan in 2019 and quickly won buyers with the Sportage and Seltis — generous equipment and striking design at aggressive prices.</p>',1,4),
('Hyundai','hyundai','South Korea',1967,'assets/images/brands/hyundai.svg','<p>Hyundai offers Korean value with growing polish: the Tucson SUV and Elantra sedan are its Pakistani flagships.</p>',1,5),
('MG','mg','United Kingdom/China',1924,'assets/images/brands/mg.svg','<p>MG, now owned by SAIC, undercuts rivals on price while delivering SUV style and long equipment lists — the HS is its best seller locally.</p>',1,6),
('Changan','changan','China',1862,'assets/images/brands/changan.svg','<p>Changan is one of China''s oldest automakers and entered Pakistan with the Oshan X7, a seven-seat-value SUV that disrupted the market.</p>',0,7),
('Tesla','tesla','United States',2003,'assets/images/brands/tesla.svg','<p>Tesla made electric cars desirable: long range, over-the-air updates and industry-leading efficiency. Models are imported into Pakistan.</p>',0,8),
('BMW','bmw','Germany',1916,'assets/images/brands/bmw.svg','<p>BMW builds the driver''s choice among German luxury sedans — the 3 Series remains the benchmark for handling in its class.</p>',0,9);

-- ── Car models ───────────────────────────────────────────────
DROP TABLE IF EXISTS car_models;
CREATE TABLE car_models (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  generation VARCHAR(60) NULL,
  year_start SMALLINT UNSIGNED NULL,
  body_type VARCHAR(50) NULL,
  segment VARCHAR(50) NULL,
  price DECIMAL(12,2) NULL,
  price_note VARCHAR(120) NULL,
  tagline VARCHAR(190) NULL,
  summary TEXT NULL,
  overview MEDIUMTEXT NULL,
  pros TEXT NULL COMMENT 'JSON array',
  cons TEXT NULL COMMENT 'JSON array',
  faq TEXT NULL COMMENT 'JSON [{q,a}]',
  main_image VARCHAR(255) NULL,
  status ENUM('published','draft','upcoming') NOT NULL DEFAULT 'published',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_popular TINYINT(1) NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  seo_title VARCHAR(150) NULL,
  meta_description VARCHAR(300) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_car (brand_id, slug),
  KEY idx_status (status),
  KEY idx_body (body_type),
  CONSTRAINT fk_car_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO car_models (brand_id, name, slug, generation, year_start, body_type, segment, price, price_note, tagline, overview, pros, cons, faq, main_image, status, is_featured, is_popular, views) VALUES
(1,'Corolla Alt Grande','corolla-alt-grande','12th Gen (E210)',2026,'Sedan','C-Segment',7649000,NULL,'The benchmark sedan — now with ADAS suite','<p>The Toyota Corolla Alt Grande is the best-known sedan on Pakistani roads. The 1.8-litre engine is tuned for durability and resale, while the 2026 model adds a full driver-assistance suite to the top variant. It is not the cheapest, but it is the safest used-car bet in the country.</p><h2>What''s new for 2026</h2><p>Updated grille, wireless smartphone mirroring, and Toyota Safety Sense across the Grande trim.</p>','["Legendary reliability and resale value","Strong dealer and parts network nationwide","Comfortable, well-damped ride for rough roads","New ADAS safety suite on the top variant"]','["CVT drones under hard acceleration","Rear seat space trails some rivals","Infotainment lags newer Korean competitors"]','[{"q":"What is the fuel average of the Corolla Alt?","a":"Expect 10–11 km/l in city driving and up to 14 km/l on the highway with a light foot."},{"q":"Is the Corolla Alt Grande worth the premium?","a":"If resale value and ADAS features matter to you, yes — the Grande holds its value better than any rival."}]','uploads/cars/toyota-corolla.jpg','published',1,1,2384),
(2,'Civic RS Turbo','civic-rs-turbo','11th Gen',2026,'Sedan','C-Segment',9099000,NULL,'Turbocharged style leader of the sedan segment','<p>The Honda Civic RS pairs a 1.5-litre VTEC Turbo with the sharpest design in its class. The cabin feels a class above, with honeycomb vents and a 9-inch touchscreen, and the chassis is genuinely fun on a winding road.</p>','["Strong turbocharged performance","Class-leading design and cabin quality","Excellent handling and steering feel","Honda sensing ADAS suite"]','["Firm low-speed ride","Price closes on entry German cars","Ground clearance needs care on speed breakers"]','[{"q":"Does the Civic RS need hi-octane fuel?","a":"Honda recommends 92 RON minimum; hi-octane improves throttle response and is advised for the RS turbo."}]','uploads/cars/honda-civic.jpg','published',1,1,1975),
(3,'Alto VXL AGS','alto-vxl-ags','8th Gen',2026,'Hatchback','A-Segment',2459000,NULL,'Pakistan''s most affordable automatic car','<p>The Suzuki Alto VXL AGS is the default first car: a 660cc kei-car platform with an automated manual gearbox. It is tiny outside but seats four, sips fuel, and parts are available in every town.</p>','["Lowest purchase price for an automatic","18+ km/l highway fuel economy","Cheap parts and servicing everywhere","Compact dimensions ideal for city traffic"]','["AGS gearbox shifts slowly","Noisy at motorway speeds","Basic safety kit — two airbags max"]','[{"q":"Is the Alto VXL AGS good for the motorway?","a":"It manages, but 100–110 km/h is the comfortable limit; long highway trips are tiring."},{"q":"What is the real fuel average?","a":"Owners report 15–18 km/l in the city and up to 21 km/l on the highway."}]','uploads/cars/suzuki-alto.jpg','published',0,1,3120),
(4,'Sportage AWD','sportage-awd','5th Gen (NQ5)',2026,'SUV','C-SUV',8049000,NULL,'The SUV that changed Pakistan''s market','<p>The Kia Sportage proved Pakistani buyers would pay premium-hatch money for a fully-loaded SUV. You get a turbo option, all-wheel drive, panoramic sunroof and a full ADAS suite in the top trims.</p>','["Loaded equipment list even in mid trims","Comfortable, quiet highway cruiser","Strong resale demand","Available all-wheel drive"]','["Dual-clutch transmission can hesitate","Rear headroom is merely adequate","Service costs above Japanese rivals"]','[{"q":"Which Sportage trim is the best value?","a":"The mid trim keeps the panoramic roof and key safety kit while saving roughly PKR 800,000 over the flagship."}]','uploads/cars/kia-sportage.jpg','published',1,1,4210),
(5,'Tucson Ultimate','tucson-ultimate','4th Gen (NX4)',2026,'SUV','C-SUV',8349000,NULL,'Hyundai''s design-led family SUV','<p>The Hyundai Tucson walks the line between family SUV and design statement. The Ultimate trim adds a panoramic roof, powered tailgate and Hyundai SmartSense safety suite as standard.</p>','["Bold, award-winning exterior design","Spacious, flexible interior","Generous standard safety tech","Refined 6-speed automatic"]','["Naturally-aspirated 2.0 lacks punch","No turbo option locally","Ride firms up on large rims"]','NULL','uploads/cars/hyundai-tucson.jpg','published',0,1,1543),
(6,'MG HS 1.5T','mg-hs-1-5t','2nd Gen',2026,'SUV','C-SUV',7199000,NULL,'SUV presence at hatchback money','<p>The MG HS undercuts every rival on price while looking a size larger. The 1.5 turbo and 7-speed DCT deliver respectable pace, and the cabin is loaded with soft-touch surfaces where buyers look first.</p>','["Sharpest price in the segment","Loaded features: sunroof, 360 camera, leather","Warranty up to 6 years","Turbo engine feels stronger than figures"]','["DCT hesitates in bumper-to-bumper traffic","Resale history still unproven","Some panel gaps betray the price"]','NULL','uploads/cars/mg-hs.jpg','published',0,1,1766),
(7,'Oshan X7 FutureSense','oshan-x7-futuresense','1st Gen',2026,'SUV','D-SUV',7449000,NULL,'Seven seats and a flagship feature list','<p>Changan''s Oshan X7 offers something no rival at this price dares: seven seats, a 1.5 turbo, adaptive cruise and lane-keep assist in the FutureSense trim.</p>','["Seven usable seats","FutureSense ADAS at a mid price","Strong turbo mid-range","Big boot in five-seat mode"]','["Brand service network still maturing","Third row is kids-only","Firm ride when unladen"]','NULL','uploads/cars/changan-oshan-x7.jpg','published',0,0,987),
(8,'Model 3 Long Range','model-3-long-range','Highland Facelift',2026,'Sedan','D-Segment',16499000,'Import — indicative price','The electric benchmark','<p>The Tesla Model 3 Long Range remains the most efficient way to travel electrically: around 580 km of mixed range, a minimal cabin that ages well, and a Supercharger-grade charging curve on DC fast charging.</p>','["Class-leading efficiency and real range","Instant, silent acceleration","Minimal, high-quality cabin","Over-the-air software updates"]','["Import pricing with duties","Charging infrastructure still thin","Service network is third-party"]','[{"q":"Can the Model 3 be charged at home in Pakistan?","a":"Yes — a 7 kW home charger adds about 40 km of range per hour. A full overnight charge covers a week of city driving."}]','uploads/cars/tesla-model-3.jpg','published',1,1,2871),
(9,'330i M Sport','330i-m-sport','7th Gen (G20)',2026,'Sedan','D-Segment',21499000,'Import — indicative price','The driver''s compact executive','<p>The BMW 330i is the enthusiast''s answer in the executive class: a 255 hp 2.0 turbo, near-perfect 50:50 balance and an interior that finally matches the badge. The M Sport trim adds the body kit and suspension buyers actually want.</p>','["Best-in-class handling","Strong, efficient B48 engine","High-quality, driver-focused cabin","Holds value well among imports"]','["Expensive options list","Run-flat tyres hurt ride comfort","Ground clearance is optimistic for local roads"]','NULL','uploads/cars/bmw-330i.jpg','published',0,0,1102),
(1,'Fortuner Legender','fortuner-legender','2nd Gen',2026,'SUV','E-SUV',33999000,NULL,'The status SUV','<p>The Toyota Fortuner Legender is the default statement SUV: body-on-frame toughness, a 204 hp 2.8 diesel and commanding road presence. The Legender trim adds the full safety suite and sharper styling.</p>','["Commanding road presence","Bulletproof 2.8 diesel + 4WD","Excellent resale value","7 seats as standard"]','["Firm, jiggly ride","Truck-like handling","Price has climbed steeply"]','NULL','uploads/cars/toyota-fortuner.jpg','published',0,1,2410);

-- ── Car specs (1:1) ──────────────────────────────────────────
DROP TABLE IF EXISTS car_specs;
CREATE TABLE car_specs (
  car_id INT UNSIGNED PRIMARY KEY,
  engine VARCHAR(120) NULL,
  displacement_cc SMALLINT UNSIGNED NULL,
  fuel_type VARCHAR(40) NULL,
  power_hp SMALLINT UNSIGNED NULL,
  torque_nm SMALLINT UNSIGNED NULL,
  transmission VARCHAR(80) NULL,
  drive_type VARCHAR(30) NULL,
  acceleration_s DECIMAL(4,1) NULL COMMENT '0-100 km/h',
  top_speed_kmh SMALLINT UNSIGNED NULL,
  fuel_tank_l TINYINT UNSIGNED NULL,
  mileage_city_kml DECIMAL(4,1) NULL,
  mileage_highway_kml DECIMAL(4,1) NULL,
  battery_kwh DECIMAL(5,1) NULL,
  range_km SMALLINT UNSIGNED NULL,
  length_mm SMALLINT UNSIGNED NULL,
  width_mm SMALLINT UNSIGNED NULL,
  height_mm SMALLINT UNSIGNED NULL,
  wheelbase_mm SMALLINT UNSIGNED NULL,
  ground_clearance_mm TINYINT UNSIGNED NULL,
  curb_weight_kg SMALLINT UNSIGNED NULL,
  boot_space_l SMALLINT UNSIGNED NULL,
  seating TINYINT UNSIGNED NULL,
  doors TINYINT UNSIGNED NULL,
  CONSTRAINT fk_spec_car FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO car_specs VALUES
(1,'1.8L 4-cyl NA (2ZR-FE)',1798,'Petrol',138,173,'CVT (7-speed sequential)','FWD',10.2,190,50,10.5,14.0,NULL,NULL,4630,1795,1435,2700,155,1295,470,5,4),
(2,'1.5L VTEC Turbo',1498,'Petrol',176,220,'CVT','FWD',8.5,210,47,9.8,13.5,NULL,NULL,4674,1802,1415,2735,134,1323,410,5,4),
(3,'0.66L 3-cyl (R06A)',658,'Petrol',40,56,'5-speed AGS','FWD',19.0,140,27,17.5,21.5,NULL,NULL,3395,1475,1490,2460,160,670,74,4,5),
(4,'2.0L MPI 4-cyl',1999,'Petrol',155,192,'6-speed automatic','AWD',11.0,180,58,9.5,12.5,NULL,NULL,4515,1865,1650,2680,180,1540,580,5,5),
(5,'2.0L MPI 4-cyl',1999,'Petrol',156,192,'6-speed automatic','FWD',11.5,181,54,9.0,12.0,NULL,NULL,4475,1850,1660,2680,181,1530,590,5,5),
(6,'1.5L Turbo 4-cyl',1490,'Petrol',160,250,'7-speed DCT','FWD',9.2,190,55,9.0,12.0,NULL,NULL,4574,1876,1664,2720,160,1575,483,5,5),
(7,'1.5L Turbo 4-cyl',1499,'Petrol',185,300,'7-speed DCT','FWD',9.8,180,58,8.5,12.0,NULL,NULL,4705,1860,1720,2780,190,1630,400,7,5),
(8,'Dual Motor AWD',NULL,'Electric',394,493,'Single-speed','AWD',4.4,201,NULL,NULL,NULL,79,580,4720,1848,1442,2875,140,1830,594,5,4),
(9,'2.0L Turbo (B48)',1998,'Petrol',255,400,'8-speed automatic','RWD',5.6,250,59,9.8,14.0,NULL,NULL,4713,1827,1442,2851,145,1595,480,5,4),
(10,'2.8L Turbo Diesel',2755,'Diesel',204,500,'6-speed automatic','4WD',9.9,180,80,9.0,13.0,NULL,NULL,4795,1855,1835,2745,220,2135,200,7,5);

-- ── Car categories (pivot) ───────────────────────────────────
DROP TABLE IF EXISTS car_categories;
CREATE TABLE car_categories (
  car_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (car_id, category_id),
  FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO car_categories VALUES
(1,7),(2,7),(3,8),(4,6),(5,6),(6,6),(7,6),(8,7),(8,9),(9,7),(10,6);

-- ── Car features (grouped lists) ─────────────────────────────
DROP TABLE IF EXISTS car_features;
CREATE TABLE car_features (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  car_id INT UNSIGNED NOT NULL,
  feature_group ENUM('safety','technology','interior','exterior') NOT NULL,
  item VARCHAR(150) NOT NULL,
  FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE,
  KEY idx_car_group (car_id, feature_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO car_features (car_id, feature_group, item) VALUES
(1,'safety','6 airbags'),(1,'safety','ABS with EBD'),(1,'safety','Vehicle stability control'),(1,'safety','Adaptive cruise control'),(1,'safety','Lane departure alert'),(1,'technology','9-inch touchscreen'),(1,'technology','Wireless Android Auto / CarPlay'),(1,'technology','Reversing camera'),(1,'interior','Leatherette seats'),(1,'interior','Auto climate control'),(1,'exterior','LED headlamps'),(1,'exterior','16-inch alloys'),
(2,'safety','6 airbags + knee airbag'),(2,'safety','Honda Sensing ADAS'),(2,'safety','Lane keeping assist'),(2,'safety','Collision mitigation braking'),(2,'technology','9-inch touchscreen'),(2,'technology','Wireless charging'),(2,'technology','8-speaker audio'),(2,'interior','Honeycomb dash design'),(2,'interior','Sunroof'),(2,'exterior','LED headlamps'),(2,'exterior','17-inch alloys'),
(3,'safety','2 airbags'),(3,'safety','ABS with EBD'),(3,'technology','7-inch touchscreen'),(3,'technology','Smartphone mirroring'),(3,'interior','Manual AC'),(3,'interior','Power windows'),(3,'exterior','13-inch alloys'),
(4,'safety','6 airbags'),(4,'safety','Adaptive cruise'),(4,'safety','Blind-spot monitor'),(4,'technology','10.25-inch infotainment'),(4,'technology','360-degree camera'),(4,'technology','Panoramic sunroof'),(4,'interior','Ventilated front seats'),(4,'exterior','18-inch alloys'),(4,'exterior','LED DRLs'),
(5,'safety','6 airbags'),(5,'safety','SmartSense ADAS'),(5,'technology','10.25-inch touchscreen'),(5,'technology','Wireless CarPlay'),(5,'interior','Powered driver seat'),(5,'interior','Dual-zone climate'),(5,'exterior','Panoramic roof'),(5,'exterior','18-inch alloys'),
(6,'safety','6 airbags'),(6,'technology','12.3-inch touchscreen'),(6,'technology','360-degree camera'),(6,'interior','Leather seats'),(6,'interior','Panoramic sunroof'),(6,'exterior','18-inch alloys'),
(7,'safety','6 airbags'),(7,'safety','Adaptive cruise + lane keep'),(7,'technology','12.3-inch touchscreen'),(7,'interior','7-seat layout'),(7,'exterior','19-inch alloys'),
(8,'safety','Autopilot standard'),(8,'safety','360-degree cameras'),(8,'technology','15-inch centre display'),(8,'technology','Over-the-air updates'),(8,'interior','Glass roof'),(8,'interior','Vegan leather'),(8,'exterior','Aero 18-inch wheels'),
(9,'safety','6 airbags'),(9,'safety','Driving Assistant Professional'),(9,'technology','Live Cockpit Professional'),(9,'interior','M Sport seats'),(9,'interior','Ambient lighting'),(9,'exterior','M Aerodynamics kit'),
(10,'safety','7 airbags'),(10,'safety','Toyota Safety Sense'),(10,'technology','9-inch infotainment'),(10,'interior','Leather seats'),(10,'interior','7 seats'),(10,'exterior','18-inch alloys');

-- ── Car images (galleries) ───────────────────────────────────
DROP TABLE IF EXISTS car_images;
CREATE TABLE car_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  car_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(190) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Articles ─────────────────────────────────────────────────
DROP TABLE IF EXISTS article_tags;
DROP TABLE IF EXISTS article_categories;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS articles;
CREATE TABLE articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  brand_id INT UNSIGNED NULL,
  category_id INT UNSIGNED NULL,
  title VARCHAR(190) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  excerpt VARCHAR(400) NULL,
  content MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  status ENUM('published','draft','scheduled') NOT NULL DEFAULT 'draft',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  allow_comments TINYINT(1) NOT NULL DEFAULT 1,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  faq TEXT NULL COMMENT 'JSON [{q,a}]',
  seo_title VARCHAR(150) NULL,
  meta_description VARCHAR(300) NULL,
  canonical_url VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_status_pub (status, published_at),
  KEY idx_category (category_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FULLTEXT KEY ft_title (title, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_categories (
  article_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (article_id, category_id),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE article_tags (
  article_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (article_id, tag_id),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO articles (user_id, brand_id, category_id, title, slug, excerpt, content, featured_image, status, is_featured, views, published_at, faq) VALUES
(1, NULL, 3, 'Best SUVs in Pakistan for 2026: Every Tier Compared', 'best-suvs-pakistan-2026',
 'From the value-packed MG HS to the segment-leading Kia Sportage — every SUV on sale in Pakistan in 2026, sorted by budget, features and resale.',
 '<p>The Pakistani SUV market has never been more competitive. Korean brands pushed features up and prices down, Chinese entrants added seven seats at hatchback money, and Toyota keeps charging a premium for peace of mind. Here is how the field stacks up in 2026.</p><h2>Under PKR 8 million: the value kings</h2><p>The <strong>MG HS 1.5T</strong> remains the benchmark for value: a turbo engine, panoramic roof and 360 camera for less than a mid-spec sedan. The <strong>Changan Oshan X7</strong> counters with seven seats and a surprisingly capable ADAS suite.</p><h2>PKR 8–9 million: the mainstream battle</h2><p>The <strong>Kia Sportage</strong> and <strong>Hyundai Tucson</strong> trade blows here. The Sportage wins on equipment and available AWD; the Tucson counters with a more spacious cabin and calmer styling. Both are genuinely good cars — the decision comes down to which dealership is closer.</p><h2>Above PKR 30 million</h2><p>The <strong>Toyota Fortuner Legender</strong> is unbeatable for resale and presence, and nothing else in this list is happy doing 500 km of Balochistan highway either.</p><h2>Our picks</h2><ul><li><strong>Best value:</strong> MG HS 1.5T</li><li><strong>Best all-rounder:</strong> Kia Sportage AWD</li><li><strong>Best for big families:</strong> Changan Oshan X7 FutureSense</li><li><strong>Best resale:</strong> Toyota Fortuner</li></ul>',
 'uploads/cars/kia-sportage.jpg','published',1,1842,'2026-08-26 09:00:00',
 '[{"q":"Which SUV has the best fuel average in Pakistan?","a":"Among turbo petrol SUVs, the MG HS and Tucson return 9–12 km/l; the diesel Fortuner manages similar figures with far more torque."},{"q":"Are Chinese SUVs reliable?","a":"Early data is encouraging, and warranties run up to 6 years — but the service network is still maturing outside major cities."}]'),

(1, 1, 2, 'Toyota Corolla Alt Grande 2026: First Drive Review', 'toyota-corolla-alt-2026-review',
 'The 2026 Corolla adds ADAS and a fresher face. We drove 400 km to find out if the king still deserves its crown.',
 '<p>Every reviewer dreams of discovering a hidden gem. The Corolla is the opposite: the most predictable car in Pakistan, and that is precisely the point.</p><h2>What''s new</h2><p>The 2026 Alt Grande receives Toyota Safety Sense — adaptive cruise, lane departure alert with steering assist, and auto high-beam. The grille is sharper, the 9-inch unit finally does wireless mirroring, and rear vents appear for the first time.</p><h2>Driving it</h2><p>The 138 hp 1.8 is unhurried but adequate, and the CVT settles at motorway speeds. Where the Corolla excels is ride quality: broken tarmac that punishes the Civic simply disappears under it.</p><h2>Verdict</h2><p>The Corolla is not the most exciting purchase, but it might be the most rational one in the country. <strong>8.4/10</strong>.</p>',
 'uploads/cars/toyota-corolla.jpg','published',1,1204,'2026-08-18 09:00:00',NULL),

(2, NULL, 3, 'Honda Civic RS vs Toyota Corolla Alt: Which Sedan Wins in 2026?', 'civic-rs-vs-corolla-alt-2026',
 'The oldest rivalry in the Pakistani sedan market, decided by the numbers that actually matter.',
 '<p>Both cost more than they used to and both sell in numbers rivals envy. But they aim at different buyers.</p><h2>Performance</h2><p>The Civic''s 1.5 turbo (176 hp) out-powers the Corolla''s 1.8 NA (138 hp) by a wide margin — 0–100 km/h falls in 8.5 s versus 10.2 s. The Corolla''s CVT is smoother; the Civic''s is quicker.</p><h2>Comfort and space</h2><p>The Corolla rides better on broken roads and offers more rear headroom. The Civic counters with superior seats and cabin design.</p><h2>Running costs</h2><p>Parts parity is close; fuel economy slightly favours the Corolla. Insurance is higher on the Civic.</p><h2>Resale</h2><p>No contest — the Corolla depreciates slowest of any car in Pakistan.</p><h2>Verdict</h2><p><strong>Buy the Civic</strong> if you love driving. <strong>Buy the Corolla</strong> if you love your future self''s bank balance.</p>',
 'uploads/cars/honda-civic.jpg','published',0,933,'2026-08-10 09:00:00',NULL),

(2, NULL, 5, 'EV Ownership in Pakistan: What You Need to Know Before Going Electric', 'ev-ownership-pakistan-guide',
 'Charging at home, import duties, running costs and the honest realities of driving electric in Pakistan today.',
 '<p>Import duties make EVs expensive to buy, but absurdly cheap to run. A Model 3 costs about PKR 4 per km in electricity at domestic rates — a third of what a Corolla burns in fuel.</p><h2>Charging at home</h2><p>Any EV can charge from a regular socket overnight, but a proper 7 kW wallbox turns 8 hours into 3. You will need an electrician to verify your load — most homes manage a 7 kW circuit easily.</p><h2>Public charging</h2><p>Fast-charging corridors exist on the M-2 and M-9, and the network is growing — but plan intercity trips around it. This is the single biggest lifestyle change.</p><h2>Battery life</h2><p>Modern packs retain 85%+ capacity after 200,000 km. Heat management matters more in Pakistan''s climate, so prefer liquid-cooled packs (Tesla, BMW, Hyundai).</p><h2>Who should buy one</h2><p>If you have home charging and a second car for long trips, an EV is genuinely practical today.</p>',
 'uploads/cars/tesla-model-3.jpg','published',0,1101,'2026-07-28 09:00:00',
 '[{"q":"How much does it cost to charge an EV at home in Pakistan?","a":"At roughly PKR 60/unit domestic slab, a full Model 3 charge (~75 kWh usable) costs about PKR 4,500 for ~550 km — under PKR 9 per litre-equivalent running cost."}]'),

(2, NULL, 4, '5 Summer Maintenance Tips Every Car Needs in Pakistan', 'summer-car-maintenance-tips',
 '50°C road temperatures punish batteries, tyres and coolant. Five checks that prevent the most common summer breakdowns.',
 '<p>Pakistani summers kill car batteries faster than anything else. Have yours load-tested before June — most fail between the third and fourth summer.</p><h2>1. Battery health</h2><p>Heat accelerates chemical wear. If your battery is 3+ years old, test it; replacements before summer are cheaper than a towing bill after.</p><h2>2. Coolant, not just water</h2><p>Plain water boils. Use the correct premix coolant and check levels cold — never open a hot radiator cap.</p><h2>3. Tyre pressure rises with heat</h2><p>Check pressures cold, monthly. Overinflated tyres on 55°C tarmac are blowout candidates on the motorway.</p><h2>4. AC service</h2><p>A gas top-up and condenser clean can drop cabin temperatures by 6–8°C and cut fuel-wasting compressor strain.</p><h2>5. Wipers and washer</h2><p>Monsoon follows summer. Replace cracked blades now.</p>',
 'uploads/general/hero.jpg','published',0,742,'2026-07-15 09:00:00',NULL),

(1, 4, 1, 'Kia Sportage 2026: New Prices, Trims and What Changed', 'kia-sportage-2026-prices-trims',
 'Kia reshuffles the Sportage line-up for 2026 — here is what each trim adds and which one is worth your money.',
 '<p>Kia Pakistan has trimmed the Sportage range to three variants, cutting the entry price slightly while making the panoramic roof standard across the line-up.</p><h2>The line-up</h2><ul><li><strong>Alpha:</strong> 2.0L + 6AT, fabric seats, standard safety kit</li><li><strong>Sportage:</strong> adds panoramic roof, smart key, rear camera</li><li><strong>AWD:</strong> all-wheel drive, ventilated seats, ADAS suite</li></ul><h2>What changed</h2><p>Wireless CarPlay is now standard, the grill is restyled, and Kia claims improved sound insulation — which our first drive confirmed.</p><h2>Which to buy</h2><p>The mid trim is the sweet spot unless you genuinely need AWD for northern trips.</p>',
 'uploads/cars/kia-sportage.jpg','published',0,869,'2026-08-30 09:00:00',NULL),

(1, NULL, 3, 'How to Check a Used Car Before Buying in Pakistan', 'used-car-inspection-checklist-pakistan',
 'Meter tampering, flood damage and accident repairs — a practical 20-minute checklist before you hand over cash.',
 '<p>The used market is full of honest cars and cleverly disguised lemons. This 20-minute inspection catches 90% of the common tricks.</p><h2>Paperwork first</h2><p>Match the chassis and engine numbers to the book. Verify token tax history and that the seller''s CNIC matches the owner''s.</p><h2>Meter tampering</h2><p>Compare odometer readings with service records and pedal wear. A 40,000 km car with shiny brake pedals and a worn driver''s bolster has stories.</p><h2>Flood damage</h2><p>Look under carpets, check seat-rail bolts for rust, and smell for damp. Test every electrical switch.</p><h2>Accident repairs</h2><p>Run a magnet (or look for waviness) along panels — filler doesn''t lie. Panel gaps should be even.</p><h2>The test drive</h2><p>Listen for suspension knocks on rough roads, feel for steering pull, and brake hard once from 60 km/h on empty road.</p>',
 'uploads/cars/suzuki-alto.jpg','published',0,1367,'2026-06-20 09:00:00',NULL);

INSERT INTO article_categories (article_id, category_id) VALUES
(1,3),(2,2),(2,1),(3,3),(4,5),(5,4),(6,1),(7,3);
INSERT INTO article_tags (article_id, tag_id) VALUES
(1,4),(1,6),(1,10),(1,12),(2,1),(2,11),(3,1),(3,2),(3,11),(4,8),(4,12),(5,13),(6,4),(6,13),(7,15),(7,14);

-- ── Comments (moderated) ─────────────────────────────────────
DROP TABLE IF EXISTS comments;
CREATE TABLE comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NULL,
  car_id INT UNSIGNED NULL,
  author_name VARCHAR(80) NOT NULL,
  author_email VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_article_status (article_id, status),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO comments (article_id, author_name, author_email, body, status) VALUES
(1,'Bilal R.','bilal@example.com','Great comparison! I would add the Seltis to the under-8 list though — it deserves a mention.','approved'),
(1,'Sana K.','sana@example.com','MG HS resale has actually improved a lot this year, worth updating the article.','approved'),
(4,'Hamza','hamza@example.com','What about solar charging for EVs? Panels are getting cheap.','pending');

-- ── CMS pages ────────────────────────────────────────────────
DROP TABLE IF EXISTS pages;
CREATE TABLE pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  slug VARCHAR(170) NOT NULL UNIQUE,
  content MEDIUMTEXT NULL,
  status ENUM('published','draft') NOT NULL DEFAULT 'published',
  show_in_footer TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  seo_title VARCHAR(150) NULL,
  meta_description VARCHAR(300) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pages (title, slug, content, show_in_footer, sort_order) VALUES
('About Us','about-us','<h2>Who we are</h2><p>AutoPulse is an independent automotive publication. We catalogue specifications, track prices and review the cars that matter to local buyers — without dealership pressure or advertising spin.</p><h2>How we work</h2><p>Every specification in our database is checked against manufacturer documentation, and prices are updated as the market moves. Review verdicts are our own.</p><h2>Contact</h2><p>Corrections and tips are welcome via the <a href="/contact">contact page</a>.</p>',1,1),
('Privacy Policy','privacy-policy','<h2>Information we collect</h2><p>We collect the minimum needed to operate the site: anonymous analytics (if enabled), and the name/email you choose to provide when commenting or contacting us.</p><h2>How we use it</h2><p>Comments are published after moderation; your email is never displayed or sold. Newsletter subscriptions are used only to send updates and can be cancelled anytime.</p><h2>Cookies</h2><p>We use a single session cookie for site functionality (such as the comment form security token) and, when analytics is enabled, the provider''s cookies.</p><h2>Third-party ads</h2><p>Advertising partners may use cookies to serve relevant ads. You can control this via your browser or your ad provider''s opt-out page.</p>',1,2),
('Terms & Conditions','terms-and-conditions','<h2>Use of content</h2><p>Articles and specifications are provided for personal, non-commercial use. Reproduction requires written permission and attribution.</p><h2>Accuracy</h2><p>We work hard to keep prices and specifications current, but they change frequently. Always confirm final figures with an authorised dealer.</p><h2>Comments</h2><p>You are responsible for what you post. We remove spam, abuse and misleading content at our discretion.</p><h2>Liability</h2><p>AutoPulse accepts no liability for decisions made based on site content.</p>',1,3),
('Disclaimer','disclaimer','<p>All prices shown are indicative ex-factory or estimated import figures in PKR and may change without notice. Fuel economy figures are manufacturer claims or editorial estimates; real-world results vary with driving style, load and fuel quality.</p><p>Vehicle availability and specifications differ by market. This site is not affiliated with any manufacturer or dealership.</p>',1,4);

-- ── Menus ────────────────────────────────────────────────────
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS menus;
CREATE TABLE menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  label VARCHAR(80) NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus (name, slug) VALUES ('Main Menu','main');
INSERT INTO menu_items (menu_id, parent_id, label, url, sort_order) VALUES
(1,NULL,'Home','/',1),
(1,NULL,'Cars','cars',2),
(1,2,'All Cars','cars',1),
(1,2,'Compare Cars','compare',2),
(1,2,'SUVs','category/suv',3),
(1,2,'Sedans','category/sedan',4),
(1,2,'Hatchbacks','category/hatchback',5),
(1,2,'Electric Cars','category/electric',6),
(1,NULL,'Articles','articles',3),
(1,9,'News','category/car-news',1),
(1,9,'Reviews','category/car-reviews',2),
(1,9,'Buying Guides','category/buying-guides',3),
(1,9,'Maintenance','category/maintenance',4),
(1,NULL,'Brands','brands',4),
(1,NULL,'Compare','compare',5),
(1,NULL,'Contact','contact',6);

-- ── Ad slots ─────────────────────────────────────────────────
DROP TABLE IF EXISTS ad_slots;
CREATE TABLE ad_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  code TEXT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ad_slots (position, name, enabled) VALUES
('header','Header banner',0),
('home_top','Homepage — top',0),
('home_mid','Homepage — middle',0),
('article_top','Article — top',0),
('article_mid','Article — middle',0),
('article_bottom','Article — bottom',0),
('sidebar','Sidebar',0),
('footer','Footer',0);

-- ── Redirects ────────────────────────────────────────────────
DROP TABLE IF EXISTS redirects;
CREATE TABLE redirects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  old_path VARCHAR(255) NOT NULL UNIQUE,
  new_path VARCHAR(255) NOT NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO redirects (old_path, new_path, status_code) VALUES
('blog','articles',301),
('car','cars',301),
('vehicles','cars',301);

-- ── Contact messages ─────────────────────────────────────────
DROP TABLE IF EXISTS contact_messages;
CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO contact_messages (name, email, subject, message) VALUES
('Ayesha Tariq','ayesha@example.com','Price correction — Civic RS','Hi! The Civic RS price listed seems slightly outdated; dealer quoted me a higher figure yesterday. Could you double-check? Thanks!');

-- ── Media library ────────────────────────────────────────────
DROP TABLE IF EXISTS media;
CREATE TABLE media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL,
  alt VARCHAR(190) NULL,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Newsletter ───────────────────────────────────────────────
DROP TABLE IF EXISTS newsletter_subscribers;
CREATE TABLE newsletter_subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  status ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO media (filename, path, size_bytes, uploaded_by) VALUES
('bmw-330i.jpg', 'uploads/cars/bmw-330i.jpg', 186223, 1),
('changan-oshan-x7.jpg', 'uploads/cars/changan-oshan-x7.jpg', 245672, 1),
('honda-civic.jpg', 'uploads/cars/honda-civic.jpg', 217864, 1),
('hyundai-tucson.jpg', 'uploads/cars/hyundai-tucson.jpg', 200830, 1),
('kia-sportage.jpg', 'uploads/cars/kia-sportage.jpg', 174648, 1),
('mg-hs.jpg', 'uploads/cars/mg-hs.jpg', 194670, 1),
('suzuki-alto.jpg', 'uploads/cars/suzuki-alto.jpg', 248245, 1),
('tesla-model-3.jpg', 'uploads/cars/tesla-model-3.jpg', 239026, 1),
('toyota-corolla.jpg', 'uploads/cars/toyota-corolla.jpg', 163707, 1),
('toyota-fortuner.jpg', 'uploads/cars/toyota-fortuner.jpg', 256667, 1),
('hero.jpg', 'uploads/general/hero.jpg', 174648, 1);

SET foreign_key_checks = 1;
