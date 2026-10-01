<?php
/**
 * Liste des marques.
 */
$brands = DB::all('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.published = 1) AS n FROM brands b WHERE b.active = 1 ORDER BY b.featured DESC, b.sort, b.name');
$pageTitle = __('brands');
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><span><?= e(__('brands')) ?></span></nav>
  <h1><?= e(setting('menu_label_brands', __('brands'))) ?></h1>
  <div class="brand-grid">
    <?php foreach ($brands as $b): ?>
    <a class="brand-card <?= $b['featured'] ? 'featured' : '' ?>" href="<?= e(url('marque/' . $b['slug'])) ?>">
      <?php if ($b['logo']): ?><img src="<?= e(media_url($b['logo'])) ?>" alt="<?= e($b['name']) ?>" loading="lazy"><?php else: ?><strong><?= e($b['name']) ?></strong><?php endif; ?>
      <span><?= (int)$b['n'] ?> <?= e(__('products')) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
