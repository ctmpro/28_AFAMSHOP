<?php
/**
 * Mot de passe oublié : envoi d'un lien de réinitialisation valable 1 heure.
 */
$sent = false;
if (is_post()) {
    require_csrf();
    $email = strtolower(trim((string)post('email')));
    if (valid_email($email) && !too_many_attempts('reset', $email, 3, 60)) {
        record_attempt('reset', $email);
        $u = DB::one("SELECT * FROM customers WHERE email = :e AND status = 'active'", ['e' => $email]);
        if ($u) {
            $token = random_token(32);
            DB::update('customers', ['reset_token' => hash('sha256', $token), 'reset_expires' => date('Y-m-d H:i:s', time() + 3600)], 'id = :id', ['id' => $u['id']]);
            Mailer::sendTemplate('password_reset', $u['email'], [
                'first_name' => $u['first_name'],
                'link' => url('compte/reinitialisation', ['email' => $u['email'], 'token' => $token]),
            ]);
        }
    }
    $sent = true; // Même message dans tous les cas (pas d'énumération des comptes)
}
$pageTitle = __('forgot_password');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container narrow">
  <section class="card auth-card">
    <h1><?= e(__('reset_password')) ?></h1>
    <?php if ($sent): ?>
      <div class="alert alert-success"><?= e(__('reset_link_sent')) ?></div>
    <?php else: ?>
    <form method="post">
      <?= csrf_field() ?>
      <label><?= e(__('email')) ?><input type="email" name="email" required maxlength="190" autocomplete="email"></label>
      <button class="btn btn-primary btn-block" type="submit"><?= e(__('send')) ?></button>
    </form>
    <?php endif; ?>
    <p><a href="<?= e(url('compte/connexion')) ?>">← <?= e(__('login')) ?></a></p>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
