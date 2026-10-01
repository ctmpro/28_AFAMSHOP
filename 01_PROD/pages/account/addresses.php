<?php
/**
 * Espace client : carnet d'adresses.
 */
$u = Auth::require();
$zones = Cart::zones();
$errors = [];
if (is_post()) {
    require_csrf();
    $id = int_param('id');
    $owned = $id ? DB::one('SELECT * FROM addresses WHERE id = :id AND customer_id = :c', ['id' => $id, 'c' => $u['id']]) : null;
    $action = post('action');
    if ($action === 'delete' && $owned) {
        DB::exec('DELETE FROM addresses WHERE id = :id', ['id' => $id]);
        flash('success', __('deleted'));
        redirect('compte/adresses');
    }
    if ($action === 'default' && $owned) {
        DB::exec('UPDATE addresses SET is_default = (id = :id) WHERE customer_id = :c', ['id' => $id, 'c' => $u['id']]);
        redirect('compte/adresses');
    }
    if ($action === 'save') {
        $d = [];
        foreach (['label' => 60, 'full_name' => 190, 'phone' => 40, 'address' => 255, 'city' => 120] as $k => $max) $d[$k] = mb_substr(trim((string)post($k)), 0, $max);
        $d['zone_id'] = int_param('zone_id') ?: null;
        foreach (['full_name' => __('full_name'), 'address' => __('address'), 'city' => __('city')] as $k => $l) {
            if ($d[$k] === '') $errors[] = __('field_required', ['field' => $l]);
        }
        if (!$errors) {
            if ($owned) {
                DB::update('addresses', $d, 'id = :id', ['id' => $id]);
            } else {
                $d['customer_id'] = $u['id'];
                $d['is_default'] = DB::val('SELECT COUNT(*) FROM addresses WHERE customer_id = :c', ['c' => $u['id']]) ? 0 : 1;
                DB::insert('addresses', $d);
            }
            flash('success', __('saved'));
            redirect('compte/adresses');
        }
    }
}
$addresses = DB::all('SELECT a.*, z.name zone_name FROM addresses a LEFT JOIN delivery_zones z ON z.id = a.zone_id WHERE a.customer_id = :c ORDER BY a.is_default DESC, a.id', ['c' => $u['id']]);
$edit = int_param('modifier') ? DB::one('SELECT * FROM addresses WHERE id = :id AND customer_id = :c', ['id' => int_param('modifier'), 'c' => $u['id']]) : null;
$active = 'addresses';
$pageTitle = __('my_addresses');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_addresses')) ?></h1>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="address-grid">
      <?php if (!$addresses): ?><p class="muted"><?= e(__('no_addresses')) ?></p><?php endif; ?>
      <?php foreach ($addresses as $a): ?>
      <div class="card address-card <?= $a['is_default'] ? 'default' : '' ?>">
        <?php if ($a['is_default']): ?><span class="badge badge-primary"><?= e(__('default_address')) ?></span><?php endif; ?>
        <strong><?= e($a['label'] ?: $a['full_name']) ?></strong>
        <p><?= e($a['full_name']) ?><br><?= e($a['address']) ?><br><?= e($a['city']) ?><?= $a['zone_name'] ? ' — ' . e($a['zone_name']) : '' ?><br><?= e($a['phone']) ?></p>
        <div class="row-actions">
          <a class="btn btn-link btn-sm" href="<?= e(url('compte/adresses', ['modifier' => $a['id']])) ?>"><?= e(__('edit')) ?></a>
          <?php if (!$a['is_default']): ?>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-link btn-sm" name="action" value="default"><?= e(__('default_address')) ?></button></form>
          <?php endif; ?>
          <form method="post" data-confirm="Supprimer cette adresse ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="btn btn-link btn-sm danger" name="action" value="delete"><?= e(__('delete')) ?></button></form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="card">
      <h2><?= e($edit ? __('edit') : __('add_address')) ?></h2>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
      <div class="form-grid">
        <label><?= e(__('address_label')) ?><input type="text" name="label" value="<?= e($edit['label'] ?? '') ?>" maxlength="60"></label>
        <label><?= e(__('full_name')) ?> *<input type="text" name="full_name" value="<?= e($edit['full_name'] ?? $u['first_name'] . ' ' . $u['last_name']) ?>" required maxlength="190"></label>
        <label><?= e(__('phone')) ?><input type="tel" name="phone" value="<?= e($edit['phone'] ?? $u['phone']) ?>" maxlength="40"></label>
        <label><?= e(__('delivery_zone')) ?>
          <select name="zone_id"><option value="">—</option><?php foreach ($zones as $z): ?><option value="<?= (int)$z['id'] ?>" <?= (int)($edit['zone_id'] ?? 0) === (int)$z['id'] ? 'selected' : '' ?>><?= e($z['name']) ?></option><?php endforeach; ?></select>
        </label>
        <label class="span-2"><?= e(__('address')) ?> *<input type="text" name="address" value="<?= e($edit['address'] ?? '') ?>" required maxlength="255"></label>
        <label><?= e(__('city')) ?> *<input type="text" name="city" value="<?= e($edit['city'] ?? '') ?>" required maxlength="120"></label>
      </div>
      <button class="btn btn-primary" type="submit"><?= e(__('save')) ?></button>
    </form>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
