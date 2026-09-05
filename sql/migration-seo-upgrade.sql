-- AutoPulse — SEO/AEO/GEO upgrade migration
-- Run THIS FILE in phpMyAdmin ONLY IF your database was created with an
-- older version of database.sql (before the SEO/AEO/GEO upgrade).
-- If you are installing fresh, just import database.sql — skip this file.

ALTER TABLE articles
  ADD COLUMN og_title VARCHAR(150) NULL,
  ADD COLUMN og_description VARCHAR(300) NULL,
  ADD COLUMN og_image VARCHAR(255) NULL,
  ADD COLUMN robots VARCHAR(40) NULL DEFAULT 'index, follow',
  ADD COLUMN focus_keyword VARCHAR(120) NULL,
  ADD COLUMN secondary_keywords VARCHAR(500) NULL,
  ADD COLUMN search_intent VARCHAR(40) NULL,
  ADD COLUMN quick_answer TEXT NULL,
  ADD COLUMN last_verified_at DATETIME NULL,
  ADD COLUMN fact_checked_by VARCHAR(120) NULL,
  ADD COLUMN updated_by INT UNSIGNED NULL;

ALTER TABLE car_models
  ADD COLUMN og_title VARCHAR(150) NULL,
  ADD COLUMN og_description VARCHAR(300) NULL,
  ADD COLUMN og_image VARCHAR(255) NULL,
  ADD COLUMN canonical_url VARCHAR(255) NULL,
  ADD COLUMN robots VARCHAR(40) NULL DEFAULT 'index, follow',
  ADD COLUMN focus_keyword VARCHAR(120) NULL,
  ADD COLUMN quick_answer TEXT NULL,
  ADD COLUMN last_verified_at DATETIME NULL,
  ADD COLUMN updated_by INT UNSIGNED NULL;

ALTER TABLE users
  ADD COLUMN expertise VARCHAR(255) NULL,
  ADD COLUMN social_twitter VARCHAR(255) NULL,
  ADD COLUMN social_linkedin VARCHAR(255) NULL;

ALTER TABLE media
  ADD COLUMN caption VARCHAR(300) NULL,
  ADD COLUMN description VARCHAR(500) NULL,
  ADD COLUMN title VARCHAR(190) NULL,
  ADD COLUMN width SMALLINT UNSIGNED NULL,
  ADD COLUMN height SMALLINT UNSIGNED NULL;

ALTER TABLE redirects
  ADD COLUMN notes VARCHAR(255) NULL,
  ADD COLUMN hits INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS content_sources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('article','car') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  url VARCHAR(255) NULL,
  source_type ENUM('manufacturer','government','regulator','research','official_documentation','independent_testing','other') NOT NULL DEFAULT 'other',
  published_date DATE NULL,
  accessed_date DATE NULL,
  notes VARCHAR(300) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS answer_blocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('article','car') NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  question VARCHAR(300) NOT NULL,
  short_answer TEXT NOT NULL,
  explanation TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value) VALUES
  ('org_description', 'Independent automotive publication cataloguing US-market car specifications, prices and reviews.');

INSERT INTO pages (title, slug, content, status, show_in_footer, sort_order, seo_title, meta_description) VALUES
('Editorial Policy','editorial-policy',
'<h2>How we create content</h2><p>Specifications, prices and features on this site are compiled from manufacturer documentation and official EPA data. Review verdicts are the independent opinions of our editors.</p><h2>Corrections</h2><p>Errors happen. If you spot one, use the contact page with the page URL and we will correct it and note the change.</p><h2>Independence</h2><p>We are not affiliated with any manufacturer or dealership. Advertising, where present, is labeled and does not influence editorial conclusions.</p>',
'published',1,6,'Editorial Policy','How we research, write and correct car specifications, prices and reviews.'),
('Review Methodology','review-methodology',
'<h2>Data sources</h2><p>Every specification is checked against manufacturer documentation. Prices are MSRP plus destination charges unless noted. Fuel economy figures are EPA estimates.</p><h2>Verdicts</h2><p>Pros and cons reflect how a car compares with its direct segment rivals on the criteria buyers in that segment care about most.</p><h2>Freshness</h2><p>Pages carry a "last verified" date. Dynamic information such as pricing is re-checked when the model year or manufacturer data changes.</p>',
'published',1,7,'Review Methodology','How we verify car specifications, prices and review verdicts.');

-- Optional: mark existing cars/articles as verified on today's date
-- UPDATE car_models SET last_verified_at = NOW() WHERE last_verified_at IS NULL;
-- UPDATE articles SET last_verified_at = updated_at WHERE last_verified_at IS NULL;
