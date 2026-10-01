<?php
/**
 * Profil de l'administrateur connecté : nom et mot de passe (mot de passe actuel requis).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$me = AdminAuth::require('dashboard');

$errors = [];
if (is_post()) {
    require_csrf();
    $name = (string)post('name');
    $current = (string)($_POST['current_password'] ?? '');
    $pwd = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    $hash = (string)DB::val('SELECT password_hash FROM admins WHERE id = :id', ['id' => $me['id']]);

    if (mb_strlen($name) < 2) $errors[] = 'Le nom est obligatoire.';
    if (!password_verify($current, $hash)) $errors[] = 'Le mot de passe actuel est incorrect.';
    if ($pwd !== '') {
        $errors = array_merge($errors, admin_password_errors($pwd, $confirm));
        if (password_verify($pwd, $hash)) $errors[] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
    }

    if (!$errors) {
        $data = ['name' => $name];
        if ($pwd !== '') {
            $data['password_hash'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        DB::update('admins', $data, 'id = :id', ['id' => $me['id']]);
        if ($pwd !== '') {
            session_regenerate_id(true);
        }
        AdminAuth::log('profile_update', 'admin', (int)$me['id'], ['password_changed' => $pwd !== '']);
        flash('success', $pwd !== '' ? 'Profil et mot de passe mis à jour.' : 'Profil mis à jour.');
        redirect(admin_url('profile.php'));
    }
    $me['name'] = $name;
}

$pageTitle = 'Mon profil';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="grid grid-main">
  <form method="post" class="card" autocomplete="off">
    <?= csrf_field() ?>
    <h2>Informations</h2>
    <div class="form-grid">
      <?= field_input('name', 'Nom', $me['name'], ['required' => true, 'attrs' => ['maxlength' => 120]]) ?>
      <?= field_input('email_ro', 'Email (identifiant)', $me['email'], ['type' => 'email', 'attrs' => ['readonly' => true], 'help' => 'Contactez un super administrateur pour le modifier.']) ?>
    </div>
    <h2 class="mt">Changer de mot de passe</h2>
    <div class="form-grid">
      <?= field_input('password', 'Nouveau mot de passe', '', ['type' => 'password', 'help' => 'Laisser vide pour le conserver. 8 caractères minimum, avec majuscule, minuscule et chiffre.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
      <?= field_input('password_confirm', 'Confirmation', '', ['type' => 'password', 'attrs' => ['autocomplete' => 'new-password']]) ?>
    </div>
    <?= field_input('current_password', 'Mot de passe actuel (obligatoire pour valider)', '', ['type' => 'password', 'required' => true, 'attrs' => ['autocomplete' => 'current-password']]) ?>
    <div class="form-actions"><button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button></div>
  </form>
  <div class="card">
    <h2>Mon compte</h2>
    <?= info_row('Rôle', e(AdminAuth::ROLES[$me['role']] ?? $me['role'])) ?>
    <?= info_row('Dernière connexion', e(format_date($me['last_login'], true))) ?>
    <?= info_row('Compte créé le', e(format_date($me['created_at']))) ?>
    <h3 class="mt">Modules accessibles</h3>
    <p class="small"><?= $me['role'] === 'super_admin' ? 'Tous les modules.' : e(implode(', ', AdminAuth::PERMISSIONS[$me['role']] ?? [])) ?></p>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
