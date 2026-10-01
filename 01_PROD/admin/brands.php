<?php
/**
 * Marques : liste, création / modification (logo, mise en avant, ordre), suppression.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('brands');

$action = (string)query_param('action');
$id = int_param('id');
$brand = $id ? DB::one('SELECT * FROM brands WHERE id = :id', ['id' => $id]) : null;
if ($id && !$brand) {
    flash('error', 'Marque introuvable.');
    redirect(admin_url('brands.php'));
}
$errors = [];
$form = $brand ?: ['name' => '', 'slug' => '', 'logo' => null, 'description' => '', 'featured' => 0, 'active' => 1, 'sort' => 0];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');

    if ($do === 'delete' && $brand) {
        $products = (int)DB::val('SELECT COUNT(*) FROM products WHERE brand_id = :id', ['id' => $id]);
        $models = (int)DB::val('SELECT COUNT(*) FROM printer_models WHERE brand_id = :id', ['id' => $id]);
        if ($products) {
            flash('error', 'Impossible de supprimer : ' . $products . ' produit(s) sont rattachés à cette marque. Désactivez-la plutôt.');
        } else {
            DB::exec('DELETE FROM brands WHERE id = :id', ['id' => $id]); // supprime aussi ses modèles d'imprimantes (cascade)
            delete_upload($brand['logo']);
            AdminAuth::log('brand_delete', 'brand', $id, $brand['name']);
            flash('success', 'Marque supprimée' . ($models ? ' ainsi que ses ' . $models . ' modèle(s) d\'imprimantes.' : '.'));
        }
        redirect(admin_url('brands.php'));
    }

    $in = [
        'name' => (string)post('name'),
        'slug' => (string)post('slug'),
        'description' => (string)post('description') ?: null,
        'featured' => post_bool('featured'),
        'active' => post_bool('active'),
        'sort' => (int)post('sort'),
    ];
    if ($in['name'] === '') $errors[] = 'Le nom est obligatoire.';
    elseif (DB::val('SELECT id FROM brands WHERE name = :n AND id <> :id', ['n' => $in['name'], 'id' => $id])) $errors[] = 'Une marque porte déjà ce nom.';
    if (!$errors) {
        try {
            $in['logo'] = admin_process_file('logo', 'brands', $brand['logo'] ?? null);
        } catch (RuntimeException $e) {
            $errors[] = 'Logo : ' . $e->getMessage();
        }
    }
    if ($errors) {
        $form = array_merge($form, $in);
        $action = $brand ? 'edit' : 'new';
    } else {
        $in['slug'] = unique_slug('brands', $in['slug'] !== '' ? $in['slug'] : $in['name'], $id ?: null);
        if ($brand) {
            DB::update('brands', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('brands', $in);
        }
        AdminAuth::log($brand ? 'brand_update' : 'brand_create', 'brand', $id, $in['name']);
        flash('success', $brand ? 'Marque enregistrée.' : 'Marque créée.');
        redirect(admin_url('brands.php'));
    }
}

$rows = DB::all('SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS n_products,
    (SELECT COUNT(*) FROM printer_models m WHERE m.brand_id = b.id) AS n_models FROM brands b ORDER BY b.featured DESC, b.sort, b.name');

$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Marques';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('brands.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('brands.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle marque</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" enctype="multipart/form-data" class="card" action="<?= e(admin_url('brands.php', ['id' => $brand['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $brand ? 'Modifier la marque' : 'Nouvelle marque' ?></h2>
    <div class="form-grid">
      <?= field_input('name', 'Nom', $form['name'], ['required' => true, 'attrs' => ['maxlength' => 120]]) ?>
      <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['help' => 'Laisser vide pour la générer.', 'attrs' => ['data-slug-from' => 'f_name', 'maxlength' => 140]]) ?>
      <?= field_textarea('description', 'Description', $form['description'], ['class' => 'span-2', 'rows' => 3]) ?>
      <?= field_file('logo', 'Logo', $form['logo'], ['class' => 'span-2']) ?>
      <?= field_input('sort', 'Ordre d\'affichage', $form['sort'], ['type' => 'number']) ?>
      <div>
        <?= field_checkbox('featured', 'Marque mise en avant (accueil)', (bool)$form['featured']) ?>
        <?= field_checkbox('active', 'Active', (bool)$form['active']) ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('brands.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Logo</th><th>Marque</th><th class="num">Produits</th><th class="num">Modèles</th><th>Mise en avant</th><th>Statut</th><th class="num">Ordre</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b): ?>
          <tr class="<?= $b['active'] ? '' : 'is-muted' ?>">
            <td><?php if ($b['logo']): ?><img class="thumb" src="<?= e(media_url($b['logo'])) ?>" alt=""><?php endif; ?></td>
            <td><a href="<?= e(admin_url('brands.php', ['action' => 'edit', 'id' => $b['id']])) ?>"><strong><?= e($b['name']) ?></strong></a><br><code class="muted"><?= e($b['slug']) ?></code></td>
            <td class="num"><a href="<?= e(admin_url('products.php', ['brand' => $b['id']])) ?>"><?= (int)$b['n_products'] ?></a></td>
            <td class="num"><a href="<?= e(admin_url('compatibility.php', ['brand' => $b['id']])) ?>"><?= (int)$b['n_models'] ?></a></td>
            <td><?= $b['featured'] ? badge('Oui', 'primary') : '<span class="muted">—</span>' ?></td>
            <td><?= bool_badge($b['active']) ?></td>
            <td class="num"><?= (int)$b['sort'] ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('brands.php', ['action' => 'edit', 'id' => $b['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
              <form method="post" class="inline" action="<?= e(admin_url('brands.php', ['id' => $b['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete">
                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer la marque « <?= e($b['name']) ?> » et ses modèles d'imprimantes ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Aucune marque.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
