<?php
/**
 * Espace client : informations personnelles.
 */
$u = Auth::require();
$errors = [];
if (is_post()) {
    require_csrf();
    $d = [];
    foreach (['first_name' => 100, 'last_name' => 100, 'company' => 190, 'phone' => 40, 'email' => 190] as $k => $max) $d[$k] = mb_substr(trim((string)post($k)), 0, $max);
    $d['email'] = strtolower($d['email']);
    $d['account_type'] = in_array(post('account_type'), ['individual', 'business', 'administration'], true) ? post('account_type') : $u['account_type'];
    foreach (['first_name' => __('first_name'), 'last_name' => __('last_name'), 'email' => __('email')] as $k => $l) {
        if ($d[$k] === '') $errors[] = __('field_required', ['field' => $l]);
    }
    if ($d['email'] !== '' && !valid_email($d['email'])) $errors[] = __('invalid_email');
    if ($d['email'] !== $u['email']) {
        if (DB::val('SELECT id FROM customers WHERE email = :e AND id <> :id', ['e' => $d['email'], 'id' => $u['id']])) $errors[] = __('email_taken');
        if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password_hash'])) $errors[] = __('current_password') . ' : ' . __('bad_credentials');
    }
    if (!$errors) {
        $d['company'] = $d['company'] ?: null;
        DB::update('customers', $d, 'id = :id', ['id' => $u['id']]);
        flash('success', __('profile_updated'));
        redirect('compte/profil');
    }
    $u = array_merge($u, $d);
}
$active = 'profile';
$pageTitle = __('my_profile');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_profile')) ?></h1>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" class="card">
      <?= csrf_field() ?>
      <fieldset class="choice-inline">
        <legend><?= e(__('account_type')) ?></legend>
        <?php foreach (['individual', 'business', 'administration'] as $t): ?>
          <label class="check"><input type="radio" name="account_type" value="<?= $t ?>" <?= $u['account_type'] === $t ? 'checked' : '' ?>> <?= e(__('type_' . $t)) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="form-grid">
        <label><?= e(__('first_name')) ?> *<input type="text" name="first_name" value="<?= e($u['first_name']) ?>" required maxlength="100"></label>
        <label><?= e(__('last_name')) ?> *<input type="text" name="last_name" value="<?= e($u['last_name']) ?>" required maxlength="100"></label>
        <label><?= e(__('company')) ?><input type="text" name="company" value="<?= e($u['company']) ?>" maxlength="190"></label>
        <label><?= e(__('phone')) ?><input type="tel" name="phone" value="<?= e($u['phone']) ?>" maxlength="40"></label>
        <label><?= e(__('email')) ?> *<input type="email" name="email" value="<?= e($u['email']) ?>" required maxlength="190"></label>
        <label><?= e(__('current_password')) ?> <small>(si changement d'email)</small><input type="password" name="current_password" autocomplete="current-password"></label>
      </div>
      <button class="btn btn-primary" type="submit"><?= e(__('save')) ?></button>
    </form>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
