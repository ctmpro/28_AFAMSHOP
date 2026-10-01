<?php
/**
 * Espace client : favoris.
 */
$u = Auth::require();
$ids = array_map('intval', DB::col('SELECT product_id FROM favorites WHERE customer_id = :c ORDER BY created_at DESC', ['c' => $u['id']]));
$byId = Catalog::productsByIds($ids);
$favoriteIds = $ids;
$active = 'favorites';
$pageTitle = __('my_favorites');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_favorites')) ?></h1>
    <?php if (!$byId): ?><div class="card"><p class="muted"><?= e(__('no_favorites')) ?></p></div><?php else: ?>
    <div class="product-grid product-grid-3">
      <?php foreach ($ids as $id): if (!isset($byId[$id])) continue; $p = $byId[$id]; require INCLUDES_PATH . '/layout/product-card.php'; endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
