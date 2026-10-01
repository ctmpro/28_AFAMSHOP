<?php
/**
 * En-tête commun du back-office : barre latérale (modules autorisés), barre supérieure, messages flash.
 * Variables attendues : $pageTitle (string), $pageActions (HTML optionnel, boutons à droite du titre).
 */
// Inclusion uniquement (pas d'accès direct)
if (!defined('ROOT_PATH')) {
    http_response_code(404);
    exit;
}

$__admin = AdminAuth::user();
$__active = admin_active_file();
$__primary = setting('color_primary', '#0b4f8a');
if (!preg_match('/^#[0-9a-f]{3,8}$/i', $__primary)) {
    $__primary = '#0b4f8a';
}
$__newRequests = AdminAuth::can('requests') ? (int)DB::val("SELECT COUNT(*) FROM requests WHERE status = 'new'") : 0;
$__pendingReviews = AdminAuth::can('reviews') ? (int)DB::val("SELECT COUNT(*) FROM reviews WHERE status = 'pending'") : 0;
$__counters = ['requests.php' => $__newRequests, 'reviews.php' => $__pendingReviews];
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e(($pageTitle ?? 'Administration') . ' · ' . setting('site_name', 'AFAMSHOP') . ' Admin') ?></title>
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
<style>:root{--primary:<?= e($__primary) ?>;}</style>
<?php if (setting('favicon')): ?><link rel="icon" href="<?= e(media_url(setting('favicon'))) ?>"><?php endif; ?>
</head>
<body class="admin">
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <a href="<?= e(admin_url('index.php')) ?>"><?= e(setting('site_name', 'AFAMSHOP')) ?> <span>Admin</span></a>
      <button type="button" class="icon-btn sidebar-close" data-sidebar-toggle aria-label="Fermer le menu"><?= aicon('x') ?></button>
    </div>
    <nav class="sidebar-nav">
      <?php foreach (admin_menu() as $__group => $__items):
          $__visible = array_filter($__items, fn($i) => AdminAuth::can($i[0]));
          if (!$__visible) continue; ?>
        <div class="nav-group"><?= e($__group) ?></div>
        <?php foreach ($__visible as [$__mod, $__file, $__label, $__icon]): ?>
          <a href="<?= e(admin_url($__file)) ?>" class="nav-link<?= $__active === $__file ? ' active' : '' ?>">
            <?= aicon($__icon) ?><span><?= e($__label) ?></span>
            <?php if (!empty($__counters[$__file])): ?><em class="nav-count"><?= (int)$__counters[$__file] ?></em><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-toggle></div>

  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn menu-btn" data-sidebar-toggle aria-label="Ouvrir le menu"><?= aicon('menu') ?></button>
      <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
      <div class="topbar-right">
        <a class="topbar-link" href="<?= e(url('')) ?>" target="_blank" rel="noopener" title="Voir le site"><?= aicon('external') ?><span class="hide-sm">Voir le site</span></a>
        <a class="topbar-user" href="<?= e(admin_url('profile.php')) ?>" title="Mon profil">
          <span class="avatar"><?= e(mb_strtoupper(mb_substr($__admin['name'] ?? '?', 0, 1))) ?></span>
          <span class="hide-sm"><strong><?= e($__admin['name'] ?? '') ?></strong><small><?= e(AdminAuth::ROLES[$__admin['role'] ?? ''] ?? '') ?></small></span>
        </a>
        <form method="post" action="<?= e(admin_url('logout.php')) ?>" class="inline">
          <?= csrf_field() ?>
          <button type="submit" class="icon-btn" title="Déconnexion" aria-label="Déconnexion"><?= aicon('logout') ?></button>
        </form>
      </div>
    </header>

    <main class="content">
      <?php if (!empty($pageTitle) || !empty($pageActions)): ?>
        <div class="page-head">
          <h1><?= e($pageTitle ?? '') ?></h1>
          <?php if (!empty($pageActions)): ?><div class="page-actions"><?= $pageActions ?></div><?php endif; ?>
        </div>
      <?php endif; ?>
      <?php foreach (get_flashes() as $__f): ?>
        <div class="alert alert-<?= e($__f['type'] === 'error' ? 'danger' : $__f['type']) ?>" role="alert">
          <span><?= e($__f['message']) ?></span>
          <button type="button" class="alert-close" aria-label="Fermer">&times;</button>
        </div>
      <?php endforeach; ?>
