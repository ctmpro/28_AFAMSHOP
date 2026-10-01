<?php
/**
 * Réinitialisation du mot de passe via le lien reçu par email.
 */
$email = strtolower((string)input('email'));
$token = (string)input('token');
$u = ($email && $token) ? DB::one('SELECT * FROM customers WHERE email = :e AND reset_token IS NOT NULL AND reset_expires > NOW()', ['e' => $email]) : null;
$valid = $u && hash_equals($u['reset_token'], hash('sha256', $token));
$error = null;
if ($valid && is_post()) {
    require_csrf();
    $error = Auth::passwordError((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''));
    if (!$error) {
        DB::update('customers', ['password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT), 'reset_token' => null, 'reset_expires' => null], 'id = :id', ['id' => $u['id']]);
        clear_attempts('customer', $email);
        flash('success', __('password_updated'));
        redirect('compte/connexion');
    }
}
$pageTitle = __('reset_password');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container narrow">
  <section class="card auth-card">
    <h1><?= e(__('reset_password')) ?></h1>
    <?php if (!$valid): ?>
      <div class="alert alert-error"><?= e(__('reset_invalid')) ?></div>
      <a class="btn btn-primary" href="<?= e(url('compte/mot-de-passe-oublie')) ?>"><?= e(__('forgot_password')) ?></a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($email) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label><?= e(__('new_password')) ?><input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label><?= e(__('password_confirm')) ?><input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
        <p class="muted small"><?= e(__('password_rules')) ?></p>
        <button class="btn btn-primary btn-block" type="submit"><?= e(__('save')) ?></button>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
