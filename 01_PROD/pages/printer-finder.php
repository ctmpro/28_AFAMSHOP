<?php
/**
 * Recherche de consommables par imprimante : marque → modèle → consommables compatibles.
 */
$brands = Catalog::printerBrands();
$model = null;
$brandId = int_param('marque');
if (query_param('modele') !== '') {
    $m = query_param('modele');
    $model = ctype_digit((string)$m)
        ? DB::one('SELECT m.*, b.name AS brand_name, b.slug AS brand_slug FROM printer_models m JOIN brands b ON b.id = m.brand_id WHERE m.id = :id AND m.active = 1', ['id' => (int)$m])
        : Catalog::printerModel((string)$m);
    if ($model) $brandId = (int)$model['brand_id'];
}
$models = $brandId ? Catalog::printerModels($brandId) : [];
$result = $model ? Catalog::search(['model' => (int)$model['id'], 'sort' => query_param('tri') ?: 'relevance'], max(1, int_param('page', 1)), 48) : null;
$favoriteIds = Auth::id() ? array_map('intval', DB::col('SELECT product_id FROM favorites WHERE customer_id = :c', ['c' => Auth::id()])) : [];

$pageTitle = $model ? __('compatible_consumables', ['model' => $model['brand_name'] . ' ' . $model['name']]) : __('printer_finder');
$bodyClass = 'page-finder';
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container">
  <nav class="breadcrumb"><a href="<?= e(url()) ?>"><?= e(__('home')) ?></a><span>›</span><a href="<?= e(url('recherche-imprimante')) ?>"><?= e(__('printer_finder')) ?></a><?php if ($model): ?><span>›</span><span><?= e($model['brand_name'] . ' ' . $model['name']) ?></span><?php endif; ?></nav>

  <section class="finder-panel card">
    <div>
      <h1><?= icon('printer') ?> <?= e(__('printer_finder')) ?></h1>
      <p><?= e(__('printer_finder_intro')) ?></p>
    </div>
    <form method="get" class="finder-form finder-form-lg" data-finder>
      <label><span>1. <?= e(__('choose_brand')) ?></span>
        <select name="marque" data-finder-brand data-models-url="<?= e(url('api/printer-models.php')) ?>" required>
          <option value=""><?= e(__('choose_brand')) ?></option>
          <?php foreach ($brands as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $brandId === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label><span>2. <?= e(__('choose_model')) ?></span>
        <select name="modele" data-finder-model <?= $models ? '' : 'disabled' ?> required>
          <option value=""><?= e(__('choose_model')) ?></option>
          <?php $series = null; foreach ($models as $m):
            if ($m['series'] !== $series) { if ($series !== null) echo '</optgroup>'; $series = $m['series']; echo '<optgroup label="' . e($series ?: '—') . '">'; } ?>
            <option value="<?= (int)$m['id'] ?>" <?= $model && (int)$model['id'] === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
          <?php endforeach; if ($series !== null) echo '</optgroup>'; ?>
        </select>
      </label>
      <button class="btn btn-accent" type="submit"><?= icon('search', 'icon icon-sm') ?> <?= e(__('find')) ?></button>
    </form>
    <form action="<?= e(url('recherche')) ?>" method="get" class="finder-ref">
      <input type="text" name="q" placeholder="<?= e(__('search_by_ref')) ?>" maxlength="100" aria-label="<?= e(__('search_by_ref')) ?>">
      <button class="btn btn-outline" type="submit"><?= icon('search', 'icon icon-sm') ?></button>
    </form>
  </section>

  <?php if ($model): ?>
    <h2 class="finder-title"><?= e(__('compatible_consumables', ['model' => $model['brand_name'] . ' ' . $model['name']])) ?> <small>(<?= (int)$result['pagination']['total'] ?>)</small></h2>
    <?php if ($result['items']): ?>
      <div class="product-grid">
        <?php foreach ($result['items'] as $p) require INCLUDES_PATH . '/layout/product-card.php'; ?>
      </div>
      <?= pagination_links($result['pagination']) ?>
    <?php else: ?>
      <div class="empty-state"><p><?= e(__('no_products')) ?></p>
        <a class="btn btn-primary" href="<?= e(url('devis', ['message' => 'Consommables pour ' . $model['brand_name'] . ' ' . $model['name']])) ?>"><?= e(__('request_quote')) ?></a></div>
    <?php endif; ?>
  <?php elseif ($brands): ?>
    <section class="section">
      <div class="finder-brands">
        <?php foreach ($brands as $b): ?>
          <a class="chip chip-lg" href="<?= e(url('recherche-imprimante', ['marque' => $b['id']])) ?>"><?= e($b['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
