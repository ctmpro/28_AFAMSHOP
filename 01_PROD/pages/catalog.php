<?php
/**
 * Listing produits : catégorie, marque, recherche, promotions, nouveautés.
 */
$route = $params['route'];
$filters = [
    'q' => mb_substr((string)query_param('q'), 0, 100),
    'brand' => array_filter(array_map('intval', (array)($_GET['marque'] ?? []))),
    'price_min' => is_numeric(query_param('prix_min')) ? query_param('prix_min') : '',
    'price_max' => is_numeric(query_param('prix_max')) ? query_param('prix_max') : '',
    'availability' => in_array(query_param('dispo'), ['in_stock', 'on_order'], true) ? query_param('dispo') : '',
    'type' => array_filter(array_map('strval', (array)($_GET['type'] ?? []))),
    'color' => array_filter(array_map('strval', (array)($_GET['couleur'] ?? []))),
    'model' => int_param('modele'),
    'sort' => in_array(query_param('tri'), ['relevance', 'price_asc', 'price_desc', 'newest', 'bestsellers'], true) ? query_param('tri') : 'relevance',
];

$category = null;
$brand = null;
$title = __('all_categories');
$intro = '';
$crumbs = [];
$subcats = [];

if (str_starts_with($route, 'categorie/')) {
    $category = Catalog::category($slug);
    if (!$category) {
        http_response_code(404);
        require PAGES_PATH . '/404.php';
        return;
    }
    $filters['category_id'] = (int)$category['id'];
    $title = $category['name'];
    $intro = $category['description'];
    foreach (Catalog::breadcrumb((int)$category['id']) as $c) {
        $crumbs[] = [$c['name'], url('categorie/' . $c['slug'])];
    }
    $subcats = array_filter(Catalog::categories(), fn($c) => (int)$c['parent_id'] === (int)$category['id']);
} elseif (str_starts_with($route, 'marque/')) {
    $brand = Catalog::brand($slug);
    if (!$brand) {
        http_response_code(404);
        require PAGES_PATH . '/404.php';
        return;
    }
    $filters['brand_id'] = (int)$brand['id'];
    $title = $brand['name'];
    $intro = $brand['description'];
    $crumbs[] = [__('brands'), url('marques')];
    $crumbs[] = [$brand['name'], url('marque/' . $brand['slug'])];
    // Catégories de la marque
    $subcats = DB::all('SELECT DISTINCT c.* FROM categories c JOIN products p ON p.category_id = c.id WHERE p.brand_id = :b AND p.published = 1 AND c.active = 1 ORDER BY c.name LIMIT 12', ['b' => $brand['id']]);
} elseif ($route === 'promotions') {
    $filters['promo'] = 1;
    $title = setting('menu_label_promotions', __('promotions'));
    $crumbs[] = [$title, url('promotions')];
} elseif ($route === 'nouveautes') {
    $filters['sort'] = query_param('tri') ?: 'newest';
    $title = __('sort_newest');
} elseif ($filters['q'] !== '') {
    $title = __('search_results', ['q' => $filters['q']]);
    $crumbs[] = [__('search'), url('recherche', ['q' => $filters['q']])];
}

$perPage = max(6, min(96, (int)setting('products_per_page', 24)));
$result = Catalog::search($filters, max(1, int_param('page', 1)), $perPage);
$items = $result['items'];
$pg = $result['pagination'];
$facets = $result['facets'];
$favoriteIds = Auth::id() ? array_map('intval', DB::col('SELECT product_id FROM favorites WHERE customer_id = :c', ['c' => Auth::id()])) : [];

$activeFilters = count($filters['brand']) + count($filters['type']) + count($filters['color'])
    + ($filters['availability'] ? 1 : 0) + ($filters['price_min'] !== '' ? 1 : 0) + ($filters['price_max'] !== '' ? 1 : 0);

$pageTitle = $title;
$metaDescription = $intro ? truncate($intro, 160) : setting('meta_description');
$noIndex = $filters['q'] !== '' || $activeFilters > 0;
$bodyClass = 'page-catalog';
require INCLUDES_PATH . '/layout/header.php';
$baseUrl = strtok(current_url(), '?');
?>
<div class="container">
  <nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url()) ?>"><?= e(__('home')) ?></a>
    <?php foreach ($crumbs as [$label, $link]): ?><span>›</span><a href="<?= e($link) ?>"><?= e($label) ?></a><?php endforeach; ?>
  </nav>

  <header class="catalog-head">
    <h1><?= e($title) ?></h1>
    <?php if ($intro): ?><div class="catalog-intro"><?= clean_html($intro) ?></div><?php endif; ?>
    <?php if ($subcats): ?>
    <div class="chips">
      <?php foreach ($subcats as $sc): ?><a class="chip" href="<?= e(url('categorie/' . $sc['slug'])) ?>"><?= e($sc['name']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </header>

  <div class="catalog-layout">
    <aside class="filters" data-filters>
      <form method="get" action="<?= e($baseUrl) ?>" class="filters-form">
        <div class="filters-head">
          <strong><?= icon('filter', 'icon icon-sm') ?> <?= e(__('filters')) ?></strong>
          <button type="button" class="icon-btn filters-close" data-filters-close aria-label="Fermer"><?= icon('close') ?></button>
        </div>
        <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
        <?php if ($filters['model']): ?><input type="hidden" name="modele" value="<?= (int)$filters['model'] ?>"><?php endif; ?>
        <input type="hidden" name="tri" value="<?= e($filters['sort']) ?>">

        <?php if (!$brand && $facets['brands']): ?>
        <fieldset class="filter-group">
          <legend><?= e(__('brand')) ?></legend>
          <?php foreach ($facets['brands'] as $b): ?>
          <label class="check"><input type="checkbox" name="marque[]" value="<?= (int)$b['id'] ?>" <?= in_array((int)$b['id'], $filters['brand'], true) ? 'checked' : '' ?> data-autosubmit> <?= e($b['name']) ?> <small>(<?= (int)$b['n'] ?>)</small></label>
          <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <fieldset class="filter-group">
          <legend><?= e(__('price')) ?> (<?= e(currencies()[base_currency()]['symbol']) ?>)</legend>
          <div class="price-range">
            <input type="number" name="prix_min" min="0" step="1" value="<?= e($filters['price_min']) ?>" placeholder="<?= e(__('price_min')) ?> <?= (int)$facets['price_min'] ?>" aria-label="<?= e(__('price_min')) ?>">
            <span>–</span>
            <input type="number" name="prix_max" min="0" step="1" value="<?= e($filters['price_max']) ?>" placeholder="<?= e(__('price_max')) ?> <?= (int)ceil($facets['price_max']) ?>" aria-label="<?= e(__('price_max')) ?>">
          </div>
        </fieldset>

        <fieldset class="filter-group">
          <legend><?= e(__('availability')) ?></legend>
          <label class="check"><input type="radio" name="dispo" value="" <?= !$filters['availability'] ? 'checked' : '' ?> data-autosubmit> Tous</label>
          <label class="check"><input type="radio" name="dispo" value="in_stock" <?= $filters['availability'] === 'in_stock' ? 'checked' : '' ?> data-autosubmit> <?= e(__('stock_in_stock')) ?></label>
          <label class="check"><input type="radio" name="dispo" value="on_order" <?= $filters['availability'] === 'on_order' ? 'checked' : '' ?> data-autosubmit> <?= e(__('stock_on_order')) ?></label>
        </fieldset>

        <?php if ($facets['types']): ?>
        <fieldset class="filter-group">
          <legend><?= e(__('type')) ?></legend>
          <?php foreach ($facets['types'] as $t): ?>
          <label class="check"><input type="checkbox" name="type[]" value="<?= e($t['v']) ?>" <?= in_array($t['v'], $filters['type'], true) ? 'checked' : '' ?> data-autosubmit> <?= e($t['v']) ?> <small>(<?= (int)$t['n'] ?>)</small></label>
          <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <?php if ($facets['colors']): ?>
        <fieldset class="filter-group">
          <legend><?= e(__('color')) ?></legend>
          <?php foreach ($facets['colors'] as $c): ?>
          <label class="check"><input type="checkbox" name="couleur[]" value="<?= e($c['v']) ?>" <?= in_array($c['v'], $filters['color'], true) ? 'checked' : '' ?> data-autosubmit> <?= e($c['v']) ?> <small>(<?= (int)$c['n'] ?>)</small></label>
          <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>

        <fieldset class="filter-group">
          <legend><?= e(__('compatibility')) ?></legend>
          <a class="btn btn-outline btn-sm btn-block" href="<?= e(url('recherche-imprimante')) ?>"><?= icon('printer', 'icon icon-sm') ?> <?= e(__('printer_finder')) ?></a>
        </fieldset>

        <div class="filters-actions">
          <button class="btn btn-primary btn-block" type="submit"><?= e(__('apply_filters')) ?></button>
          <?php if ($activeFilters): ?><a class="btn btn-link btn-block" href="<?= e($baseUrl . ($filters['q'] !== '' ? '?q=' . urlencode($filters['q']) : '')) ?>"><?= e(__('reset_filters')) ?></a><?php endif; ?>
        </div>
      </form>
    </aside>

    <section class="catalog-results">
      <div class="toolbar">
        <button class="btn btn-outline btn-sm filters-open" data-filters-open><?= icon('filter', 'icon icon-sm') ?> <?= e(__('filters')) ?><?= $activeFilters ? ' (' . $activeFilters . ')' : '' ?></button>
        <span class="result-count"><?= e(__('products_found', ['n' => $pg['total']])) ?></span>
        <form method="get" class="sort-form">
          <?php foreach ($_GET as $k => $v): if (in_array($k, ['tri', 'page'], true)) continue;
            foreach ((array)$v as $vv): ?><input type="hidden" name="<?= e(is_array($v) ? $k . '[]' : $k) ?>" value="<?= e($vv) ?>"><?php endforeach; endforeach; ?>
          <label for="tri"><?= e(__('sort_by')) ?></label>
          <select id="tri" name="tri" onchange="this.form.submit()">
            <?php foreach (['relevance', 'price_asc', 'price_desc', 'newest', 'bestsellers'] as $s): ?>
              <option value="<?= $s ?>" <?= $filters['sort'] === $s ? 'selected' : '' ?>><?= e(__('sort_' . $s)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if ($items): ?>
        <div class="product-grid">
          <?php foreach ($items as $p) require INCLUDES_PATH . '/layout/product-card.php'; ?>
        </div>
        <?= pagination_links($pg, $baseUrl) ?>
      <?php else: ?>
        <div class="empty-state">
          <?= icon('search', 'icon icon-xl') ?>
          <p><?= e(__('no_products')) ?></p>
          <a class="btn btn-primary" href="<?= e(url('devis', ['message' => $filters['q']])) ?>"><?= e(__('request_quote')) ?></a>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
