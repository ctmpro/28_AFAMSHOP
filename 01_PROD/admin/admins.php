<?php
/**
 * Administrateurs (super administrateur uniquement) : comptes, rôles, activation, mot de passe.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$me = AdminAuth::require('admins');

$action = (string)query_param('action');
$id = int_param('id');
$target = $id ? DB::one('SELECT * FROM admins WHERE id = :id', ['id' => $id]) : null;
if ($id && !$target) {
    flash('error', 'Administrateur introuvable.');
    redirect(admin_url('admins.php'));
}
$isSelf = $target && (int)$target['id'] === (int)$me['id'];
$errors = [];
$form = $target ?: ['name' => '', 'email' => '', 'role' => 'editor', 'active' => 1];

/** Nombre de super administrateurs actifs, hors un identifiant donné. */
$otherSuperAdmins = fn(int $exceptId) => (int)DB::val("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND active = 1 AND id <> :id", ['id' => $exceptId]);

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');

    if ($do === 'delete' && $target) {
        if ($isSelf) {
            flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        } elseif ($target['role'] === 'super_admin' && !$otherSuperAdmins((int)$target['id'])) {
            flash('error', 'Impossible de supprimer le dernier super administrateur actif.');
        } else {
            DB::exec('DELETE FROM admins WHERE id = :id', ['id' => $target['id']]);
            AdminAuth::log('admin_delete', 'admin', (int)$target['id'], $target['email']);
            flash('success', 'Compte administrateur supprimé.');
        }
        redirect(admin_url('admins.php'));
    }

    $in = [
        'name' => (string)post('name'),
        'email' => strtolower((string)post('email')),
        'role' => (string)post('role'),
        'active' => post_bool('active'),
    ];
    $pwd = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    if ($isSelf) {
        // On ne peut ni se désactiver ni modifier son propre rôle
        $in['active'] = 1;
        $in['role'] = $target['role'];
    }
    if (mb_strlen($in['name']) < 2) $errors[] = 'Le nom est obligatoire.';
    if (!valid_email($in['email'])) $errors[] = 'Adresse email invalide.';
    elseif (DB::val('SELECT id FROM admins WHERE email = :e AND id <> :id', ['e' => $in['email'], 'id' => $id])) $errors[] = 'Cette adresse email est déjà utilisée.';
    if (!isset(AdminAuth::ROLES[$in['role']])) $errors[] = 'Rôle invalide.';
    if ($target && $target['role'] === 'super_admin' && ($in['role'] !== 'super_admin' || !$in['active']) && !$otherSuperAdmins((int)$target['id'])) {
        $errors[] = 'Il doit rester au moins un super administrateur actif.';
    }
    if (!$target || $pwd !== '') {
        $errors = array_merge($errors, admin_password_errors($pwd, $confirm));
    }

    if ($errors) {
        $form = array_merge($form, $in);
        $action = $target ? 'edit' : 'new';
    } else {
        if ($pwd !== '') {
            $in['password_hash'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        if ($target) {
            DB::update('admins', $in, 'id = :id', ['id' => $target['id']]);
            $newId = (int)$target['id'];
        } else {
            $newId = DB::insert('admins', $in);
        }
        AdminAuth::log($target ? 'admin_update' : 'admin_create', 'admin', $newId, ['email' => $in['email'], 'role' => $in['role'], 'active' => $in['active'], 'password_changed' => $pwd !== '']);
        flash('success', $target ? 'Compte mis à jour' . ($pwd !== '' ? ' (mot de passe réinitialisé).' : '.') : 'Compte administrateur créé.');
        redirect(admin_url('admins.php'));
    }
}

$rows = DB::all('SELECT * FROM admins ORDER BY active DESC, role, name');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Administrateurs';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('admins.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('admins.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvel administrateur</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <div class="grid grid-main">
    <form method="post" class="card" action="<?= e(admin_url('admins.php', ['id' => $target['id'] ?? null])) ?>" autocomplete="off">
      <?= csrf_field() ?>
      <h2><?= $target ? 'Modifier ' . e($target['name']) : 'Nouvel administrateur' ?></h2>
      <div class="form-grid">
        <?= field_input('name', 'Nom', $form['name'], ['required' => true, 'attrs' => ['maxlength' => 120]]) ?>
        <?= field_input('email', 'Email (identifiant)', $form['email'], ['type' => 'email', 'required' => true, 'attrs' => ['maxlength' => 190]]) ?>
        <?= field_select('role', 'Rôle', AdminAuth::ROLES, $form['role'], $isSelf ? ['attrs' => ['disabled' => true], 'help' => 'Vous ne pouvez pas modifier votre propre rôle.'] : []) ?>
        <?= field_checkbox('active', 'Compte actif', (bool)$form['active'], $isSelf ? ['help' => 'Vous ne pouvez pas désactiver votre propre compte.'] : []) ?>
        <?= field_input('password', $target ? 'Nouveau mot de passe (réinitialisation)' : 'Mot de passe', '', ['type' => 'password', 'required' => !$target, 'help' => ($target ? 'Laisser vide pour conserver le mot de passe actuel. ' : '') . '8 caractères minimum, avec majuscule, minuscule et chiffre.', 'attrs' => ['autocomplete' => 'new-password']]) ?>
        <?= field_input('password_confirm', 'Confirmation', '', ['type' => 'password', 'required' => !$target, 'attrs' => ['autocomplete' => 'new-password']]) ?>
      </div>
      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
        <a class="btn" href="<?= e(admin_url('admins.php')) ?>">Annuler</a>
      </div>
    </form>
    <div class="card">
      <h2>Droits par rôle</h2>
      <?= info_row(AdminAuth::ROLES['super_admin'], 'Accès complet, y compris administrateurs, journal et réglages boutique / paiement.') ?>
      <?php foreach (AdminAuth::PERMISSIONS as $role => $mods): ?>
        <?= info_row(AdminAuth::ROLES[$role] ?? $role, e(implode(', ', $mods))) ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Dernière connexion</th><th>Créé le</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $a): $self = (int)$a['id'] === (int)$me['id']; ?>
          <tr class="<?= $a['active'] ? '' : 'is-muted' ?>">
            <td><strong><?= e($a['name']) ?></strong><?= $self ? ' ' . badge('Vous', 'info') : '' ?></td>
            <td><?= e($a['email']) ?></td>
            <td><?= badge(AdminAuth::ROLES[$a['role']] ?? $a['role'], $a['role'] === 'super_admin' ? 'primary' : 'muted') ?></td>
            <td class="nowrap"><?= e(format_date($a['last_login'], true)) ?: '<span class="muted">jamais</span>' ?></td>
            <td class="nowrap"><?= e(format_date($a['created_at'])) ?></td>
            <td><?= bool_badge($a['active']) ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('admins.php', ['action' => 'edit', 'id' => $a['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
              <?php if (!$self): ?>
                <form method="post" class="inline" action="<?= e(admin_url('admins.php', ['id' => $a['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer le compte de <?= e($a['name']) ?> ?"><?= aicon('trash') ?></button></form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
