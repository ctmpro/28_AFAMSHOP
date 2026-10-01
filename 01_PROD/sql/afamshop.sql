-- AFAMSHOP : base complète (schéma + paramètres + catalogue de démonstration)
-- Import via phpMyAdmin, puis ouvrir /admin/setup.php pour créer le premier Super Admin.
SET NAMES utf8mb4;
/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `label` varchar(60) DEFAULT NULL,
  `full_name` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(120) NOT NULL,
  `zone_id` int(10) unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `entity` varchar(60) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','manager','sales','editor') NOT NULL DEFAULT 'editor',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position` varchar(40) NOT NULL DEFAULT 'home_hero',
  `title` varchar(190) DEFAULT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `button_text` varchar(80) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `image_desktop` varchar(255) DEFAULT NULL,
  `image_mobile` varchar(255) DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES
(1,'home_promo','Jusqu\'à -15 % sur les toners Sharp','Consommables d\'origine, livraison rapide','J\'en profite','promotions','assets/img/contenus/promo-toners.jpg',NULL,NULL,NULL,1,1),
(2,'home_promo','Location de multifonctions','Maintenance et consommables inclus','Demander une étude','service/location','assets/img/contenus/promo-location.jpg',NULL,NULL,NULL,1,2),
(3,'home_hero','Multifonctions Sharp','Performance, économies et respect de l\'environnement','Découvrir','marque/sharp','assets/img/contenus/slide-sharp.jpg',NULL,NULL,NULL,1,1),
(4,'home_hero','Informatique professionnelle','Ordinateurs, écrans, onduleurs et accessoires','Voir le catalogue','categorie/informatique','assets/img/contenus/slide-informatique.jpg',NULL,NULL,NULL,1,2);
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES
(1,'Sharp','sharp',NULL,NULL,1,1,0),
(2,'Canon','canon',NULL,NULL,1,1,1),
(3,'HP','hp',NULL,NULL,1,1,2),
(4,'Ricoh','ricoh',NULL,NULL,1,1,3),
(5,'Epson','epson',NULL,NULL,0,1,4),
(6,'Brother','brother',NULL,NULL,0,1,5),
(7,'Dell','dell',NULL,NULL,0,1,6),
(8,'Lenovo','lenovo',NULL,NULL,0,1,7),
(9,'APC','apc',NULL,NULL,0,1,8),
(10,'Logitech','logitech',NULL,NULL,0,1,9),
(11,'Kingston','kingston',NULL,NULL,0,1,10),
(12,'Navigator','navigator',NULL,NULL,0,1,11),
(13,'Bic','bic',NULL,NULL,0,1,12);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `show_in_menu` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,NULL,'Impression','impression',NULL,'assets/img/contenus/cat-impression.jpg','printer',0,1,1),
(2,1,'Imprimantes laser','imprimantes-laser',NULL,NULL,NULL,0,1,1),
(3,1,'Imprimantes jet d\'encre','imprimantes-jet-d-encre',NULL,NULL,NULL,1,1,1),
(4,1,'Multifonctions','multifonctions',NULL,NULL,NULL,2,1,1),
(5,1,'Copieurs','copieurs',NULL,NULL,NULL,3,1,1),
(6,1,'Imprimantes professionnelles','imprimantes-professionnelles',NULL,NULL,NULL,4,1,1),
(7,1,'Imprimantes couleur','imprimantes-couleur',NULL,NULL,NULL,5,1,1),
(8,1,'Imprimantes monochromes','imprimantes-monochromes',NULL,NULL,NULL,6,1,1),
(9,1,'Traceurs','traceurs',NULL,NULL,NULL,7,1,1),
(10,NULL,'Consommables','consommables',NULL,'assets/img/contenus/cat-consommables.jpg','drop',1,1,1),
(11,10,'Toners','toners',NULL,NULL,NULL,0,1,1),
(12,10,'Cartouches d\'encre','cartouches-d-encre',NULL,NULL,NULL,1,1,1),
(13,10,'Tambours','tambours',NULL,NULL,NULL,2,1,1),
(14,10,'Kits de maintenance','kits-de-maintenance',NULL,NULL,NULL,3,1,1),
(15,10,'Rubans','rubans',NULL,NULL,NULL,4,1,1),
(16,10,'Encres','encres',NULL,NULL,NULL,5,1,1),
(17,10,'Consommables multifonctions','consommables-multifonctions',NULL,NULL,NULL,6,1,1),
(18,NULL,'Informatique','informatique',NULL,'assets/img/contenus/cat-informatique.jpg','laptop',2,1,1),
(19,18,'Ordinateurs portables','ordinateurs-portables',NULL,NULL,NULL,0,1,1),
(20,18,'Ordinateurs de bureau','ordinateurs-de-bureau',NULL,NULL,NULL,1,1,1),
(21,18,'Écrans','ecrans',NULL,NULL,NULL,2,1,1),
(22,18,'Claviers et souris','claviers-et-souris',NULL,NULL,NULL,3,1,1),
(23,18,'Onduleurs','onduleurs',NULL,NULL,NULL,4,1,1),
(24,18,'Stockage','stockage',NULL,NULL,NULL,5,1,1),
(25,18,'Clés USB','cles-usb',NULL,NULL,NULL,6,1,1),
(26,18,'Réseau','reseau',NULL,NULL,NULL,7,1,1),
(27,18,'Accessoires informatiques','accessoires-informatiques',NULL,NULL,NULL,8,1,1),
(28,NULL,'Papeterie','papeterie',NULL,'assets/img/contenus/cat-papeterie.jpg','pen',3,1,1),
(29,28,'Papier et ramettes','papier-et-ramettes',NULL,NULL,NULL,0,1,1),
(30,28,'Enveloppes','enveloppes',NULL,NULL,NULL,1,1,1),
(31,28,'Cahiers','cahiers',NULL,NULL,NULL,2,1,1),
(32,28,'Classeurs','classeurs',NULL,NULL,NULL,3,1,1),
(33,28,'Stylos et crayons','stylos-et-crayons',NULL,NULL,NULL,4,1,1),
(34,28,'Agrafes et trombones','agrafes-et-trombones',NULL,NULL,NULL,5,1,1),
(35,28,'Fournitures de bureau','fournitures-de-bureau',NULL,NULL,NULL,6,1,1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon` (`coupon_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `value` decimal(14,2) NOT NULL,
  `min_amount` decimal(14,2) DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `uses` int(11) NOT NULL DEFAULT 0,
  `per_customer_limit` int(11) DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES
(1,'BIENVENUE10','percent',10.00,20000.00,NULL,0,1,NULL,NULL,1,'2026-10-01 02:01:16');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `code` char(3) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `rate` decimal(20,10) NOT NULL DEFAULT 1.0000000000,
  `decimals` tinyint(4) NOT NULL DEFAULT 0,
  `symbol_after` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES
('EUR','Euro','€',0.0015244902,2,1,0,1),
('USD','Dollar US','$',0.0016500000,2,0,0,0),
('XOF','Franc CFA','FCFA',1.0000000000,0,1,1,1);
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_type` enum('individual','business','administration') NOT NULL DEFAULT 'individual',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `company` varchar(190) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `delivery_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_zones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `free_threshold` decimal(14,2) DEFAULT NULL,
  `delay` varchar(80) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `delivery_zones` WRITE;
/*!40000 ALTER TABLE `delivery_zones` DISABLE KEYS */;
INSERT INTO `delivery_zones` VALUES
(1,'Dakar Plateau',2000.00,NULL,'24 h',1,1),
(2,'Dakar (autres communes)',3000.00,NULL,'24 à 48 h',1,2),
(3,'Pikine / Guédiawaye / Rufisque',4000.00,NULL,'48 h',1,3),
(4,'Thiès / Mbour',6000.00,250000.00,'48 à 72 h',1,4),
(5,'Autres régions',10000.00,500000.00,'3 à 5 jours',1,5);
/*!40000 ALTER TABLE `delivery_zones` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `customer_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(20) NOT NULL,
  `identifier` varchar(190) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scope_ident` (`scope`,`identifier`,`attempted_at`),
  KEY `idx_scope_ip` (`scope`,`ip`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `original` varchar(255) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) NOT NULL,
  `source` enum('file','manual') NOT NULL DEFAULT 'file',
  `checksum` char(64) DEFAULT NULL,
  `sql_content` mediumtext DEFAULT NULL,
  `status` enum('success','error','marked') NOT NULL,
  `statements` int(11) NOT NULL DEFAULT 0,
  `error` text DEFAULT NULL,
  `duration_ms` int(11) NOT NULL DEFAULT 0,
  `backup_file` varchar(190) DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `executed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2026-10-01_images-et-devises.sql','file','63833e490fb93b0555c1b151521d82fa4a60ee950d8bc0762708688d3d104cc3','-- =====================================================================\n-- AFAMSHOP — mise à jour d\'une base déjà installée (octobre 2026)\n--  * Active l\'affichage des prix en euros (parité fixe 1 € = 655,957 FCFA)\n--  * Met en place les images par défaut (uniquement là où aucune image n\'est définie)\n-- Exécutée depuis Admin › Migration (ou importée dans phpMyAdmin). Sans risque si relancée.\n-- =====================================================================\nSET NAMES utf8mb4;\n\n-- Devises : FCFA (paiement) + Euro (affichage) — taux stocké avec plus de précision\nALTER TABLE currencies MODIFY rate DECIMAL(20,10) NOT NULL DEFAULT 1;\nINSERT INTO currencies (code, name, symbol, rate, decimals, symbol_after, is_default, active)\nVALUES (\'EUR\', \'Euro\', \'€\', 0.0015244902, 2, 1, 0, 1)\nON DUPLICATE KEY UPDATE active = 1, rate = 0.0015244902, decimals = 2;\n\n-- Paramètres du site (images vides uniquement)\nUPDATE settings SET svalue = \'assets/img/contenus/logo.png\'             WHERE skey = \'logo\'                   AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/logo-blanc.png\'       WHERE skey = \'logo_footer\'            AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/favicon.png\'          WHERE skey = \'favicon\'                AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/hero.jpg\'             WHERE skey = \'hero_image\'             AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/home-pro.jpg\'         WHERE skey = \'home_pro_image\'         AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/home-location.jpg\'    WHERE skey = \'home_rental_image\'      AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/home-maintenance.jpg\' WHERE skey = \'home_maintenance_image\' AND (svalue IS NULL OR svalue = \'\');\nUPDATE settings SET svalue = \'assets/img/contenus/home-sharp.jpg\'       WHERE skey = \'home_sharp_image\'       AND (svalue IS NULL OR svalue = \'\');\n\n-- Catégories principales\nUPDATE categories SET image = \'assets/img/contenus/cat-impression.jpg\'   WHERE slug = \'impression\'   AND (image IS NULL OR image = \'\');\nUPDATE categories SET image = \'assets/img/contenus/cat-consommables.jpg\' WHERE slug = \'consommables\' AND (image IS NULL OR image = \'\');\nUPDATE categories SET image = \'assets/img/contenus/cat-informatique.jpg\' WHERE slug = \'informatique\' AND (image IS NULL OR image = \'\');\nUPDATE categories SET image = \'assets/img/contenus/cat-papeterie.jpg\'    WHERE slug = \'papeterie\'    AND (image IS NULL OR image = \'\');\n\n-- Services\nUPDATE services SET image = \'assets/img/contenus/service-location.jpg\'    WHERE slug = \'location\'             AND (image IS NULL OR image = \'\');\nUPDATE services SET image = \'assets/img/contenus/service-maintenance.jpg\' WHERE slug = \'maintenance\'          AND (image IS NULL OR image = \'\');\nUPDATE services SET image = \'assets/img/contenus/service-solutions.jpg\'   WHERE slug = \'solutions-impression\' AND (image IS NULL OR image = \'\');\nUPDATE services SET image = \'assets/img/contenus/service-vente.jpg\'       WHERE slug = \'vente-materiel\'       AND (image IS NULL OR image = \'\');\n\n-- Bannières promotionnelles existantes\nUPDATE banners SET image_desktop = \'assets/img/contenus/promo-toners.jpg\'   WHERE position = \'home_promo\' AND link = \'promotions\'       AND (image_desktop IS NULL OR image_desktop = \'\');\nUPDATE banners SET image_desktop = \'assets/img/contenus/promo-location.jpg\' WHERE position = \'home_promo\' AND link = \'service/location\' AND (image_desktop IS NULL OR image_desktop = \'\');\n\n-- Diaporama d\'accueil (ajouté seulement s\'il n\'existe encore aucune diapositive)\nINSERT INTO banners (position, title, subtitle, button_text, link, image_desktop, sort)\nSELECT \'home_hero\', \'Multifonctions Sharp\', \'Performance, économies et respect de l\'\'environnement\', \'Découvrir\', \'marque/sharp\', \'assets/img/contenus/slide-sharp.jpg\', 1\nFROM DUAL WHERE NOT EXISTS (SELECT 1 FROM banners WHERE position = \'home_hero\');\nINSERT INTO banners (position, title, subtitle, button_text, link, image_desktop, sort)\nSELECT \'home_hero\', \'Informatique professionnelle\', \'Ordinateurs, écrans, onduleurs et accessoires\', \'Voir le catalogue\', \'categorie/informatique\', \'assets/img/contenus/slide-informatique.jpg\', 2\nFROM DUAL WHERE (SELECT COUNT(*) FROM banners WHERE position = \'home_hero\') = 1;\n','marked',0,NULL,0,NULL,NULL,'2026-10-01 02:01:16'),
(2,'2026-10-02_pages-legales.sql','file','9b82cd2aae224543cb218819d5924917ae053bede235b178fc434b6dc26edfbc','-- =====================================================================\n-- AFAMSHOP — Pages légales (CGV, mentions légales, confidentialité)\n-- + crédit Neosen en pied de page et nouveaux paramètres (représentant légal, hébergeur)\n-- Remplace le contenu actuel des 3 pages légales. Exécutable depuis Admin › Migration.\n-- =====================================================================\nSET NAMES utf8mb4;\n\nINSERT IGNORE INTO settings (skey, svalue, sgroup, label, type, sort) VALUES (\'legal_representative\', \'Le gérant de AFAM\', \'general\', \'Directeur de la publication / représentant légal (pages légales)\', \'text\', 13);\nINSERT IGNORE INTO settings (skey, svalue, sgroup, label, type, sort) VALUES (\'host_info\', \'Nom de l\\\'hébergeur — adresse — téléphone (à compléter)\', \'general\', \'Hébergeur du site (mentions légales)\', \'textarea\', 14);\nINSERT IGNORE INTO settings (skey, svalue, sgroup, label, type, sort) VALUES (\'footer_credit\', \'Site réalisé par Neosen\', \'footer\', \'Crédit du concepteur (pied de page)\', \'text\', 80);\nINSERT IGNORE INTO settings (skey, svalue, sgroup, label, type, sort) VALUES (\'footer_credit_url\', \'https://neosen.tech\', \'footer\', \'Lien du crédit\', \'url\', 81);\n\nINSERT INTO pages (slug, title, content, footer_group, published, sort) VALUES (\'cgv\', \'Conditions générales de vente\', \'<p><em>Conditions générales de vente en vigueur à compter du 1<sup>er</sup> octobre 2026.</em></p>\\n\\n<h2>Article 1 — Objet</h2>\\n<p>Les présentes conditions générales de vente (ci-après « CGV ») régissent les ventes de produits et les demandes de services conclues à distance sur le site <strong>{domain}</strong> (ci-après « le Site »), exploité par <strong>{company_legal_name}</strong>, {address}, {company_ids} (ci-après « {company_name} » ou « le Vendeur »).</p>\\n<p>Toute commande passée sur le Site implique l\\\'acceptation pleine et entière des présentes CGV, que le client reconnaît avoir lues avant de valider sa commande.</p>\\n\\n<h2>Article 2 — Clients</h2>\\n<p>Le Site s\\\'adresse aux particuliers, professionnels, entreprises et administrations (ci-après « le Client »). Le Client déclare être majeur et disposer de la capacité juridique de contracter, ou être dûment habilité à engager la structure qu\\\'il représente.</p>\\n<p>Les ventes en volume, les marchés publics et les contrats de location ou de maintenance peuvent faire l\\\'objet de conditions particulières (devis, bon de commande, contrat) qui prévalent alors sur les présentes CGV.</p>\\n\\n<h2>Article 3 — Produits</h2>\\n<p>Les produits proposés (imprimantes, multifonctions, copieurs, consommables, matériel informatique, papeterie et fournitures de bureau) sont décrits avec la plus grande exactitude possible. Les photographies, illustrations et caractéristiques sont communiquées à titre indicatif et n\\\'ont pas de valeur contractuelle.</p>\\n<p>Les informations de compatibilité des consommables sont fournies sur la base des données des constructeurs. En cas de doute, le Client est invité à contacter {company_name} avant sa commande.</p>\\n<p>Les offres sont valables dans la limite des stocks disponibles. La disponibilité est indiquée sur chaque fiche produit (« En stock », « Stock faible », « Sur commande », « Rupture »).</p>\\n\\n<h2>Article 4 — Prix</h2>\\n<p>Les prix sont indiqués en francs CFA (FCFA / XOF), toutes taxes comprises (TVA au taux en vigueur incluse), hors frais de livraison. Les frais de livraison sont précisés avant la validation de la commande.</p>\\n<p>L\\\'affichage des prix en euros est proposé à titre purement indicatif, sur la base de la parité fixe 1 € = 655,957 FCFA. La commande est conclue et payée en francs CFA.</p>\\n<p>{company_name} se réserve le droit de modifier ses prix à tout moment. Les produits sont facturés au prix en vigueur au moment de la validation de la commande. En cas d\\\'erreur manifeste de prix, {company_name} pourra annuler la commande après en avoir informé le Client, qui sera intégralement remboursé.</p>\\n\\n<h2>Article 5 — Commande</h2>\\n<p>Le Client peut commander avec ou sans création de compte. Le processus de commande comprend les étapes suivantes : sélection des produits, vérification du panier, saisie des informations et de l\\\'adresse de livraison, choix du mode de livraison et du moyen de paiement, acceptation des CGV, puis validation et paiement.</p>\\n<p>La validation de la commande vaut acceptation de son contenu et de son prix. Un email de confirmation récapitulant la commande est adressé au Client. {company_name} se réserve le droit d\\\'annuler ou de refuser toute commande d\\\'un Client avec lequel existerait un litige relatif au paiement d\\\'une commande antérieure, ou en cas de suspicion de fraude.</p>\\n<p>Les codes promotionnels sont soumis à leurs conditions propres (durée de validité, montant minimum d\\\'achat, nombre d\\\'utilisations) et ne sont pas cumulables, sauf mention contraire.</p>\\n\\n<h2>Article 6 — Paiement</h2>\\n<p>Le prix est payable en totalité à la commande, selon l\\\'un des moyens proposés :</p>\\n<ul>\\n<li><strong>Carte bancaire</strong> (Visa, Mastercard…), via la plateforme sécurisée Stripe ;</li>\\n<li><strong>Mobile money</strong> (Wave, Orange Money et autres moyens disponibles), via la plateforme sécurisée PayDunya ;</li>\\n<li><strong>Paiement à la livraison</strong> ou <strong>virement bancaire</strong>, lorsque ces options sont proposées.</li>\\n</ul>\\n<p>Les données bancaires sont saisies directement sur les pages sécurisées des prestataires de paiement et ne sont jamais stockées par {company_name}. La commande est considérée comme payée après confirmation de l\\\'encaissement par le prestataire. En cas de refus ou d\\\'échec du paiement, la commande n\\\'est pas traitée.</p>\\n\\n<h2>Article 7 — Livraison et retrait</h2>\\n<p>Les produits sont livrés à l\\\'adresse indiquée par le Client, dans les zones desservies au Sénégal. Les frais et délais indicatifs dépendent de la zone de livraison et sont précisés lors de la commande ; la livraison peut être offerte au-delà d\\\'un montant d\\\'achat indiqué sur le Site. Le Client peut également choisir le retrait gratuit à l\\\'adresse de retrait indiquée, lorsque cette option est proposée.</p>\\n<p>Les délais de livraison sont donnés à titre indicatif. Un retard de livraison ne peut donner lieu à annulation de la commande ou à des dommages et intérêts, sauf retard supérieur à quinze (15) jours ouvrés non justifié, auquel cas le Client pourra demander l\\\'annulation et le remboursement de sa commande.</p>\\n<p>Le Client est tenu de fournir des informations exactes et d\\\'être joignable au numéro indiqué. En cas d\\\'absence répétée ou d\\\'adresse erronée, des frais de nouvelle présentation peuvent être facturés.</p>\\n\\n<h2>Article 8 — Réception</h2>\\n<p>Le Client vérifie l\\\'état des colis à la réception, en présence du livreur. Toute anomalie (colis endommagé, produit manquant ou non conforme) doit être mentionnée sur le bon de livraison et signalée à {company_name} dans un délai de quarante-huit (48) heures, par email à <a href=\\\"mailto:{email}\\\">{email}</a> ou par téléphone au {phone}, photographies à l\\\'appui.</p>\\n\\n<h2>Article 9 — Retours</h2>\\n<p>Le Client peut demander le retour d\\\'un produit dans un délai de sept (7) jours à compter de sa réception, en contactant préalablement {company_name}. Le produit doit être retourné neuf, complet, dans son emballage d\\\'origine intact et non ouvert, accompagné de la facture. Les frais de retour sont à la charge du Client, sauf en cas d\\\'erreur ou de défaut imputable à {company_name}.</p>\\n<p>Ne peuvent faire l\\\'objet d\\\'un retour : les consommables (toners, cartouches, encres, tambours) dont l\\\'emballage scellé a été ouvert, les produits commandés spécifiquement à la demande du Client (« sur commande »), les logiciels descellés et les produits utilisés, endommagés ou incomplets du fait du Client.</p>\\n<p>Après réception et contrôle du produit, le remboursement est effectué sous quatorze (14) jours, par le moyen de paiement utilisé lors de la commande ou, avec l\\\'accord du Client, sous forme d\\\'avoir.</p>\\n\\n<h2>Article 10 — Garanties</h2>\\n<p>Les produits bénéficient de la garantie légale contre les vices cachés prévue par le Code des obligations civiles et commerciales, ainsi que, le cas échéant, de la garantie commerciale du constructeur dont la durée est indiquée sur la fiche produit.</p>\\n<p>La garantie ne couvre pas les dommages résultant d\\\'une mauvaise utilisation, d\\\'une installation non conforme, de l\\\'usage de consommables inadaptés, d\\\'une surtension électrique, d\\\'une chute ou d\\\'une intervention par un tiers non agréé. Pour toute mise en œuvre de la garantie, le Client contacte le service client muni de sa facture.</p>\\n\\n<h2>Article 11 — Devis, location et maintenance</h2>\\n<p>Les demandes de devis, d\\\'étude de location et de maintenance effectuées via le Site n\\\'engagent ni le Client ni {company_name}. Elles donnent lieu à une proposition commerciale ou à un contrat spécifique précisant les prix, durées, niveaux de service et conditions applicables.</p>\\n\\n<h2>Article 12 — Responsabilité</h2>\\n<p>{company_name} est responsable de la bonne exécution de ses obligations. Sa responsabilité ne saurait être engagée en cas d\\\'inexécution imputable au Client, au fait imprévisible et insurmontable d\\\'un tiers, ou à un cas de force majeure tel que défini par la loi et la jurisprudence. En toute hypothèse, et sauf disposition légale impérative contraire, sa responsabilité est limitée au montant de la commande concernée et ne couvre pas les dommages indirects (perte de données, perte d\\\'exploitation, manque à gagner).</p>\\n<p>Il appartient au Client de sauvegarder ses données avant toute intervention technique ou tout retour de matériel.</p>\\n\\n<h2>Article 13 — Données personnelles</h2>\\n<p>Les données collectées lors de la commande sont nécessaires à son traitement et à la relation commerciale. Elles sont traitées conformément à la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel et à notre <a href=\\\"{site_url}/confidentialite\\\">Politique de confidentialité</a>.</p>\\n\\n<h2>Article 14 — Preuve et archivage</h2>\\n<p>Les registres informatisés de {company_name}, conservés dans des conditions raisonnables de sécurité, sont considérés comme la preuve des communications, commandes et paiements intervenus entre les parties, conformément à la loi n° 2008-08 du 25 janvier 2008 sur les transactions électroniques. Les factures sont accessibles depuis l\\\'espace client.</p>\\n\\n<h2>Article 15 — Service client et réclamations</h2>\\n<p>Pour toute information, réclamation ou demande relative à une commande : <a href=\\\"mailto:{email}\\\">{email}</a> — {phone} — {address}.</p>\\n\\n<h2>Article 16 — Droit applicable et litiges</h2>\\n<p>Les présentes CGV sont soumises au droit sénégalais. En cas de litige, les parties s\\\'efforceront de trouver une solution amiable. À défaut, le litige sera porté devant les juridictions compétentes de Dakar, sous réserve des règles impératives applicables aux consommateurs.</p>\\n\\n<h2>Article 17 — Modification des CGV</h2>\\n<p>{company_name} se réserve le droit de modifier les présentes CGV à tout moment. Les CGV applicables sont celles en vigueur à la date de validation de la commande.</p>\\n\', \'legal\', 1, 1)\nON DUPLICATE KEY UPDATE content = VALUES(content), published = 1;\n\nINSERT INTO pages (slug, title, content, footer_group, published, sort) VALUES (\'mentions-legales\', \'Mentions légales\', \'<p>Conformément à la loi n° 2008-08 du 25 janvier 2008 sur les transactions électroniques, les informations suivantes sont portées à la connaissance des utilisateurs du site <strong>{domain}</strong> (ci-après « le Site »).</p>\\n\\n<h2>1. Éditeur du site</h2>\\n<p>Le Site <strong>{site_name}</strong> est édité par :</p>\\n<ul>\\n<li><strong>{company_legal_name}</strong></li>\\n<li>Siège social : {address}</li>\\n<li>Identifiants légaux : {company_ids}</li>\\n<li>Téléphone : {phone}</li>\\n<li>Email : <a href=\\\"mailto:{email}\\\">{email}</a></li>\\n</ul>\\n<p><strong>Directeur de la publication :</strong> {legal_representative}.</p>\\n<p>{company_name} est une entreprise sénégalaise spécialisée dans les solutions d\\\'impression professionnelles, représentant exclusif de la marque Sharp au Sénégal.</p>\\n\\n<h2>2. Conception et développement</h2>\\n<p>Le Site a été conçu et développé par la société <strong>Neosen</strong> — <a href=\\\"https://neosen.tech\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">neosen.tech</a>.</p>\\n<ul>\\n<li>Gérant : <strong>Cheikh Tidiane MBAYE</strong></li>\\n<li>Site web : <a href=\\\"https://neosen.tech\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">https://neosen.tech</a></li>\\n</ul>\\n<p>Neosen intervient en qualité de prestataire technique. Le contenu publié sur le Site (produits, prix, textes, images) relève de la seule responsabilité de l\\\'éditeur.</p>\\n\\n<h2>3. Hébergement</h2>\\n<p>{host_info}</p>\\n\\n<h2>4. Propriété intellectuelle</h2>\\n<p>L\\\'ensemble des éléments composant le Site (structure, textes, illustrations, logos, graphismes, base de données, logiciels) est la propriété exclusive de {company_name} ou fait l\\\'objet d\\\'une autorisation d\\\'utilisation. Toute reproduction, représentation, modification ou exploitation, totale ou partielle, sans autorisation écrite préalable est interdite et constitue une contrefaçon.</p>\\n<p>Les marques citées sur le Site (notamment Sharp, Canon, HP, Ricoh) ainsi que les visuels des produits appartiennent à leurs propriétaires respectifs et sont utilisés à seule fin d\\\'identification des produits proposés à la vente.</p>\\n\\n<h2>5. Responsabilité</h2>\\n<p>{company_name} s\\\'efforce d\\\'assurer l\\\'exactitude et la mise à jour des informations diffusées sur le Site, mais ne peut garantir l\\\'absence d\\\'erreurs ou d\\\'omissions. Les photographies et descriptions des produits sont fournies à titre indicatif et ne sont pas contractuelles.</p>\\n<p>{company_name} ne saurait être tenue responsable des dommages résultant d\\\'une interruption du Site, d\\\'un dysfonctionnement technique, de l\\\'intrusion d\\\'un tiers ou de l\\\'utilisation d\\\'un matériel informatique inadapté par l\\\'utilisateur.</p>\\n<p>Les liens vers des sites tiers sont proposés pour la commodité des utilisateurs ; {company_name} n\\\'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu.</p>\\n\\n<h2>6. Données personnelles et cookies</h2>\\n<p>Le traitement des données personnelles collectées sur le Site est décrit dans la <a href=\\\"{site_url}/confidentialite\\\">Politique de confidentialité</a>, conformément à la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel. L\\\'utilisation des cookies est détaillée dans la <a href=\\\"{site_url}/cookies\\\">Politique de cookies</a>.</p>\\n\\n<h2>7. Droit applicable</h2>\\n<p>Les présentes mentions légales sont régies par le droit sénégalais. Tout litige relatif à l\\\'utilisation du Site relève, à défaut de règlement amiable, de la compétence des juridictions de Dakar.</p>\\n\\n<h2>8. Contact</h2>\\n<p>Pour toute question relative au Site : <a href=\\\"mailto:{email}\\\">{email}</a> — {phone}.</p>\\n\', \'legal\', 1, 2)\nON DUPLICATE KEY UPDATE content = VALUES(content), published = 1;\n\nINSERT INTO pages (slug, title, content, footer_group, published, sort) VALUES (\'confidentialite\', \'Politique de confidentialité\', \'<p>La présente politique explique comment <strong>{company_legal_name}</strong> collecte et utilise les données personnelles des utilisateurs du site <strong>{domain}</strong>, dans le respect de la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel et de ses textes d\\\'application.</p>\\n\\n<h2>1. Responsable du traitement</h2>\\n<p>Le responsable du traitement est <strong>{company_legal_name}</strong>, {address}, {company_ids}, représentée par {legal_representative}. Contact : <a href=\\\"mailto:{email}\\\">{email}</a> — {phone}.</p>\\n\\n<h2>2. Données collectées</h2>\\n<ul>\\n<li><strong>Identité et coordonnées</strong> : nom, prénom, entreprise, email, téléphone, adresses de livraison ;</li>\\n<li><strong>Compte client</strong> : identifiants de connexion (le mot de passe est stocké sous forme chiffrée irréversible), historique des commandes, favoris ;</li>\\n<li><strong>Commandes et paiements</strong> : produits commandés, montants, mode de livraison, moyen et statut du paiement, références de transaction. Les données de carte bancaire et de compte mobile money sont saisies directement chez nos prestataires de paiement (Stripe, PayDunya) et ne sont jamais conservées par {company_name} ;</li>\\n<li><strong>Demandes</strong> : devis, location, maintenance et contact (contenu du message, modèle et numéro de série d\\\'imprimante, pièces jointes) ;</li>\\n<li><strong>Avis clients</strong> : note, commentaire et nom affiché ;</li>\\n<li><strong>Données techniques</strong> : adresse IP, journaux de connexion et de sécurité, cookies nécessaires au fonctionnement du Site.</li>\\n</ul>\\n\\n<h2>3. Finalités et bases légales</h2>\\n<table>\\n<thead><tr><th>Finalité</th><th>Base légale</th></tr></thead>\\n<tbody>\\n<tr><td>Gestion des commandes, paiements, livraisons, factures, retours et garanties</td><td>Exécution du contrat</td></tr>\\n<tr><td>Gestion du compte client et du service client</td><td>Exécution du contrat</td></tr>\\n<tr><td>Traitement des demandes de devis, location, maintenance et contact</td><td>Mesures précontractuelles à la demande de la personne</td></tr>\\n<tr><td>Obligations comptables, fiscales et prévention de la fraude</td><td>Obligation légale et intérêt légitime</td></tr>\\n<tr><td>Sécurité du Site (protection contre les attaques, limitation des tentatives de connexion)</td><td>Intérêt légitime</td></tr>\\n<tr><td>Publication des avis clients après modération</td><td>Consentement</td></tr>\\n<tr><td>Mesure d\\\'audience, lorsqu\\\'elle est activée</td><td>Consentement</td></tr>\\n</tbody>\\n</table>\\n\\n<h2>4. Destinataires</h2>\\n<p>Les données sont destinées aux services habilités de {company_name} (commercial, logistique, comptabilité, service technique). Elles peuvent être communiquées, dans la stricte limite nécessaire, à :</p>\\n<ul>\\n<li>nos prestataires de paiement : <strong>Stripe</strong> (carte bancaire) et <strong>PayDunya</strong> (Wave, Orange Money…) ;</li>\\n<li>nos livreurs et transporteurs, pour l\\\'acheminement des commandes ;</li>\\n<li>notre hébergeur et notre prestataire technique <strong>Neosen</strong> (<a href=\\\"https://neosen.tech\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">neosen.tech</a>), chargé du développement et de la maintenance du Site, agissant en qualité de sous-traitant ;</li>\\n<li>notre prestataire d\\\'envoi d\\\'emails et, le cas échéant, le service Google reCAPTCHA de protection contre les robots ;</li>\\n<li>les autorités administratives ou judiciaires, sur réquisition légale.</li>\\n</ul>\\n<p>{company_name} ne vend ni ne loue vos données personnelles.</p>\\n\\n<h2>5. Transferts hors du Sénégal</h2>\\n<p>Certains prestataires (notamment Stripe et Google) peuvent traiter des données hors du Sénégal. Ces transferts sont limités à ce qui est nécessaire au service rendu et encadrés par des garanties appropriées, conformément à la loi n° 2008-12 et aux exigences de la Commission de Protection des Données Personnelles (CDP).</p>\\n\\n<h2>6. Durées de conservation</h2>\\n<ul>\\n<li><strong>Compte client</strong> : pendant la durée de la relation, puis 3 ans après le dernier contact ou la dernière commande ;</li>\\n<li><strong>Commandes et factures</strong> : 10 ans, au titre des obligations comptables et fiscales ;</li>\\n<li><strong>Demandes de devis et de contact</strong> : 3 ans à compter du dernier échange ;</li>\\n<li><strong>Journaux de sécurité et de connexion</strong> : 12 mois ;</li>\\n<li><strong>Jetons de réinitialisation de mot de passe</strong> : 1 heure.</li>\\n</ul>\\n\\n<h2>7. Sécurité</h2>\\n<p>{company_name} met en œuvre des mesures techniques et organisationnelles adaptées : connexion chiffrée (HTTPS), mots de passe chiffrés de manière irréversible, protection contre les attaques courantes (injections, falsification de requêtes), contrôle des fichiers envoyés, limitation des tentatives de connexion, accès à l\\\'administration restreint par rôle et journalisé, sauvegardes régulières.</p>\\n\\n<h2>8. Vos droits</h2>\\n<p>Conformément à la loi n° 2008-12 du 25 janvier 2008, vous disposez d\\\'un droit d\\\'information, d\\\'accès, de rectification, d\\\'effacement et d\\\'opposition (y compris à la prospection commerciale) sur vos données. Vous pouvez modifier la plupart de vos informations directement depuis votre espace client.</p>\\n<p>Pour exercer vos droits, écrivez à <a href=\\\"mailto:{email}\\\">{email}</a> ou à {company_legal_name}, {address}, en justifiant de votre identité. Une réponse vous sera apportée dans les meilleurs délais.</p>\\n<p>Vous pouvez également introduire une réclamation auprès de la <strong>Commission de Protection des Données Personnelles (CDP)</strong> du Sénégal — <a href=\\\"https://www.cdp.sn\\\" target=\\\"_blank\\\" rel=\\\"noopener\\\">www.cdp.sn</a>.</p>\\n\\n<h2>9. Cookies</h2>\\n<p>Le Site utilise des cookies strictement nécessaires à son fonctionnement (session, panier, sécurité, mémorisation de la devise d\\\'affichage) et, le cas échéant, des cookies de mesure d\\\'audience. Pour en savoir plus, consultez notre <a href=\\\"{site_url}/cookies\\\">Politique de cookies</a>.</p>\\n\\n<h2>10. Mineurs</h2>\\n<p>Le Site n\\\'est pas destiné aux personnes de moins de 18 ans. Les commandes doivent être passées par une personne majeure.</p>\\n\\n<h2>11. Modification de la politique</h2>\\n<p>La présente politique peut être mise à jour pour tenir compte des évolutions légales ou du Site. La date de dernière mise à jour figure en bas de cette page.</p>\\n\', \'legal\', 1, 3)\nON DUPLICATE KEY UPDATE content = VALUES(content), published = 1;\n\n','marked',0,NULL,0,NULL,NULL,'2026-10-01 02:01:16');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `sku` varchar(80) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit_price` decimal(14,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_status_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `status` varchar(30) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_status_history` WRITE;
/*!40000 ALTER TABLE `order_status_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_history` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(30) NOT NULL,
  `access_token` varchar(64) NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `company` varchar(190) DEFAULT NULL,
  `delivery_method` enum('delivery','pickup') NOT NULL DEFAULT 'delivery',
  `ship_address` varchar(255) DEFAULT NULL,
  `ship_city` varchar(120) DEFAULT NULL,
  `zone_id` int(10) unsigned DEFAULT NULL,
  `zone_name` varchar(120) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `delivery_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'XOF',
  `payment_method` varchar(30) NOT NULL,
  `payment_status` enum('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `status` enum('received','paid','preparing','shipped','delivered','cancelled','refunded') NOT NULL DEFAULT 'received',
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `stock_decremented` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_number` varchar(30) DEFAULT NULL,
  `invoice_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) NOT NULL,
  `title` varchar(190) NOT NULL,
  `content` mediumtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `footer_group` varchar(30) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'a-propos','À propos','<h2>AFAM, votre partenaire impression au Sénégal</h2><p>AFAM est une entreprise sénégalaise spécialisée dans les solutions d\'impression professionnelles. Représentant exclusif de Sharp au Sénégal, AFAM propose des offres de location et de vente clé en main alliant performance, économies et respect de l\'environnement.</p><p>L\'entreprise propose également des services de maintenance et des solutions adaptées permettant de réduire les coûts d\'impression et d\'améliorer l\'efficacité.</p>',NULL,NULL,'company',1,1,'2026-10-01 02:01:15'),
(2,'afam-sharp','AFAM × Sharp','<h2>Représentant exclusif Sharp au Sénégal</h2><p>Découvrez la gamme complète de multifonctions et solutions Sharp, avec l\'expertise locale d\'AFAM : installation, formation, consommables d\'origine et maintenance.</p>',NULL,NULL,'company',1,2,'2026-10-01 02:01:15'),
(3,'solutions-professionnelles','Solutions professionnelles','<h2>Des solutions pour les entreprises et administrations</h2><p>Audit de parc, gestion des impressions, tarifs adaptés aux volumes, contrats de service : AFAM accompagne les organisations dans l\'optimisation de leurs coûts d\'impression.</p>',NULL,NULL,'company',1,3,'2026-10-01 02:01:15'),
(4,'faq','FAQ','<h3>Comment trouver le consommable de mon imprimante ?</h3><p>Utilisez la recherche par imprimante : choisissez la marque puis le modèle.</p><h3>Quels sont les moyens de paiement ?</h3><p>Carte bancaire (Stripe), Wave, Orange Money (PayDunya) et paiement à la livraison selon disponibilité.</p><h3>Livrez-vous hors de Dakar ?</h3><p>Oui, dans toutes les régions du Sénégal. Les frais dépendent de la zone de livraison.</p>',NULL,NULL,'help',1,1,'2026-10-01 02:01:15'),
(5,'livraison-retours','Politique de livraison et retours','<p>Contenu à personnaliser depuis le back-office (Contenus &gt; Pages).</p>',NULL,NULL,'help',1,2,'2026-10-01 02:01:15'),
(6,'garantie','Garantie','<p>Contenu à personnaliser depuis le back-office (Contenus &gt; Pages).</p>',NULL,NULL,'help',1,3,'2026-10-01 02:01:15'),
(7,'cgv','Conditions générales de vente','<p><em>Conditions générales de vente en vigueur à compter du 1<sup>er</sup> octobre 2026.</em></p>\n\n<h2>Article 1 — Objet</h2>\n<p>Les présentes conditions générales de vente (ci-après « CGV ») régissent les ventes de produits et les demandes de services conclues à distance sur le site <strong>{domain}</strong> (ci-après « le Site »), exploité par <strong>{company_legal_name}</strong>, {address}, {company_ids} (ci-après « {company_name} » ou « le Vendeur »).</p>\n<p>Toute commande passée sur le Site implique l\'acceptation pleine et entière des présentes CGV, que le client reconnaît avoir lues avant de valider sa commande.</p>\n\n<h2>Article 2 — Clients</h2>\n<p>Le Site s\'adresse aux particuliers, professionnels, entreprises et administrations (ci-après « le Client »). Le Client déclare être majeur et disposer de la capacité juridique de contracter, ou être dûment habilité à engager la structure qu\'il représente.</p>\n<p>Les ventes en volume, les marchés publics et les contrats de location ou de maintenance peuvent faire l\'objet de conditions particulières (devis, bon de commande, contrat) qui prévalent alors sur les présentes CGV.</p>\n\n<h2>Article 3 — Produits</h2>\n<p>Les produits proposés (imprimantes, multifonctions, copieurs, consommables, matériel informatique, papeterie et fournitures de bureau) sont décrits avec la plus grande exactitude possible. Les photographies, illustrations et caractéristiques sont communiquées à titre indicatif et n\'ont pas de valeur contractuelle.</p>\n<p>Les informations de compatibilité des consommables sont fournies sur la base des données des constructeurs. En cas de doute, le Client est invité à contacter {company_name} avant sa commande.</p>\n<p>Les offres sont valables dans la limite des stocks disponibles. La disponibilité est indiquée sur chaque fiche produit (« En stock », « Stock faible », « Sur commande », « Rupture »).</p>\n\n<h2>Article 4 — Prix</h2>\n<p>Les prix sont indiqués en francs CFA (FCFA / XOF), toutes taxes comprises (TVA au taux en vigueur incluse), hors frais de livraison. Les frais de livraison sont précisés avant la validation de la commande.</p>\n<p>L\'affichage des prix en euros est proposé à titre purement indicatif, sur la base de la parité fixe 1 € = 655,957 FCFA. La commande est conclue et payée en francs CFA.</p>\n<p>{company_name} se réserve le droit de modifier ses prix à tout moment. Les produits sont facturés au prix en vigueur au moment de la validation de la commande. En cas d\'erreur manifeste de prix, {company_name} pourra annuler la commande après en avoir informé le Client, qui sera intégralement remboursé.</p>\n\n<h2>Article 5 — Commande</h2>\n<p>Le Client peut commander avec ou sans création de compte. Le processus de commande comprend les étapes suivantes : sélection des produits, vérification du panier, saisie des informations et de l\'adresse de livraison, choix du mode de livraison et du moyen de paiement, acceptation des CGV, puis validation et paiement.</p>\n<p>La validation de la commande vaut acceptation de son contenu et de son prix. Un email de confirmation récapitulant la commande est adressé au Client. {company_name} se réserve le droit d\'annuler ou de refuser toute commande d\'un Client avec lequel existerait un litige relatif au paiement d\'une commande antérieure, ou en cas de suspicion de fraude.</p>\n<p>Les codes promotionnels sont soumis à leurs conditions propres (durée de validité, montant minimum d\'achat, nombre d\'utilisations) et ne sont pas cumulables, sauf mention contraire.</p>\n\n<h2>Article 6 — Paiement</h2>\n<p>Le prix est payable en totalité à la commande, selon l\'un des moyens proposés :</p>\n<ul>\n<li><strong>Carte bancaire</strong> (Visa, Mastercard…), via la plateforme sécurisée Stripe ;</li>\n<li><strong>Mobile money</strong> (Wave, Orange Money et autres moyens disponibles), via la plateforme sécurisée PayDunya ;</li>\n<li><strong>Paiement à la livraison</strong> ou <strong>virement bancaire</strong>, lorsque ces options sont proposées.</li>\n</ul>\n<p>Les données bancaires sont saisies directement sur les pages sécurisées des prestataires de paiement et ne sont jamais stockées par {company_name}. La commande est considérée comme payée après confirmation de l\'encaissement par le prestataire. En cas de refus ou d\'échec du paiement, la commande n\'est pas traitée.</p>\n\n<h2>Article 7 — Livraison et retrait</h2>\n<p>Les produits sont livrés à l\'adresse indiquée par le Client, dans les zones desservies au Sénégal. Les frais et délais indicatifs dépendent de la zone de livraison et sont précisés lors de la commande ; la livraison peut être offerte au-delà d\'un montant d\'achat indiqué sur le Site. Le Client peut également choisir le retrait gratuit à l\'adresse de retrait indiquée, lorsque cette option est proposée.</p>\n<p>Les délais de livraison sont donnés à titre indicatif. Un retard de livraison ne peut donner lieu à annulation de la commande ou à des dommages et intérêts, sauf retard supérieur à quinze (15) jours ouvrés non justifié, auquel cas le Client pourra demander l\'annulation et le remboursement de sa commande.</p>\n<p>Le Client est tenu de fournir des informations exactes et d\'être joignable au numéro indiqué. En cas d\'absence répétée ou d\'adresse erronée, des frais de nouvelle présentation peuvent être facturés.</p>\n\n<h2>Article 8 — Réception</h2>\n<p>Le Client vérifie l\'état des colis à la réception, en présence du livreur. Toute anomalie (colis endommagé, produit manquant ou non conforme) doit être mentionnée sur le bon de livraison et signalée à {company_name} dans un délai de quarante-huit (48) heures, par email à <a href=\"mailto:{email}\">{email}</a> ou par téléphone au {phone}, photographies à l\'appui.</p>\n\n<h2>Article 9 — Retours</h2>\n<p>Le Client peut demander le retour d\'un produit dans un délai de sept (7) jours à compter de sa réception, en contactant préalablement {company_name}. Le produit doit être retourné neuf, complet, dans son emballage d\'origine intact et non ouvert, accompagné de la facture. Les frais de retour sont à la charge du Client, sauf en cas d\'erreur ou de défaut imputable à {company_name}.</p>\n<p>Ne peuvent faire l\'objet d\'un retour : les consommables (toners, cartouches, encres, tambours) dont l\'emballage scellé a été ouvert, les produits commandés spécifiquement à la demande du Client (« sur commande »), les logiciels descellés et les produits utilisés, endommagés ou incomplets du fait du Client.</p>\n<p>Après réception et contrôle du produit, le remboursement est effectué sous quatorze (14) jours, par le moyen de paiement utilisé lors de la commande ou, avec l\'accord du Client, sous forme d\'avoir.</p>\n\n<h2>Article 10 — Garanties</h2>\n<p>Les produits bénéficient de la garantie légale contre les vices cachés prévue par le Code des obligations civiles et commerciales, ainsi que, le cas échéant, de la garantie commerciale du constructeur dont la durée est indiquée sur la fiche produit.</p>\n<p>La garantie ne couvre pas les dommages résultant d\'une mauvaise utilisation, d\'une installation non conforme, de l\'usage de consommables inadaptés, d\'une surtension électrique, d\'une chute ou d\'une intervention par un tiers non agréé. Pour toute mise en œuvre de la garantie, le Client contacte le service client muni de sa facture.</p>\n\n<h2>Article 11 — Devis, location et maintenance</h2>\n<p>Les demandes de devis, d\'étude de location et de maintenance effectuées via le Site n\'engagent ni le Client ni {company_name}. Elles donnent lieu à une proposition commerciale ou à un contrat spécifique précisant les prix, durées, niveaux de service et conditions applicables.</p>\n\n<h2>Article 12 — Responsabilité</h2>\n<p>{company_name} est responsable de la bonne exécution de ses obligations. Sa responsabilité ne saurait être engagée en cas d\'inexécution imputable au Client, au fait imprévisible et insurmontable d\'un tiers, ou à un cas de force majeure tel que défini par la loi et la jurisprudence. En toute hypothèse, et sauf disposition légale impérative contraire, sa responsabilité est limitée au montant de la commande concernée et ne couvre pas les dommages indirects (perte de données, perte d\'exploitation, manque à gagner).</p>\n<p>Il appartient au Client de sauvegarder ses données avant toute intervention technique ou tout retour de matériel.</p>\n\n<h2>Article 13 — Données personnelles</h2>\n<p>Les données collectées lors de la commande sont nécessaires à son traitement et à la relation commerciale. Elles sont traitées conformément à la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel et à notre <a href=\"{site_url}/confidentialite\">Politique de confidentialité</a>.</p>\n\n<h2>Article 14 — Preuve et archivage</h2>\n<p>Les registres informatisés de {company_name}, conservés dans des conditions raisonnables de sécurité, sont considérés comme la preuve des communications, commandes et paiements intervenus entre les parties, conformément à la loi n° 2008-08 du 25 janvier 2008 sur les transactions électroniques. Les factures sont accessibles depuis l\'espace client.</p>\n\n<h2>Article 15 — Service client et réclamations</h2>\n<p>Pour toute information, réclamation ou demande relative à une commande : <a href=\"mailto:{email}\">{email}</a> — {phone} — {address}.</p>\n\n<h2>Article 16 — Droit applicable et litiges</h2>\n<p>Les présentes CGV sont soumises au droit sénégalais. En cas de litige, les parties s\'efforceront de trouver une solution amiable. À défaut, le litige sera porté devant les juridictions compétentes de Dakar, sous réserve des règles impératives applicables aux consommateurs.</p>\n\n<h2>Article 17 — Modification des CGV</h2>\n<p>{company_name} se réserve le droit de modifier les présentes CGV à tout moment. Les CGV applicables sont celles en vigueur à la date de validation de la commande.</p>\n',NULL,NULL,'legal',1,1,'2026-10-01 02:01:15'),
(8,'mentions-legales','Mentions légales','<p>Conformément à la loi n° 2008-08 du 25 janvier 2008 sur les transactions électroniques, les informations suivantes sont portées à la connaissance des utilisateurs du site <strong>{domain}</strong> (ci-après « le Site »).</p>\n\n<h2>1. Éditeur du site</h2>\n<p>Le Site <strong>{site_name}</strong> est édité par :</p>\n<ul>\n<li><strong>{company_legal_name}</strong></li>\n<li>Siège social : {address}</li>\n<li>Identifiants légaux : {company_ids}</li>\n<li>Téléphone : {phone}</li>\n<li>Email : <a href=\"mailto:{email}\">{email}</a></li>\n</ul>\n<p><strong>Directeur de la publication :</strong> {legal_representative}.</p>\n<p>{company_name} est une entreprise sénégalaise spécialisée dans les solutions d\'impression professionnelles, représentant exclusif de la marque Sharp au Sénégal.</p>\n\n<h2>2. Conception et développement</h2>\n<p>Le Site a été conçu et développé par la société <strong>Neosen</strong> — <a href=\"https://neosen.tech\" target=\"_blank\" rel=\"noopener\">neosen.tech</a>.</p>\n<ul>\n<li>Gérant : <strong>Cheikh Tidiane MBAYE</strong></li>\n<li>Site web : <a href=\"https://neosen.tech\" target=\"_blank\" rel=\"noopener\">https://neosen.tech</a></li>\n</ul>\n<p>Neosen intervient en qualité de prestataire technique. Le contenu publié sur le Site (produits, prix, textes, images) relève de la seule responsabilité de l\'éditeur.</p>\n\n<h2>3. Hébergement</h2>\n<p>{host_info}</p>\n\n<h2>4. Propriété intellectuelle</h2>\n<p>L\'ensemble des éléments composant le Site (structure, textes, illustrations, logos, graphismes, base de données, logiciels) est la propriété exclusive de {company_name} ou fait l\'objet d\'une autorisation d\'utilisation. Toute reproduction, représentation, modification ou exploitation, totale ou partielle, sans autorisation écrite préalable est interdite et constitue une contrefaçon.</p>\n<p>Les marques citées sur le Site (notamment Sharp, Canon, HP, Ricoh) ainsi que les visuels des produits appartiennent à leurs propriétaires respectifs et sont utilisés à seule fin d\'identification des produits proposés à la vente.</p>\n\n<h2>5. Responsabilité</h2>\n<p>{company_name} s\'efforce d\'assurer l\'exactitude et la mise à jour des informations diffusées sur le Site, mais ne peut garantir l\'absence d\'erreurs ou d\'omissions. Les photographies et descriptions des produits sont fournies à titre indicatif et ne sont pas contractuelles.</p>\n<p>{company_name} ne saurait être tenue responsable des dommages résultant d\'une interruption du Site, d\'un dysfonctionnement technique, de l\'intrusion d\'un tiers ou de l\'utilisation d\'un matériel informatique inadapté par l\'utilisateur.</p>\n<p>Les liens vers des sites tiers sont proposés pour la commodité des utilisateurs ; {company_name} n\'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu.</p>\n\n<h2>6. Données personnelles et cookies</h2>\n<p>Le traitement des données personnelles collectées sur le Site est décrit dans la <a href=\"{site_url}/confidentialite\">Politique de confidentialité</a>, conformément à la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel. L\'utilisation des cookies est détaillée dans la <a href=\"{site_url}/cookies\">Politique de cookies</a>.</p>\n\n<h2>7. Droit applicable</h2>\n<p>Les présentes mentions légales sont régies par le droit sénégalais. Tout litige relatif à l\'utilisation du Site relève, à défaut de règlement amiable, de la compétence des juridictions de Dakar.</p>\n\n<h2>8. Contact</h2>\n<p>Pour toute question relative au Site : <a href=\"mailto:{email}\">{email}</a> — {phone}.</p>\n',NULL,NULL,'legal',1,2,'2026-10-01 02:01:15'),
(9,'confidentialite','Politique de confidentialité','<p>La présente politique explique comment <strong>{company_legal_name}</strong> collecte et utilise les données personnelles des utilisateurs du site <strong>{domain}</strong>, dans le respect de la loi n° 2008-12 du 25 janvier 2008 portant sur la protection des données à caractère personnel et de ses textes d\'application.</p>\n\n<h2>1. Responsable du traitement</h2>\n<p>Le responsable du traitement est <strong>{company_legal_name}</strong>, {address}, {company_ids}, représentée par {legal_representative}. Contact : <a href=\"mailto:{email}\">{email}</a> — {phone}.</p>\n\n<h2>2. Données collectées</h2>\n<ul>\n<li><strong>Identité et coordonnées</strong> : nom, prénom, entreprise, email, téléphone, adresses de livraison ;</li>\n<li><strong>Compte client</strong> : identifiants de connexion (le mot de passe est stocké sous forme chiffrée irréversible), historique des commandes, favoris ;</li>\n<li><strong>Commandes et paiements</strong> : produits commandés, montants, mode de livraison, moyen et statut du paiement, références de transaction. Les données de carte bancaire et de compte mobile money sont saisies directement chez nos prestataires de paiement (Stripe, PayDunya) et ne sont jamais conservées par {company_name} ;</li>\n<li><strong>Demandes</strong> : devis, location, maintenance et contact (contenu du message, modèle et numéro de série d\'imprimante, pièces jointes) ;</li>\n<li><strong>Avis clients</strong> : note, commentaire et nom affiché ;</li>\n<li><strong>Données techniques</strong> : adresse IP, journaux de connexion et de sécurité, cookies nécessaires au fonctionnement du Site.</li>\n</ul>\n\n<h2>3. Finalités et bases légales</h2>\n<table>\n<thead><tr><th>Finalité</th><th>Base légale</th></tr></thead>\n<tbody>\n<tr><td>Gestion des commandes, paiements, livraisons, factures, retours et garanties</td><td>Exécution du contrat</td></tr>\n<tr><td>Gestion du compte client et du service client</td><td>Exécution du contrat</td></tr>\n<tr><td>Traitement des demandes de devis, location, maintenance et contact</td><td>Mesures précontractuelles à la demande de la personne</td></tr>\n<tr><td>Obligations comptables, fiscales et prévention de la fraude</td><td>Obligation légale et intérêt légitime</td></tr>\n<tr><td>Sécurité du Site (protection contre les attaques, limitation des tentatives de connexion)</td><td>Intérêt légitime</td></tr>\n<tr><td>Publication des avis clients après modération</td><td>Consentement</td></tr>\n<tr><td>Mesure d\'audience, lorsqu\'elle est activée</td><td>Consentement</td></tr>\n</tbody>\n</table>\n\n<h2>4. Destinataires</h2>\n<p>Les données sont destinées aux services habilités de {company_name} (commercial, logistique, comptabilité, service technique). Elles peuvent être communiquées, dans la stricte limite nécessaire, à :</p>\n<ul>\n<li>nos prestataires de paiement : <strong>Stripe</strong> (carte bancaire) et <strong>PayDunya</strong> (Wave, Orange Money…) ;</li>\n<li>nos livreurs et transporteurs, pour l\'acheminement des commandes ;</li>\n<li>notre hébergeur et notre prestataire technique <strong>Neosen</strong> (<a href=\"https://neosen.tech\" target=\"_blank\" rel=\"noopener\">neosen.tech</a>), chargé du développement et de la maintenance du Site, agissant en qualité de sous-traitant ;</li>\n<li>notre prestataire d\'envoi d\'emails et, le cas échéant, le service Google reCAPTCHA de protection contre les robots ;</li>\n<li>les autorités administratives ou judiciaires, sur réquisition légale.</li>\n</ul>\n<p>{company_name} ne vend ni ne loue vos données personnelles.</p>\n\n<h2>5. Transferts hors du Sénégal</h2>\n<p>Certains prestataires (notamment Stripe et Google) peuvent traiter des données hors du Sénégal. Ces transferts sont limités à ce qui est nécessaire au service rendu et encadrés par des garanties appropriées, conformément à la loi n° 2008-12 et aux exigences de la Commission de Protection des Données Personnelles (CDP).</p>\n\n<h2>6. Durées de conservation</h2>\n<ul>\n<li><strong>Compte client</strong> : pendant la durée de la relation, puis 3 ans après le dernier contact ou la dernière commande ;</li>\n<li><strong>Commandes et factures</strong> : 10 ans, au titre des obligations comptables et fiscales ;</li>\n<li><strong>Demandes de devis et de contact</strong> : 3 ans à compter du dernier échange ;</li>\n<li><strong>Journaux de sécurité et de connexion</strong> : 12 mois ;</li>\n<li><strong>Jetons de réinitialisation de mot de passe</strong> : 1 heure.</li>\n</ul>\n\n<h2>7. Sécurité</h2>\n<p>{company_name} met en œuvre des mesures techniques et organisationnelles adaptées : connexion chiffrée (HTTPS), mots de passe chiffrés de manière irréversible, protection contre les attaques courantes (injections, falsification de requêtes), contrôle des fichiers envoyés, limitation des tentatives de connexion, accès à l\'administration restreint par rôle et journalisé, sauvegardes régulières.</p>\n\n<h2>8. Vos droits</h2>\n<p>Conformément à la loi n° 2008-12 du 25 janvier 2008, vous disposez d\'un droit d\'information, d\'accès, de rectification, d\'effacement et d\'opposition (y compris à la prospection commerciale) sur vos données. Vous pouvez modifier la plupart de vos informations directement depuis votre espace client.</p>\n<p>Pour exercer vos droits, écrivez à <a href=\"mailto:{email}\">{email}</a> ou à {company_legal_name}, {address}, en justifiant de votre identité. Une réponse vous sera apportée dans les meilleurs délais.</p>\n<p>Vous pouvez également introduire une réclamation auprès de la <strong>Commission de Protection des Données Personnelles (CDP)</strong> du Sénégal — <a href=\"https://www.cdp.sn\" target=\"_blank\" rel=\"noopener\">www.cdp.sn</a>.</p>\n\n<h2>9. Cookies</h2>\n<p>Le Site utilise des cookies strictement nécessaires à son fonctionnement (session, panier, sécurité, mémorisation de la devise d\'affichage) et, le cas échéant, des cookies de mesure d\'audience. Pour en savoir plus, consultez notre <a href=\"{site_url}/cookies\">Politique de cookies</a>.</p>\n\n<h2>10. Mineurs</h2>\n<p>Le Site n\'est pas destiné aux personnes de moins de 18 ans. Les commandes doivent être passées par une personne majeure.</p>\n\n<h2>11. Modification de la politique</h2>\n<p>La présente politique peut être mise à jour pour tenir compte des évolutions légales ou du Site. La date de dernière mise à jour figure en bas de cette page.</p>\n',NULL,NULL,'legal',1,3,'2026-10-01 02:01:15'),
(10,'cookies','Politique de cookies','<p>Ce site utilise uniquement des cookies nécessaires à son fonctionnement (session, panier, sécurité) ainsi que, le cas échéant, des cookies de mesure d\'audience.</p>',NULL,NULL,'legal',1,4,'2026-10-01 02:01:15');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `provider` varchar(30) NOT NULL,
  `reference` varchar(190) DEFAULT NULL,
  `transaction_id` varchar(190) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` char(3) NOT NULL,
  `status` enum('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `raw_response` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_ref` (`reference`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `printer_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `printer_models` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(170) NOT NULL,
  `series` varchar(120) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_brand` (`brand_id`),
  CONSTRAINT `printer_models_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `printer_models` WRITE;
/*!40000 ALTER TABLE `printer_models` DISABLE KEYS */;
INSERT INTO `printer_models` VALUES
(1,1,'MX-2651','sharp-mx-2651','MX',1),
(2,1,'MX-3051','sharp-mx-3051','MX',1),
(3,1,'MX-3551','sharp-mx-3551','MX',1),
(4,1,'MX-4051','sharp-mx-4051','MX',1),
(5,1,'MX-5051','sharp-mx-5051','MX',1),
(6,1,'MX-M3051','sharp-mx-m3051','MX-M',1),
(7,1,'MX-M3551','sharp-mx-m3551','MX-M',1),
(8,1,'BP-30M28','sharp-bp-30m28','BP',1),
(9,1,'BP-50C26','sharp-bp-50c26','BP',1),
(10,1,'AR-6020','sharp-ar-6020','AR',1),
(11,2,'imageRUNNER 2425','canon-imagerunner-2425','imageRUNNER',1),
(12,2,'imageRUNNER 2630i','canon-imagerunner-2630i','imageRUNNER',1),
(13,2,'iR-ADV C3530','canon-ir-adv-c3530','imageRUNNER ADVANCE',1),
(14,2,'iR-ADV C3525','canon-ir-adv-c3525','imageRUNNER ADVANCE',1),
(15,2,'i-SENSYS MF445dw','canon-i-sensys-mf445dw','i-SENSYS',1),
(16,2,'i-SENSYS LBP223dw','canon-i-sensys-lbp223dw','i-SENSYS',1),
(17,2,'PIXMA G3410','canon-pixma-g3410','PIXMA',1),
(18,3,'LaserJet Pro M404dn','hp-laserjet-pro-m404dn','LaserJet Pro',1),
(19,3,'LaserJet Pro MFP M428fdw','hp-laserjet-pro-mfp-m428fdw','LaserJet Pro',1),
(20,3,'LaserJet M211dw','hp-laserjet-m211dw','LaserJet',1),
(21,3,'Color LaserJet Pro M454dw','hp-color-laserjet-pro-m454dw','Color LaserJet',1),
(22,3,'OfficeJet Pro 9010','hp-officejet-pro-9010','OfficeJet',1),
(23,3,'DeskJet 2720','hp-deskjet-2720','DeskJet',1),
(24,4,'IM 2702','ricoh-im-2702','IM',1),
(25,4,'IM C3000','ricoh-im-c3000','IM C',1),
(26,4,'MP 2014','ricoh-mp-2014','MP',1),
(27,4,'SP 230DNw','ricoh-sp-230dnw','SP',1);
/*!40000 ALTER TABLE `printer_models` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_compatibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_compatibility` (
  `product_id` int(10) unsigned NOT NULL,
  `printer_model_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`printer_model_id`),
  KEY `idx_model` (`printer_model_id`),
  CONSTRAINT `product_compatibility_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_compatibility_ibfk_2` FOREIGN KEY (`printer_model_id`) REFERENCES `printer_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_compatibility` WRITE;
/*!40000 ALTER TABLE `product_compatibility` DISABLE KEYS */;
INSERT INTO `product_compatibility` VALUES
(1,1),
(1,2),
(1,3),
(1,4),
(1,5),
(2,1),
(2,2),
(2,3),
(2,4),
(2,5),
(3,1),
(3,2),
(3,3),
(3,4),
(3,5),
(4,1),
(4,2),
(4,3),
(4,4),
(4,5),
(5,6),
(5,7),
(6,1),
(6,2),
(6,3),
(6,4),
(6,5),
(7,9),
(8,10),
(9,11),
(10,13),
(10,14),
(11,15),
(11,16),
(12,17),
(13,18),
(13,19),
(14,20),
(15,21),
(16,22),
(17,23),
(18,24),
(19,27);
/*!40000 ALTER TABLE `product_compatibility` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(80) NOT NULL,
  `manufacturer_ref` varchar(120) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(270) NOT NULL,
  `brand_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(10) unsigned DEFAULT NULL,
  `product_type` varchar(80) DEFAULT NULL,
  `color` varchar(60) DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `specs` mediumtext DEFAULT NULL,
  `price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `promo_price` decimal(14,2) DEFAULT NULL,
  `promo_start` datetime DEFAULT NULL,
  `promo_end` datetime DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT NULL,
  `on_order` tinyint(1) NOT NULL DEFAULT 0,
  `weight` varchar(40) DEFAULT NULL,
  `dimensions` varchar(80) DEFAULT NULL,
  `warranty` varchar(120) DEFAULT NULL,
  `delivery_delay` varchar(120) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_brand` (`brand_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_published` (`published`),
  KEY `idx_mref` (`manufacturer_ref`),
  FULLTEXT KEY `ft_search` (`name`,`sku`,`manufacturer_ref`,`short_description`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(1,'AF-SH-MX61GTBA','MX-61GTBA','Toner Sharp MX-61GTBA noir','toner-sharp-mx-61gtba-noir',1,11,'Toner','Noir','Toner d\'origine Sharp noir, environ 40 000 pages.','<p>Toner d&#039;origine Sharp noir, environ 40 000 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-61GTBA\nCouleur: Noir\nType: Toner',38000.00,NULL,NULL,NULL,25,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,20,0,NULL,NULL,'2026-10-01 02:01:15','2026-10-01 02:01:15'),
(2,'AF-SH-MX61GTCA','MX-61GTCA','Toner Sharp MX-61GTCA cyan','toner-sharp-mx-61gtca-cyan',1,11,'Toner','Cyan','Toner d\'origine Sharp cyan, environ 24 000 pages.','<p>Toner d&#039;origine Sharp cyan, environ 24 000 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-61GTCA\nCouleur: Cyan\nType: Toner',62000.00,55000.00,NULL,NULL,12,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,36,0,NULL,NULL,'2026-10-01 02:01:15','2026-10-01 02:01:15'),
(3,'AF-SH-MX61GTMA','MX-61GTMA','Toner Sharp MX-61GTMA magenta','toner-sharp-mx-61gtma-magenta',1,11,'Toner','Magenta','Toner d\'origine Sharp magenta, environ 24 000 pages.','<p>Toner d&#039;origine Sharp magenta, environ 24 000 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-61GTMA\nCouleur: Magenta\nType: Toner',62000.00,NULL,NULL,NULL,9,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,53,0,NULL,NULL,'2026-10-01 02:01:15','2026-10-01 02:01:15'),
(4,'AF-SH-MX61GTYA','MX-61GTYA','Toner Sharp MX-61GTYA jaune','toner-sharp-mx-61gtya-jaune',1,11,'Toner','Jaune','Toner d\'origine Sharp jaune, environ 24 000 pages.','<p>Toner d&#039;origine Sharp jaune, environ 24 000 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-61GTYA\nCouleur: Jaune\nType: Toner',62000.00,NULL,NULL,NULL,3,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,48,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(5,'AF-SH-MX315GT','MX-315GT','Toner Sharp MX-315GT noir','toner-sharp-mx-315gt-noir',1,11,'Toner','Noir','Toner Sharp haute capacité pour multifonctions monochromes MX-M.','<p>Toner Sharp haute capacité pour multifonctions monochromes MX-M.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-315GT\nCouleur: Noir\nType: Toner',45000.00,NULL,NULL,NULL,18,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,8,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(6,'AF-SH-MX61DR','MX-61GRSA','Tambour Sharp MX-61GRSA','tambour-sharp-mx-61grsa',1,13,'Tambour','Noir','Kit tambour d\'origine Sharp.','<p>Kit tambour d&#039;origine Sharp.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-61GRSA\nCouleur: Noir\nType: Tambour',95000.00,NULL,NULL,NULL,4,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,7,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(7,'AF-SH-BPGT20BA','BP-GT20BA','Toner Sharp BP-GT20BA noir','toner-sharp-bp-gt20ba-noir',1,11,'Toner','Noir','Toner Sharp pour la gamme BP.','<p>Toner Sharp pour la gamme BP.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: BP-GT20BA\nCouleur: Noir\nType: Toner',41000.00,NULL,NULL,NULL,0,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,34,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(8,'AF-SH-AR020','AR-020LT','Toner Sharp AR-020LT noir','toner-sharp-ar-020lt-noir',1,11,'Toner','Noir','Toner pour copieurs Sharp AR-6020.','<p>Toner pour copieurs Sharp AR-6020.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: AR-020LT\nCouleur: Noir\nType: Toner',18500.00,16500.00,NULL,NULL,30,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,34,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(9,'AF-CA-CEXV33','C-EXV33','Toner Canon C-EXV33 noir','toner-canon-c-exv33-noir',2,11,'Toner','Noir','Toner d\'origine Canon, environ 14 600 pages.','<p>Toner d&#039;origine Canon, environ 14 600 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: C-EXV33\nCouleur: Noir\nType: Toner',29000.00,NULL,NULL,NULL,14,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,45,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(10,'AF-CA-CEXV49K','C-EXV49','Toner Canon C-EXV49 noir','toner-canon-c-exv49-noir',2,11,'Toner','Noir','Toner d\'origine Canon pour imageRUNNER ADVANCE couleur.','<p>Toner d&#039;origine Canon pour imageRUNNER ADVANCE couleur.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: C-EXV49\nCouleur: Noir\nType: Toner',52000.00,NULL,NULL,NULL,7,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,21,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(11,'AF-CA-CRG057','CRG-057','Cartouche toner Canon 057 noir','cartouche-toner-canon-057-noir',2,11,'Toner','Noir','Cartouche Canon 057, 3 100 pages.','<p>Cartouche Canon 057, 3 100 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: CRG-057\nCouleur: Noir\nType: Toner',64000.00,58000.00,NULL,NULL,10,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,52,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(12,'AF-CA-GI41BK','GI-41 BK','Bouteille d\'encre Canon GI-41 noir','bouteille-d-encre-canon-gi-41-noir',2,16,'Encre','Noir','Encre d\'origine pour imprimantes PIXMA G.','<p>Encre d&#039;origine pour imprimantes PIXMA G.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: GI-41 BK\nCouleur: Noir\nType: Encre',7500.00,NULL,NULL,NULL,40,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,40,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(13,'AF-HP-59A','CF259A','Toner HP 59A noir','toner-hp-59a-noir',3,11,'Toner','Noir','Toner HP LaserJet d\'origine, 3 000 pages.','<p>Toner HP LaserJet d&#039;origine, 3 000 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: CF259A\nCouleur: Noir\nType: Toner',98000.00,NULL,NULL,NULL,11,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,50,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(14,'AF-HP-135A','W1350A','Toner HP 135A noir','toner-hp-135a-noir',3,11,'Toner','Noir','Toner HP d\'origine, 1 100 pages.','<p>Toner HP d&#039;origine, 1 100 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: W1350A\nCouleur: Noir\nType: Toner',49000.00,NULL,NULL,NULL,2,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,48,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(15,'AF-HP-415A','W2030A','Toner HP 415A noir','toner-hp-415a-noir',3,11,'Toner','Noir','Toner HP Color LaserJet, 2 400 pages.','<p>Toner HP Color LaserJet, 2 400 pages.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: W2030A\nCouleur: Noir\nType: Toner',89000.00,NULL,NULL,NULL,6,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,40,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(16,'AF-HP-963XL','3JA30AE','Cartouche HP 963XL noir','cartouche-hp-963xl-noir',3,12,'Cartouche','Noir','Cartouche d\'encre HP haute capacité.','<p>Cartouche d&#039;encre HP haute capacité.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: 3JA30AE\nCouleur: Noir\nType: Cartouche',39000.00,35000.00,NULL,NULL,15,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,17,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(17,'AF-HP-305','3YM61AE','Cartouche HP 305 noir','cartouche-hp-305-noir',3,12,'Cartouche','Noir','Cartouche d\'encre HP 305.','<p>Cartouche d&#039;encre HP 305.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: 3YM61AE\nCouleur: Noir\nType: Cartouche',9500.00,NULL,NULL,NULL,50,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,50,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(18,'AF-RI-842311','842311','Toner Ricoh IM 2702 noir','toner-ricoh-im-2702-noir',4,11,'Toner','Noir','Toner d\'origine Ricoh.','<p>Toner d&#039;origine Ricoh.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Ricoh\nRéférence: 842311\nCouleur: Noir\nType: Toner',33000.00,NULL,NULL,NULL,8,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,1,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(19,'AF-RI-SP230','408294','Toner Ricoh SP 230H noir','toner-ricoh-sp-230h-noir',4,11,'Toner','Noir','Toner Ricoh haute capacité.','<p>Toner Ricoh haute capacité.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Ricoh\nRéférence: 408294\nCouleur: Noir\nType: Toner',42000.00,NULL,NULL,NULL,0,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,59,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(20,'AF-SH-MX3061','MX-3061','Multifonction Sharp MX-3061 couleur A3','multifonction-sharp-mx-3061-couleur-a3',1,4,'Multifonction','Couleur','Multifonction couleur A3 30 ppm, écran tactile 10,1\", recto-verso, scan réseau.','<p>Multifonction couleur A3 30 ppm, écran tactile 10,1&quot;, recto-verso, scan réseau.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-3061\nCouleur: Couleur\nType: Multifonction',3450000.00,NULL,NULL,NULL,3,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,7,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(21,'AF-SH-MXM3071','MX-M3071','Multifonction Sharp MX-M3071 monochrome A3','multifonction-sharp-mx-m3071-monochrome-a3',1,4,'Multifonction','Monochrome','Multifonction monochrome A3 30 ppm pour bureaux exigeants.','<p>Multifonction monochrome A3 30 ppm pour bureaux exigeants.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: MX-M3071\nCouleur: Monochrome\nType: Multifonction',2450000.00,2290000.00,NULL,NULL,2,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,23,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(22,'AF-SH-BP30M28','BP-30M28','Copieur Sharp BP-30M28 monochrome','copieur-sharp-bp-30m28-monochrome',1,5,'Copieur','Monochrome','Copieur multifonction A3 28 ppm.','<p>Copieur multifonction A3 28 ppm.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Sharp\nRéférence: BP-30M28\nCouleur: Monochrome\nType: Copieur',1850000.00,NULL,NULL,NULL,4,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,29,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(23,'AF-CA-MF445','MF445dw','Imprimante Canon i-SENSYS MF445dw','imprimante-canon-i-sensys-mf445dw',2,2,'Laser','Monochrome','Multifonction laser 4-en-1 Wi-Fi, 38 ppm.','<p>Multifonction laser 4-en-1 Wi-Fi, 38 ppm.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: MF445dw\nCouleur: Monochrome\nType: Laser',365000.00,NULL,NULL,NULL,6,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,46,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(24,'AF-CA-G3410','G3410','Imprimante Canon PIXMA G3410','imprimante-canon-pixma-g3410',2,3,'Jet d\'encre','Couleur','Imprimante à réservoirs rechargeables Wi-Fi.','<p>Imprimante à réservoirs rechargeables Wi-Fi.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Canon\nRéférence: G3410\nCouleur: Couleur\nType: Jet d\'encre',125000.00,115000.00,NULL,NULL,9,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,42,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(25,'AF-HP-M404','W1A53A','Imprimante HP LaserJet Pro M404dn','imprimante-hp-laserjet-pro-m404dn',3,2,'Laser','Monochrome','Imprimante laser monochrome réseau, recto-verso.','<p>Imprimante laser monochrome réseau, recto-verso.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: W1A53A\nCouleur: Monochrome\nType: Laser',295000.00,NULL,NULL,NULL,5,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,13,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(26,'AF-HP-T230','5HB07A','Traceur HP DesignJet T230 24\"','traceur-hp-designjet-t230-24',3,9,'Traceur','Couleur','Traceur grand format 24 pouces Wi-Fi.','<p>Traceur grand format 24 pouces Wi-Fi.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: 5HB07A\nCouleur: Couleur\nType: Traceur',1150000.00,NULL,NULL,NULL,1,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,3,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(27,'AF-DE-LAT5440','LAT5440-I5','Ordinateur portable Dell Latitude 5440 i5 16 Go 512 Go','ordinateur-portable-dell-latitude-5440-i5-16-go-512-go',7,19,'Portable',NULL,'Portable professionnel 14\", Intel Core i5, 16 Go RAM, SSD 512 Go.','<p>Portable professionnel 14&quot;, Intel Core i5, 16 Go RAM, SSD 512 Go.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Dell\nRéférence: LAT5440-I5\nType: Portable',785000.00,NULL,NULL,NULL,7,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,23,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(28,'AF-HP-PB450','PB450G10','Ordinateur portable HP ProBook 450 G10','ordinateur-portable-hp-probook-450-g10',3,19,'Portable',NULL,'Portable 15,6\", Intel Core i5, 8 Go RAM, SSD 512 Go.','<p>Portable 15,6&quot;, Intel Core i5, 8 Go RAM, SSD 512 Go.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: HP\nRéférence: PB450G10\nType: Portable',695000.00,649000.00,NULL,NULL,5,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,49,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(29,'AF-LE-M70Q','M70Q-G4','Ordinateur de bureau Lenovo ThinkCentre M70q','ordinateur-de-bureau-lenovo-thinkcentre-m70q',8,20,'Bureau',NULL,'Mini PC professionnel Intel Core i5.','<p>Mini PC professionnel Intel Core i5.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Lenovo\nRéférence: M70Q-G4\nType: Bureau',545000.00,NULL,NULL,NULL,4,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,8,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(30,'AF-DE-P2423','P2423','Écran Dell P2423 24\"','ecran-dell-p2423-24',7,21,'Écran',NULL,'Écran IPS 24\" WUXGA.','<p>Écran IPS 24&quot; WUXGA.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Dell\nRéférence: P2423\nType: Écran',165000.00,NULL,NULL,NULL,12,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,57,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(31,'AF-LO-MK270','MK270','Clavier + souris sans fil Logitech MK270','clavier-souris-sans-fil-logitech-mk270',10,22,'Clavier/Souris',NULL,'Ensemble sans fil AZERTY.','<p>Ensemble sans fil AZERTY.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Logitech\nRéférence: MK270\nType: Clavier/Souris',22000.00,NULL,NULL,NULL,35,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,23,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(32,'AF-AP-BX1200','BX1200MI','Onduleur APC Back-UPS 1200 VA','onduleur-apc-back-ups-1200-va',9,23,'Onduleur',NULL,'Onduleur line-interactive 1200 VA, 6 prises.','<p>Onduleur line-interactive 1200 VA, 6 prises.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: APC\nRéférence: BX1200MI\nType: Onduleur',98000.00,NULL,NULL,NULL,8,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,12,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(33,'AF-KI-DT64','DTX/64GB','Clé USB Kingston DataTraveler 64 Go','cle-usb-kingston-datatraveler-64-go',11,25,'Clé USB',NULL,'Clé USB 3.2 64 Go.','<p>Clé USB 3.2 64 Go.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Kingston\nRéférence: DTX/64GB\nType: Clé USB',6500.00,5500.00,NULL,NULL,80,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,55,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(34,'AF-KI-A400','SA400S37/480G','SSD Kingston A400 480 Go','ssd-kingston-a400-480-go',11,24,'SSD',NULL,'SSD SATA 2,5\" 480 Go.','<p>SSD SATA 2,5&quot; 480 Go.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Kingston\nRéférence: SA400S37/480G\nType: SSD',32000.00,NULL,NULL,NULL,20,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,56,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(35,'AF-NA-A480','NAV-A4-80','Ramette papier Navigator A4 80 g (500 feuilles)','ramette-papier-navigator-a4-80-g-500-feuilles',12,29,'Papier','Blanc','Papier premium A4 80 g/m².','<p>Papier premium A4 80 g/m².</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Navigator\nRéférence: NAV-A4-80\nCouleur: Blanc\nType: Papier',4500.00,NULL,NULL,NULL,300,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',1,1,40,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(36,'AF-NA-A4C5','NAV-A4-80-C5','Carton 5 ramettes Navigator A4 80 g','carton-5-ramettes-navigator-a4-80-g',12,29,'Papier','Blanc','Carton de 5 ramettes A4.','<p>Carton de 5 ramettes A4.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Navigator\nRéférence: NAV-A4-80-C5\nCouleur: Blanc\nType: Papier',21000.00,19500.00,NULL,NULL,60,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,7,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(37,'AF-BI-CRIST50','BIC-CR-50','Stylos Bic Cristal bleu (boîte de 50)','stylos-bic-cristal-bleu-boite-de-50',13,33,'Stylo','Bleu','Stylos bille pointe moyenne.','<p>Stylos bille pointe moyenne.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Bic\nRéférence: BIC-CR-50\nCouleur: Bleu\nType: Stylo',7500.00,NULL,NULL,NULL,45,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,0,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(38,'AF-FB-ENV-C4','ENV-C4-250','Enveloppes C4 kraft (boîte de 250)','enveloppes-c4-kraft-boite-de-250',NULL,30,'Enveloppe',NULL,'Enveloppes kraft 229 x 324 mm.','<p>Enveloppes kraft 229 x 324 mm.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Générique\nRéférence: ENV-C4-250\nType: Enveloppe',18000.00,NULL,NULL,NULL,15,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,43,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(39,'AF-FB-CLAS80','CLAS-A4-80','Classeur à levier A4 dos 80 mm','classeur-a-levier-a4-dos-80-mm',NULL,32,'Classeur',NULL,'Classeur carton à levier.','<p>Classeur carton à levier.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Générique\nRéférence: CLAS-A4-80\nType: Classeur',2200.00,NULL,NULL,NULL,120,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,10,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16'),
(40,'AF-FB-AGR266','AGR-26-6','Agrafes 26/6 (boîte de 1000)','agrafes-26-6-boite-de-1000',NULL,34,'Agrafes',NULL,'Agrafes galvanisées standard.','<p>Agrafes galvanisées standard.</p><p>Produit garanti, livré par AFAM. Contactez-nous pour un tarif professionnel ou un achat en volume.</p>','Marque: Générique\nRéférence: AGR-26-6\nType: Agrafes',600.00,NULL,NULL,NULL,200,NULL,0,NULL,NULL,'1 an','24 à 72 h à Dakar',0,1,23,0,NULL,NULL,'2026-10-01 02:01:16','2026-10-01 02:01:16');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `discount_type` enum('percent','amount') NOT NULL DEFAULT 'percent',
  `value` decimal(14,2) NOT NULL,
  `scope` enum('product','category','brand','all') NOT NULL DEFAULT 'product',
  `target_id` int(10) unsigned DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES
(1,'Promo papeterie','percent',5.00,'category',28,NULL,NULL,1,'2026-10-01 02:01:16');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('quote','rental','maintenance','contact') NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `company` varchar(190) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_label` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `printer_model` varchar(150) DEFAULT NULL,
  `serial_number` varchar(120) DEFAULT NULL,
  `subject` varchar(190) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('new','in_progress','done','closed') NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `requests` WRITE;
/*!40000 ALTER TABLE `requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(190) DEFAULT NULL,
  `body` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`,`status`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) NOT NULL,
  `title` varchar(190) NOT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `short_desc` varchar(255) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `form_type` varchar(20) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'location','Location d\'imprimantes','key','Des multifonctions en location clé en main, maintenance et consommables inclus.','<h2>La location clé en main</h2><p>Équipez vos bureaux avec des multifonctions Sharp récentes sans investissement initial.</p><h3>Avantages</h3><ul><li>Aucun investissement : un loyer mensuel maîtrisé</li><li>Maintenance et consommables inclus</li><li>Matériel récent et évolutif</li><li>Interventions rapides de techniciens certifiés</li></ul><h3>Maintenance associée</h3><p>Chaque contrat comprend la maintenance préventive et corrective ainsi que le remplacement des pièces d\'usure.</p>','assets/img/contenus/service-location.jpg','rental',1,1),
(2,'maintenance','Maintenance','wrench','Maintenance préventive et corrective, remplacement de pièces et assistance.','<h2>Maintenance de votre parc d\'impression</h2><ul><li><strong>Maintenance préventive</strong> : visites planifiées pour éviter les pannes</li><li><strong>Maintenance corrective</strong> : diagnostic et réparation</li><li><strong>Intervention</strong> sur site à Dakar et en régions</li><li><strong>Remplacement de pièces</strong> d\'origine</li><li><strong>Assistance</strong> téléphonique et à distance</li></ul>','assets/img/contenus/service-maintenance.jpg','maintenance',2,1),
(3,'solutions-impression','Solutions d\'impression','briefcase','Audit, gestion de parc et réduction des coûts d\'impression.','<h2>Optimisez vos impressions</h2><p>Nous analysons vos volumes et usages pour proposer la solution la plus économique et écologique.</p>','assets/img/contenus/service-solutions.jpg','quote',3,1),
(4,'vente-materiel','Vente de matériel','printer','Imprimantes, copieurs, informatique et consommables d\'origine.','<h2>Vente de matériel professionnel</h2><p>Un large choix de matériel des plus grandes marques avec installation et formation.</p>','assets/img/contenus/service-vente.jpg','quote',4,1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `skey` varchar(100) NOT NULL,
  `svalue` mediumtext DEFAULT NULL,
  `sgroup` varchar(50) NOT NULL DEFAULT 'general',
  `label` varchar(190) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
('admin_notification_email','commandes@afamshop.sn','general','Email des notifications admin','email',14),
('allow_backorders','0','shop','Autoriser la commande des produits en rupture','bool',82),
('attach_invoice','1','shop','Joindre la facture PDF à l\'email de paiement','bool',93),
('color_accent','#e8730c','appearance','Couleur d\'accent (boutons d\'achat, promos)','color',23),
('color_primary','#0b4f8a','appearance','Couleur principale','color',21),
('color_secondary','#0f2a44','appearance','Couleur secondaire (header/footer)','color',22),
('company_address','Dakar, Sénégal','general','Adresse','textarea',5),
('company_ids','NINEA : à compléter\nRCCM : à compléter','general','Identifiants légaux (NINEA, RCCM...)','textarea',6),
('company_legal_name','AFAM SARL','general','Raison sociale (factures)','text',4),
('company_name','AFAM','general','Nom de l\'entreprise','text',3),
('contact_email','contact@afamshop.sn','general','Email de contact','email',7),
('contact_phone','+221 33 000 00 00','general','Téléphone','text',8),
('contact_phone2','','general','Téléphone secondaire','text',9),
('cookie_banner_enabled','1','footer','Afficher le bandeau cookies','bool',78),
('custom_css','','appearance','CSS personnalisé','textarea',24),
('default_delivery_delay','24 à 72 h à Dakar','shop','Délai de livraison par défaut','text',96),
('default_warranty','1 an','shop','Garantie par défaut','text',95),
('email_footer','{company_name} — {address} — {phone}','emails','Pied des emails','textarea',107),
('email_order_cancelled_body','Bonjour {first_name},\n\nVotre commande {order_number} a été annulée.\n{comment}','emails','Annulation : contenu','textarea',121),
('email_order_cancelled_subject','Annulation de votre commande {order_number}','emails','Annulation : sujet','text',120),
('email_order_confirmation_body','Bonjour {first_name},\n\nNous avons bien reçu votre commande {order_number} d\'un montant de {total}.\nMode de paiement : {payment_method}.','emails','Confirmation de commande : contenu','textarea',111),
('email_order_confirmation_subject','Confirmation de votre commande {order_number}','emails','Confirmation de commande : sujet','text',110),
('email_order_delivered_body','Bonjour {first_name},\n\nVotre commande {order_number} a été livrée. Merci pour votre confiance !','emails','Livraison : contenu','textarea',119),
('email_order_delivered_subject','Votre commande {order_number} a été livrée','emails','Livraison : sujet','text',118),
('email_order_preparing_body','Bonjour {first_name},\n\nVotre commande {order_number} est en cours de préparation.\n{comment}','emails','Préparation : contenu','textarea',115),
('email_order_preparing_subject','Votre commande {order_number} est en préparation','emails','Préparation : sujet','text',114),
('email_order_refunded_body','Bonjour {first_name},\n\nLe remboursement de votre commande {order_number} a été effectué.\n{comment}','emails','Remboursement : contenu','textarea',123),
('email_order_refunded_subject','Remboursement de votre commande {order_number}','emails','Remboursement : sujet','text',122),
('email_order_shipped_body','Bonjour {first_name},\n\nBonne nouvelle : votre commande {order_number} est en route !\n{comment}','emails','Expédition : contenu','textarea',117),
('email_order_shipped_subject','Votre commande {order_number} a été expédiée','emails','Expédition : sujet','text',116),
('email_password_reset_body','Bonjour {first_name},\n\nPour choisir un nouveau mot de passe, cliquez sur le lien ci-dessous (valable 1 heure) :\n{link}\n\nSi vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.','emails','Mot de passe oublié : contenu','textarea',125),
('email_password_reset_subject','Réinitialisation de votre mot de passe {site_name}','emails','Mot de passe oublié : sujet','text',124),
('email_payment_confirmation_body','Bonjour {first_name},\n\nNous confirmons la réception de votre paiement de {total} pour la commande {order_number}. Votre facture est disponible dans votre espace client.','emails','Confirmation de paiement : contenu','textarea',113),
('email_payment_confirmation_subject','Paiement confirmé — commande {order_number}','emails','Confirmation de paiement : sujet','text',112),
('email_request_ack_body','Bonjour {name},\n\nMerci pour votre demande ({type}). Notre équipe vous recontacte dans les plus brefs délais.','emails','Accusé de réception des demandes : contenu','textarea',127),
('email_request_ack_subject','Nous avons bien reçu votre demande','emails','Accusé de réception des demandes : sujet','text',126),
('email_welcome_body','Bonjour {first_name},\n\nVotre compte {site_name} a bien été créé. Vous pouvez dès maintenant suivre vos commandes, gérer vos adresses et vos favoris.\n\nÀ bientôt !','emails','Création de compte : contenu','textarea',109),
('email_welcome_subject','Bienvenue sur {site_name}','emails','Création de compte : sujet','text',108),
('favicon','assets/img/contenus/favicon.png','general','Favicon','image',20),
('footer_about','AFAM est une entreprise sénégalaise spécialisée dans les solutions d\'impression professionnelles : vente, location et maintenance.','footer','Texte de présentation','textarea',69),
('footer_copyright','© {year} {company_name} — {site_name}. Tous droits réservés.','footer','Copyright','text',75),
('footer_credit','Site réalisé par Neosen','footer','Crédit du concepteur (pied de page)','text',76),
('footer_credit_url','https://neosen.tech','footer','Lien du crédit','url',77),
('free_shipping_threshold','100000','shop','Livraison gratuite à partir de (0 = désactivé)','number',84),
('google_analytics_id','','seo','Google Analytics (ID G-XXXX)','text',105),
('hero_button_link','categorie/impression','home','Bannière : lien bouton 1','text',39),
('hero_button_text','Découvrir le catalogue','home','Bannière : bouton 1','text',38),
('hero_button2_link','devis','home','Bannière : lien bouton 2','text',41),
('hero_button2_text','Demander un devis','home','Bannière : bouton 2','text',40),
('hero_image','assets/img/contenus/hero.jpg','home','Bannière : image de fond','image',42),
('hero_subtitle','Imprimantes, copieurs, consommables, informatique et fournitures de bureau. Vente, location et maintenance clé en main avec AFAM, représentant exclusif Sharp au Sénégal.','home','Bannière : sous-titre','textarea',37),
('hero_title','Des solutions d\'impression professionnelles adaptées à vos besoins','home','Bannière : titre','text',36),
('hero_video','','home','Bannière : vidéo de fond (MP4)','video',43),
('home_brands_title','Nos marques','home','Titre : marques','text',65),
('home_categories_title','Nos univers','home','Titre : catégories','text',48),
('home_contact_text','Notre équipe commerciale vous répond par téléphone, email ou WhatsApp.','home','Bloc contact : texte','textarea',67),
('home_contact_title','Besoin d\'un conseil ?','home','Bloc contact : titre','text',66),
('home_finder_text','Choisissez la marque et le modèle de votre imprimante : nous affichons instantanément les toners, cartouches et tambours compatibles.','home','Texte : recherche par imprimante','textarea',52),
('home_finder_title','Trouvez le consommable de votre imprimante','home','Titre : recherche par imprimante','text',51),
('home_maintenance_image','assets/img/contenus/home-maintenance.jpg','home','Bloc maintenance : image','image',61),
('home_maintenance_text','Maintenance préventive et corrective, remplacement de pièces et intervention rapide de nos techniciens certifiés.','home','Bloc maintenance : texte','textarea',60),
('home_maintenance_title','Maintenance & assistance','home','Bloc maintenance : titre','text',59),
('home_popular_count','8','home','Nombre de produits populaires','number',68),
('home_popular_title','Produits populaires','home','Titre : produits populaires','text',49),
('home_pro_image','assets/img/contenus/home-pro.jpg','home','Bloc pro : image','image',55),
('home_pro_text','Entreprises et administrations : bénéficiez d\'un accompagnement personnalisé, de tarifs adaptés et d\'un audit de votre parc d\'impression pour réduire vos coûts.','home','Bloc pro : texte','textarea',54),
('home_pro_title','Solutions professionnelles','home','Bloc pro : titre','text',53),
('home_promo_title','Offres et promotions','home','Titre : promotions','text',50),
('home_rental_image','assets/img/contenus/home-location.jpg','home','Bloc location : image','image',58),
('home_rental_text','Des multifonctions Sharp performantes en location clé en main : matériel, consommables et maintenance inclus dans un loyer maîtrisé.','home','Bloc location : texte','textarea',57),
('home_rental_title','Location d\'imprimantes','home','Bloc location : titre','text',56),
('home_sharp_image','assets/img/contenus/home-sharp.jpg','home','Bloc Sharp : image','image',64),
('home_sharp_text','Performance, économies et respect de l\'environnement : découvrez la gamme de multifonctions Sharp et nos offres clé en main.','home','Bloc Sharp : texte','textarea',63),
('home_sharp_title','AFAM, représentant exclusif Sharp au Sénégal','home','Bloc Sharp : titre','text',62),
('host_info','Nom de l\'hébergeur — adresse — téléphone (à compléter)','general','Hébergeur du site (mentions légales)','textarea',16),
('invoice_counter','0','system','Compteur de factures','number',0),
('invoice_footer','{company_name} — {address} — {phone} — {email}. Merci pour votre confiance.','shop','Mentions en bas de facture','textarea',91),
('invoice_logo','','shop','Logo des factures (JPEG/PNG)','image',92),
('invoice_prefix','FAC','shop','Préfixe des numéros de facture','text',90),
('legal_representative','Le gérant de AFAM','general','Directeur de la publication / représentant légal (pages légales)','text',15),
('logo','assets/img/contenus/logo.png','general','Logo','image',18),
('logo_footer','assets/img/contenus/logo-blanc.png','general','Logo (pied de page)','image',19),
('low_stock_threshold','5','shop','Seuil de stock faible (par défaut)','number',80),
('map_embed','','general','Carte (URL d\'intégration Google Maps)','url',17),
('menu_label_about','À propos','header','Menu : À propos','text',34),
('menu_label_brands','Marques','header','Menu : Marques','text',32),
('menu_label_consumables','Consommables','header','Menu : Consommables','text',28),
('menu_label_contact','Contact','header','Menu : Contact','text',35),
('menu_label_it','Informatique','header','Menu : Informatique','text',29),
('menu_label_printing','Impression','header','Menu : Impression','text',27),
('menu_label_promotions','Promotions','header','Menu : Promotions','text',31),
('menu_label_services','Services','header','Menu : Services','text',33),
('menu_label_stationery','Papeterie','header','Menu : Papeterie','text',30),
('meta_description','Imprimantes, copieurs Sharp, toners, cartouches, informatique et fournitures de bureau au Sénégal. Vente, location et maintenance.','seo','Description SEO par défaut','textarea',104),
('meta_title','AFAMSHOP — Impression, consommables et informatique au Sénégal','seo','Titre SEO par défaut','text',103),
('opening_hours','Lundi – Vendredi : 8h30 – 18h00\nSamedi : 9h00 – 13h00','general','Horaires','textarea',13),
('order_prefix','AF','shop','Préfixe des numéros de commande','text',89),
('paydunya_channels','','payment','Canaux PayDunya (ex : wave-senegal,orange-money-senegal,card) — vide = tous','text',99),
('payment_cod_enabled','1','payment','Activer le paiement à la livraison','bool',100),
('payment_paydunya_enabled','1','payment','Activer PayDunya (Wave, Orange Money...)','bool',98),
('payment_stripe_enabled','1','payment','Activer Stripe (carte bancaire)','bool',97),
('payment_transfer_enabled','0','payment','Activer le virement bancaire','bool',101),
('pickup_address','Showroom AFAM, Dakar','shop','Adresse de retrait','text',86),
('pickup_enabled','1','shop','Autoriser le retrait en magasin','bool',85),
('prices_include_tax','1','shop','Prix affichés TTC','bool',88),
('products_per_page','24','shop','Produits par page','number',79),
('reassurance_1','Livraison rapide|Dakar et régions','home','Réassurance 1 (titre|texte)','text',44),
('reassurance_2','Paiement sécurisé|Carte, Wave, Orange Money','home','Réassurance 2 (titre|texte)','text',45),
('reassurance_3','Produits authentiques|Consommables d\'origine','home','Réassurance 3 (titre|texte)','text',46),
('reassurance_4','Support expert|Maintenance et assistance','home','Réassurance 4 (titre|texte)','text',47),
('recaptcha_enabled','1','seo','Activer reCAPTCHA sur les formulaires (clés dans .env)','bool',106),
('reviews_enabled','1','shop','Activer les avis clients','bool',94),
('show_stock_qty','1','shop','Afficher la quantité en stock','bool',83),
('site_domain','afamshop.sn','general','Nom de domaine','text',2),
('site_name','AFAMSHOP','general','Nom de la plateforme','text',0),
('site_tagline','Solutions d\'impression professionnelles au Sénégal','general','Slogan','text',1),
('social_facebook','','footer','Facebook (URL)','url',70),
('social_instagram','','footer','Instagram (URL)','url',71),
('social_linkedin','','footer','LinkedIn (URL)','url',72),
('social_tiktok','','footer','TikTok (URL)','url',74),
('social_youtube','','footer','YouTube (URL)','url',73),
('stock_decrement_on','paid','shop','Décrément du stock : \"paid\" (paiement confirmé / préparation) ou \"order\" (dès la commande)','text',81),
('tax_rate','18','shop','Taux de TVA (%)','number',87),
('topbar_enabled','1','header','Afficher la barre d\'information','bool',25),
('topbar_text','Livraison rapide à Dakar • Représentant exclusif Sharp au Sénégal','header','Texte de la barre d\'information','text',26),
('transfer_instructions','Banque : …\\nIBAN : …\\nMerci d\'indiquer votre numéro de commande en référence.','payment','Instructions de virement','textarea',102),
('whatsapp_enabled','1','general','Afficher le bouton WhatsApp flottant','bool',12),
('whatsapp_message','Bonjour, je souhaite un renseignement.','general','Message WhatsApp pré-rempli','text',11),
('whatsapp_number','+221770000000','general','Numéro WhatsApp','text',10);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `qty_change` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `reason` varchar(60) NOT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`,`created_at`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES
(1,1,25,25,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:15'),
(2,2,12,12,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:15'),
(3,3,9,9,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(4,4,3,3,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(5,5,18,18,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(6,6,4,4,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(7,8,30,30,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(8,9,14,14,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(9,10,7,7,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(10,11,10,10,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(11,12,40,40,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(12,13,11,11,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(13,14,2,2,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(14,15,6,6,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(15,16,15,15,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(16,17,50,50,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(17,18,8,8,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(18,20,3,3,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(19,21,2,2,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(20,22,4,4,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(21,23,6,6,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(22,24,9,9,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(23,25,5,5,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(24,26,1,1,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(25,27,7,7,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(26,28,5,5,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(27,29,4,4,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(28,30,12,12,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(29,31,35,35,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(30,32,8,8,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(31,33,80,80,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(32,34,20,20,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(33,35,300,300,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(34,36,60,60,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(35,37,45,45,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(36,38,15,15,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(37,39,120,120,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16'),
(38,40,200,200,'import',NULL,NULL,'Stock initial','2026-10-01 02:01:16');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

