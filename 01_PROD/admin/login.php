<?php
/**
 * Connexion au back-office (CSRF + limitation des tentatives via AdminAuth::attempt).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';

$retour = (string)input('retour', '');

if (AdminAuth::user()) {
    redirect(admin_safe_return($retour));
}

// Aucun administrateur : rediriger vers l'assistant de création
if ((int)DB::val('SELECT COUNT(*) FROM admins') === 0) {
    redirect(admin_url('setup.php'));
}

$error = null;
$email = '';
if (is_post()) {
    require_csrf();
    $email = (string)post('email');
    $password = (string)($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Veuillez saisir votre email et votre mot de passe.';
    } else {
        $error = AdminAuth::attempt($email, $password);
        if ($error === null) {
            redirect(admin_safe_return($retour));
        }
    }
}

$primary = setting('color_primary', '#0b4f8a');
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Connexion · <?= e(setting('site_name', 'AFAMSHOP')) ?> Admin</title>
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
<style>:root{--primary:<?= preg_match('/^#[0-9a-f]{3,8}$/i', $primary) ? e($primary) : '#0b4f8a' ?>;}</style>
</head>
<body class="admin auth">
  <div class="auth-card">
    <div class="auth-brand"><?= e(setting('site_name', 'AFAMSHOP')) ?></div>
    <h1>Espace d'administration</h1>
    <?php foreach (get_flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type'] === 'error' ? 'danger' : $f['type']) ?>"><span><?= e($f['message']) ?></span></div>
    <?php endforeach; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><span><?= e($error) ?></span></div>
    <?php endif; ?>
    <form method="post" action="<?= e(admin_url('login.php')) ?>" autocomplete="on">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="<?= e($retour) ?>">
      <?= field_input('email', 'Adresse email', $email, ['type' => 'email', 'required' => true, 'attrs' => ['autocomplete' => 'username', 'autofocus' => true]]) ?>
      <?= field_input('password', 'Mot de passe', '', ['type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]) ?>
      <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    <p class="muted small center mt"><a href="<?= e(url('')) ?>">← Retour au site</a></p>
  </div>
</body>
</html>
