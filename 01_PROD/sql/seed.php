<?php
/**
 * Données initiales : paramètres du site, contenus, catalogue de démonstration.
 * Utilisation (CLI) : php sql/install.php
 */

$settings = [
    // ---- Identité / général
    ['site_name', 'AFAMSHOP', 'general', 'Nom de la plateforme', 'text'],
    ['site_tagline', 'Solutions d\'impression professionnelles au Sénégal', 'general', 'Slogan', 'text'],
    ['site_domain', 'afamshop.sn', 'general', 'Nom de domaine', 'text'],
    ['company_name', 'AFAM', 'general', 'Nom de l\'entreprise', 'text'],
    ['company_legal_name', 'AFAM SARL', 'general', 'Raison sociale (factures)', 'text'],
    ['company_address', "Dakar, Sénégal", 'general', 'Adresse', 'textarea'],
    ['company_ids', "NINEA : à compléter\nRCCM : à compléter", 'general', 'Identifiants légaux (NINEA, RCCM...)', 'textarea'],
    ['contact_email', 'contact@afamshop.sn', 'general', 'Email de contact', 'email'],
    ['contact_phone', '+221 33 000 00 00', 'general', 'Téléphone', 'text'],
    ['contact_phone2', '', 'general', 'Téléphone secondaire', 'text'],
    ['whatsapp_number', '+221770000000', 'general', 'Numéro WhatsApp', 'text'],
    ['whatsapp_message', 'Bonjour, je souhaite un renseignement.', 'general', 'Message WhatsApp pré-rempli', 'text'],
    ['whatsapp_enabled', '1', 'general', 'Afficher le bouton WhatsApp flottant', 'bool'],
    ['opening_hours', "Lundi – Vendredi : 8h30 – 18h00\nSamedi : 9h00 – 13h00", 'general', 'Horaires', 'textarea'],
    ['admin_notification_email', 'commandes@afamshop.sn', 'general', 'Email des notifications admin', 'email'],
    ['legal_representative', 'Le gérant de AFAM', 'general', 'Directeur de la publication / représentant légal (pages légales)', 'text'],
    ['host_info', "Nom de l'hébergeur — adresse — téléphone (à compléter)", 'general', 'Hébergeur du site (mentions légales)', 'textarea'],
    ['map_embed', '', 'general', 'Carte (URL d\'intégration Google Maps)', 'url'],
    ['logo', 'assets/img/contenus/logo.png', 'general', 'Logo', 'image'],
    ['logo_footer', 'assets/img/contenus/logo-blanc.png', 'general', 'Logo (pied de page)', 'image'],
    ['favicon', 'assets/img/contenus/favicon.png', 'general', 'Favicon', 'image'],

    // ---- Apparence
    ['color_primary', '#0b4f8a', 'appearance', 'Couleur principale', 'color'],
    ['color_secondary', '#0f2a44', 'appearance', 'Couleur secondaire (header/footer)', 'color'],
    ['color_accent', '#e8730c', 'appearance', 'Couleur d\'accent (boutons d\'achat, promos)', 'color'],
    ['custom_css', '', 'appearance', 'CSS personnalisé', 'textarea'],

    // ---- En-tête
    ['topbar_enabled', '1', 'header', 'Afficher la barre d\'information', 'bool'],
    ['topbar_text', 'Livraison rapide à Dakar • Représentant exclusif Sharp au Sénégal', 'header', 'Texte de la barre d\'information', 'text'],
    ['menu_label_printing', 'Impression', 'header', 'Menu : Impression', 'text'],
    ['menu_label_consumables', 'Consommables', 'header', 'Menu : Consommables', 'text'],
    ['menu_label_it', 'Informatique', 'header', 'Menu : Informatique', 'text'],
    ['menu_label_stationery', 'Papeterie', 'header', 'Menu : Papeterie', 'text'],
    ['menu_label_promotions', 'Promotions', 'header', 'Menu : Promotions', 'text'],
    ['menu_label_brands', 'Marques', 'header', 'Menu : Marques', 'text'],
    ['menu_label_services', 'Services', 'header', 'Menu : Services', 'text'],
    ['menu_label_about', 'À propos', 'header', 'Menu : À propos', 'text'],
    ['menu_label_contact', 'Contact', 'header', 'Menu : Contact', 'text'],

    // ---- Page d'accueil
    ['hero_title', 'Des solutions d\'impression professionnelles adaptées à vos besoins', 'home', 'Bannière : titre', 'text'],
    ['hero_subtitle', 'Imprimantes, copieurs, consommables, informatique et fournitures de bureau. Vente, location et maintenance clé en main avec AFAM, représentant exclusif Sharp au Sénégal.', 'home', 'Bannière : sous-titre', 'textarea'],
    ['hero_button_text', 'Découvrir le catalogue', 'home', 'Bannière : bouton 1', 'text'],
    ['hero_button_link', 'categorie/impression', 'home', 'Bannière : lien bouton 1', 'text'],
    ['hero_button2_text', 'Demander un devis', 'home', 'Bannière : bouton 2', 'text'],
    ['hero_button2_link', 'devis', 'home', 'Bannière : lien bouton 2', 'text'],
    ['hero_image', 'assets/img/contenus/hero.jpg', 'home', 'Bannière : image de fond', 'image'],
    ['hero_video', '', 'home', 'Bannière : vidéo de fond (MP4)', 'video'],
    ['reassurance_1', 'Livraison rapide|Dakar et régions', 'home', 'Réassurance 1 (titre|texte)', 'text'],
    ['reassurance_2', 'Paiement sécurisé|Carte, Wave, Orange Money', 'home', 'Réassurance 2 (titre|texte)', 'text'],
    ['reassurance_3', 'Produits authentiques|Consommables d\'origine', 'home', 'Réassurance 3 (titre|texte)', 'text'],
    ['reassurance_4', 'Support expert|Maintenance et assistance', 'home', 'Réassurance 4 (titre|texte)', 'text'],
    ['home_categories_title', 'Nos univers', 'home', 'Titre : catégories', 'text'],
    ['home_popular_title', 'Produits populaires', 'home', 'Titre : produits populaires', 'text'],
    ['home_promo_title', 'Offres et promotions', 'home', 'Titre : promotions', 'text'],
    ['home_finder_title', 'Trouvez le consommable de votre imprimante', 'home', 'Titre : recherche par imprimante', 'text'],
    ['home_finder_text', 'Choisissez la marque et le modèle de votre imprimante : nous affichons instantanément les toners, cartouches et tambours compatibles.', 'home', 'Texte : recherche par imprimante', 'textarea'],
    ['home_pro_title', 'Solutions professionnelles', 'home', 'Bloc pro : titre', 'text'],
    ['home_pro_text', 'Entreprises et administrations : bénéficiez d\'un accompagnement personnalisé, de tarifs adaptés et d\'un audit de votre parc d\'impression pour réduire vos coûts.', 'home', 'Bloc pro : texte', 'textarea'],
    ['home_pro_image', 'assets/img/contenus/home-pro.jpg', 'home', 'Bloc pro : image', 'image'],
    ['home_rental_title', 'Location d\'imprimantes', 'home', 'Bloc location : titre', 'text'],
    ['home_rental_text', 'Des multifonctions Sharp performantes en location clé en main : matériel, consommables et maintenance inclus dans un loyer maîtrisé.', 'home', 'Bloc location : texte', 'textarea'],
    ['home_rental_image', 'assets/img/contenus/home-location.jpg', 'home', 'Bloc location : image', 'image'],
    ['home_maintenance_title', 'Maintenance & assistance', 'home', 'Bloc maintenance : titre', 'text'],
    ['home_maintenance_text', 'Maintenance préventive et corrective, remplacement de pièces et intervention rapide de nos techniciens certifiés.', 'home', 'Bloc maintenance : texte', 'textarea'],
    ['home_maintenance_image', 'assets/img/contenus/home-maintenance.jpg', 'home', 'Bloc maintenance : image', 'image'],
    ['home_sharp_title', 'AFAM, représentant exclusif Sharp au Sénégal', 'home', 'Bloc Sharp : titre', 'text'],
    ['home_sharp_text', 'Performance, économies et respect de l\'environnement : découvrez la gamme de multifonctions Sharp et nos offres clé en main.', 'home', 'Bloc Sharp : texte', 'textarea'],
    ['home_sharp_image', 'assets/img/contenus/home-sharp.jpg', 'home', 'Bloc Sharp : image', 'image'],
    ['home_brands_title', 'Nos marques', 'home', 'Titre : marques', 'text'],
    ['home_contact_title', 'Besoin d\'un conseil ?', 'home', 'Bloc contact : titre', 'text'],
    ['home_contact_text', 'Notre équipe commerciale vous répond par téléphone, email ou WhatsApp.', 'home', 'Bloc contact : texte', 'textarea'],
    ['home_popular_count', '8', 'home', 'Nombre de produits populaires', 'number'],

    // ---- Pied de page
    ['footer_about', 'AFAM est une entreprise sénégalaise spécialisée dans les solutions d\'impression professionnelles : vente, location et maintenance.', 'footer', 'Texte de présentation', 'textarea'],
    ['social_facebook', '', 'footer', 'Facebook (URL)', 'url'],
    ['social_instagram', '', 'footer', 'Instagram (URL)', 'url'],
    ['social_linkedin', '', 'footer', 'LinkedIn (URL)', 'url'],
    ['social_youtube', '', 'footer', 'YouTube (URL)', 'url'],
    ['social_tiktok', '', 'footer', 'TikTok (URL)', 'url'],
    ['footer_copyright', '© {year} {company_name} — {site_name}. Tous droits réservés.', 'footer', 'Copyright', 'text'],
    ['footer_credit', 'Site réalisé par Neosen', 'footer', 'Crédit du concepteur (pied de page)', 'text'],
    ['footer_credit_url', 'https://neosen.tech', 'footer', 'Lien du crédit', 'url'],
    ['cookie_banner_enabled', '1', 'footer', 'Afficher le bandeau cookies', 'bool'],

    // ---- Boutique
    ['products_per_page', '24', 'shop', 'Produits par page', 'number'],
    ['low_stock_threshold', '5', 'shop', 'Seuil de stock faible (par défaut)', 'number'],
    ['stock_decrement_on', 'paid', 'shop', 'Décrément du stock : "paid" (paiement confirmé / préparation) ou "order" (dès la commande)', 'text'],
    ['allow_backorders', '0', 'shop', 'Autoriser la commande des produits en rupture', 'bool'],
    ['show_stock_qty', '1', 'shop', 'Afficher la quantité en stock', 'bool'],
    ['free_shipping_threshold', '100000', 'shop', 'Livraison gratuite à partir de (0 = désactivé)', 'number'],
    ['pickup_enabled', '1', 'shop', 'Autoriser le retrait en magasin', 'bool'],
    ['pickup_address', 'Showroom AFAM, Dakar', 'shop', 'Adresse de retrait', 'text'],
    ['tax_rate', '18', 'shop', 'Taux de TVA (%)', 'number'],
    ['prices_include_tax', '1', 'shop', 'Prix affichés TTC', 'bool'],
    ['order_prefix', 'AF', 'shop', 'Préfixe des numéros de commande', 'text'],
    ['invoice_prefix', 'FAC', 'shop', 'Préfixe des numéros de facture', 'text'],
    ['invoice_footer', '{company_name} — {address} — {phone} — {email}. Merci pour votre confiance.', 'shop', 'Mentions en bas de facture', 'textarea'],
    ['invoice_logo', '', 'shop', 'Logo des factures (JPEG/PNG)', 'image'],
    ['attach_invoice', '1', 'shop', 'Joindre la facture PDF à l\'email de paiement', 'bool'],
    ['reviews_enabled', '1', 'shop', 'Activer les avis clients', 'bool'],
    ['default_warranty', '1 an', 'shop', 'Garantie par défaut', 'text'],
    ['default_delivery_delay', '24 à 72 h à Dakar', 'shop', 'Délai de livraison par défaut', 'text'],

    // ---- Paiement
    ['payment_stripe_enabled', '1', 'payment', 'Activer Stripe (carte bancaire)', 'bool'],
    ['payment_paydunya_enabled', '1', 'payment', 'Activer PayDunya (Wave, Orange Money...)', 'bool'],
    ['paydunya_channels', '', 'payment', 'Canaux PayDunya (ex : wave-senegal,orange-money-senegal,card) — vide = tous', 'text'],
    ['payment_cod_enabled', '1', 'payment', 'Activer le paiement à la livraison', 'bool'],
    ['payment_transfer_enabled', '0', 'payment', 'Activer le virement bancaire', 'bool'],
    ['transfer_instructions', 'Banque : …\nIBAN : …\nMerci d\'indiquer votre numéro de commande en référence.', 'payment', 'Instructions de virement', 'textarea'],

    // ---- SEO
    ['meta_title', 'AFAMSHOP — Impression, consommables et informatique au Sénégal', 'seo', 'Titre SEO par défaut', 'text'],
    ['meta_description', 'Imprimantes, copieurs Sharp, toners, cartouches, informatique et fournitures de bureau au Sénégal. Vente, location et maintenance.', 'seo', 'Description SEO par défaut', 'textarea'],
    ['google_analytics_id', '', 'seo', 'Google Analytics (ID G-XXXX)', 'text'],
    ['recaptcha_enabled', '1', 'seo', 'Activer reCAPTCHA sur les formulaires (clés dans .env)', 'bool'],

    // ---- Emails (modèles modifiables — variables entre accolades)
    ['email_footer', '{company_name} — {address} — {phone}', 'emails', 'Pied des emails', 'textarea'],
    ['email_welcome_subject', 'Bienvenue sur {site_name}', 'emails', 'Création de compte : sujet', 'text'],
    ['email_welcome_body', "Bonjour {first_name},\n\nVotre compte {site_name} a bien été créé. Vous pouvez dès maintenant suivre vos commandes, gérer vos adresses et vos favoris.\n\nÀ bientôt !", 'emails', 'Création de compte : contenu', 'textarea'],
    ['email_order_confirmation_subject', 'Confirmation de votre commande {order_number}', 'emails', 'Confirmation de commande : sujet', 'text'],
    ['email_order_confirmation_body', "Bonjour {first_name},\n\nNous avons bien reçu votre commande {order_number} d'un montant de {total}.\nMode de paiement : {payment_method}.", 'emails', 'Confirmation de commande : contenu', 'textarea'],
    ['email_payment_confirmation_subject', 'Paiement confirmé — commande {order_number}', 'emails', 'Confirmation de paiement : sujet', 'text'],
    ['email_payment_confirmation_body', "Bonjour {first_name},\n\nNous confirmons la réception de votre paiement de {total} pour la commande {order_number}. Votre facture est disponible dans votre espace client.", 'emails', 'Confirmation de paiement : contenu', 'textarea'],
    ['email_order_preparing_subject', 'Votre commande {order_number} est en préparation', 'emails', 'Préparation : sujet', 'text'],
    ['email_order_preparing_body', "Bonjour {first_name},\n\nVotre commande {order_number} est en cours de préparation.\n{comment}", 'emails', 'Préparation : contenu', 'textarea'],
    ['email_order_shipped_subject', 'Votre commande {order_number} a été expédiée', 'emails', 'Expédition : sujet', 'text'],
    ['email_order_shipped_body', "Bonjour {first_name},\n\nBonne nouvelle : votre commande {order_number} est en route !\n{comment}", 'emails', 'Expédition : contenu', 'textarea'],
    ['email_order_delivered_subject', 'Votre commande {order_number} a été livrée', 'emails', 'Livraison : sujet', 'text'],
    ['email_order_delivered_body', "Bonjour {first_name},\n\nVotre commande {order_number} a été livrée. Merci pour votre confiance !", 'emails', 'Livraison : contenu', 'textarea'],
    ['email_order_cancelled_subject', 'Annulation de votre commande {order_number}', 'emails', 'Annulation : sujet', 'text'],
    ['email_order_cancelled_body', "Bonjour {first_name},\n\nVotre commande {order_number} a été annulée.\n{comment}", 'emails', 'Annulation : contenu', 'textarea'],
    ['email_order_refunded_subject', 'Remboursement de votre commande {order_number}', 'emails', 'Remboursement : sujet', 'text'],
    ['email_order_refunded_body', "Bonjour {first_name},\n\nLe remboursement de votre commande {order_number} a été effectué.\n{comment}", 'emails', 'Remboursement : contenu', 'textarea'],
    ['email_password_reset_subject', 'Réinitialisation de votre mot de passe {site_name}', 'emails', 'Mot de passe oublié : sujet', 'text'],
    ['email_password_reset_body', "Bonjour {first_name},\n\nPour choisir un nouveau mot de passe, cliquez sur le lien ci-dessous (valable 1 heure) :\n{link}\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.", 'emails', 'Mot de passe oublié : contenu', 'textarea'],
    ['email_request_ack_subject', 'Nous avons bien reçu votre demande', 'emails', 'Accusé de réception des demandes : sujet', 'text'],
    ['email_request_ack_body', "Bonjour {name},\n\nMerci pour votre demande ({type}). Notre équipe vous recontacte dans les plus brefs délais.", 'emails', 'Accusé de réception des demandes : contenu', 'textarea'],
];

$currencies = [
    ['XOF', 'Franc CFA', 'FCFA', 1, 0, 1, 1, 1],
    ['EUR', 'Euro', '€', 0.0015244902, 2, 1, 0, 1], // parité fixe : 1 € = 655,957 FCFA
    ['USD', 'Dollar US', '$', 0.00165, 2, 0, 0, 0],
];

// [nom, slug, icône, image, sous-catégories]
$categories = [
    ['Impression', 'impression', 'printer', 'assets/img/contenus/cat-impression.jpg', ['Imprimantes laser', 'Imprimantes jet d\'encre', 'Multifonctions', 'Copieurs', 'Imprimantes professionnelles', 'Imprimantes couleur', 'Imprimantes monochromes', 'Traceurs']],
    ['Consommables', 'consommables', 'drop', 'assets/img/contenus/cat-consommables.jpg', ['Toners', 'Cartouches d\'encre', 'Tambours', 'Kits de maintenance', 'Rubans', 'Encres', 'Consommables multifonctions']],
    ['Informatique', 'informatique', 'laptop', 'assets/img/contenus/cat-informatique.jpg', ['Ordinateurs portables', 'Ordinateurs de bureau', 'Écrans', 'Claviers et souris', 'Onduleurs', 'Stockage', 'Clés USB', 'Réseau', 'Accessoires informatiques']],
    ['Papeterie', 'papeterie', 'pen', 'assets/img/contenus/cat-papeterie.jpg', ['Papier et ramettes', 'Enveloppes', 'Cahiers', 'Classeurs', 'Stylos et crayons', 'Agrafes et trombones', 'Fournitures de bureau']],
];

$brands = [
    ['Sharp', 1], ['Canon', 1], ['HP', 1], ['Ricoh', 1], ['Epson', 0], ['Brother', 0], ['Dell', 0], ['Lenovo', 0], ['APC', 0], ['Logitech', 0], ['Kingston', 0], ['Navigator', 0], ['Bic', 0],
];

$printerModels = [
    'Sharp' => ['MX-2651' => 'MX', 'MX-3051' => 'MX', 'MX-3551' => 'MX', 'MX-4051' => 'MX', 'MX-5051' => 'MX', 'MX-M3051' => 'MX-M', 'MX-M3551' => 'MX-M', 'BP-30M28' => 'BP', 'BP-50C26' => 'BP', 'AR-6020' => 'AR'],
    'Canon' => ['imageRUNNER 2425' => 'imageRUNNER', 'imageRUNNER 2630i' => 'imageRUNNER', 'iR-ADV C3530' => 'imageRUNNER ADVANCE', 'iR-ADV C3525' => 'imageRUNNER ADVANCE', 'i-SENSYS MF445dw' => 'i-SENSYS', 'i-SENSYS LBP223dw' => 'i-SENSYS', 'PIXMA G3410' => 'PIXMA'],
    'HP' => ['LaserJet Pro M404dn' => 'LaserJet Pro', 'LaserJet Pro MFP M428fdw' => 'LaserJet Pro', 'LaserJet M211dw' => 'LaserJet', 'Color LaserJet Pro M454dw' => 'Color LaserJet', 'OfficeJet Pro 9010' => 'OfficeJet', 'DeskJet 2720' => 'DeskJet'],
    'Ricoh' => ['IM 2702' => 'IM', 'IM C3000' => 'IM C', 'MP 2014' => 'MP', 'SP 230DNw' => 'SP'],
];

// sku, ref, nom, marque, catégorie, type, couleur, prix, promo, stock, court, compat[], featured
$products = [
    ['AF-SH-MX61GTBA', 'MX-61GTBA', 'Toner Sharp MX-61GTBA noir', 'Sharp', 'toners', 'Toner', 'Noir', 38000, null, 25, 'Toner d\'origine Sharp noir, environ 40 000 pages.', ['MX-2651', 'MX-3051', 'MX-3551', 'MX-4051', 'MX-5051'], 1],
    ['AF-SH-MX61GTCA', 'MX-61GTCA', 'Toner Sharp MX-61GTCA cyan', 'Sharp', 'toners', 'Toner', 'Cyan', 62000, 55000, 12, 'Toner d\'origine Sharp cyan, environ 24 000 pages.', ['MX-2651', 'MX-3051', 'MX-3551', 'MX-4051', 'MX-5051'], 0],
    ['AF-SH-MX61GTMA', 'MX-61GTMA', 'Toner Sharp MX-61GTMA magenta', 'Sharp', 'toners', 'Toner', 'Magenta', 62000, null, 9, 'Toner d\'origine Sharp magenta, environ 24 000 pages.', ['MX-2651', 'MX-3051', 'MX-3551', 'MX-4051', 'MX-5051'], 0],
    ['AF-SH-MX61GTYA', 'MX-61GTYA', 'Toner Sharp MX-61GTYA jaune', 'Sharp', 'toners', 'Toner', 'Jaune', 62000, null, 3, 'Toner d\'origine Sharp jaune, environ 24 000 pages.', ['MX-2651', 'MX-3051', 'MX-3551', 'MX-4051', 'MX-5051'], 0],
    ['AF-SH-MX315GT', 'MX-315GT', 'Toner Sharp MX-315GT noir', 'Sharp', 'toners', 'Toner', 'Noir', 45000, null, 18, 'Toner Sharp haute capacité pour multifonctions monochromes MX-M.', ['MX-M3051', 'MX-M3551'], 1],
    ['AF-SH-MX61DR', 'MX-61GRSA', 'Tambour Sharp MX-61GRSA', 'Sharp', 'tambours', 'Tambour', 'Noir', 95000, null, 4, 'Kit tambour d\'origine Sharp.', ['MX-2651', 'MX-3051', 'MX-3551', 'MX-4051', 'MX-5051'], 0],
    ['AF-SH-BPGT20BA', 'BP-GT20BA', 'Toner Sharp BP-GT20BA noir', 'Sharp', 'toners', 'Toner', 'Noir', 41000, null, 0, 'Toner Sharp pour la gamme BP.', ['BP-50C26'], 0],
    ['AF-SH-AR020', 'AR-020LT', 'Toner Sharp AR-020LT noir', 'Sharp', 'toners', 'Toner', 'Noir', 18500, 16500, 30, 'Toner pour copieurs Sharp AR-6020.', ['AR-6020'], 0],
    ['AF-CA-CEXV33', 'C-EXV33', 'Toner Canon C-EXV33 noir', 'Canon', 'toners', 'Toner', 'Noir', 29000, null, 14, 'Toner d\'origine Canon, environ 14 600 pages.', ['imageRUNNER 2425'], 0],
    ['AF-CA-CEXV49K', 'C-EXV49', 'Toner Canon C-EXV49 noir', 'Canon', 'toners', 'Toner', 'Noir', 52000, null, 7, 'Toner d\'origine Canon pour imageRUNNER ADVANCE couleur.', ['iR-ADV C3530', 'iR-ADV C3525'], 0],
    ['AF-CA-CRG057', 'CRG-057', 'Cartouche toner Canon 057 noir', 'Canon', 'toners', 'Toner', 'Noir', 64000, 58000, 10, 'Cartouche Canon 057, 3 100 pages.', ['i-SENSYS MF445dw', 'i-SENSYS LBP223dw'], 1],
    ['AF-CA-GI41BK', 'GI-41 BK', 'Bouteille d\'encre Canon GI-41 noir', 'Canon', 'encres', 'Encre', 'Noir', 7500, null, 40, 'Encre d\'origine pour imprimantes PIXMA G.', ['PIXMA G3410'], 0],
    ['AF-HP-59A', 'CF259A', 'Toner HP 59A noir', 'HP', 'toners', 'Toner', 'Noir', 98000, null, 11, 'Toner HP LaserJet d\'origine, 3 000 pages.', ['LaserJet Pro M404dn', 'LaserJet Pro MFP M428fdw'], 1],
    ['AF-HP-135A', 'W1350A', 'Toner HP 135A noir', 'HP', 'toners', 'Toner', 'Noir', 49000, null, 2, 'Toner HP d\'origine, 1 100 pages.', ['LaserJet M211dw'], 0],
    ['AF-HP-415A', 'W2030A', 'Toner HP 415A noir', 'HP', 'toners', 'Toner', 'Noir', 89000, null, 6, 'Toner HP Color LaserJet, 2 400 pages.', ['Color LaserJet Pro M454dw'], 0],
    ['AF-HP-963XL', '3JA30AE', 'Cartouche HP 963XL noir', 'HP', 'cartouches-d-encre', 'Cartouche', 'Noir', 39000, 35000, 15, 'Cartouche d\'encre HP haute capacité.', ['OfficeJet Pro 9010'], 0],
    ['AF-HP-305', '3YM61AE', 'Cartouche HP 305 noir', 'HP', 'cartouches-d-encre', 'Cartouche', 'Noir', 9500, null, 50, 'Cartouche d\'encre HP 305.', ['DeskJet 2720'], 0],
    ['AF-RI-842311', '842311', 'Toner Ricoh IM 2702 noir', 'Ricoh', 'toners', 'Toner', 'Noir', 33000, null, 8, 'Toner d\'origine Ricoh.', ['IM 2702'], 0],
    ['AF-RI-SP230', '408294', 'Toner Ricoh SP 230H noir', 'Ricoh', 'toners', 'Toner', 'Noir', 42000, null, 0, 'Toner Ricoh haute capacité.', ['SP 230DNw'], 0],
    ['AF-SH-MX3061', 'MX-3061', 'Multifonction Sharp MX-3061 couleur A3', 'Sharp', 'multifonctions', 'Multifonction', 'Couleur', 3450000, null, 3, 'Multifonction couleur A3 30 ppm, écran tactile 10,1", recto-verso, scan réseau.', [], 1],
    ['AF-SH-MXM3071', 'MX-M3071', 'Multifonction Sharp MX-M3071 monochrome A3', 'Sharp', 'multifonctions', 'Multifonction', 'Monochrome', 2450000, 2290000, 2, 'Multifonction monochrome A3 30 ppm pour bureaux exigeants.', [], 1],
    ['AF-SH-BP30M28', 'BP-30M28', 'Copieur Sharp BP-30M28 monochrome', 'Sharp', 'copieurs', 'Copieur', 'Monochrome', 1850000, null, 4, 'Copieur multifonction A3 28 ppm.', [], 0],
    ['AF-CA-MF445', 'MF445dw', 'Imprimante Canon i-SENSYS MF445dw', 'Canon', 'imprimantes-laser', 'Laser', 'Monochrome', 365000, null, 6, 'Multifonction laser 4-en-1 Wi-Fi, 38 ppm.', [], 0],
    ['AF-CA-G3410', 'G3410', 'Imprimante Canon PIXMA G3410', 'Canon', 'imprimantes-jet-d-encre', 'Jet d\'encre', 'Couleur', 125000, 115000, 9, 'Imprimante à réservoirs rechargeables Wi-Fi.', [], 0],
    ['AF-HP-M404', 'W1A53A', 'Imprimante HP LaserJet Pro M404dn', 'HP', 'imprimantes-laser', 'Laser', 'Monochrome', 295000, null, 5, 'Imprimante laser monochrome réseau, recto-verso.', [], 1],
    ['AF-HP-T230', '5HB07A', 'Traceur HP DesignJet T230 24"', 'HP', 'traceurs', 'Traceur', 'Couleur', 1150000, null, 1, 'Traceur grand format 24 pouces Wi-Fi.', [], 0],
    ['AF-DE-LAT5440', 'LAT5440-I5', 'Ordinateur portable Dell Latitude 5440 i5 16 Go 512 Go', 'Dell', 'ordinateurs-portables', 'Portable', null, 785000, null, 7, 'Portable professionnel 14", Intel Core i5, 16 Go RAM, SSD 512 Go.', [], 1],
    ['AF-HP-PB450', 'PB450G10', 'Ordinateur portable HP ProBook 450 G10', 'HP', 'ordinateurs-portables', 'Portable', null, 695000, 649000, 5, 'Portable 15,6", Intel Core i5, 8 Go RAM, SSD 512 Go.', [], 0],
    ['AF-LE-M70Q', 'M70Q-G4', 'Ordinateur de bureau Lenovo ThinkCentre M70q', 'Lenovo', 'ordinateurs-de-bureau', 'Bureau', null, 545000, null, 4, 'Mini PC professionnel Intel Core i5.', [], 0],
    ['AF-DE-P2423', 'P2423', 'Écran Dell P2423 24"', 'Dell', 'ecrans', 'Écran', null, 165000, null, 12, 'Écran IPS 24" WUXGA.', [], 0],
    ['AF-LO-MK270', 'MK270', 'Clavier + souris sans fil Logitech MK270', 'Logitech', 'claviers-et-souris', 'Clavier/Souris', null, 22000, null, 35, 'Ensemble sans fil AZERTY.', [], 0],
    ['AF-AP-BX1200', 'BX1200MI', 'Onduleur APC Back-UPS 1200 VA', 'APC', 'onduleurs', 'Onduleur', null, 98000, null, 8, 'Onduleur line-interactive 1200 VA, 6 prises.', [], 0],
    ['AF-KI-DT64', 'DTX/64GB', 'Clé USB Kingston DataTraveler 64 Go', 'Kingston', 'cles-usb', 'Clé USB', null, 6500, 5500, 80, 'Clé USB 3.2 64 Go.', [], 0],
    ['AF-KI-A400', 'SA400S37/480G', 'SSD Kingston A400 480 Go', 'Kingston', 'stockage', 'SSD', null, 32000, null, 20, 'SSD SATA 2,5" 480 Go.', [], 0],
    ['AF-NA-A480', 'NAV-A4-80', 'Ramette papier Navigator A4 80 g (500 feuilles)', 'Navigator', 'papier-et-ramettes', 'Papier', 'Blanc', 4500, null, 300, 'Papier premium A4 80 g/m².', [], 1],
    ['AF-NA-A4C5', 'NAV-A4-80-C5', 'Carton 5 ramettes Navigator A4 80 g', 'Navigator', 'papier-et-ramettes', 'Papier', 'Blanc', 21000, 19500, 60, 'Carton de 5 ramettes A4.', [], 0],
    ['AF-BI-CRIST50', 'BIC-CR-50', 'Stylos Bic Cristal bleu (boîte de 50)', 'Bic', 'stylos-et-crayons', 'Stylo', 'Bleu', 7500, null, 45, 'Stylos bille pointe moyenne.', [], 0],
    ['AF-FB-ENV-C4', 'ENV-C4-250', 'Enveloppes C4 kraft (boîte de 250)', null, 'enveloppes', 'Enveloppe', null, 18000, null, 15, 'Enveloppes kraft 229 x 324 mm.', [], 0],
    ['AF-FB-CLAS80', 'CLAS-A4-80', 'Classeur à levier A4 dos 80 mm', null, 'classeurs', 'Classeur', null, 2200, null, 120, 'Classeur carton à levier.', [], 0],
    ['AF-FB-AGR266', 'AGR-26-6', 'Agrafes 26/6 (boîte de 1000)', null, 'agrafes-et-trombones', 'Agrafes', null, 600, null, 200, 'Agrafes galvanisées standard.', [], 0],
];

$zones = [
    ['Dakar Plateau', 2000, null, '24 h', 1],
    ['Dakar (autres communes)', 3000, null, '24 à 48 h', 2],
    ['Pikine / Guédiawaye / Rufisque', 4000, null, '48 h', 3],
    ['Thiès / Mbour', 6000, 250000, '48 à 72 h', 4],
    ['Autres régions', 10000, 500000, '3 à 5 jours', 5],
];

$lorem = '<p>Contenu à personnaliser depuis le back-office (Contenus &gt; Pages).</p>';
$pages = [
    ['a-propos', 'À propos', '<h2>AFAM, votre partenaire impression au Sénégal</h2><p>AFAM est une entreprise sénégalaise spécialisée dans les solutions d\'impression professionnelles. Représentant exclusif de Sharp au Sénégal, AFAM propose des offres de location et de vente clé en main alliant performance, économies et respect de l\'environnement.</p><p>L\'entreprise propose également des services de maintenance et des solutions adaptées permettant de réduire les coûts d\'impression et d\'améliorer l\'efficacité.</p>', 'company', 1],
    ['afam-sharp', 'AFAM × Sharp', '<h2>Représentant exclusif Sharp au Sénégal</h2><p>Découvrez la gamme complète de multifonctions et solutions Sharp, avec l\'expertise locale d\'AFAM : installation, formation, consommables d\'origine et maintenance.</p>', 'company', 2],
    ['solutions-professionnelles', 'Solutions professionnelles', '<h2>Des solutions pour les entreprises et administrations</h2><p>Audit de parc, gestion des impressions, tarifs adaptés aux volumes, contrats de service : AFAM accompagne les organisations dans l\'optimisation de leurs coûts d\'impression.</p>', 'company', 3],
    ['faq', 'FAQ', '<h3>Comment trouver le consommable de mon imprimante ?</h3><p>Utilisez la recherche par imprimante : choisissez la marque puis le modèle.</p><h3>Quels sont les moyens de paiement ?</h3><p>Carte bancaire (Stripe), Wave, Orange Money (PayDunya) et paiement à la livraison selon disponibilité.</p><h3>Livrez-vous hors de Dakar ?</h3><p>Oui, dans toutes les régions du Sénégal. Les frais dépendent de la zone de livraison.</p>', 'help', 1],
    ['livraison-retours', 'Politique de livraison et retours', $lorem, 'help', 2],
    ['garantie', 'Garantie', $lorem, 'help', 3],
    ['cgv', 'Conditions générales de vente', file_get_contents(__DIR__ . '/contenus/cgv.html'), 'legal', 1],
    ['mentions-legales', 'Mentions légales', file_get_contents(__DIR__ . '/contenus/mentions-legales.html'), 'legal', 2],
    ['confidentialite', 'Politique de confidentialité', file_get_contents(__DIR__ . '/contenus/confidentialite.html'), 'legal', 3],
    ['cookies', 'Politique de cookies', '<p>Ce site utilise uniquement des cookies nécessaires à son fonctionnement (session, panier, sécurité) ainsi que, le cas échéant, des cookies de mesure d\'audience.</p>', 'legal', 4],
];

$services = [
    ['location', 'Location d\'imprimantes', 'key', 'Des multifonctions en location clé en main, maintenance et consommables inclus.', '<h2>La location clé en main</h2><p>Équipez vos bureaux avec des multifonctions Sharp récentes sans investissement initial.</p><h3>Avantages</h3><ul><li>Aucun investissement : un loyer mensuel maîtrisé</li><li>Maintenance et consommables inclus</li><li>Matériel récent et évolutif</li><li>Interventions rapides de techniciens certifiés</li></ul><h3>Maintenance associée</h3><p>Chaque contrat comprend la maintenance préventive et corrective ainsi que le remplacement des pièces d\'usure.</p>', 'rental', 1, 'assets/img/contenus/service-location.jpg'],
    ['maintenance', 'Maintenance', 'wrench', 'Maintenance préventive et corrective, remplacement de pièces et assistance.', '<h2>Maintenance de votre parc d\'impression</h2><ul><li><strong>Maintenance préventive</strong> : visites planifiées pour éviter les pannes</li><li><strong>Maintenance corrective</strong> : diagnostic et réparation</li><li><strong>Intervention</strong> sur site à Dakar et en régions</li><li><strong>Remplacement de pièces</strong> d\'origine</li><li><strong>Assistance</strong> téléphonique et à distance</li></ul>', 'maintenance', 2, 'assets/img/contenus/service-maintenance.jpg'],
    ['solutions-impression', 'Solutions d\'impression', 'briefcase', 'Audit, gestion de parc et réduction des coûts d\'impression.', '<h2>Optimisez vos impressions</h2><p>Nous analysons vos volumes et usages pour proposer la solution la plus économique et écologique.</p>', 'quote', 3, 'assets/img/contenus/service-solutions.jpg'],
    ['vente-materiel', 'Vente de matériel', 'printer', 'Imprimantes, copieurs, informatique et consommables d\'origine.', '<h2>Vente de matériel professionnel</h2><p>Un large choix de matériel des plus grandes marques avec installation et formation.</p>', 'quote', 4, 'assets/img/contenus/service-vente.jpg'],
];

$banners = [
    ['home_promo', 'Jusqu\'à -15 % sur les toners Sharp', 'Consommables d\'origine, livraison rapide', 'J\'en profite', 'promotions', 1, 'assets/img/contenus/promo-toners.jpg'],
    ['home_promo', 'Location de multifonctions', 'Maintenance et consommables inclus', 'Demander une étude', 'service/location', 2, 'assets/img/contenus/promo-location.jpg'],
    ['home_hero', 'Multifonctions Sharp', 'Performance, économies et respect de l\'environnement', 'Découvrir', 'marque/sharp', 1, 'assets/img/contenus/slide-sharp.jpg'],
    ['home_hero', 'Informatique professionnelle', 'Ordinateurs, écrans, onduleurs et accessoires', 'Voir le catalogue', 'categorie/informatique', 2, 'assets/img/contenus/slide-informatique.jpg'],
];
