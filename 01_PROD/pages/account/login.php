<?php
/**
 * Connexion client.
 */
if (Auth::check()) redirect('compte');
$return = safe_return((string)input('retour'), 'compte');
$error = null;
if (is_post()) {
    require_csrf();
    $error = Auth::attempt((string)post('email'), (string)($_POST['password'] ?? ''));
    if (!$error) {
        flash('success', __('welcome_back', ['name' => Auth::user()['first_name']]));
        redirect($return);
    }
}
$pageTitle = __('login');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container auth-layout">
  <section class="card auth-card">
    <h1><?= e(__('login')) ?></h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="<?= e($return) ?>">
      <label><?= e(__('email')) ?><input type="email" name="email" value="<?= e(post('email')) ?>" required autocomplete="email" maxlength="190"></label>
      <label><?= e(__('password')) ?><input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-primary btn-block" type="submit"><?= e(__('login')) ?></button>
      <p><a href="<?= e(url('compte/mot-de-passe-oublie')) ?>"><?= e(__('forgot_password')) ?></a></p>
    </form>
  </section>
  <section class="card auth-card auth-alt">
    <h2><?= e(__('register')) ?></h2>
    <p>Suivez vos commandes, téléchargez vos factures, enregistrez vos adresses et vos favoris, retrouvez vos demandes de devis.</p>
    <a class="btn btn-outline btn-block" href="<?= e(url('compte/inscription', ['retour' => $return])) ?>"><?= e(__('register')) ?></a>
    <?php if (Cart::count()): ?><a class="btn btn-link btn-block" href="<?= e(url('commande')) ?>"><?= e(__('guest_checkout')) ?> →</a><?php endif; ?>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
