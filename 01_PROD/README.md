# AFAMSHOP — plateforme e-commerce AFAM (Sénégal)

Site e-commerce en **PHP 8.1+ / MySQL (ou MariaDB)**, HTML5, CSS3 et JavaScript natif. Aucun framework, CMS ou Composer requis.
Ce dossier `01_PROD/` contient toute l'application et constitue la racine web.

## Arborescence

```
01_PROD/
├── index.php            Contrôleur frontal du site public (URLs propres)
├── .htaccess            Réécriture d'URL + blocage des dossiers sensibles
├── .env                 Secrets et configuration technique (NON versionné)
├── admin/               Back-office (une page par module)
├── api/                 Endpoints AJAX + webhooks Stripe / IPN PayDunya
├── assets/              CSS, JS, images du site (assets/admin pour le back-office)
├── config/              env.php (loadEnvFile) et config.php (constantes)
├── includes/            Logique métier et accès aux données
│   ├── DB.php           Couche PDO (requêtes préparées)
│   ├── functions.php    Utilitaires (sécurité, formatage, uploads, CSRF…)
│   ├── classes/         Catalog, Cart, Orders, Stock, Auth, AdminAuth, Mailer,
│   │                    StripeGateway, PayDunyaGateway, Invoice, Pdf, Importer, Requests, Recaptcha
│   └── layout/          Gabarits communs (en-tête, pied de page, carte produit…)
├── lang/                Textes d'interface (fr.php ; ajouter en.php pour l'anglais)
├── pages/               Pages du site public (présentation)
├── sql/                 schema.sql, seed.php, install.php, env.sample
├── uploads/             Fichiers envoyés (uploads/private non accessible depuis le web)
└── utilitaires/         PHPMailer et ReCaptcha (non versionnés, à déposer manuellement)
```

## Installation

1. **Hébergement** : Apache avec `mod_rewrite`, PHP ≥ 8.1 (extensions `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `zip`, `gd`, `intl` recommandée), MySQL ≥ 5.7 ou MariaDB ≥ 10.3. Faire pointer le domaine vers `01_PROD/`.
2. **Utilitaires** : copier **PHPMailer** dans `utilitaires/PHPMailer/` (fichiers `src/PHPMailer.php`, `src/SMTP.php`, `src/Exception.php`) et la librairie **ReCaptcha** dans `utilitaires/ReCaptcha/` (fichier `src/autoload.php`). Sans la librairie ReCaptcha, la vérification se fait directement via l'API Google.
3. **Configuration** : copier `sql/env.sample` en `01_PROD/.env` et renseigner base de données, SMTP, Stripe, PayDunya et reCAPTCHA.
4. **Base de données** :
   - en ligne de commande : `php sql/install.php` (schéma, paramètres, catégories, marques, catalogue de démonstration) ; options `--no-demo` (sans produits de démonstration) et `--admin=email --password=MotDePasse1` ;
   - sans accès SSH : importer `sql/afamshop.sql` dans phpMyAdmin.
5. **Premier administrateur** : ouvrir `https://votre-domaine/admin/setup.php` (fonctionne uniquement tant qu'aucun administrateur n'existe).
6. **Droits d'écriture** : `uploads/` doit être accessible en écriture par PHP.
7. **HTTPS** : installer un certificat, laisser `FORCE_HTTPS=true` dans `.env` (redirection automatique + cookies sécurisés).
8. **Mise à jour des paramètres** après une évolution : `php sql/install.php --settings-only` ajoute les nouveaux paramètres sans toucher aux valeurs existantes.

Développement local : `php -S localhost:8080 -t 01_PROD 01_PROD/index.php` avec `APP_ENV=local`, `APP_DEBUG=true`, `FORCE_HTTPS=false`, `MAIL_DRIVER=log`.

## Paiements

### Stripe (carte bancaire)
- Paiement via **Stripe Checkout** ; la référence (session, payment_intent) et la réponse sont enregistrées dans la table `payments`.
- Statuts : `pending`, `paid`, `failed`, `cancelled`, `refunded`.
- Webhook à déclarer dans le tableau de bord Stripe : `https://votre-domaine/api/stripe-webhook.php` avec les événements `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `checkout.session.expired`, `charge.refunded`. Copier le secret de signature dans `STRIPE_WEBHOOK_SECRET`.
- Le XOF est une devise sans décimale chez Stripe : les montants sont envoyés tels quels.

### PayDunya (Wave, Orange Money…)
- Création d'une facture PayDunya puis redirection du client. L'URL de notification IPN (`/api/paydunya-ipn.php`) est transmise automatiquement.
- Chaque notification est contrôlée (hash SHA-512 de la clé principale) puis **revérifiée auprès de l'API PayDunya** (`confirm`) avant validation de la commande, et le montant payé est comparé au total.
- Les canaux proposés (Wave, Orange Money, carte…) se règlent dans *Admin › Contenus › Paiement*.

Le paiement à la livraison et le virement bancaire peuvent être activés en complément.

## Règles métier principales

- **Stock** : décrémenté une seule fois par commande, au paiement confirmé ou au passage « en préparation » (paramètre `stock_decrement_on` = `paid`), ou dès la commande (`order`). Remis en stock en cas d'annulation / remboursement. Chaque mouvement est historisé (`stock_movements`). Alerte email au franchissement du seuil de stock faible.
- **Statuts de commande** : reçue, paiement confirmé, en préparation, expédiée, livrée, annulée, remboursée — un email est envoyé au client à chaque étape (modèles modifiables dans l'admin).
- **Factures PDF** : numérotation séquentielle (`FAC-AAAA-00001`), attribuée au paiement ou à la livraison ; TVA configurable (prix TTC ou HT).
- **Prix** : prix promo daté sur le produit + promotions par produit / catégorie / marque / tout le catalogue + coupons (montant ou %, minimum d'achat, limites d'utilisation globales et par client).
- **Livraison** : zones avec frais et délai, seuil de gratuité global ou par zone, retrait en magasin optionnel.

## Tout est paramétrable

Dans *Admin › Contenus* : nom de la plateforme, domaine, coordonnées, logo, couleurs, textes et images de la page d'accueil (bannière, vidéo, blocs pro / location / maintenance / Sharp…), libellés du menu, pied de page, réseaux sociaux, WhatsApp, TVA, préfixes de commande et de facture, moyens de paiement, SEO, modèles d'emails. Les pages institutionnelles, services, bannières, catégories, marques et produits sont gérés dans leurs modules respectifs. Les variables `{site_name}`, `{company_name}`, `{phone}`, `{email}`, `{address}`, `{domain}`, `{year}` sont remplacées automatiquement dans les contenus.

## Sécurité

Mots de passe `password_hash()`, requêtes préparées PDO, jeton CSRF sur tous les formulaires et appels AJAX, échappement systématique des sorties (XSS) et nettoyage du HTML administrable, validation serveur, uploads contrôlés (type MIME réel, taille, nom aléatoire, exécution PHP interdite dans `uploads/`), limitation des tentatives de connexion, sessions sécurisées (HttpOnly, SameSite, Secure en HTTPS, régénération d'identifiant), permissions vérifiées côté serveur par rôle (Super Admin, Gestionnaire, Commercial, Éditeur), journal des actions administratives, secrets uniquement dans `.env`, en-têtes de sécurité HTTP.

**Sauvegardes** : planifier une sauvegarde quotidienne de la base (`mysqldump`) et du dossier `uploads/` via l'hébergeur ou une tâche cron.

## Images de contenu

Le dossier `assets/img/contenus/` contient des illustrations prêtes à l'emploi (logo, bannière d'accueil, blocs pro / location / maintenance / Sharp, catégories, services, bannières promotionnelles, diaporama). Elles sont utilisées par défaut et se remplacent depuis l'admin (*Contenus du site*, *Catégories*, *Services*, *Bannières*) en envoyant votre propre fichier. Le script `sql/generer-images.js` (Node + Playwright) permet de les régénérer, par exemple après un changement de couleurs.

Formats recommandés : bannière d'accueil 1920×860, blocs d'accueil 1200×750, catégories 800×500, services 800×450, bannières promo 1000×420, diaporama 1280×360, logo PNG transparent 520×120.

## Mise à jour d'une installation existante

Importer `sql/mise-a-jour-2026-10.sql` dans phpMyAdmin : active l'affichage en euros et applique les images par défaut là où aucune image n'est définie (aucune donnée existante n'est écrasée).

## Évolutivité

- **Devises** : le visiteur choisit FCFA ou € dans la barre du haut (choix mémorisé 1 an). L'euro est converti à la parité fixe 1 € = 655,957 FCFA ; le paiement, les factures, les emails et le back-office restent en FCFA, et une mention l'indique au panier et à la commande. D'autres devises (USD…) s'activent dans *Admin › Livraison & devises*.
- **Langues** : tous les textes d'interface passent par `__()` ; ajouter `lang/en.php` pour l'anglais.
- **API REST / application mobile** : la logique métier est isolée dans `includes/classes/` et réutilisable par de nouveaux endpoints dans `api/`.
