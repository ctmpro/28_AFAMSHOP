<?php
/**
 * Catégories : arborescence, création / modification / suppression.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('categories');

$action = (string)query_param('action');
$id = int_param('id');
$cat = $id ? DB::one('SELECT * FROM categories WHERE id = :id', ['id' => $id]) : null;
if ($id && !$cat) {
    flash('error', 'Catégorie introuvable.');
    redirect(admin_url('categories.php'));
}
$errors = [];
$form = $cat ?: ['name' => '', 'slug' => '', 'parent_id' => '', 'description' => '', 'image' => null, 'icon' => '', 'sort' => 0, 'show_in_menu' => 1, 'active' => 1];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');

    if ($do === 'delete' && $cat) {
        $children = (int)DB::val('SELECT COUNT(*) FROM categories WHERE parent_id = :id', ['id' => $id]);
        $products = (int)DB::val('SELECT COUNT(*) FROM products WHERE category_id = :id', ['id' => $id]);
        if ($children) {
            flash('error', 'Impossible de supprimer : cette catégorie contient ' . $children . ' sous-catégorie(s).');
        } else {
            DB::exec('UPDATE products SET category_id = NULL WHERE category_id = :id', ['id' => $id]);
            DB::exec('DELETE FROM categories WHERE id = :id', ['id' => $id]);
            delete_upload($cat['image']);
            AdminAuth::log('category_delete', 'category', $id, $cat['name']);
            flash('success', 'Catégorie supprimée' . ($products ? ' (' . $products . ' produit(s) désormais sans catégorie).' : '.'));
        }
        redirect(admin_url('categories.php'));
    }

    if ($do === 'sort') {
        foreach ((array)($_POST['sort'] ?? []) as $cid => $s) {
            DB::update('categories', ['sort' => (int)$s], 'id = :id', ['id' => (int)$cid]);
        }
        AdminAuth::log('category_sort', 'category');
        flash('success', 'Ordre enregistré.');
        redirect(admin_url('categories.php'));
    }

    $in = [
        'name' => (string)post('name'),
        'slug' => (string)post('slug'),
        'parent_id' => (int)post('parent_id') ?: null,
        'description' => (string)post('description') ?: null,
        'icon' => preg_replace('/[^a-z0-9_-]/i', '', (string)post('icon')) ?: null,
        'sort' => (int)post('sort'),
        'show_in_menu' => post_bool('show_in_menu'),
        'active' => post_bool('active'),
    ];
    if ($in['name'] === '') $errors[] = 'Le nom est obligatoire.';
    if ($in['parent_id']) {
        if ($cat && in_array($in['parent_id'], Catalog::categoryDescendants($id), true)) {
            $errors[] = 'Une catégorie ne peut pas être rangée dans elle-même ou dans une de ses sous-catégories.';
        } elseif (!DB::val('SELECT id FROM categories WHERE id = :id', ['id' => $in['parent_id']])) {
            $errors[] = 'Catégorie parente invalide.';
        }
    }
    if (!$errors) {
        try {
            $in['image'] = admin_process_file('image', 'categories', $cat['image'] ?? null);
        } catch (RuntimeException $e) {
            $errors[] = 'Image : ' . $e->getMessage();
        }
    }
    if ($errors) {
        admin_files_rollback();
        $form = array_merge($form, $in);
        $action = $cat ? 'edit' : 'new';
    } else {
        $in['slug'] = unique_slug('categories', $in['slug'] !== '' ? $in['slug'] : $in['name'], $id ?: null);
        if ($cat) {
            DB::update('categories', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('categories', $in);
        }
        admin_files_commit();
        AdminAuth::log($cat ? 'category_update' : 'category_create', 'category', $id, $in['name']);
        flash('success', $cat ? 'Catégorie enregistrée.' : 'Catégorie créée.');
        redirect(admin_url('categories.php'));
    }
}

// Arbre aplati pour l'affichage
$counts = [];
foreach (DB::all('SELECT category_id, COUNT(*) n FROM products WHERE category_id IS NOT NULL GROUP BY category_id') as $r) {
    $counts[(int)$r['category_id']] = (int)$r['n'];
}
$flat = [];
$walk = function (array $nodes, int $depth) use (&$walk, &$flat) {
    foreach ($nodes as $n) {
        $n['depth'] = $depth;
        $flat[] = $n;
        $walk($n['children'], $depth + 1);
    }
};
$walk(Catalog::categoryTree(false), 0);

$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Catégories';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('categories.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('categories.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle catégorie</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" enctype="multipart/form-data" class="card" action="<?= e(admin_url('categories.php', ['id' => $cat['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $cat ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h2>
    <div class="form-grid">
      <?= field_input('name', 'Nom', $form['name'], ['required' => true, 'attrs' => ['maxlength' => 150]]) ?>
      <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['help' => 'Laisser vide pour la générer.', 'attrs' => ['data-slug-from' => 'f_name', 'maxlength' => 170]]) ?>
      <?= field_select('parent_id', 'Catégorie parente', category_options($cat ? (int)$cat['id'] : null), $form['parent_id'], ['placeholder' => '— Aucune (racine) —']) ?>
      <?= field_input('icon', 'Icône', $form['icon'], ['help' => 'Nom d\'icône du site : printer, drop, laptop, pen, tag, wrench, box…', 'attrs' => ['maxlength' => 40]]) ?>
      <?= field_textarea('description', 'Description', $form['description'], ['class' => 'span-2', 'rows' => 3]) ?>
      <?= field_file('image', 'Image', $form['image'], ['class' => 'span-2']) ?>
      <?= field_input('sort', 'Ordre d\'affichage', $form['sort'], ['type' => 'number']) ?>
      <div>
        <?= field_checkbox('show_in_menu', 'Afficher dans le menu', (bool)$form['show_in_menu']) ?>
        <?= field_checkbox('active', 'Active', (bool)$form['active']) ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('categories.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <form method="post" class="card flush">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="sort">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th></th><th>Catégorie</th><th>Slug</th><th class="num">Produits</th><th>Menu</th><th>Statut</th><th>Ordre</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($flat as $c): ?>
          <tr class="<?= $c['active'] ? '' : 'is-muted' ?>">
            <td><?php if ($c['image']): ?><img class="thumb" src="<?= e(media_url($c['image'])) ?>" alt=""><?php endif; ?></td>
            <td><span class="tree-indent"><?= str_repeat('│&nbsp;&nbsp;', max(0, $c['depth'] - 1)) . ($c['depth'] ? '└─ ' : '') ?></span><a href="<?= e(admin_url('categories.php', ['action' => 'edit', 'id' => $c['id']])) ?>"><strong><?= e($c['name']) ?></strong></a></td>
            <td><code><?= e($c['slug']) ?></code></td>
            <td class="num"><a href="<?= e(admin_url('products.php', ['category' => $c['id']])) ?>"><?= $counts[(int)$c['id']] ?? 0 ?></a></td>
            <td><?= bool_badge($c['show_in_menu'], 'Oui', 'Non') ?></td>
            <td><?= bool_badge($c['active']) ?></td>
            <td><input type="number" name="sort[<?= (int)$c['id'] ?>]" value="<?= (int)$c['sort'] ?>" style="width:80px" aria-label="Ordre"></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('categories.php', ['action' => 'edit', 'id' => $c['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
              <button class="btn btn-sm btn-danger" type="submit" form="del-<?= (int)$c['id'] ?>" data-confirm="Supprimer la catégorie « <?= e($c['name']) ?> » ?"><?= aicon('trash') ?></button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$flat): ?><tr><td colspan="8" class="table-empty">Aucune catégorie.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($flat): ?><div class="bulk-bar"><button class="btn" type="submit">Enregistrer l'ordre</button></div><?php endif; ?>
  </form>
  <?php foreach ($flat as $c): ?>
    <form method="post" id="del-<?= (int)$c['id'] ?>" action="<?= e(admin_url('categories.php', ['id' => $c['id']])) ?>" hidden><?= csrf_field() ?><input type="hidden" name="do" value="delete"></form>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
