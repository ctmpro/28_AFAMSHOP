<?php
/**
 * Comparateur de produits.
 */
$ids = array_map('intval', $_SESSION['compare'] ?? []);
$byId = Catalog::productsByIds($ids);
$products = array_values(array_filter(array_map(fn($id) => $byId[$id] ?? null, $ids)));
$specs = [];
$keys = [];
foreach ($products as $p) {
    $specs[$p['id']] = Catalog::parseSpecs($p['specs']);
    foreach (array_keys($specs[$p['id']]) as $k) $keys[$k] = true;
}
$pageTitle = __('compare_products');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <h1><?= e(__('compare_products')) ?></h1>
  <?php if (!$products): ?>
    <div class="empty-state"><?= icon('compare', 'icon icon-xl') ?><p><?= e(__('compare_empty')) ?></p><a class="btn btn-primary" href="<?= e(url('categorie/impression')) ?>"><?= e(__('continue_shopping')) ?></a></div>
  <?php else: ?>
  <form method="post" action="<?= e(url('api/compare.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-link"><?= e(__('compare_clear')) ?></button></form>
  <div class="table-scroll">
    <table class="compare-table">
      <thead><tr><th></th>
        <?php foreach ($products as $p): ?>
        <th>
          <a href="<?= e(url('produit/' . $p['slug'])) ?>"><img src="<?= e(media_url($p['main_image'])) ?>" alt=""><span><?= e($p['name']) ?></span></a>
          <button class="btn btn-link btn-sm" data-compare-remove="<?= (int)$p['id'] ?>"><?= e(__('remove')) ?></button>
        </th>
        <?php endforeach; ?>
      </tr></thead>
      <tbody>
        <tr><th><?= e(__('price')) ?></th><?php foreach ($products as $p): ?><td><strong><?= e(money($p['final_price'])) ?></strong><?= $p['old_price'] ? ' <del>' . e(money($p['old_price'])) . '</del>' : '' ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('brand')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['brand_name']) ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('manufacturer_ref')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['manufacturer_ref']) ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('availability')) ?></th><?php foreach ($products as $p): ?><td><span class="stock stock-<?= e($p['stock_status']) ?>"><?= e(Catalog::stockLabel($p['stock_status'])) ?></span></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('type')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['product_type']) ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('color')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['color']) ?></td><?php endforeach; ?></tr>
        <?php foreach (array_keys($keys) as $k): ?>
        <tr><th><?= e($k) ?></th><?php foreach ($products as $p): ?><td><?= e($specs[$p['id']][$k] ?? '—') ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <tr><th><?= e(__('weight')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['weight']) ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('dimensions')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['dimensions']) ?></td><?php endforeach; ?></tr>
        <tr><th><?= e(__('warranty')) ?></th><?php foreach ($products as $p): ?><td><?= e($p['warranty'] ?: setting('default_warranty')) ?></td><?php endforeach; ?></tr>
        <tr><th></th><?php foreach ($products as $p): ?><td><?php if (Catalog::canBuy($p)): ?><button class="btn btn-accent btn-sm" data-add-to-cart="<?= (int)$p['id'] ?>"><?= e(__('add_to_cart')) ?></button><?php endif; ?></td><?php endforeach; ?></tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
