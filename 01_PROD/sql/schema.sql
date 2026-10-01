-- =====================================================================
-- AFAMSHOP - Schéma de base de données MySQL / MariaDB
-- Encodage : utf8mb4
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Paramètres du site (tout le contenu modifiable depuis l'admin)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  skey        VARCHAR(100) NOT NULL PRIMARY KEY,
  svalue      MEDIUMTEXT NULL,
  sgroup      VARCHAR(50) NOT NULL DEFAULT 'general',
  label       VARCHAR(190) NOT NULL DEFAULT '',
  type        VARCHAR(20) NOT NULL DEFAULT 'text', -- text, textarea, html, image, video, bool, number, email, color, url
  sort        INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS currencies;
CREATE TABLE currencies (
  code        CHAR(3) NOT NULL PRIMARY KEY,
  name        VARCHAR(50) NOT NULL,
  symbol      VARCHAR(10) NOT NULL,
  rate        DECIMAL(20,10) NOT NULL DEFAULT 1, -- 1 unité de devise de base = rate unités de cette devise
  decimals    TINYINT NOT NULL DEFAULT 0,
  symbol_after TINYINT(1) NOT NULL DEFAULT 1,
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Administrateurs, sécurité, logs
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('super_admin','manager','sales','editor') NOT NULL DEFAULT 'editor',
  active        TINYINT(1) NOT NULL DEFAULT 1,
  last_login    DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS login_attempts;
CREATE TABLE login_attempts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope        VARCHAR(20) NOT NULL,         -- admin / customer
  identifier   VARCHAR(190) NOT NULL,
  ip           VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_scope_ident (scope, identifier, attempted_at),
  INDEX idx_scope_ip (scope, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS admin_logs;
CREATE TABLE admin_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NULL,
  action      VARCHAR(60) NOT NULL,
  entity      VARCHAR(60) NULL,
  entity_id   INT UNSIGNED NULL,
  details     TEXT NULL,
  ip          VARCHAR(45) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Clients
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_type  ENUM('individual','business','administration') NOT NULL DEFAULT 'individual',
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  company       VARCHAR(190) NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  phone         VARCHAR(40) NULL,
  password_hash VARCHAR(255) NOT NULL,
  status        ENUM('active','blocked') NOT NULL DEFAULT 'active',
  reset_token   VARCHAR(64) NULL,
  reset_expires DATETIME NULL,
  last_login    DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  label       VARCHAR(60) NULL,
  full_name   VARCHAR(190) NOT NULL,
  phone       VARCHAR(40) NULL,
  address     VARCHAR(255) NOT NULL,
  city        VARCHAR(120) NOT NULL,
  zone_id     INT UNSIGNED NULL,
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catalogue
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id    INT UNSIGNED NULL,
  name         VARCHAR(150) NOT NULL,
  slug         VARCHAR(170) NOT NULL UNIQUE,
  description  TEXT NULL,
  image        VARCHAR(255) NULL,
  icon         VARCHAR(40) NULL,
  sort         INT NOT NULL DEFAULT 0,
  show_in_menu TINYINT(1) NOT NULL DEFAULT 1,
  active       TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS brands;
CREATE TABLE brands (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  slug        VARCHAR(140) NOT NULL UNIQUE,
  logo        VARCHAR(255) NULL,
  description TEXT NULL,
  featured    TINYINT(1) NOT NULL DEFAULT 0,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  sort        INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku                 VARCHAR(80) NOT NULL UNIQUE,
  manufacturer_ref    VARCHAR(120) NULL,
  name                VARCHAR(255) NOT NULL,
  slug                VARCHAR(270) NOT NULL UNIQUE,
  brand_id            INT UNSIGNED NULL,
  category_id         INT UNSIGNED NULL,
  product_type        VARCHAR(80) NULL,
  color               VARCHAR(60) NULL,
  short_description   TEXT NULL,
  description         MEDIUMTEXT NULL,
  specs               MEDIUMTEXT NULL,    -- une caractéristique par ligne "Clé: valeur"
  price               DECIMAL(14,2) NOT NULL DEFAULT 0,
  promo_price         DECIMAL(14,2) NULL,
  promo_start         DATETIME NULL,
  promo_end           DATETIME NULL,
  stock               INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NULL,
  on_order            TINYINT(1) NOT NULL DEFAULT 0, -- disponible sur commande quand le stock est épuisé
  weight              VARCHAR(40) NULL,
  dimensions          VARCHAR(80) NULL,
  warranty            VARCHAR(120) NULL,
  delivery_delay      VARCHAR(120) NULL,
  is_featured         TINYINT(1) NOT NULL DEFAULT 0,
  published           TINYINT(1) NOT NULL DEFAULT 1,
  sales_count         INT NOT NULL DEFAULT 0,
  views               INT NOT NULL DEFAULT 0,
  meta_title          VARCHAR(255) NULL,
  meta_description    VARCHAR(255) NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brand (brand_id),
  INDEX idx_category (category_id),
  INDEX idx_published (published),
  INDEX idx_mref (manufacturer_ref),
  FULLTEXT KEY ft_search (name, sku, manufacturer_ref, short_description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  path        VARCHAR(255) NOT NULL,
  alt         VARCHAR(255) NULL,
  is_main     TINYINT(1) NOT NULL DEFAULT 0,
  sort        INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS printer_models;
CREATE TABLE printer_models (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  brand_id  INT UNSIGNED NOT NULL,
  name      VARCHAR(150) NOT NULL,
  slug      VARCHAR(170) NOT NULL UNIQUE,
  series    VARCHAR(120) NULL,
  active    TINYINT(1) NOT NULL DEFAULT 1,
  INDEX idx_brand (brand_id),
  FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS product_compatibility;
CREATE TABLE product_compatibility (
  product_id       INT UNSIGNED NOT NULL,
  printer_model_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, printer_model_id),
  INDEX idx_model (printer_model_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (printer_model_id) REFERENCES printer_models(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS stock_movements;
CREATE TABLE stock_movements (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  qty_change  INT NOT NULL,
  stock_after INT NOT NULL,
  reason      VARCHAR(60) NOT NULL,  -- order, cancel, manual, import, restock
  order_id    INT UNSIGNED NULL,
  admin_id    INT UNSIGNED NULL,
  note        VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_product (product_id, created_at),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  name        VARCHAR(120) NOT NULL,
  rating      TINYINT NOT NULL,
  title       VARCHAR(190) NULL,
  body        TEXT NOT NULL,
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_product (product_id, status),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS favorites;
CREATE TABLE favorites (
  customer_id INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id, product_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Promotions, coupons
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS promotions;
CREATE TABLE promotions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  discount_type ENUM('percent','amount') NOT NULL DEFAULT 'percent',
  value       DECIMAL(14,2) NOT NULL,
  scope       ENUM('product','category','brand','all') NOT NULL DEFAULT 'product',
  target_id   INT UNSIGNED NULL,
  start_at    DATETIME NULL,
  end_at      DATETIME NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS coupons;
CREATE TABLE coupons (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code               VARCHAR(50) NOT NULL UNIQUE,
  discount_type      ENUM('percent','amount') NOT NULL DEFAULT 'percent',
  value              DECIMAL(14,2) NOT NULL,
  min_amount         DECIMAL(14,2) NULL,
  max_uses           INT NULL,
  uses               INT NOT NULL DEFAULT 0,
  per_customer_limit INT NULL,
  start_at           DATETIME NULL,
  end_at             DATETIME NULL,
  active             TINYINT(1) NOT NULL DEFAULT 1,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Livraison
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS delivery_zones;
CREATE TABLE delivery_zones (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  fee            DECIMAL(14,2) NOT NULL DEFAULT 0,
  free_threshold DECIMAL(14,2) NULL, -- NULL = seuil global des paramètres
  delay          VARCHAR(80) NULL,
  active         TINYINT(1) NOT NULL DEFAULT 1,
  sort           INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Commandes et paiements
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number     VARCHAR(30) NOT NULL UNIQUE,
  access_token     VARCHAR(64) NOT NULL,
  customer_id      INT UNSIGNED NULL,
  email            VARCHAR(190) NOT NULL,
  phone            VARCHAR(40) NOT NULL,
  first_name       VARCHAR(100) NOT NULL,
  last_name        VARCHAR(100) NOT NULL,
  company          VARCHAR(190) NULL,
  delivery_method  ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  ship_address     VARCHAR(255) NULL,
  ship_city        VARCHAR(120) NULL,
  zone_id          INT UNSIGNED NULL,
  zone_name        VARCHAR(120) NULL,
  subtotal         DECIMAL(14,2) NOT NULL DEFAULT 0,
  discount         DECIMAL(14,2) NOT NULL DEFAULT 0,
  coupon_code      VARCHAR(50) NULL,
  delivery_fee     DECIMAL(14,2) NOT NULL DEFAULT 0,
  tax_rate         DECIMAL(5,2) NOT NULL DEFAULT 0,
  tax_amount       DECIMAL(14,2) NOT NULL DEFAULT 0,
  total            DECIMAL(14,2) NOT NULL DEFAULT 0,
  currency         CHAR(3) NOT NULL DEFAULT 'XOF',
  payment_method   VARCHAR(30) NOT NULL,
  payment_status   ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  status           ENUM('received','paid','preparing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'received',
  notes            TEXT NULL,
  admin_notes      TEXT NULL,
  stock_decremented TINYINT(1) NOT NULL DEFAULT 0,
  invoice_number   VARCHAR(30) NULL UNIQUE,
  invoice_date     DATETIME NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer (customer_id),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NULL,
  sku         VARCHAR(80) NOT NULL,
  name        VARCHAR(255) NOT NULL,
  unit_price  DECIMAL(14,2) NOT NULL,
  qty         INT NOT NULL,
  line_total  DECIMAL(14,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS order_status_history;
CREATE TABLE order_status_history (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  status      VARCHAR(30) NOT NULL,
  comment     VARCHAR(255) NULL,
  admin_id    INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS coupon_usages;
CREATE TABLE coupon_usages (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id   INT UNSIGNED NOT NULL,
  order_id    INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NULL,
  email       VARCHAR(190) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_coupon (coupon_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id       INT UNSIGNED NOT NULL,
  provider       VARCHAR(30) NOT NULL,      -- stripe, paydunya, cod
  reference      VARCHAR(190) NULL,         -- id session Stripe / token PayDunya
  transaction_id VARCHAR(190) NULL,         -- payment_intent / receipt
  amount         DECIMAL(14,2) NOT NULL,
  currency       CHAR(3) NOT NULL,
  status         ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  raw_response   MEDIUMTEXT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order (order_id),
  INDEX idx_ref (reference),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Demandes : devis, location, maintenance, contact
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS requests;
CREATE TABLE requests (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type          ENUM('quote','rental','maintenance','contact') NOT NULL,
  customer_id   INT UNSIGNED NULL,
  name          VARCHAR(150) NOT NULL,
  company       VARCHAR(190) NULL,
  phone         VARCHAR(40) NULL,
  email         VARCHAR(190) NOT NULL,
  product_id    INT UNSIGNED NULL,
  product_label VARCHAR(255) NULL,
  quantity      INT NULL,
  printer_model VARCHAR(150) NULL,
  serial_number VARCHAR(120) NULL,
  subject       VARCHAR(190) NULL,
  message       TEXT NULL,
  attachment    VARCHAR(255) NULL,
  status        ENUM('new','in_progress','done','closed') NOT NULL DEFAULT 'new',
  admin_notes   TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_type (type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Contenus : bannières, pages, services, médias
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS banners;
CREATE TABLE banners (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position      VARCHAR(40) NOT NULL DEFAULT 'home_hero', -- home_hero, home_promo
  title         VARCHAR(190) NULL,
  subtitle      VARCHAR(255) NULL,
  button_text   VARCHAR(80) NULL,
  link          VARCHAR(255) NULL,
  image_desktop VARCHAR(255) NULL,
  image_mobile  VARCHAR(255) NULL,
  start_at      DATETIME NULL,
  end_at        DATETIME NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  sort          INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS pages;
CREATE TABLE pages (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(120) NOT NULL UNIQUE,
  title            VARCHAR(190) NOT NULL,
  content          MEDIUMTEXT NULL,
  meta_title       VARCHAR(255) NULL,
  meta_description VARCHAR(255) NULL,
  footer_group     VARCHAR(30) NULL,  -- company, help, legal, NULL = hors footer
  published        TINYINT(1) NOT NULL DEFAULT 1,
  sort             INT NOT NULL DEFAULT 0,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS services;
CREATE TABLE services (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  title       VARCHAR(190) NOT NULL,
  icon        VARCHAR(40) NULL,
  short_desc  VARCHAR(255) NULL,
  content     MEDIUMTEXT NULL,
  image       VARCHAR(255) NULL,
  form_type   VARCHAR(20) NULL,   -- rental, maintenance, quote : formulaire affiché sur la page
  sort        INT NOT NULL DEFAULT 0,
  active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS media;
CREATE TABLE media (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path        VARCHAR(255) NOT NULL,
  original    VARCHAR(255) NULL,
  mime        VARCHAR(100) NULL,
  size        INT UNSIGNED NULL,
  admin_id    INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
