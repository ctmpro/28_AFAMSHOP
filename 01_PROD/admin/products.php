<?php
/**
 * Liste des produits : recherche, filtres, tri, pagination et actions groupées.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('products');

// --- Actions groupées
if (is_post()) {
    require_csrf();
    $ids = post_ids('ids');
    $action = (string)post('bulk_action');
    if (!$ids) {
        flash('warning', 'Aucun produit sélectionné.');
    } else {
        $params = [];
        $in = DB::in($ids, $params);
        switch ($action) {
            case 'publish':
            case 'unpublish':
                $n = DB::exec('UPDATE products SET published = ' . ($action === 'publish' ? 1 : 0) . ' WHERE id IN ' . $in, $params);
                AdminAuth::log('product_bulk_' . $action, 'product', null, ['ids' => $ids]);
                flash('success', $n . ' produit(s) ' . ($action === 'publish' ? 'publié(s)' : 'dépublié(s)') . '.');
                break;
            case 'feature':
            case 'unfeature':
                DB::exec('UPDATE products SET is_featured = ' . ($action === 'feature' ? 1 : 0) . ' WHERE id IN ' . $in, $params);
                AdminAuth::log('product_bulk_' . $action, 'product', null, ['ids' => $ids]);
                flash('success', 'Produits mis à jour.');
                break;
            case 'delete':
                $paths = DB::col('SELECT path FROM product_images WHERE product_id IN ' . $in, $params);
                $n = DB::exec('DELETE FROM products WHERE id IN ' . $in, $params);
                product_image_cleanup($paths);
                AdminAuth::log('product_bulk_delete', 'product', null, ['ids' => $ids]);
                flash('success', $n . ' produit(s) supprimé(s).');
                break;
            default:
                flash('warning', 'Action inconnue.');
        }
    }
    redirect(admin_url('products.php') . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

// --- Filtres
$q = (string)query_param('q');
$categoryId = (int)query_param('category', 0);
$brandId = (int)query_param('brand', 0);
$published = (string)query_param('published');
$stockFilter = (string)query_param('stock');

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.manufacturer_ref LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
if ($categoryId) {
    $where[] = 'p.category_id IN ' . DB::in(Catalog::categoryDescendants($categoryId), $params, 'cat');
}
if ($brandId) {
    $where[] = 'p.brand_id = :brand';
    $params['brand'] = $brandId;
}
if ($published === '1' || $published === '0') {
    $where[] = 'p.published = :pub';
    $params['pub'] = (int)$published;
}
if ($stockFilter !== '') {
    $params['thr'] = (int)setting('low_stock_threshold', 5);
    $where[] = match ($stockFilter) {
        'in' => 'p.stock > COALESCE(p.low_stock_threshold, :thr)',
        'low' => 'p.stock > 0 AND p.stock <= COALESCE(p.low_stock_threshold, :thr)',
        'out' => 'p.stock <= 0 AND p.on_order = 0',
        'on_order' => 'p.stock <= 0 AND p.on_order = 1',
        default => '1=1',
    };
    if (!in_array($stockFilter, ['in', 'low'], true)) unset($params['thr']);
}
$whereSql = implode(' AND ', $where);
$total = (int)DB::val('SELECT COUNT(*) FROM products p WHERE ' . $whereSql, $params);
$pg = paginate($total, 25, (int)query_param('page', 1));
$order = admin_sort_sql([
    'name' => 'p.name', 'sku' => 'p.sku', 'price' => 'p.price', 'stock' => 'p.stock',
    'sales' => 'p.sales_count', 'date' => 'p.created_at',
], 'date');
$rows = DB::all(
    'SELECT p.*, b.name AS brand_name, c.name AS category_name FROM products p
     LEFT JOIN brands b ON b.id = p.brand_id LEFT JOIN categories c ON c.id = p.category_id
     WHERE ' . $whereSql . ' ORDER BY ' . $order . ', p.id DESC LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'],
    $params
);
$rows = Catalog::decorate($rows);

$catOptions = category_options();
$brandOptions = brand_options();

$pageTitle = 'Produits';
$pageActions = '<a class="btn" href="' . e(admin_url('import.php')) . '">' . aicon('upload') . ' Import / export</a>'
    . '<a class="btn btn-primary" href="' . e(admin_url('product_edit.php')) . '">' . aicon('plus') . ' Nouveau produit</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="card">
  <form method="get" class="filters">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom, SKU, référence constructeur…"></div>
    <div class="field"><label for="category">Catégorie</label><select id="category" name="category"><?= select_options($catOptions, $categoryId ?: '', 'Toutes') ?></select></div>
    <div class="field"><label for="brand">Marque</label><select id="brand" name="brand"><?= select_options($brandOptions, $brandId ?: '', 'Toutes') ?></select></div>
    <div class="field"><label for="published">Publication</label><select id="published" name="published"><?= select_options(['1' => 'Publiés', '0' => 'Non publiés'], $published, 'Tous') ?></select></div>
    <div class="field"><label for="stock">Stock</label><select id="stock" name="stock"><?= select_options(['in' => 'En stock', 'low' => 'Stock faible', 'out' => 'Rupture', 'on_order' => 'Sur commande'], $stockFilter, 'Tous') ?></select></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
    <a class="btn" href="<?= e(admin_url('products.php')) ?>">Réinitialiser</a>
  </form>
</div>

<form method="post" class="card flush" data-confirm-bulk>
  <?= csrf_field() ?>
  <div class="card-head"><h2><?= $total ?> produit(s)</h2></div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th><input type="checkbox" data-check-all="ids[]" aria-label="Tout sélectionner"></th>
          <th></th>
          <th><?= sort_link('name', 'Produit', 'date') ?></th>
          <th><?= sort_link('sku', 'SKU', 'date') ?></th>
          <th>Marque / Catégorie</th>
          <th class="num"><?= sort_link('price', 'Prix', 'date') ?></th>
          <th class="num"><?= sort_link('stock', 'Stock', 'date') ?></th>
          <th class="num"><?= sort_link('sales', 'Ventes', 'date') ?></th>
          <th>Statut</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $p): ?>
        <tr class="<?= $p['published'] ? '' : 'is-muted' ?>">
          <td><input type="checkbox" name="ids[]" value="<?= (int)$p['id'] ?>" aria-label="Sélectionner"></td>
          <td><img class="thumb" src="<?= e(media_url($p['main_image'])) ?>" alt="" loading="lazy"></td>
          <td>
            <a href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><strong><?= e($p['name']) ?></strong></a>
            <?php if ($p['is_featured']): ?> <?= badge('Vedette', 'primary') ?><?php endif; ?>
            <?php if ($p['manufacturer_ref']): ?><br><span class="muted small">Réf. <?= e($p['manufacturer_ref']) ?></span><?php endif; ?>
          </td>
          <td class="nowrap"><code><?= e($p['sku']) ?></code></td>
          <td><?= e($p['brand_name'] ?? '—') ?><br><span class="muted small"><?= e($p['category_name'] ?? '—') ?></span></td>
          <td class="num">
            <?php if ($p['old_price']): ?><s class="muted small"><?= e(admin_money($p['price'])) ?></s><br><?php endif; ?>
            <strong><?= e(admin_money($p['final_price'])) ?></strong>
          </td>
          <td class="num"><?= (int)$p['stock'] ?><br><?= stock_badge($p['stock_status']) ?></td>
          <td class="num"><?= (int)$p['sales_count'] ?></td>
          <td><?= bool_badge($p['published'], 'Publié', 'Brouillon') ?></td>
          <td class="actions">
            <a class="btn btn-sm" href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
            <?php if ($p['published']): ?><a class="btn btn-sm" href="<?= e(url('produit/' . $p['slug'])) ?>" target="_blank" rel="noopener" title="Voir sur le site"><?= aicon('eye') ?></a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="10" class="table-empty">Aucun produit ne correspond à ces critères.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="bulk-bar">
    <select name="bulk_action" aria-label="Action groupée">
      <option value="">Action groupée…</option>
      <option value="publish">Publier</option>
      <option value="unpublish">Dépublier</option>
      <option value="feature">Mettre en vedette</option>
      <option value="unfeature">Retirer de la vedette</option>
      <option value="delete">Supprimer définitivement</option>
    </select>
    <button type="submit" class="btn" data-confirm="Appliquer l'action aux produits sélectionnés ?">Appliquer</button>
  </div>
  <?= pagination_links($pg) ?>
</form>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
