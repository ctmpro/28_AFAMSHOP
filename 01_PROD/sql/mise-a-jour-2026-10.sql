-- =====================================================================
-- AFAMSHOP — mise à jour d'une base déjà installée (octobre 2026)
--  * Active l'affichage des prix en euros (parité fixe 1 € = 655,957 FCFA)
--  * Met en place les images par défaut (uniquement là où aucune image n'est définie)
-- À importer une fois dans phpMyAdmin (onglet « Importer »). Sans risque si relancé.
-- =====================================================================
SET NAMES utf8mb4;

-- Devises : FCFA (paiement) + Euro (affichage) — taux stocké avec plus de précision
ALTER TABLE currencies MODIFY rate DECIMAL(20,10) NOT NULL DEFAULT 1;
INSERT INTO currencies (code, name, symbol, rate, decimals, symbol_after, is_default, active)
VALUES ('EUR', 'Euro', '€', 0.0015244902, 2, 1, 0, 1)
ON DUPLICATE KEY UPDATE active = 1, rate = 0.0015244902, decimals = 2;

-- Paramètres du site (images vides uniquement)
UPDATE settings SET svalue = 'assets/img/contenus/logo.png'             WHERE skey = 'logo'                   AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/logo-blanc.png'       WHERE skey = 'logo_footer'            AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/favicon.png'          WHERE skey = 'favicon'                AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/hero.jpg'             WHERE skey = 'hero_image'             AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/home-pro.jpg'         WHERE skey = 'home_pro_image'         AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/home-location.jpg'    WHERE skey = 'home_rental_image'      AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/home-maintenance.jpg' WHERE skey = 'home_maintenance_image' AND (svalue IS NULL OR svalue = '');
UPDATE settings SET svalue = 'assets/img/contenus/home-sharp.jpg'       WHERE skey = 'home_sharp_image'       AND (svalue IS NULL OR svalue = '');

-- Catégories principales
UPDATE categories SET image = 'assets/img/contenus/cat-impression.jpg'   WHERE slug = 'impression'   AND (image IS NULL OR image = '');
UPDATE categories SET image = 'assets/img/contenus/cat-consommables.jpg' WHERE slug = 'consommables' AND (image IS NULL OR image = '');
UPDATE categories SET image = 'assets/img/contenus/cat-informatique.jpg' WHERE slug = 'informatique' AND (image IS NULL OR image = '');
UPDATE categories SET image = 'assets/img/contenus/cat-papeterie.jpg'    WHERE slug = 'papeterie'    AND (image IS NULL OR image = '');

-- Services
UPDATE services SET image = 'assets/img/contenus/service-location.jpg'    WHERE slug = 'location'             AND (image IS NULL OR image = '');
UPDATE services SET image = 'assets/img/contenus/service-maintenance.jpg' WHERE slug = 'maintenance'          AND (image IS NULL OR image = '');
UPDATE services SET image = 'assets/img/contenus/service-solutions.jpg'   WHERE slug = 'solutions-impression' AND (image IS NULL OR image = '');
UPDATE services SET image = 'assets/img/contenus/service-vente.jpg'       WHERE slug = 'vente-materiel'       AND (image IS NULL OR image = '');

-- Bannières promotionnelles existantes
UPDATE banners SET image_desktop = 'assets/img/contenus/promo-toners.jpg'   WHERE position = 'home_promo' AND link = 'promotions'       AND (image_desktop IS NULL OR image_desktop = '');
UPDATE banners SET image_desktop = 'assets/img/contenus/promo-location.jpg' WHERE position = 'home_promo' AND link = 'service/location' AND (image_desktop IS NULL OR image_desktop = '');

-- Diaporama d'accueil (ajouté seulement s'il n'existe encore aucune diapositive)
INSERT INTO banners (position, title, subtitle, button_text, link, image_desktop, sort)
SELECT 'home_hero', 'Multifonctions Sharp', 'Performance, économies et respect de l''environnement', 'Découvrir', 'marque/sharp', 'assets/img/contenus/slide-sharp.jpg', 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM banners WHERE position = 'home_hero');
INSERT INTO banners (position, title, subtitle, button_text, link, image_desktop, sort)
SELECT 'home_hero', 'Informatique professionnelle', 'Ordinateurs, écrans, onduleurs et accessoires', 'Voir le catalogue', 'categorie/informatique', 'assets/img/contenus/slide-informatique.jpg', 2
FROM DUAL WHERE (SELECT COUNT(*) FROM banners WHERE position = 'home_hero') = 1;
