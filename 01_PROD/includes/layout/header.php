<?php
/**
 * En-tête commun du site public.
 * Variables optionnelles : $pageTitle, $metaDescription, $bodyClass, $noIndex, $ogImage
 */
$siteName = setting('site_name', 'AFAMSHOP');
$fullTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : setting('meta_title', $siteName);
$metaDescription = $metaDescription ?? setting('meta_description');
$tree = Catalog::categoryTree();
$menuMap = [
    'impression' => setting('menu_label_printing', 'Impression'),
    'consommables' => setting('menu_label_consumables', 'Consommables'),
    'informatique' => setting('menu_label_it', 'Informatique'),
    'papeterie' => setting('menu_label_stationery', 'Papeterie'),
];
$menuCats = [];
foreach ($tree as $c) {
    if ($c['show_in_menu']) {
        $c['label'] = $menuMap[$c['slug']] ?? $c['name'];
        $menuCats[] = $c;
    }
}
$menuBrands = array_slice(Catalog::brands(), 0, 8);
$cartCount = Cart::count();
$customer = Auth::user();
$favicon = setting('favicon');
?><!doctype html>
<html lang="<?= e(current_lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e(truncate($metaDescription, 160)) ?>">
<?php if (!empty($noIndex)): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e(truncate($metaDescription, 160)) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<link rel="icon" href="<?= e($favicon ? media_url($favicon) : asset('img/favicon.svg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<style>:root{--primary:<?= e(setting('color_primary', '#0b4f8a')) ?>;--secondary:<?= e(setting('color_secondary', '#0f2a44')) ?>;--accent:<?= e(setting('color_accent', '#e8730c')) ?>}<?= strip_tags(setting('custom_css')) ?></style>
<?php if ($ga = setting('google_analytics_id')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e($ga) ?>');</script>
<?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>" data-base="<?= e(APP_URL) ?>">
<a class="skip-link" href="#main">Aller au contenu</a>

<?php $showTopText = setting_bool('topbar_enabled', true) && setting('topbar_text'); if ($showTopText || count(currencies()) > 1): ?>
<div class="topbar">
  <div class="container topbar-inner">
    <span><?= $showTopText ? e(render_vars(setting('topbar_text'))) : '' ?></span>
    <span class="topbar-right">
      <?php if (setting('contact_phone')): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', setting('contact_phone'))) ?>"><?= icon('phone', 'icon icon-sm') ?> <?= e(setting('contact_phone')) ?></a><?php endif; ?>
      <?php if (count(currencies()) > 1): ?>
      <nav class="currency-switch" aria-label="<?= e(__('currency')) ?>">
        <?php foreach (currencies() as $code => $c): ?>
          <a href="<?= e(currency_switch_url($code)) ?>" rel="nofollow" class="<?= $code === display_currency() ? 'active' : '' ?>" <?= $code === display_currency() ? 'aria-current="true"' : '' ?> title="<?= e($c['name']) ?>"><?= e($c['symbol']) ?></a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </span>
  </div>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="container header-main">
    <button class="icon-btn menu-toggle" aria-label="Menu" aria-expanded="false" data-menu-toggle><?= icon('menu') ?></button>
    <a class="logo" href="<?= e(url()) ?>" aria-label="<?= e($siteName) ?>">
      <?php if (setting('logo')): ?>
        <img src="<?= e(media_url(setting('logo'))) ?>" alt="<?= e($siteName) ?>">
      <?php else: ?>
        <span class="logo-mark">A</span><span class="logo-text"><?= e($siteName) ?></span>
      <?php endif; ?>
    </a>
    <form class="search-form" action="<?= e(url('recherche')) ?>" method="get" role="search" autocomplete="off">
      <label class="sr-only" for="q"><?= e(__('search')) ?></label>
      <input type="search" id="q" name="q" value="<?= e(query_param('q')) ?>" placeholder="<?= e(__('search_placeholder')) ?>" data-suggest="<?= e(url('api/search.php')) ?>" maxlength="100">
      <button type="submit" aria-label="<?= e(__('search')) ?>"><?= icon('search') ?></button>
      <div class="suggest-box" hidden></div>
    </form>
    <nav class="header-actions" aria-label="Compte">
      <a class="header-action" href="<?= e(url($customer ? 'compte' : 'compte/connexion')) ?>">
        <?= icon('user') ?><span><?= e($customer ? $customer['first_name'] : __('login')) ?></span>
      </a>
      <a class="header-action hide-sm" href="<?= e(url('compte/favoris')) ?>"><?= icon('heart') ?><span><?= e(__('my_favorites')) ?></span></a>
      <a class="header-action cart-link" href="<?= e(url('panier')) ?>">
        <?= icon('cart') ?><span><?= e(__('cart')) ?></span>
        <b class="cart-count" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= (int)$cartCount ?></b>
      </a>
    </nav>
  </div>

  <nav class="main-nav" aria-label="Navigation principale" data-menu>
    <div class="container nav-inner">
      <div class="mobile-nav-head">
        <strong><?= e($siteName) ?></strong>
        <button class="icon-btn" aria-label="Fermer" data-menu-close><?= icon('close') ?></button>
      </div>
      <ul class="nav-list">
        <?php foreach ($menuCats as $c): ?>
        <li class="nav-item has-mega">
          <a href="<?= e(url('categorie/' . $c['slug'])) ?>" class="nav-link"><?= icon($c['icon'] ?: 'box', 'icon icon-sm') ?> <?= e($c['label']) ?></a>
          <button class="sub-toggle" aria-label="Ouvrir" data-sub-toggle><?= icon('chevron', 'icon icon-sm') ?></button>
          <?php if ($c['children']): ?>
          <div class="mega">
            <div class="mega-inner container">
              <div class="mega-cols">
                <?php foreach ($c['children'] as $child): ?>
                  <div class="mega-col">
                    <a class="mega-title" href="<?= e(url('categorie/' . $child['slug'])) ?>"><?= e($child['name']) ?></a>
                    <?php foreach ($child['children'] as $g): ?>
                      <a href="<?= e(url('categorie/' . $g['slug'])) ?>"><?= e($g['name']) ?></a>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="mega-side">
                <a class="mega-all" href="<?= e(url('categorie/' . $c['slug'])) ?>"><?= e(__('see_all')) ?> : <?= e($c['label']) ?> →</a>
                <?php if ($c['slug'] === 'consommables'): ?>
                  <a class="btn btn-accent btn-sm" href="<?= e(url('recherche-imprimante')) ?>"><?= icon('printer', 'icon icon-sm') ?> <?= e(__('printer_finder')) ?></a>
                <?php endif; ?>
                <div class="mega-brands">
                  <?php foreach ($menuBrands as $b): ?><a href="<?= e(url('marque/' . $b['slug'])) ?>"><?= e($b['name']) ?></a><?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
        <li class="nav-item"><a class="nav-link nav-promo" href="<?= e(url('promotions')) ?>"><?= icon('tag', 'icon icon-sm') ?> <?= e(setting('menu_label_promotions', 'Promotions')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('marques')) ?>"><?= e(setting('menu_label_brands', 'Marques')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('services')) ?>"><?= e(setting('menu_label_services', 'Services')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('a-propos')) ?>"><?= e(setting('menu_label_about', 'À propos')) ?></a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('contact')) ?>"><?= e(setting('menu_label_contact', 'Contact')) ?></a></li>
        <li class="nav-item nav-cta"><a class="nav-link" href="<?= e(url('recherche-imprimante')) ?>"><?= icon('printer', 'icon icon-sm') ?> <?= e(__('printer_finder')) ?></a></li>
      </ul>
    </div>
  </nav>
  <div class="nav-backdrop" data-menu-close></div>
</header>

<main id="main" class="site-main">
<?php $flashes = get_flashes(); if ($flashes): ?>
<div class="container flashes">
  <?php foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
