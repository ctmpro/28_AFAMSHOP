<?php
/**
 * Compatibilités : modèles d'imprimantes (CRUD) et produits compatibles par modèle.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('compatibility');

$action = (string)query_param('action');
$id = int_param('id');
$model = $id ? DB::one('SELECT m.*, b.name AS brand_name FROM printer_models m JOIN brands b ON b.id = m.brand_id WHERE m.id = :id', ['id' => $id]) : null;
if ($id && !$model) {
    flash('error', 'Modèle introuvable.');
    redirect(admin_url('compatibility.php'));
}
$errors = [];
$form = $model ?: ['brand_id' => (int)query_param('brand', 0) ?: '', 'name' => '', 'slug' => '', 'series' => '', 'active' => 1];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');

    if ($do === 'delete' && $model) {
        DB::exec('DELETE FROM printer_models WHERE id = :id', ['id' => $id]);
        AdminAuth::log('printer_model_delete', 'printer_model', $id, $model['brand_name'] . ' ' . $model['name']);
        flash('success', 'Modèle supprimé.');
        redirect(admin_url('compatibility.php'));
    }

    // Ajout de produits compatibles par SKU (plusieurs SKU séparés par virgule, espace ou retour à la ligne)
    if ($do === 'add_products' && $model) {
        $skus = array_filter(array_map('trim', preg_split('/[\s,;]+/', (string)post('skus'))));
        $added = 0;
        $unknown = [];
        foreach ($skus as $sku) {
            $pid = DB::val('SELECT id FROM products WHERE sku = :s', ['s' => $sku]);
            if (!$pid) {
                $unknown[] = $sku;
                continue;
            }
            $added += DB::exec('INSERT IGNORE INTO product_compatibility (product_id, printer_model_id) VALUES (:p, :m)', ['p' => $pid, 'm' => $id]);
        }
        AdminAuth::log('compat_add', 'printer_model', $id, ['skus' => array_values($skus)]);
        flash($added ? 'success' : 'info', $added . ' produit(s) ajouté(s).');
        if ($unknown) flash('warning', 'SKU introuvable(s) : ' . implode(', ', $unknown));
        redirect(admin_url('compatibility.php', ['action' => 'products', 'id' => $id]));
    }

    if ($do === 'remove_product' && $model) {
        $pid = (int)post('product_id');
        DB::exec('DELETE FROM product_compatibility WHERE product_id = :p AND printer_model_id = :m', ['p' => $pid, 'm' => $id]);
        AdminAuth::log('compat_remove', 'printer_model', $id, ['product_id' => $pid]);
        flash('success', 'Produit retiré de ce modèle.');
        redirect(admin_url('compatibility.php', ['action' => 'products', 'id' => $id]));
    }

    $in = [
        'brand_id' => (int)post('brand_id'),
        'name' => (string)post('name'),
        'series' => (string)post('series') ?: null,
        'active' => post_bool('active'),
    ];
    $slugIn = (string)post('slug');
    $brandName = DB::val('SELECT name FROM brands WHERE id = :id', ['id' => $in['brand_id']]);
    if (!$brandName) $errors[] = 'Choisissez une marque.';
    if ($in['name'] === '') $errors[] = 'Le nom du modèle est obligatoire.';
    elseif (DB::val('SELECT id FROM printer_models WHERE brand_id = :b AND name = :n AND id <> :id', ['b' => $in['brand_id'], 'n' => $in['name'], 'id' => $id])) {
        $errors[] = 'Ce modèle existe déjà pour cette marque.';
    }
    if ($errors) {
        $form = array_merge($form, $in, ['slug' => $slugIn]);
        $action = $model ? 'edit' : 'new';
    } else {
        $in['slug'] = unique_slug('printer_models', $slugIn !== '' ? $slugIn : $brandName . ' ' . $in['name'], $id ?: null);
        if ($model) {
            DB::update('printer_models', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('printer_models', $in);
        }
        AdminAuth::log($model ? 'printer_model_update' : 'printer_model_create', 'printer_model', $id, $brandName . ' ' . $in['name']);
        flash('success', 'Modèle enregistré.');
        redirect(admin_url('compatibility.php', ['brand' => $in['brand_id']]));
    }
}

$brands = brand_options();
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$showProducts = $action === 'products' && $model;

if ($showProducts) {
    $linked = DB::all('SELECT p.id, p.sku, p.name, p.stock, p.published FROM product_compatibility pc JOIN products p ON p.id = pc.product_id WHERE pc.printer_model_id = :m ORDER BY p.name', ['m' => $id]);
} elseif (!$showForm) {
    $brandFilter = (int)query_param('brand', 0);
    $q = (string)query_param('q');
    $where = ['1=1'];
    $params = [];
    if ($brandFilter) {
        $where[] = 'm.brand_id = :b';
        $params['b'] = $brandFilter;
    }
    if ($q !== '') {
        $where[] = '(m.name LIKE :q OR m.series LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    $total = (int)DB::val('SELECT COUNT(*) FROM printer_models m WHERE ' . implode(' AND ', $where), $params);
    $pg = paginate($total, 50, (int)query_param('page', 1));
    $rows = DB::all('SELECT m.*, b.name AS brand_name, (SELECT COUNT(*) FROM product_compatibility pc WHERE pc.printer_model_id = m.id) AS n_products
        FROM printer_models m JOIN brands b ON b.id = m.brand_id WHERE ' . implode(' AND ', $where) . '
        ORDER BY b.name, m.series, m.name LIMIT ' . $pg['per_page'] . ' OFFSET ' . $pg['offset'], $params);
}

$pageTitle = $showProducts ? 'Produits compatibles : ' . $model['brand_name'] . ' ' . $model['name'] : "Modèles d'imprimantes";
$pageActions = ($showForm || $showProducts) ? '<a class="btn" href="' . e(admin_url('compatibility.php', ['brand' => $model['brand_id'] ?? null])) . '">← Liste des modèles</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('compatibility.php', ['action' => 'new', 'brand' => $brandFilter ?: null])) . '">' . aicon('plus') . ' Nouveau modèle</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" class="card" action="<?= e(admin_url('compatibility.php', ['id' => $model['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $model ? 'Modifier le modèle' : 'Nouveau modèle d\'imprimante' ?></h2>
    <div class="form-grid">
      <?= field_select('brand_id', 'Marque', $brands, $form['brand_id'], ['required' => true, 'placeholder' => '— Choisir —']) ?>
      <?= field_input('name', 'Nom du modèle', $form['name'], ['required' => true, 'help' => 'Sans la marque, ex. « MX-3051 ».', 'attrs' => ['maxlength' => 150]]) ?>
      <?= field_input('series', 'Série / gamme', $form['series'], ['attrs' => ['maxlength' => 120]]) ?>
      <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['help' => 'Laisser vide pour la générer (marque + modèle).', 'attrs' => ['maxlength' => 170]]) ?>
      <?= field_checkbox('active', 'Actif (proposé dans la recherche par imprimante)', (bool)$form['active']) ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('compatibility.php')) ?>">Annuler</a>
    </div>
  </form>

<?php elseif ($showProducts): ?>
  <div class="grid grid-main">
    <div class="card flush">
      <div class="card-head"><h2><?= count($linked) ?> produit(s) compatible(s)</h2></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>SKU</th><th>Produit</th><th class="num">Stock</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($linked as $p): ?>
            <tr>
              <td><code><?= e($p['sku']) ?></code></td>
              <td><a href="<?= e(admin_url('product_edit.php', ['id' => $p['id']])) ?>"><?= e($p['name']) ?></a></td>
              <td class="num"><?= (int)$p['stock'] ?></td>
              <td><?= bool_badge($p['published'], 'Publié', 'Brouillon') ?></td>
              <td class="actions">
                <form method="post" class="inline" action="<?= e(admin_url('compatibility.php', ['id' => $id])) ?>"><?= csrf_field() ?>
                  <input type="hidden" name="do" value="remove_product"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit" data-confirm="Retirer ce produit de la liste des compatibilités ?">Retirer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$linked): ?><tr><td colspan="5" class="table-empty">Aucun produit associé à ce modèle.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <form method="post" class="card" action="<?= e(admin_url('compatibility.php', ['id' => $id])) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="add_products">
      <h2>Ajouter des produits</h2>
      <?= field_textarea('skus', 'SKU des produits', '', ['rows' => 5, 'help' => 'Un ou plusieurs SKU séparés par des virgules, espaces ou retours à la ligne.']) ?>
      <button class="btn btn-primary" type="submit"><?= aicon('plus') ?> Ajouter</button>
      <p class="muted small mt">Modèle : <strong><?= e($model['brand_name'] . ' ' . $model['name']) ?></strong><?= $model['series'] ? ' · série ' . e($model['series']) : '' ?></p>
    </form>
  </div>

<?php else: ?>
  <div class="card">
    <form method="get" class="filters">
      <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom ou série du modèle"></div>
      <div class="field"><label for="brand">Marque</label><select id="brand" name="brand"><?= select_options($brands, $brandFilter ?: '', 'Toutes') ?></select></div>
      <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
    </form>
  </div>
  <div class="card flush">
    <div class="card-head"><h2><?= $total ?> modèle(s)</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Marque</th><th>Modèle</th><th>Série</th><th class="num">Produits</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $m): ?>
          <tr class="<?= $m['active'] ? '' : 'is-muted' ?>">
            <td><?= e($m['brand_name']) ?></td>
            <td><strong><?= e($m['name']) ?></strong><br><code class="muted small"><?= e($m['slug']) ?></code></td>
            <td><?= e($m['series'] ?? '') ?></td>
            <td class="num"><a href="<?= e(admin_url('compatibility.php', ['action' => 'products', 'id' => $m['id']])) ?>"><?= (int)$m['n_products'] ?></a></td>
            <td><?= bool_badge($m['active']) ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('compatibility.php', ['action' => 'products', 'id' => $m['id']])) ?>"><?= aicon('box') ?> Produits</a>
              <a class="btn btn-sm" href="<?= e(admin_url('compatibility.php', ['action' => 'edit', 'id' => $m['id']])) ?>"><?= aicon('edit') ?></a>
              <form method="post" class="inline" action="<?= e(admin_url('compatibility.php', ['id' => $m['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete">
                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer ce modèle et ses liaisons de compatibilité ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Aucun modèle.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= pagination_links($pg) ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
