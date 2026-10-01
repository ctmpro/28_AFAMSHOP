<?php
/**
 * Liste des services.
 */
$services = DB::all('SELECT * FROM services WHERE active = 1 ORDER BY sort, title');
$pageTitle = __('services');
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><span><?= e(__('services')) ?></span></nav>
  <h1><?= e(setting('menu_label_services', __('services'))) ?></h1>
  <div class="service-grid service-grid-lg">
    <?php foreach ($services as $s): ?>
    <a class="service-card" href="<?= e(url('service/' . $s['slug'])) ?>">
      <?php if ($s['image']): ?><img src="<?= e(media_url($s['image'])) ?>" alt="" loading="lazy"><?php else: ?><?= icon($s['icon'] ?: 'briefcase', 'icon icon-lg') ?><?php endif; ?>
      <strong><?= e($s['title']) ?></strong>
      <span><?= e($s['short_desc']) ?></span>
      <em><?= e(__('learn_more')) ?> →</em>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
