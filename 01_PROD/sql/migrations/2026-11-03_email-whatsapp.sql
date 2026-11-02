-- =====================================================================
-- AFAMSHOP — Coordonnées : email contact@afamshop.sn et numéro WhatsApp
--  * Email de contact et des notifications : contact@afamshop.sn
--  * WhatsApp (bouton flottant, fiches produits, pages services) : +221 77 789 88 79
-- Les numéros d'appel (+221 78 353 22 03 et +221 33 827 51 41) ne changent pas.
-- =====================================================================
SET NAMES utf8mb4;

UPDATE settings SET svalue = 'contact@afamshop.sn' WHERE skey IN ('contact_email', 'admin_notification_email');
UPDATE settings SET svalue = '+221777898879' WHERE skey = 'whatsapp_number';
UPDATE settings SET svalue = '1' WHERE skey = 'whatsapp_enabled';

-- Vérification
SELECT skey, svalue FROM settings WHERE skey IN ('contact_email', 'admin_notification_email', 'whatsapp_number', 'contact_phone', 'contact_phone2');
