<?php
/**
 * Codes promo : création / modification, limites d'utilisation, période de validité.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('coupons');

$types = ['percent' => 'Pourcentage (%)', 'amount' => 'Montant fixe (' . base_currency() . ')'];
$action = (string)query_param('action');
$id = int_param('id');
$coupon = $id ? DB::one('SELECT * FROM coupons WHERE id = :id', ['id' => $id]) : null;
if ($id && !$coupon) {
    flash('error', 'Code promo introuvable.');
    redirect(admin_url('coupons.php'));
}
$errors = [];
$form = $coupon ?: ['code' => '', 'discount_type' => 'percent', 'value' => '', 'min_amount' => '', 'max_uses' => '', 'per_customer_limit' => '', 'start_at' => null, 'end_at' => null, 'active' => 1, 'uses' => 0];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');
    if ($do === 'delete' && $coupon) {
        DB::exec('DELETE FROM coupons WHERE id = :id', ['id' => $id]);
        AdminAuth::log('coupon_delete', 'coupon', $id, $coupon['code']);
        flash('success', 'Code promo supprimé.');
        redirect(admin_url('coupons.php'));
    }
    if ($do === 'toggle' && $coupon) {
        DB::update('coupons', ['active' => $coupon['active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        AdminAuth::log('coupon_toggle', 'coupon', $id, $coupon['code']);
        flash('success', $coupon['active'] ? 'Code désactivé.' : 'Code activé.');
        redirect(admin_url('coupons.php'));
    }

    $in = [
        'code' => strtoupper(preg_replace('/\s+/', '', (string)post('code'))),
        'discount_type' => (string)post('discount_type'),
        'value' => admin_dec(post('value')),
        'min_amount' => admin_dec(post('min_amount')),
        'max_uses' => admin_int_or_null(post('max_uses')),
        'per_customer_limit' => admin_int_or_null(post('per_customer_limit')),
        'start_at' => admin_dt_in(post('start_at')),
        'end_at' => admin_dt_in(post('end_at')),
        'active' => post_bool('active'),
    ];
    if (!preg_match('/^[A-Z0-9_-]{3,50}$/', $in['code'])) $errors[] = 'Le code doit contenir 3 à 50 caractères (lettres, chiffres, - ou _).';
    elseif (DB::val('SELECT id FROM coupons WHERE code = :c AND id <> :id', ['c' => $in['code'], 'id' => $id])) $errors[] = 'Ce code existe déjà.';
    if (!isset($types[$in['discount_type']])) $errors[] = 'Type de remise invalide.';
    if ($in['value'] === null || $in['value'] <= 0) $errors[] = 'La valeur doit être un nombre positif.';
    elseif ($in['discount_type'] === 'percent' && $in['value'] > 100) $errors[] = 'Un pourcentage ne peut pas dépasser 100.';
    if ($in['min_amount'] !== null && $in['min_amount'] < 0) $errors[] = 'Montant minimum invalide.';
    if ($in['max_uses'] !== null && $in['max_uses'] < 1) $errors[] = 'Le nombre maximum d\'utilisations doit être au moins 1.';
    if ($in['per_customer_limit'] !== null && $in['per_customer_limit'] < 1) $errors[] = 'La limite par client doit être au moins 1.';
    if ($in['start_at'] && $in['end_at'] && $in['end_at'] < $in['start_at']) $errors[] = 'La date de fin doit être postérieure à la date de début.';

    if ($errors) {
        $form = array_merge($form, $in, ['value' => post('value'), 'min_amount' => post('min_amount')]);
        $action = $coupon ? 'edit' : 'new';
    } else {
        if ($coupon) {
            DB::update('coupons', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('coupons', $in);
        }
        AdminAuth::log($coupon ? 'coupon_update' : 'coupon_create', 'coupon', $id, $in['code']);
        flash('success', 'Code promo enregistré.');
        redirect(admin_url('coupons.php'));
    }
}

$rows = DB::all('SELECT c.*, (SELECT COALESCE(SUM(o.discount),0) FROM coupon_usages u JOIN orders o ON o.id = u.order_id WHERE u.coupon_id = c.id) AS total_discount FROM coupons c ORDER BY c.active DESC, c.created_at DESC');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Codes promo';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('coupons.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('coupons.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouveau code</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" class="card" action="<?= e(admin_url('coupons.php', ['id' => $coupon['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $coupon ? 'Modifier le code ' . e($coupon['code']) : 'Nouveau code promo' ?></h2>
    <div class="form-grid">
      <?= field_input('code', 'Code', $form['code'], ['required' => true, 'help' => 'Converti en majuscules, sans espace.', 'attrs' => ['maxlength' => 50, 'style' => 'text-transform:uppercase']]) ?>
      <?= field_select('discount_type', 'Type de remise', $types, $form['discount_type']) ?>
      <?= field_input('value', 'Valeur', $form['value'], ['required' => true, 'attrs' => ['inputmode' => 'decimal']]) ?>
      <?= field_input('min_amount', 'Montant minimum du panier', $form['min_amount'], ['attrs' => ['inputmode' => 'decimal'], 'help' => 'Vide = aucun minimum.']) ?>
      <?= field_input('max_uses', 'Nombre maximum d\'utilisations', $form['max_uses'], ['type' => 'number', 'attrs' => ['min' => 1], 'help' => 'Vide = illimité. Utilisé ' . (int)$form['uses'] . ' fois.']) ?>
      <?= field_input('per_customer_limit', 'Limite par client', $form['per_customer_limit'], ['type' => 'number', 'attrs' => ['min' => 1], 'help' => 'Vide = illimité.']) ?>
      <?= field_input('start_at', 'Début de validité', admin_dt_out($form['start_at']), ['type' => 'datetime-local']) ?>
      <?= field_input('end_at', 'Fin de validité', admin_dt_out($form['end_at']), ['type' => 'datetime-local']) ?>
      <?= field_checkbox('active', 'Actif', (bool)$form['active']) ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('coupons.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Code</th><th>Remise</th><th>Conditions</th><th class="num">Utilisations</th><th class="num">Remises accordées</th><th>Validité</th><th>État</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $now = date('Y-m-d H:i:s');
            $state = !$r['active'] ? badge('Inactif')
                : (($r['end_at'] && $r['end_at'] < $now) ? badge('Expiré')
                : (($r['max_uses'] && $r['uses'] >= $r['max_uses']) ? badge('Épuisé', 'warning')
                : (($r['start_at'] && $r['start_at'] > $now) ? badge('Programmé', 'info') : badge('Actif', 'success')))); ?>
          <tr>
            <td><a href="<?= e(admin_url('coupons.php', ['action' => 'edit', 'id' => $r['id']])) ?>"><code><strong><?= e($r['code']) ?></strong></code></a></td>
            <td class="nowrap"><?= $r['discount_type'] === 'percent' ? '−' . e((string)(float)$r['value']) . ' %' : '−' . e(admin_money($r['value'])) ?></td>
            <td class="small"><?= $r['min_amount'] ? 'Dès ' . e(admin_money($r['min_amount'])) : 'Sans minimum' ?><?= $r['per_customer_limit'] ? '<br>' . (int)$r['per_customer_limit'] . ' / client' : '' ?></td>
            <td class="num"><?= (int)$r['uses'] ?><?= $r['max_uses'] ? ' / ' . (int)$r['max_uses'] : '' ?></td>
            <td class="num"><?= e(admin_money($r['total_discount'])) ?></td>
            <td class="small nowrap"><?= $r['start_at'] ? 'du ' . e(format_date($r['start_at'], true)) : 'dès maintenant' ?><br><?= $r['end_at'] ? 'au ' . e(format_date($r['end_at'], true)) : 'sans fin' ?></td>
            <td><?= $state ?></td>
            <td class="actions">
              <form method="post" class="inline" action="<?= e(admin_url('coupons.php', ['id' => $r['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="btn btn-sm" type="submit"><?= $r['active'] ? 'Désactiver' : 'Activer' ?></button></form>
              <a class="btn btn-sm" href="<?= e(admin_url('coupons.php', ['action' => 'edit', 'id' => $r['id']])) ?>"><?= aicon('edit') ?></a>
              <form method="post" class="inline" action="<?= e(admin_url('coupons.php', ['id' => $r['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer ce code promo ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="table-empty">Aucun code promo.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
