<?php
/**
 * Création de compte client.
 */
if (Auth::check()) redirect('compte');
$return = safe_return((string)input('retour'), 'compte');
$errors = [];
$f = ['account_type' => 'individual', 'first_name' => '', 'last_name' => '', 'company' => '', 'email' => '', 'phone' => ''];
if (is_post()) {
    require_csrf();
    foreach ($f as $k => $v) $f[$k] = mb_substr(trim((string)post($k)), 0, 190);
    $f['email'] = strtolower($f['email']);
    if (!in_array($f['account_type'], ['individual', 'business', 'administration'], true)) $f['account_type'] = 'individual';
    foreach (['first_name' => __('first_name'), 'last_name' => __('last_name'), 'email' => __('email')] as $k => $l) {
        if ($f[$k] === '') $errors[] = __('field_required', ['field' => $l]);
    }
    if ($f['email'] !== '' && !valid_email($f['email'])) $errors[] = __('invalid_email');
    if ($f['email'] !== '' && DB::val('SELECT id FROM customers WHERE email = :e', ['e' => $f['email']])) $errors[] = __('email_taken');
    if ($err = Auth::passwordError((string)($_POST['password'] ?? ''), (string)($_POST['password_confirm'] ?? ''))) $errors[] = $err;
    if (!post('terms')) $errors[] = __('must_accept_terms');
    if (post('website') !== '' || (!$errors && !Recaptcha::verify())) $errors[] = __('captcha_error');
    if (!$errors) {
        $id = DB::insert('customers', $f + ['password_hash' => password_hash((string)$_POST['password'], PASSWORD_DEFAULT)]);
        Auth::login($id);
        Mailer::sendTemplate('welcome', $f['email'], ['first_name' => $f['first_name']]);
        flash('success', __('account_created'));
        redirect($return);
    }
}
$pageTitle = __('register');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container narrow">
  <section class="card auth-card">
    <h1><?= e(__('register')) ?></h1>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="<?= e($return) ?>">
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <fieldset class="choice-inline">
        <legend><?= e(__('account_type')) ?></legend>
        <?php foreach (['individual', 'business', 'administration'] as $t): ?>
          <label class="check"><input type="radio" name="account_type" value="<?= $t ?>" <?= $f['account_type'] === $t ? 'checked' : '' ?>> <?= e(__('type_' . $t)) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="form-grid">
        <label><?= e(__('first_name')) ?> *<input type="text" name="first_name" value="<?= e($f['first_name']) ?>" required maxlength="100" autocomplete="given-name"></label>
        <label><?= e(__('last_name')) ?> *<input type="text" name="last_name" value="<?= e($f['last_name']) ?>" required maxlength="100" autocomplete="family-name"></label>
        <label class="span-2"><?= e(__('company')) ?><input type="text" name="company" value="<?= e($f['company']) ?>" maxlength="190" autocomplete="organization"></label>
        <label><?= e(__('email')) ?> *<input type="email" name="email" value="<?= e($f['email']) ?>" required maxlength="190" autocomplete="email"></label>
        <label><?= e(__('phone')) ?><input type="tel" name="phone" value="<?= e($f['phone']) ?>" maxlength="40" autocomplete="tel"></label>
        <label><?= e(__('password')) ?> *<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label><?= e(__('password_confirm')) ?> *<input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"></label>
      </div>
      <p class="muted small"><?= e(__('password_rules')) ?></p>
      <label class="check"><input type="checkbox" name="terms" value="1" required> <span><?= e(__('accept_terms')) ?> — <a href="<?= e(url('confidentialite')) ?>" target="_blank">Politique de confidentialité</a></span></label>
      <?= Recaptcha::widget('register') ?>
      <button class="btn btn-primary btn-block" type="submit"><?= e(__('register')) ?></button>
      <p><?= e(__('have_account')) ?> <a href="<?= e(url('compte/connexion', ['retour' => $return])) ?>"><?= e(__('login')) ?></a></p>
    </form>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
