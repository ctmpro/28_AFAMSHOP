<?php
/**
 * Promotions automatiques : pourcentage ou montant, sur un produit, une catégorie, une marque ou tout le catalogue.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('promotions');

$scopes = ['all' => 'Tout le catalogue', 'product' => 'Un produit', 'category' => 'Une catégorie (et ses sous-catégories)', 'brand' => 'Une marque'];
$types = ['percent' => 'Pourcentage (%)', 'amount' => 'Montant fixe (' . base_currency() . ')'];

$action = (string)query_param('action');
$id = int_param('id');
$promo = $id ? DB::one('SELECT * FROM promotions WHERE id = :id', ['id' => $id]) : null;
if ($id && !$promo) {
    flash('error', 'Promotion introuvable.');
    redirect(admin_url('promotions.php'));
}
$errors = [];
$form = $promo ?: ['name' => '', 'discount_type' => 'percent', 'value' => '', 'scope' => 'product', 'target_id' => null, 'start_at' => null, 'end_at' => null, 'active' => 1];
$form['target_sku'] = ($form['scope'] === 'product' && $form['target_id']) ? (string)DB::val('SELECT sku FROM products WHERE id = :id', ['id' => $form['target_id']]) : '';

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');
    if ($do === 'delete' && $promo) {
        DB::exec('DELETE FROM promotions WHERE id = :id', ['id' => $id]);
        AdminAuth::log('promotion_delete', 'promotion', $id, $promo['name']);
        flash('success', 'Promotion supprimée.');
        redirect(admin_url('promotions.php'));
    }
    if ($do === 'toggle' && $promo) {
        DB::update('promotions', ['active' => $promo['active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        AdminAuth::log('promotion_toggle', 'promotion', $id, $promo['active'] ? 'off' : 'on');
        flash('success', $promo['active'] ? 'Promotion désactivée.' : 'Promotion activée.');
        redirect(admin_url('promotions.php'));
    }

    $in = [
        'name' => (string)post('name'),
        'discount_type' => (string)post('discount_type'),
        'value' => admin_dec(post('value')),
        'scope' => (string)post('scope'),
        'target_id' => null,
        'start_at' => admin_dt_in(post('start_at')),
        'end_at' => admin_dt_in(post('end_at')),
        'active' => post_bool('active'),
    ];
    $targetSku = (string)post('target_sku');
    if ($in['name'] === '') $errors[] = 'Le nom est obligatoire.';
    if (!isset($types[$in['discount_type']])) $errors[] = 'Type de remise invalide.';
    if ($in['value'] === null || $in['value'] <= 0) $errors[] = 'La valeur doit être un nombre positif.';
    elseif ($in['discount_type'] === 'percent' && $in['value'] > 100) $errors[] = 'Un pourcentage ne peut pas dépasser 100.';
    if (!isset($scopes[$in['scope']])) {
        $errors[] = 'Portée invalide.';
    } elseif ($in['scope'] === 'product') {
        $in['target_id'] = (int)DB::val('SELECT id FROM products WHERE sku = :s', ['s' => $targetSku]) ?: null;
        if (!$in['target_id']) $errors[] = 'Produit introuvable pour le SKU « ' . $targetSku . ' ».';
    } elseif ($in['scope'] === 'category') {
        $in['target_id'] = (int)post('target_category') ?: null;
        if (!$in['target_id'] || !DB::val('SELECT id FROM categories WHERE id = :id', ['id' => $in['target_id']])) $errors[] = 'Choisissez une catégorie.';
    } elseif ($in['scope'] === 'brand') {
        $in['target_id'] = (int)post('target_brand') ?: null;
        if (!$in['target_id'] || !DB::val('SELECT id FROM brands WHERE id = :id', ['id' => $in['target_id']])) $errors[] = 'Choisissez une marque.';
    }
    if ($in['start_at'] && $in['end_at'] && $in['end_at'] < $in['start_at']) $errors[] = 'La date de fin doit être postérieure à la date de début.';

    if ($errors) {
        $form = array_merge($form, $in, ['value' => post('value'), 'target_sku' => $targetSku]);
        $action = $promo ? 'edit' : 'new';
    } else {
        if ($promo) {
            DB::update('promotions', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('promotions', $in);
        }
        AdminAuth::log($promo ? 'promotion_update' : 'promotion_create', 'promotion', $id, $in['name']);
        flash('success', 'Promotion enregistrée.');
        redirect(admin_url('promotions.php'));
    }
}

$rows = DB::all("SELECT pr.*,
    CASE pr.scope WHEN 'product' THEN (SELECT CONCAT(p.name, ' (', p.sku, ')') FROM products p WHERE p.id = pr.target_id)
                  WHEN 'category' THEN (SELECT c.name FROM categories c WHERE c.id = pr.target_id)
                  WHEN 'brand' THEN (SELECT b.name FROM brands b WHERE b.id = pr.target_id) END AS target_name
    FROM promotions pr ORDER BY pr.active DESC, pr.created_at DESC");

/** État calculé d'une période (programmée, en cours, expirée). */
$periodBadge = function (array $r): string {
    $now = date('Y-m-d H:i:s');
    if (!$r['active']) return badge('Inactive');
    if ($r['start_at'] && $r['start_at'] > $now) return badge('Programmée', 'info');
    if ($r['end_at'] && $r['end_at'] < $now) return badge('Expirée', 'muted');
    return badge('En cours', 'success');
};

$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Promotions';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('promotions.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('promotions.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle promotion</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" class="card" action="<?= e(admin_url('promotions.php', ['id' => $promo['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $promo ? 'Modifier la promotion' : 'Nouvelle promotion' ?></h2>
    <div class="form-grid">
      <?= field_input('name', 'Nom (interne)', $form['name'], ['required' => true, 'class' => 'span-2', 'attrs' => ['maxlength' => 150]]) ?>
      <?= field_select('discount_type', 'Type de remise', $types, $form['discount_type']) ?>
      <?= field_input('value', 'Valeur', $form['value'], ['required' => true, 'attrs' => ['inputmode' => 'decimal']]) ?>
      <?= field_select('scope', 'S\'applique à', $scopes, $form['scope']) ?>
      <div>
        <div data-show-when="f_scope:product"><?= field_input('target_sku', 'SKU du produit', $form['target_sku'] ?? '') ?></div>
        <div data-show-when="f_scope:category"><?= field_select('target_category', 'Catégorie', category_options(), $form['scope'] === 'category' ? $form['target_id'] : '', ['placeholder' => '— Choisir —']) ?></div>
        <div data-show-when="f_scope:brand"><?= field_select('target_brand', 'Marque', brand_options(), $form['scope'] === 'brand' ? $form['target_id'] : '', ['placeholder' => '— Choisir —']) ?></div>
      </div>
      <?= field_input('start_at', 'Début', admin_dt_out($form['start_at']), ['type' => 'datetime-local', 'help' => 'Vide = immédiatement.']) ?>
      <?= field_input('end_at', 'Fin', admin_dt_out($form['end_at']), ['type' => 'datetime-local', 'help' => 'Vide = sans limite.']) ?>
      <?= field_checkbox('active', 'Active', (bool)$form['active']) ?>
    </div>
    <p class="help">Le prix affiché est le plus bas entre le prix promotionnel du produit et les promotions applicables.</p>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('promotions.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Nom</th><th>Remise</th><th>Portée</th><th>Période</th><th>État</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="<?= e(admin_url('promotions.php', ['action' => 'edit', 'id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong></a></td>
            <td class="nowrap"><?= $r['discount_type'] === 'percent' ? '−' . e((string)(float)$r['value']) . ' %' : '−' . e(admin_money($r['value'])) ?></td>
            <td><?= e($scopes[$r['scope']] ?? $r['scope']) ?><?= $r['target_name'] ? '<br><span class="muted small">' . e($r['target_name']) . '</span>' : '' ?></td>
            <td class="small nowrap"><?= $r['start_at'] ? 'du ' . e(format_date($r['start_at'], true)) : 'dès maintenant' ?><br><?= $r['end_at'] ? 'au ' . e(format_date($r['end_at'], true)) : 'sans fin' ?></td>
            <td><?= $periodBadge($r) ?></td>
            <td class="actions">
              <form method="post" class="inline" action="<?= e(admin_url('promotions.php', ['id' => $r['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="btn btn-sm" type="submit"><?= $r['active'] ? 'Désactiver' : 'Activer' ?></button></form>
              <a class="btn btn-sm" href="<?= e(admin_url('promotions.php', ['action' => 'edit', 'id' => $r['id']])) ?>"><?= aicon('edit') ?></a>
              <form method="post" class="inline" action="<?= e(admin_url('promotions.php', ['id' => $r['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer cette promotion ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="table-empty">Aucune promotion.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
