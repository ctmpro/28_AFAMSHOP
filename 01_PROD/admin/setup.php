<?php
/**
 * Création du premier super administrateur.
 * Disponible uniquement tant que la table admins est vide (sinon 404).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';

if ((int)DB::val('SELECT COUNT(*) FROM admins') > 0) {
    http_response_code(404);
    exit('Page introuvable.');
}

$errors = [];
$name = $email = '';
if (is_post()) {
    require_csrf();
    $name = (string)post('name');
    $email = strtolower((string)post('email'));
    $pwd = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    if (mb_strlen($name) < 2) $errors[] = 'Le nom est obligatoire.';
    if (!valid_email($email)) $errors[] = 'Adresse email invalide.';
    $errors = array_merge($errors, admin_password_errors($pwd, $confirm));
    if (!$errors) {
        // Nouvelle vérification juste avant l'insertion (évite une double création)
        if ((int)DB::val('SELECT COUNT(*) FROM admins') > 0) {
            http_response_code(404);
            exit('Page introuvable.');
        }
        $id = DB::insert('admins', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($pwd, PASSWORD_DEFAULT),
            'role' => 'super_admin',
            'active' => 1,
        ]);
        AdminAuth::log('setup', 'admin', $id, $email);
        flash('success', 'Compte super administrateur créé. Vous pouvez vous connecter.');
        redirect(admin_url('login.php'));
    }
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Installation · Administration</title>
<link rel="stylesheet" href="<?= e(asset('admin/admin.css')) ?>">
</head>
<body class="admin auth">
  <div class="auth-card">
    <div class="auth-brand"><?= e(setting('site_name', 'AFAMSHOP')) ?></div>
    <h1>Créer le premier administrateur</h1>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <?= field_input('name', 'Nom complet', $name, ['required' => true]) ?>
      <?= field_input('email', 'Email', $email, ['type' => 'email', 'required' => true]) ?>
      <?= field_input('password', 'Mot de passe', '', ['type' => 'password', 'required' => true, 'help' => '8 caractères minimum, avec majuscule, minuscule et chiffre.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
      <?= field_input('password_confirm', 'Confirmation', '', ['type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'new-password']]) ?>
      <button type="submit" class="btn btn-primary">Créer le compte</button>
    </form>
  </div>
</body>
</html>
