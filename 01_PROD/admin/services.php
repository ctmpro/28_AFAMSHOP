<?php
/**
 * Services (location, maintenance, conseil…) : contenu, image, formulaire associé.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('services');

$formTypes = ['' => 'Aucun formulaire', 'rental' => 'Demande de location', 'maintenance' => 'Demande de maintenance', 'quote' => 'Demande de devis'];
$action = (string)query_param('action');
$id = int_param('id');
$service = $id ? DB::one('SELECT * FROM services WHERE id = :id', ['id' => $id]) : null;
if ($id && !$service) {
    flash('error', 'Service introuvable.');
    redirect(admin_url('services.php'));
}
$errors = [];
$form = $service ?: ['slug' => '', 'title' => '', 'icon' => '', 'short_desc' => '', 'content' => '', 'image' => null, 'form_type' => '', 'sort' => 0, 'active' => 1];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');
    if ($do === 'delete' && $service) {
        DB::exec('DELETE FROM services WHERE id = :id', ['id' => $id]);
        delete_upload($service['image']);
        AdminAuth::log('service_delete', 'service', $id, $service['slug']);
        flash('success', 'Service supprimé.');
        redirect(admin_url('services.php'));
    }

    $in = [
        'title' => (string)post('title'),
        'icon' => preg_replace('/[^a-z0-9_-]/i', '', (string)post('icon')) ?: null,
        'short_desc' => (string)post('short_desc') ?: null,
        'content' => clean_html((string)($_POST['content'] ?? '')),
        'form_type' => (string)post('form_type') ?: null,
        'sort' => (int)post('sort'),
        'active' => post_bool('active'),
    ];
    $slug = (string)post('slug');
    if ($in['title'] === '') $errors[] = 'Le titre est obligatoire.';
    if ($in['form_type'] !== null && !isset($formTypes[$in['form_type']])) $errors[] = 'Type de formulaire invalide.';
    if ($slug !== '' && slugify($slug) !== $slug) $errors[] = 'Le slug ne doit contenir que des minuscules, chiffres et tirets.';
    elseif ($slug !== '' && DB::val('SELECT id FROM services WHERE slug = :s AND id <> :id', ['s' => $slug, 'id' => $id])) $errors[] = 'Ce slug est déjà utilisé.';
    if (!$errors) {
        try {
            $in['image'] = admin_process_file('image', 'services', $service['image'] ?? null);
        } catch (RuntimeException $e) {
            $errors[] = 'Image : ' . $e->getMessage();
        }
    }
    if ($errors) {
        admin_files_rollback();
        $form = array_merge($form, $in, ['slug' => $slug]);
        $action = $service ? 'edit' : 'new';
    } else {
        $in['slug'] = $slug !== '' ? $slug : unique_slug('services', $in['title'], $id ?: null);
        if ($service) {
            DB::update('services', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('services', $in);
        }
        admin_files_commit();
        AdminAuth::log($service ? 'service_update' : 'service_create', 'service', $id, $in['slug']);
        flash('success', 'Service enregistré.');
        redirect(admin_url('services.php'));
    }
}

$rows = DB::all('SELECT * FROM services ORDER BY sort, title');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = $showForm ? ($service ? 'Service : ' . $service['title'] : 'Nouveau service') : 'Services';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('services.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('services.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouveau service</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" enctype="multipart/form-data" action="<?= e(admin_url('services.php', ['id' => $service['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <div class="grid grid-main">
      <div class="card">
        <?= field_input('title', 'Titre', $form['title'], ['required' => true, 'attrs' => ['maxlength' => 190]]) ?>
        <?= field_input('short_desc', 'Description courte', $form['short_desc'], ['attrs' => ['maxlength' => 255], 'help' => 'Affichée sur les cartes de services.']) ?>
        <?= field_textarea('content', 'Contenu de la page (HTML)', $form['content'], ['rows' => 18, 'html' => true]) ?>
      </div>
      <div class="stack">
        <div class="card">
          <h2>Paramètres</h2>
          <?= field_checkbox('active', 'Actif', (bool)$form['active']) ?>
          <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['help' => 'Vide = généré depuis le titre.', 'attrs' => ['maxlength' => 120, 'data-slug-from' => 'f_title']]) ?>
          <?= field_input('icon', 'Icône', $form['icon'], ['help' => 'printer, wrench, key, briefcase, truck, shield, leaf…', 'attrs' => ['maxlength' => 40]]) ?>
          <?= field_select('form_type', 'Formulaire affiché sur la page', $formTypes, (string)$form['form_type']) ?>
          <?= field_input('sort', 'Ordre', $form['sort'], ['type' => 'number']) ?>
        </div>
        <div class="card">
          <?= field_file('image', 'Image', $form['image']) ?>
        </div>
      </div>
    </div>
    <div class="form-actions sticky">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('services.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th></th><th>Service</th><th>Adresse</th><th>Formulaire</th><th class="num">Ordre</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $s): ?>
          <tr class="<?= $s['active'] ? '' : 'is-muted' ?>">
            <td><?php if ($s['image']): ?><img class="thumb" src="<?= e(media_url($s['image'])) ?>" alt=""><?php else: ?><?= icon($s['icon'] ?: 'box', 'aicon') ?><?php endif; ?></td>
            <td><a href="<?= e(admin_url('services.php', ['action' => 'edit', 'id' => $s['id']])) ?>"><strong><?= e($s['title']) ?></strong></a><br><span class="muted small"><?= e(truncate($s['short_desc'], 90)) ?></span></td>
            <td><code><?= e($s['slug']) ?></code></td>
            <td><?= e($formTypes[(string)$s['form_type']] ?? $s['form_type']) ?></td>
            <td class="num"><?= (int)$s['sort'] ?></td>
            <td><?= bool_badge($s['active']) ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('services.php', ['action' => 'edit', 'id' => $s['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
              <form method="post" class="inline" action="<?= e(admin_url('services.php', ['id' => $s['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer le service « <?= e($s['title']) ?> » ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucun service.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
