-- ============================================================
-- AutoPulse — MySQL database schema + demo data (US market)
-- Import via phpMyAdmin (InfinityFree) or: mysql -u root -p < database.sql
-- NOTE: CREATE DATABASE/USER lines are for local setup only — on
-- InfinityFree the database already exists; import the tables only.
-- SPEC UNITS: values are stored in US units (mpg, mph, 0-60 s,
-- inches, lbs, cu ft, gallons) even where column names are metric.
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
  display_name VARCHAR(80) NULL COMMENT 'public byline name',
  slug VARCHAR(90) NULL UNIQUE COMMENT 'author profile URL: /author/{slug}',
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  bio TEXT NULL COMMENT 'author bio (public profile page)',
  avatar VARCHAR(255) NULL COMMENT 'avatar image path (Media library)',
  status TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ⚠ TEMPORARY DEVELOPMENT CREDENTIALS — change immediately after first login
-- admin / Admin@123 · editor / Editor@123
INSERT INTO users (username, display_name, slug, bio, email, password_hash, role) VALUES
('admin', 'Ghazanfar Malik', 'ghazanfar-malik',
 'Founder and lead editor. Covers the US new-car market, pricing trends and the shift to electric. Based in Austin, Texas.',
 'admin@autopulse.test', '$2y$12$hAXZf3bj9A4884NwxfDgYOXsQZgNgl3CD2TL5gMtlLY3/FwJXKnny', 'admin'),
('editor', 'Emily Carter', 'emily-carter',
 'Senior editor covering SUVs, trucks and family vehicles. Fifteen years testing cars on American roads, from Texas interstates to Colorado passes.',
 'editor@autopulse.test', '$2y$12$oEPR9Y7FZDI0B9HV/nQUMOp/7SQKPNaOibt8cOXM7q1I1Hv7B6wmS', 'editor');

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
('currency', 'USD'),
('contact_email', 'hello@autopulse.test'),
('contact_phone', '+1 (512) 555-0134'),
('contact_address', 'Austin, TX, United States'),
('social_facebook', ''),
('social_twitter', ''),
('social_instagram', ''),
('social_youtube', ''),
('meta_description', 'AutoPulse — car specifications, prices, honest reviews, comparisons and automotive news for American buyers.'),
('og_image', 'uploads/general/hero.jpg'),
('hero_image', 'uploads/general/hero.jpg'),
('google_analytics_id', ''),
('google_verification', ''),
('gsc_file_name', ''),
('bing_verification', ''),
('header_scripts', ''),
('footer_scripts', ''),
('custom_css', ''),
('copyright_text', 'All rights reserved.'),
('footer_about', 'AutoPulse is an independent automotive publication covering car specifications, prices, reviews and news — built for American drivers who research before they buy.'),
('maintenance_mode', '0'),
('comments_enabled', '1'),
('hero_title', 'Find your next car with confidence.'),
('hero_text', 'Independent specifications, honest reviews and up-to-date prices — everything you need before you visit the dealership.'),
('hero_cta_label', 'Browse Cars'),
('hero_cta_url', 'cars'),
('hero_cta2_label', 'Compare Models'),
('hero_cta2_url', 'compare'),
('compare_cta_title', 'Can''t decide? Let the specs decide.'),
('compare_cta_text', 'Put up to three cars side by side — price, power, MPG and dimensions — and see the real differences highlighted automatically.'),
('compare_cta_btn', 'Start Comparing'),
('newsletter_title', 'Never miss an update'),
('newsletter_text', 'New models, price changes and buying guides — straight to your inbox. No spam, unsubscribe anytime.'),
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
('Car News', 'car-news', 'article', 'The latest launches, price updates and industry news from the US auto market.', 1),
('Car Reviews', 'car-reviews', 'article', 'In-depth reviews: how cars actually drive, ride and cost to own.', 2),
('Buying Guides', 'buying-guides', 'article', 'Practical advice for choosing, checking and negotiating your next car.', 3),
('Maintenance', 'maintenance', 'article', 'Service intervals, DIY checks and tips to keep your car healthy.', 4),
('EV & Hybrid', 'ev-hybrid', 'article', 'Electric and hybrid cars: technology, charging and ownership costs in America.', 5),
('SUVs & Crossovers', 'suv', 'car', 'America''s favorite body style — raised seating, cargo space and all-weather confidence.', 1),
('Sedans', 'sedan', 'car', 'Classic three-box cars focused on comfort, efficiency and refinement.', 2),
('Hatchbacks', 'hatchback', 'car', 'Compact, city-friendly cars with surprisingly practical cargo holds.', 3),
('Electric Cars', 'electric', 'car', 'Fully electric vehicles on sale in the United States.', 4),
('Trucks', 'trucks', 'car', 'Full-size and midsize pickups — towing, hauling and capability.', 5);

-- ── Tags ─────────────────────────────────────────────────────
DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tags (name, slug) VALUES
('Toyota','toyota'),('Honda','honda'),('Ford','ford'),('Chevrolet','chevrolet'),('Jeep','jeep'),
('Tesla','tesla'),('BMW','bmw'),('Volkswagen','volkswagen'),('Hyundai','hyundai'),('Kia','kia'),
('SUV','suv'),('Sedan','sedan'),('Truck','truck'),('Electric','electric'),('Muscle Cars','muscle-cars'),
('MPG','mpg'),('Used Cars','used-cars'),('Tax Credit','tax-credit');

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
('Toyota','toyota','Japan',1937,'assets/images/brands/toyota.svg','<p>Toyota is America''s best-selling automaker, famous for reliability and resale value. The Camry and RAV4 are fixtures on US roads, and the brand''s hybrid lineup leads the market.</p>',1,1),
('Ford','ford','United States',1903,'assets/images/brands/ford.svg','<p>Ford built America on wheels. Today the F-150 has been the country''s best-selling vehicle for over four decades, while the Mustang keeps the muscle-car flame alive.</p>',1,2),
('Honda','honda','Japan',1948,'assets/images/brands/honda.svg','<p>Honda pairs thrifty engines with engaging chassis tuning. The Civic and Accord are perennial 10-Best picks, and Honda''s resale numbers are among the strongest in the industry.</p>',1,3),
('Chevrolet','chevrolet','United States',1911,'assets/images/brands/chevrolet.svg','<p>Chevrolet covers America from the affordable Trax to the Corvette supercar. The Silverado and Tahoe anchor the brand''s full-size truck and SUV heartland.</p>',1,4),
('Tesla','tesla','United States',2003,'assets/images/brands/tesla.svg','<p>Tesla made electric vehicles mainstream in America: long range, over-the-air updates and the largest Supercharger network in the country.</p>',1,5),
('Jeep','jeep','United States',1941,'assets/images/brands/jeep.svg','<p>Jeep invented the SUV and still trades on genuine off-road credibility. The Grand Cherokee blends trail ability with family comfort.</p>',1,6),
('Volkswagen','volkswagen','Germany',1937,'assets/images/brands/volkswagen.svg','<p>Volkswagen brings European driving manners to mainstream America — the GTI invented the hot hatch, and the Tiguan and redesigned 2026 lineup aim squarely at US families.</p>',1,7),
('Hyundai','hyundai','South Korea',1967,'assets/images/brands/hyundai.svg','<p>Hyundai wins American buyers with value, quality and America''s Best Warranty. The Tucson is one of the best-equipped compact SUVs at any price.</p>',1,8),
('Kia','kia','South Korea',1944,'assets/images/brands/kia.svg','<p>Kia has become a design-led powerhouse: the Telluride and Sportage punch far above their price, and the brand routinely tops quality surveys.</p>',1,9),
('BMW','bmw','Germany',1916,'assets/images/brands/bmw.svg','<p>BMW builds the driver''s choice among German luxury sedans — the 3 Series remains the benchmark for handling in its class.</p>',0,10);

-- ── Car models (US market, prices in USD) ────────────────────
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

INSERT INTO car_models (id, brand_id, name, slug, generation, year_start, body_type, segment, price, price_note, tagline, overview, pros, cons, faq, main_image, status, is_featured, is_popular, views) VALUES
(1,1,'Camry LE Hybrid','camry-le-hybrid','8th Gen (XV80)',2026,'Sedan','Midsize',28995,'MSRP + destination','The best-selling car in America, now hybrid-only','<p>The Toyota Camry needs no introduction — it has been America''s best-selling passenger car for over two decades. For 2026 it is hybrid-only, and that is good news: 225 combined horsepower, up to 47 MPG combined and a quieter cabin make the default family sedan better than ever.</p><h2>What''s new for 2026</h2><p>The fifth-generation hybrid system is standard on every trim, Toyota Safety Sense 3.0 adds traffic jam assist, and the LE returns an EPA-rated 53/50 MPG city/highway.</p>','[''Class-leading fuel economy (up to 53 MPG city)'',''Standard hybrid powertrain with 225 hp'',''Excellent predicted reliability and resale value'',''Quiet, comfortable ride quality'']','[''No more V6 or plug-in option'',''Trunk is smaller than before'',''Infotainment learning curve'']','[{"q":"How many MPG does the 2026 Camry get?","a":"The Camry LE Hybrid is EPA-rated at 53 MPG city / 50 MPG highway — best in the midsize class."},{"q":"Is the Camry still reliable?","a":"Toyota''s hybrid systems have a two-decade track record; the Camry consistently tops reliability surveys."}]','uploads/cars/toyota-camry.jpg','published',1,1,3120),
(2,1,'RAV4 XLE','rav4-xle','6th Gen',2026,'SUV','Compact SUV',31090,'MSRP + destination','America''s best-selling vehicle that isn''t a truck','<p>The Toyota RAV4 is the compact SUV every competitor benchmarks. The redesigned 2026 model brings a hybrid powertrain to every trim, more tech, and up to 40 MPG combined — while keeping the cargo space and visibility families actually buy it for.</p>','[''Hybrid standard across the range'',''Excellent resale value'',''Roomy cargo area and great visibility'',''Available AWD with torque vectoring'']','[''Road noise over broken pavement'',''Base engine feels merely adequate'',''Popular trims sell at MSRP or above'']','[{"q":"Does the 2026 RAV4 come as a plug-in?","a":"Yes — the RAV4 Prime plug-in hybrid returns with around 42 miles of electric range."}]','uploads/cars/toyota-rav4.jpg','published',1,1,4380),
(3,3,'Civic Si','civic-si','11th Gen',2026,'Sedan','Compact',30250,'MSRP + destination','The affordable driver''s car, with a manual gearbox','<p>The Honda Civic Si remains the purest affordable enthusiast car in America: a 200-hp turbo, a proper six-speed manual with rev-match, and a limited-slip differential — standard. It doubles as a perfectly sensible 37-MPG-highway commuter.</p>','[''Standard 6-speed manual with rev matching'',''Limited-slip differential'',''37 MPG highway'',''Honda reliability and resale'']','[''No automatic transmission option'',''Firmer ride than a regular Civic'',''Rear seat is tight for adults'']','[{"q":"Is the Civic Si available with an automatic?","a":"No — the Si is manual-only. Buyers who want two pedals should look at the Civic Sport Touring hybrid."}]','uploads/cars/honda-civic.jpg','published',0,1,2140),
(4,7,'Golf GTI','golf-gti','Mk 8.5',2026,'Hatchback','Compact',33125,'MSRP + destination','The hot hatch that started it all','<p>Every hot hatchback is measured against the Volkswagen Golf GTI because it invented the formula in 1983: practical hatchback, turbocharged punch, genuinely good handling. The Mk 8.5 refresh adds a bigger touchscreen and a much-improved interface.</p>','[''241 hp turbo with 273 lb-ft of torque'',''Sharper interface for 2026'',''Premium-feeling cabin'',''Genuinely fun on a back road'']','[''Requires premium fuel'',''DCT can hesitate in traffic'',''Fewer standard features than rivals'']','NULL','uploads/cars/vw-golf-gti.jpg','published',0,0,1290),
(5,9,'Sportage X-Pro','sportage-x-pro','5th Gen (NQ5)',2026,'SUV','Compact SUV',32695,'MSRP + destination','The value benchmark of compact SUVs','<p>The Kia Sportage won America over by loading in equipment at aggressive prices. The X-Pro trim adds genuine light-off-road ability — all-wheel drive, all-terrain tires and a locking center differential — for thousands less than rivals'' mid trims.</p>','[''Loaded equipment list at every trim'',''X-Pro adds real off-road kit'',''Industry-leading warranty'',''Roomy second row and cargo area'']','[''Base engine is underwhelming'',''Hybrid trims get no X-Pro'',''Dealer markups on popular colors'']','[{"q":"Which Sportage trim is the best value?","a":"The EX hybrid keeps the big touchscreen and full safety suite for about $2,000 less than the SX-Prestige."}]','uploads/cars/kia-sportage.jpg','published',1,1,2860),
(6,8,'Tucson SEL','tucson-sel','4th Gen (NX4)',2026,'SUV','Compact SUV',30500,'MSRP + destination','Design-led comfort for daily family duty','<p>The Hyundai Tucson walks the line between family SUV and design statement. The SEL Convenience trim adds a power liftgate, roof rails and Blind-Spot View Monitor for less than most rivals charge for fabric seats.</p>','[''Bold, award-winning design'',''Generous standard safety tech'',''America''s Best Warranty'',''Hybrid available on every trim'']','[''2.5L engine is noisy under load'',''No hybrid on base Blue trim'',''Touch controls take getting used to'']','NULL','uploads/cars/hyundai-tucson.jpg','published',0,1,1770),
(7,7,'Tiguan SE','tiguan-se','3rd Gen',2026,'SUV','Compact SUV',30190,'MSRP + destination','The European take on the American family SUV','<p>Volkswagen redesigned the Tiguan for 2026 on the MQB Evo platform: more power (201 hp), a light hybrid system, available three rows and the sort of composed highway manners German brands trade on.</p>','[''Refined road manners'',''Available 3rd row seating'',''201 hp as standard'',''Upscale interior materials'']','[''Cargo space behind 3rd row is minimal'',''No hybrid version yet'',''Premium fuel recommended'']','NULL','uploads/cars/vw-tiguan.jpg','published',0,0,980),
(8,2,'F-150 XLT','f-150-xlt','14th Gen (P702)',2026,'Truck','Full-size Pickup',46145,'MSRP + destination','America''s best-selling vehicle, 40+ years running','<p>The Ford F-150 is not just a truck — it has been America''s best-selling vehicle of any kind for over four decades. The XLT with the 2.7L EcoBoost hits the sweet spot: 325 hp, 400 lb-ft, up to 13,000 lbs of towing in the right configuration, and running costs that surprise people who expect semi-truck bills.</p>','[''Best-selling truck for 47 years'',''Twin-turbo V6 with strong towing'',''Huge range of configurations'',''Strong resale value'']','[''Options inflate prices quickly'',''Ride is firm unloaded'',''Base V6 is thirsty'']','[{"q":"How much can the F-150 XLT tow?","a":"Properly equipped with the 2.7L EcoBoost and tow package, up to 13,000 lbs; the 3.5L EcoBoost raises that to 13,500 lbs."},{"q":"Which engine should I get?","a":"The 2.7L EcoBoost suits most owners; frequent towers should step up to the 3.5L EcoBoost."}]','uploads/cars/ford-f-150.jpg','published',1,1,5210),
(9,2,'Mustang GT','mustang-gt','7th Gen (S650)',2026,'Coupe','Muscle Car',46810,'MSRP + destination','The V8 muscle car that refuses to die','<p>While rivals go quiet and electric, the Ford Mustang GT keeps a naturally aspirated 5.0L V8 behind that long hood — 486 horsepower, an available six-speed manual and a soundtrack no speaker can fake. The S650 generation adds the digital drift brake and a far nicer cabin.</p>','[''486-hp naturally aspirated V8'',''Available 6-speed manual'',''Iconic American styling'',''Surprisingly livable daily'']','[''15 MPG city'',''Back seat is decorative'',''Winter tires a must in snow states'']','[{"q":"Does the Mustang GT need premium gas?","a":"Ford recommends 93 octane for full performance; it runs on 87 with reduced power."}]','uploads/cars/ford-mustang-gt.jpg','published',1,1,3960),
(10,4,'Tahoe Z71','tahoe-z71','5th Gen',2026,'SUV','Full-size SUV',64370,'MSRP + destination','The full-size SUV that defined the segment','<p>The Chevrolet Tahoe is the default American full-size SUV: three rows, real towing (up to 8,400 lbs) and a V8 under the hood. The Z71 adds off-road suspension, skid plates and all-terrain tires for families whose vacations leave the pavement.</p>','[''V8 power with 10-speed automatic'',''Up to 8,400 lbs towing'',''Huge cargo space behind row two'',''Commanding driving position'']','[''Thirsty in city driving'',''Massive in parking garages'',''Third row is a compromise'']','NULL','uploads/cars/chevrolet-tahoe.jpg','published',0,1,2470),
(11,6,'Grand Cherokee Limited','grand-cherokee-limited','WL',2026,'SUV','Midsize SUV',43665,'MSRP + destination','Trail-rated luxury for the school run','<p>The Jeep Grand Cherokee does double duty: a genuinely trail-capable 4x4 and a leather-trimmed family hauler. The Limited''s Quadra-Trac I all-wheel drive, panoramic sunroof and ventilated seats make it the value sweet spot of the lineup.</p>','[''Real off-road capability'',''Upscale Limited interior'',''Plug-in 4xe available'',''Comfortable air suspension option'']','[''V6 fuel economy is mediocre'',''Infotainment can be laggy'',''Higher trims get expensive fast'']','NULL','uploads/cars/jeep-grand-cherokee.jpg','published',0,0,1520),
(12,5,'Model 3 Long Range','model-3-long-range','Highland Facelift',2026,'Sedan','Compact Luxury',47740,'MSRP + est. fees','The electric benchmark','<p>The Tesla Model 3 Long Range remains the most efficient way to travel electrically in America: 363 miles of EPA range, access to the Supercharger network, and a charging curve competitors still chase. The Highland refresh fixed the ride and added a quieter cabin.</p>','[''363 miles EPA range'',''Supercharger network access'',''Instant, silent acceleration'',''May qualify for the $7,500 federal tax credit'']','[''No CarPlay or Android Auto'',''Build quality can vary'',''Service centers are sparse in rural areas'']','[{"q":"Does the Model 3 qualify for the federal tax credit?","a":"Eligibility depends on current IRS rules and final assembly sourcing — check the IRS Clean Vehicle list before purchase; leases often qualify regardless."},{"q":"How long does home charging take?","a":"On a 240V Level 2 charger, a full charge takes about 8 hours — overnight for most owners."}]','uploads/cars/tesla-model-3.jpg','published',1,1,4510),
(13,10,'330i M Sport','330i-m-sport','7th Gen (G20)',2026,'Sedan','Compact Luxury',45500,'MSRP + destination','The driver''s compact executive sedan','<p>The BMW 330i is the enthusiast''s answer in the entry-luxury class: a 255-hp turbo four, near-perfect 50:50 balance and an interior that finally matches the badge. The M Sport trim adds the body kit, bigger brakes and suspension buyers actually want.</p>','[''Best-in-class handling'',''Strong and efficient B48 engine'',''High-quality, driver-focused cabin'',''36 MPG highway'']','[''Options list gets expensive fast'',''Run-flat tires hurt ride comfort'',''Rivals offer more standard power'']','NULL','uploads/cars/bmw-330i.jpg','published',0,0,1350);

-- ── Car specs (US units: mpg, mph, 0-60 s, inches, lbs, cu ft, gal) ──
DROP TABLE IF EXISTS car_specs;
CREATE TABLE car_specs (
  car_id INT UNSIGNED PRIMARY KEY,
  engine VARCHAR(120) NULL,
  displacement_cc SMALLINT UNSIGNED NULL,
  fuel_type VARCHAR(40) NULL,
  power_hp SMALLINT UNSIGNED NULL,
  torque_nm SMALLINT UNSIGNED NULL COMMENT 'lb-ft',
  transmission VARCHAR(80) NULL,
  drive_type VARCHAR(30) NULL,
  acceleration_s DECIMAL(4,1) NULL COMMENT '0-60 mph',
  top_speed_kmh SMALLINT UNSIGNED NULL COMMENT 'mph',
  fuel_tank_l TINYINT UNSIGNED NULL COMMENT 'gallons',
  mileage_city_kml DECIMAL(4,1) NULL COMMENT 'mpg city',
  mileage_highway_kml DECIMAL(4,1) NULL COMMENT 'mpg highway',
  battery_kwh DECIMAL(5,1) NULL,
  range_km SMALLINT UNSIGNED NULL COMMENT 'miles (EPA)',
  length_mm SMALLINT UNSIGNED NULL COMMENT 'in',
  width_mm SMALLINT UNSIGNED NULL COMMENT 'in',
  height_mm SMALLINT UNSIGNED NULL COMMENT 'in',
  wheelbase_mm SMALLINT UNSIGNED NULL COMMENT 'in',
  ground_clearance_mm TINYINT UNSIGNED NULL COMMENT 'in',
  curb_weight_kg SMALLINT UNSIGNED NULL COMMENT 'lbs',
  boot_space_l SMALLINT UNSIGNED NULL COMMENT 'cu ft',
  seating TINYINT UNSIGNED NULL,
  doors TINYINT UNSIGNED NULL,
  CONSTRAINT fk_spec_car FOREIGN KEY (car_id) REFERENCES car_models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO car_specs VALUES
(1,'2.5L I4 Hybrid (A25A-FXS)',2487,'Hybrid',225,163,'8-speed automatic','FWD',7.2,115,13,53.0,50.0,NULL,NULL,191.5,72.4,56.9,112.2,5.7,3580,15.1,5,4),
(2,'2.5L I4 Hybrid',2487,'Hybrid',225,184,'8-speed automatic','AWD',7.3,112,14.5,39.0,36.0,NULL,NULL,180.1,73.0,67.3,106.3,8.3,3800,37.5,5,5),
(3,'1.5L Turbo I4',1498,'Petrol',200,192,'6-speed manual','FWD',7.0,137,12.4,27.0,37.0,NULL,NULL,184.7,70.8,55.7,107.7,4.9,2950,14.4,5,4),
(4,'2.0L Turbo I4 (EA888)',1984,'Petrol',241,273,'7-speed DCT','FWD',6.0,130,13.2,24.0,34.0,NULL,NULL,168.0,70.4,58.0,103.8,4.9,3150,19.9,5,5),
(5,'2.5L I4',2497,'Petrol',187,178,'8-speed automatic','AWD',8.8,118,14.5,23.0,28.0,NULL,NULL,183.5,73.4,66.1,108.5,8.3,3650,39.5,5,5),
(6,'2.5L I4',2497,'Petrol',187,178,'8-speed automatic','FWD',9.0,120,14.5,25.0,31.0,NULL,NULL,182.5,73.4,65.2,108.5,8.3,3600,38.7,5,5),
(7,'2.0L Turbo I4',1984,'Hybrid',201,207,'8-speed automatic','FWD',7.8,124,15.3,26.0,33.0,NULL,NULL,186.6,73.4,67.0,109.9,8.0,4000,37.1,5,5),
(8,'2.7L EcoBoost V6',2694,'Petrol',325,400,'10-speed automatic','4WD',6.1,110,23.0,19.0,23.0,NULL,NULL,233.8,79.9,78.3,145.4,9.3,4850,53.2,6,4),
(9,'5.0L V8 Coyote',5038,'Petrol',486,418,'10-speed automatic','RWD',4.2,155,16.0,15.0,24.0,NULL,NULL,188.4,75.3,55.0,107.9,4.7,3827,13.5,4,2),
(10,'5.3L V8 EcoTec3',5328,'Petrol',355,383,'10-speed automatic','4WD',7.4,112,24.0,15.0,19.0,NULL,NULL,210.7,81.0,75.9,120.9,10.0,5700,122.9,7,4),
(11,'3.6L V6 Pentastar',3604,'Petrol',293,260,'8-speed automatic','AWD',7.4,118,23.0,19.0,26.0,NULL,NULL,193.8,77.5,70.9,116.7,8.4,5150,37.7,5,5),
(12,'Dual Motor AWD',NULL,'Electric',394,351,'Single-speed','AWD',4.2,125,NULL,NULL,NULL,79,363,185.4,72.9,56.5,113.2,5.5,4034,21.0,5,4),
(13,'2.0L Turbo I4 (B48)',1998,'Petrol',255,295,'8-speed automatic','RWD',5.5,130,15.6,26.0,36.0,NULL,NULL,185.7,71.9,56.2,113.2,5.4,3560,17.0,5,4);

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
(1,7),(2,6),(3,7),(4,8),(5,6),(6,6),(7,6),(8,10),(9,10),(10,6),(11,6),(12,7),(12,9),(13,7);

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
(1,'safety','Toyota Safety Sense 3.0'),(1,'safety','8 airbags'),(1,'safety','Adaptive cruise with stop & go'),(1,'safety','Lane tracing assist'),(1,'technology','8-inch touchscreen'),(1,'technology','Wireless CarPlay / Android Auto'),(1,'technology','7-inch digital cluster'),(1,'interior','Dual-zone auto climate'),(1,'interior','Fabric seats (LE)'),(1,'exterior','LED headlamps'),(1,'exterior','16-inch alloys'),
(2,'safety','Toyota Safety Sense 3.0'),(2,'safety','Blind-spot monitor'),(2,'safety','Rear cross-traffic braking'),(2,'technology','10.5-inch touchscreen'),(2,'technology','Wireless charging'),(2,'technology','Digital key'),(2,'interior','Power driver seat'),(2,'interior','Rear USB-C ports'),(2,'exterior','Roof rails'),(2,'exterior','18-inch alloys'),
(3,'safety','Honda Sensing'),(3,'safety','Traffic sign recognition'),(3,'technology','9-inch touchscreen'),(3,'technology','Wireless CarPlay'),(3,'interior','Sport seats with red stitching'),(3,'interior','Moonroof'),(3,'exterior','18-inch matte alloys'),(3,'exterior','Center exhaust finisher'),
(4,'safety','Front assist + blind-spot monitor'),(4,'safety','Travel assist'),(4,'technology','12.9-inch touchscreen'),(4,'technology','Illuminated grille badge'),(4,'interior','Trips computer w/ lap timer'),(4,'interior','Plaid cloth / leatherette'),(4,'exterior','LED matrix headlights'),(4,'exterior','18-inch Richmond alloys'),
(5,'safety','Highway Driving Assist'),(5,'safety','Blind-Spot View Monitor'),(5,'safety','Safe Exit Assist'),(5,'technology','12.3-inch touchscreen'),(5,'technology','360-degree camera'),(5,'interior','Synthetic leather seats'),(5,'interior','Dual-zone climate'),(5,'exterior','17-inch all-terrain tires'),(5,'exterior','Roof rails + hitch'),
(6,'safety','Hyundai SmartSense'),(6,'safety','Blind-Spot View Monitor'),(6,'technology','12.3-inch touchscreen'),(6,'technology','Wireless CarPlay'),(6,'interior','Power driver seat'),(6,'interior','Rear seat reminder'),(6,'exterior','19-inch alloys'),(6,'exterior','LED daytime running lights'),
(7,'safety','IQ.DRIVE suite'),(7,'safety','Travel assist'),(7,'technology','12.9-inch touchscreen'),(7,'technology','Wireless App-Connect'),(7,'interior','Available 3rd row'),(7,'interior','V-Tex leatherette'),(7,'exterior','IQ.LIGHT LED matrix'),(7,'exterior','18-inch alloys'),
(8,'safety','Ford Co-Pilot360'),(8,'safety','Blind-spot with trailer coverage'),(8,'safety','360-degree camera package'),(8,'technology','12-inch SYNC 4 touchscreen'),(8,'technology','Pro Power Onboard 2.0kW'),(8,'technology','FordPass remote start'),(8,'interior','Work-grade vinyl/floor mats'),(8,'interior','Rear under-seat storage'),(8,'exterior','18-inch silver alloys'),(8,'exterior','LED box lighting'),
(9,'safety','Ford Co-Pilot360'),(9,'safety','Reverse brake assist'),(9,'technology','13.2-inch SYNC 4'),(9,'technology','Electronic drift brake'),(9,'technology','Bang & Olufsen audio'),(9,'interior','Leather sport seats'),(9,'interior','Selectable drive modes'),(9,'exterior','Magnetic mirrors + spoiler'),(9,'exterior','19-inch alloys'),
(10,'safety','Chevy Safety Assist'),(10,'safety','HD surround vision'),(10,'safety','Rear pedestrian braking'),(10,'technology','17.7-inch touchscreen'),(10,'technology','Wireless CarPlay'),(10,'technology','Head-up display'),(10,'interior','Leather seats'),(10,'interior','Power-folding 3rd row'),(10,'exterior','18-inch all-terrain tires'),(10,'exterior','Skid plates'),
(11,'safety','Full-speed collision warning'),(11,'safety','Parallel & perpendicular park assist'),(11,'technology','10.1-inch Uconnect 5'),(11,'technology','Wireless CarPlay'),(11,'interior','Nappa leather seats'),(11,'interior','Panoramic sunroof'),(11,'exterior','20-inch alloys'),(11,'exterior','Quadra-Trac I 4x4'),
(12,'safety','Autopilot standard'),(12,'safety','360-degree cameras'),(12,'safety','Blind-spot camera'),(12,'technology','15-inch center display'),(12,'technology','Over-the-air updates'),(12,'technology','Premium connectivity trials'),(12,'interior','Glass roof'),(12,'interior','Vegan leather'),(12,'exterior','18-inch aero wheels'),
(13,'safety','Driving Assistant Professional'),(13,'safety','Active lane keeping'),(13,'technology','Live Cockpit Professional'),(13,'technology','iDrive 8.5'),(13,'interior','M Sport seats'),(13,'interior','Ambient lighting'),(13,'exterior','M Aerodynamics kit'),(13,'exterior','18-inch M wheels');

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

INSERT INTO articles (id, user_id, brand_id, category_id, title, slug, excerpt, content, featured_image, status, is_featured, views, published_at, faq) VALUES
(1,1,NULL,3,'Best SUVs in America for 2026: Every Class Compared','best-suvs-america-2026',
 'From the hybrid-everything RAV4 to the three-row Tahoe — the best SUVs on sale in the US in 2026, sorted by class, budget and real-world MPG.',
 '<p>America buys SUVs the way the rest of the world buys cars — more than half of all new vehicles sold here wear an SUV badge. The 2026 lineup is the most competitive ever, with hybrids going mainstream and prices that finally reward cross-shopping.</p><h2>Compact SUVs: the volume kings</h2><p>The <strong>Toyota RAV4</strong> enters its new generation with a hybrid powertrain on every trim and up to 40 MPG combined — it remains the default choice. The <strong>Kia Sportage</strong> counters with more equipment per dollar, and the <strong>Hyundai Tucson</strong> brings the boldest design and America''s best warranty. Volkswagen''s redesigned <strong>Tiguan</strong> adds a European chassis and an available third row.</p><h2>Midsize 2-row: comfort first</h2><p>The <strong>Jeep Grand Cherokee</strong> is still the only one with genuine trail credentials — and its Limited trim is the value sweet spot.</p><h2>Full-size: nothing hauls like them</h2><p>The <strong>Chevrolet Tahoe</strong> tows up to 8,400 lbs, seats eight and shrinks distances on interstate trips in a way no crossover can match.</p><h2>Our picks</h2><ul><li><strong>Best overall:</strong> Toyota RAV4 Hybrid</li><li><strong>Best value:</strong> Kia Sportage EX</li><li><strong>Best for families:</strong> Chevrolet Tahoe Z71</li><li><strong>Best off-pavement:</strong> Jeep Grand Cherokee</li></ul>',
 'uploads/cars/toyota-rav4.jpg','published',1,2410,'2026-08-26 09:00:00',
 '[{"q":"Which SUV gets the best MPG in 2026?","a":"The Toyota RAV4 LE Hybrid leads at up to 41 MPG combined; the Toyota Camry-based Crown Signia trails closely."},{"q":"Are hybrid SUVs worth the premium?","a":"At current gas prices, most hybrids repay their $1,500–$3,000 premium in 3–5 years — and improve resale values too."}]'),

(2,1,1,2,'Toyota Camry 2026: First Drive Review','toyota-camry-2026-review',
 'The best-selling car in America goes hybrid-only for 2026. We drove 500 miles to see if it keeps its crown.',
 '<p>Every reviewer wants to find the next big thing. The Camry is the opposite — the most predictable car in America, and that is exactly the point.</p><h2>What''s new</h2><p>For 2026 every Camry is a hybrid: a 2.5-liter four plus a fifth-generation electric drive good for 225 horsepower on front-wheel-drive trims. The LE is EPA-rated at 53/50 MPG — numbers compacts struggled to hit a decade ago.</p><h2>Driving it</h2><p>0-60 mph takes 7.2 seconds — quick enough to never feel unsafe merging onto an interstate. The eCVT drones if you bury the throttle, but around town the Camry glides. Wind and road noise are genuinely luxury-car quiet at 75 mph.</p><h2>Verdict</h2><p>The Camry is not the most exciting purchase, but it may be the most rational one in America. <strong>8.6/10</strong>.</p>',
 'uploads/cars/toyota-camry.jpg','published',1,1560,'2026-08-18 09:00:00',NULL),

(3,2,NULL,3,'Civic Si vs Golf GTI: The Affordable Enthusiast Battle','civic-si-vs-golf-gti-2026',
 'One has a manual and a limited-slip differential. The other has 41 more pound-feet and hatchback practicality. America''s best budget driver''s car, decided.',
 '<p>Under $35,000, two cars still take driving seriously: the Honda Civic Si and the Volkswagen Golf GTI. Both start around $30K, both seat four, both sip premium fuel — and they could not feel more different.</p><h2>Powertrain</h2><p>The GTI''s 2.0T makes 241 hp and 273 lb-ft to the Si''s 200 hp and 192 — but the Honda offers something VW no longer does in the GTI: no automatic option at all. The six-speed manual with rev-matching is standard and wonderful.</p><h2>Chassis</h2><p>The Si corners flatter thanks to its limited-slip differential; the GTI rides better and feels more premium at 8/10ths.</p><h2>Running costs</h2><p>The Si returns up to 37 MPG highway and holds resale value obsessively well. The GTI''s DCT is quicker but the Si is the long-term ownership play.</p><h2>Verdict</h2><p><strong>Buy the Si</strong> if you love rowing gears. <strong>Buy the GTI</strong> if you want a single car that does everything.</p>',
 'uploads/cars/vw-golf-gti.jpg','published',0,1180,'2026-08-10 09:00:00',NULL),

(4,2,NULL,5,'EV Ownership in America: What You Need to Know Before Going Electric','ev-ownership-america-guide',
 'Home charging costs, the federal tax credit, cold-weather range and the honest math of driving electric in the US today.',
 '<p>The math on electric cars in America has never been better: residential electricity averages about 16 cents per kWh nationally, which works out to roughly 5 cents per mile in a Model 3 — a third of what a 30-MPG gas car costs at $3.50/gallon.</p><h2>Home charging is the whole game</h2><p>A 240V Level 2 charger ($500–$1,200 installed, sometimes offset by utility rebates) adds about 30 miles of range per hour. If you can charge at home, public charging becomes a road-trip-only concern.</p><h2>The federal tax credit</h2><p>Credit eligibility changes with sourcing rules — always check the IRS Clean Vehicle list before you buy. Leases frequently qualify regardless, which is why so many EV shoppers lease.</p><h2>Cold weather</h2><p>Expect 20–30% range loss in a Midwest winter. Heat-pump-equipped cars (standard on Tesla, optional elsewhere) recover much of it.</p><h2>Who should buy one</h2><p>If you have home charging and a second car for the occasional 500-mile day, an EV is genuinely cheaper to own than most gas cars today.</p>',
 'uploads/cars/tesla-model-3.jpg','published',0,1890,'2026-07-28 09:00:00',
 '[{"q":"How much does it cost to charge an EV at home?","a":"At the national average of ~16¢/kWh, a full Model 3 charge costs about $12 and covers 363 miles — around 3–5 cents per mile."},{"q":"Do EVs qualify for the $7,500 tax credit?","a":"It depends on current IRS sourcing rules; check the IRS Clean Vehicle list. Leased EVs often qualify through the manufacturer."}]'),

(5,2,NULL,4,'5 Summer Maintenance Tips Every Car Needs','summer-car-maintenance-tips',
 'Interstate heat punishes batteries, tires and coolant. Five checks that prevent the most common summer breakdowns in America.',
 '<p>Summer road-trip season is also roadside-assistance season. A little prevention beats waiting for a tow on I-40 in July.</p><h2>1. Battery health</h2><p>Heat kills batteries faster than cold. If yours is 3+ years old, most auto-parts stores will load-test it free.</p><h2>2. Coolant, not just water</h2><p>Verify the coolant level cold and confirm the mix matches your owner''s manual. Never open a hot radiator cap.</p><h2>3. Tire pressure rises with heat</h2><p>Check pressures cold, monthly — overinflated tires on 120°F asphalt are blowout candidates. Don''t forget the spare.</p><h2>4. A/C service</h2><p>A refrigerant top-up and condenser cleaning can drop cabin temps 6–10°F and cut compressor strain.</p><h2>5. Wipers and washer fluid</h2><p>Afternoon thunderstorms deserve working wipers. Replace cracked blades and top up washer fluid.</p>',
 'uploads/general/hero.jpg','published',0,960,'2026-07-15 09:00:00',NULL),

(6,1,9,1,'Kia Sportage 2026: Trims, Prices and What Changed','kia-sportage-2026-trims-prices',
 'Kia reshuffles the Sportage lineup for 2026 — here is what each trim adds and which one is worth your money.',
 '<p>Kia has streamlined the Sportage range for 2026, making the hybrid available on more trims and adding X-Line styling packages across the board.</p><h2>The lineup</h2><ul><li><strong>LX:</strong> 2.5L + 8AT, full safety suite, 12.3-inch screen</li><li><strong>EX:</strong> adds hybrid option, power liftgate, blind-spot view monitor</li><li><strong>X-Pro:</strong> AWD, all-terrain tires, locking center differential</li><li><strong>SX-Prestige:</strong> panoramic roof, ventilated seats, Harman Kardon</li></ul><h2>What changed</h2><p>Wireless CarPlay is standard now, the grill is restyled, and highway driving assist works on more road types.</p><h2>Which to buy</h2><p>The EX Hybrid. It keeps the important kit and returns 43 MPG combined — the X-Pro''s off-road hardware is wasted unless your GPS regularly loses pavement.</p>',
 'uploads/cars/kia-sportage.jpg','published',0,1240,'2026-08-30 09:00:00',NULL),

(7,1,NULL,3,'How to Inspect a Used Car Before Buying','used-car-inspection-checklist',
 'Title washing, flood cars and accident repairs — a practical 20-minute checklist before you hand over cash.',
 '<p>The used market is full of honest cars and cleverly disguised lemons. This 20-minute inspection catches 90% of the common tricks.</p><h2>Paperwork first</h2><p>Match the VIN on the dash, door jamb and title. Run the VIN through NICB.gov (free theft/flood check) and pull a vehicle-history report.</p><h2>Odometer fraud</h2><p>Compare mileage with service records, pedal wear and tire age codes. A 40,000-mile car with worn bolsters and bald pedals has stories.</p><h2>Flood damage</h2><p>Lift carpets, check seat-rail bolts for rust, and smell for damp. Test every switch — flood cars hide in electronics.</p><h2>Accident repairs</h2><p>Look for uneven panel gaps and paint texture differences. A paint-depth gauge ($30) finds filler a magnet misses on aluminum panels.</p><h2>The test drive</h2><p>Listen for suspension knocks, feel for steering pull, and brake firmly once from 60 mph. Then pay $150–$200 for a pre-purchase inspection — the best money in car buying.</p>',
 'uploads/cars/chevrolet-tahoe.jpg','published',0,1690,'2026-06-20 09:00:00',NULL);

INSERT INTO article_categories (article_id, category_id) VALUES
(1,3),(2,2),(2,1),(3,3),(4,5),(5,4),(6,1),(7,3);
INSERT INTO article_tags (article_id, tag_id) VALUES
(1,1),(1,11),(1,9),(1,8),(2,1),(2,12),(2,16),(3,2),(3,8),(3,12),(4,5),(4,14),(4,18),(4,16),(5,14),(6,10),(6,11),(7,17);

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
(1,'Mike R.','mike@example.com','Great comparison! I''d add the Honda CR-V to the compact list though — it deserves a mention.','approved'),
(1,'Jennifer K.','jen@example.com','The RAV4 hybrid really is that good. We average 39 MPG in real-world driving.','approved'),
(4,'Dave','dave@example.com','What about solar charging for EVs? Panels are getting cheap enough to matter.','pending');

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
('About Us','about-us','<h2>Who we are</h2><p>AutoPulse is an independent automotive publication based in the United States. We catalogue specifications, track prices and review the cars that matter to American buyers — without dealership pressure or advertising spin.</p><h2>How we work</h2><p>Every specification in our database is checked against manufacturer documentation, and prices reflect MSRP plus destination unless noted. Review verdicts are our own.</p><h2>Contact</h2><p>Corrections and tips are welcome via the <a href="/contact">contact page</a>.</p>',1,1),
('Privacy Policy','privacy-policy','<h2>Information we collect</h2><p>We collect the minimum needed to operate the site: anonymous analytics (if enabled), and the name/email you choose to provide when commenting or contacting us.</p><h2>How we use it</h2><p>Comments are published after moderation; your email is never displayed or sold. Newsletter subscriptions are used only to send updates and can be cancelled anytime.</p><h2>Cookies</h2><p>We use a single session cookie for site functionality (such as the comment form security token) and, when analytics is enabled, the provider''s cookies.</p><h2>Third-party ads</h2><p>Advertising partners may use cookies to serve relevant ads. You can control this via your browser settings or the Network Advertising Initiative opt-out page.</p><h2>Your rights</h2><p>US state residents (including California under CCPA/CPRA) may request access to or deletion of their personal information via the contact page.</p>',1,2),
('Terms & Conditions','terms-and-conditions','<h2>Use of content</h2><p>Articles and specifications are provided for personal, non-commercial use. Reproduction requires written permission and attribution.</p><h2>Accuracy</h2><p>We work hard to keep prices and specifications current, but they change frequently. Always confirm final figures with an authorized dealer.</p><h2>Comments</h2><p>You are responsible for what you post. We remove spam, abuse and misleading content at our discretion.</p><h2>Liability</h2><p>AutoPulse accepts no liability for decisions made based on site content.</p>',1,3),
('Disclaimer','disclaimer','<p>All prices shown are manufacturer suggested retail prices (MSRP) including estimated destination charges unless otherwise noted, and exclude taxes, title, license and dealer fees. Fuel economy figures are EPA estimates; your results will vary with driving conditions and style.</p><p>Vehicle availability and specifications differ by state and trim. This site is not affiliated with any manufacturer or dealership.</p>',1,4,
('Contact Us','contact','<p>Questions, corrections, partnership ideas or tips about the US new-car market — we read everything. Use the form below and our editorial team will get back to you within 2–3 working days.</p><p>For corrections, please include the page URL so we can fix it quickly. For advertising and partnerships, put “Partnership” in the subject line.</p>',1,5));

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
(1,2,'SUVs & Crossovers','category/suv',3),
(1,2,'Sedans','category/sedan',4),
(1,2,'Hatchbacks','category/hatchback',5),
(1,2,'Trucks','category/trucks',6),
(1,2,'Electric Cars','category/electric',7),
(1,NULL,'Articles','articles',3),
(1,9,'News','category/car-news',1),
(1,9,'Reviews','category/car-reviews',2),
(1,9,'Buying Guides','category/buying-guides',3),
(1,9,'Maintenance','category/maintenance',4),
(1,9,'EV & Hybrid','category/ev-hybrid',5),
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
('Ashley Thompson','ashley@example.com','Price correction — Model 3','Hi! The Model 3 Long Range price listed seems slightly outdated; my local dealer quoted a different number yesterday. Could you double-check? Thanks!');

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
('chevrolet-tahoe.jpg', 'uploads/cars/chevrolet-tahoe.jpg', 256667, 1),
('ford-f-150.jpg', 'uploads/cars/ford-f-150.jpg', 211324, 1),
('ford-mustang-gt.jpg', 'uploads/cars/ford-mustang-gt.jpg', 192205, 1),
('honda-civic.jpg', 'uploads/cars/honda-civic.jpg', 217864, 1),
('hyundai-tucson.jpg', 'uploads/cars/hyundai-tucson.jpg', 200830, 1),
('jeep-grand-cherokee.jpg', 'uploads/cars/jeep-grand-cherokee.jpg', 245672, 1),
('kia-sportage.jpg', 'uploads/cars/kia-sportage.jpg', 174648, 1),
('tesla-model-3.jpg', 'uploads/cars/tesla-model-3.jpg', 239026, 1),
('toyota-camry.jpg', 'uploads/cars/toyota-camry.jpg', 163707, 1),
('toyota-rav4.jpg', 'uploads/cars/toyota-rav4.jpg', 243295, 1),
('vw-golf-gti.jpg', 'uploads/cars/vw-golf-gti.jpg', 248245, 1),
('vw-tiguan.jpg', 'uploads/cars/vw-tiguan.jpg', 194670, 1),
('hero.jpg', 'uploads/general/hero.jpg', 174648, 1);

SET foreign_key_checks = 1;
