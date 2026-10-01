<?php
/**
 * Espace client : changement de mot de passe.
 */
$u = Auth::require();
$error = null;
if (is_post()) {
    require_csrf();
    if (!password_verify((string)($_POST['current_password'] ?? ''), $u['password_hash'])) {
        $error = __('current_password') . ' : ' . __('bad_credentials');
    } else {
        $error = Auth::passwordError((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
    }
    if (!$error) {
        DB::update('customers', ['password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        session_regenerate_id(true);
        flash('success', __('password_updated'));
        redirect('compte/mot-de-passe');
    }
}
$active = 'password';
$pageTitle = __('change_password');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('change_password')) ?></h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card narrow-form">
      <?= csrf_field() ?>
      <label><?= e(__('current_password')) ?><input type="password" name="current_password" required autocomplete="current-password"></label>
      <label><?= e(__('new_password')) ?><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
      <p class="muted small"><?= e(__('password_rules')) ?></p>
      <button class="btn btn-primary" type="submit"><?= e(__('save')) ?></button>
    </form>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
