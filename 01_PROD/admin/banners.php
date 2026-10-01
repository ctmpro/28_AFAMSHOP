<?php
/**
 * Bannières de l'accueil : textes, lien, images desktop / mobile, programmation, ordre.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('banners');

$positions = ['home_hero' => 'Accueil — carrousel principal', 'home_promo' => 'Accueil — bandeau promotionnel'];
$action = (string)query_param('action');
$id = int_param('id');
$banner = $id ? DB::one('SELECT * FROM banners WHERE id = :id', ['id' => $id]) : null;
if ($id && !$banner) {
    flash('error', 'Bannière introuvable.');
    redirect(admin_url('banners.php'));
}
$errors = [];
$form = $banner ?: ['position' => 'home_hero', 'title' => '', 'subtitle' => '', 'button_text' => '', 'link' => '', 'image_desktop' => null, 'image_mobile' => null, 'start_at' => null, 'end_at' => null, 'active' => 1, 'sort' => 0];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');
    if ($do === 'delete' && $banner) {
        DB::exec('DELETE FROM banners WHERE id = :id', ['id' => $id]);
        delete_upload($banner['image_desktop']);
        delete_upload($banner['image_mobile']);
        AdminAuth::log('banner_delete', 'banner', $id, $banner['title']);
        flash('success', 'Bannière supprimée.');
        redirect(admin_url('banners.php'));
    }
    if ($do === 'toggle' && $banner) {
        DB::update('banners', ['active' => $banner['active'] ? 0 : 1], 'id = :id', ['id' => $id]);
        AdminAuth::log('banner_toggle', 'banner', $id);
        flash('success', 'Bannière mise à jour.');
        redirect(admin_url('banners.php'));
    }

    $in = [
        'position' => (string)post('position'),
        'title' => (string)post('title') ?: null,
        'subtitle' => (string)post('subtitle') ?: null,
        'button_text' => (string)post('button_text') ?: null,
        'link' => (string)post('link') ?: null,
        'start_at' => admin_dt_in(post('start_at')),
        'end_at' => admin_dt_in(post('end_at')),
        'active' => post_bool('active'),
        'sort' => (int)post('sort'),
    ];
    if (!isset($positions[$in['position']])) $errors[] = 'Emplacement invalide.';
    if ($in['link'] && !preg_match('#^(https?://|/|\#)#i', $in['link'])) $errors[] = 'Le lien doit commencer par http(s)://, / ou #.';
    if ($in['start_at'] && $in['end_at'] && $in['end_at'] < $in['start_at']) $errors[] = 'La date de fin doit être postérieure à la date de début.';
    if (!$errors) {
        try {
            $in['image_desktop'] = admin_process_file('image_desktop', 'banners', $banner['image_desktop'] ?? null);
            $in['image_mobile'] = admin_process_file('image_mobile', 'banners', $banner['image_mobile'] ?? null);
        } catch (RuntimeException $e) {
            $errors[] = 'Image : ' . $e->getMessage();
        }
    }
    if (!$errors && !$in['image_desktop']) $errors[] = 'L\'image desktop est obligatoire.';

    if ($errors) {
        admin_files_rollback();
        $form = array_merge($form, $in);
        $action = $banner ? 'edit' : 'new';
    } else {
        if ($banner) {
            DB::update('banners', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('banners', $in);
        }
        admin_files_commit();
        AdminAuth::log($banner ? 'banner_update' : 'banner_create', 'banner', $id, $in['title']);
        flash('success', 'Bannière enregistrée.');
        redirect(admin_url('banners.php'));
    }
}

$rows = DB::all('SELECT * FROM banners ORDER BY position, sort, id');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = 'Bannières';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('banners.php')) . '">← Liste</a>'
    : '<a class="btn btn-primary" href="' . e(admin_url('banners.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle bannière</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" enctype="multipart/form-data" class="card" action="<?= e(admin_url('banners.php', ['id' => $banner['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <h2><?= $banner ? 'Modifier la bannière' : 'Nouvelle bannière' ?></h2>
    <div class="form-grid">
      <?= field_select('position', 'Emplacement', $positions, $form['position']) ?>
      <?= field_input('sort', 'Ordre', $form['sort'], ['type' => 'number']) ?>
      <?= field_input('title', 'Titre', $form['title'], ['attrs' => ['maxlength' => 190]]) ?>
      <?= field_input('subtitle', 'Sous-titre', $form['subtitle'], ['attrs' => ['maxlength' => 255]]) ?>
      <?= field_input('button_text', 'Texte du bouton', $form['button_text'], ['attrs' => ['maxlength' => 80]]) ?>
      <?= field_input('link', 'Lien', $form['link'], ['attrs' => ['maxlength' => 255, 'placeholder' => '/categorie/consommables ou https://…']]) ?>
      <?= field_file('image_desktop', 'Image desktop (obligatoire)', $form['image_desktop'], ['help' => 'Format paysage conseillé : 1920 × 640 px.']) ?>
      <?= field_file('image_mobile', 'Image mobile (facultative)', $form['image_mobile'], ['help' => 'Format conseillé : 800 × 900 px. À défaut, l\'image desktop est utilisée.']) ?>
      <?= field_input('start_at', 'Afficher à partir du', admin_dt_out($form['start_at']), ['type' => 'datetime-local']) ?>
      <?= field_input('end_at', 'Jusqu\'au', admin_dt_out($form['end_at']), ['type' => 'datetime-local']) ?>
      <?= field_checkbox('active', 'Active', (bool)$form['active']) ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('banners.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Aperçu</th><th>Titre</th><th>Emplacement</th><th>Programmation</th><th class="num">Ordre</th><th>État</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b):
            $now = date('Y-m-d H:i:s');
            $state = !$b['active'] ? badge('Inactive') : (($b['start_at'] && $b['start_at'] > $now) ? badge('Programmée', 'info') : (($b['end_at'] && $b['end_at'] < $now) ? badge('Expirée') : badge('En ligne', 'success'))); ?>
          <tr>
            <td><img src="<?= e(media_url($b['image_desktop'])) ?>" alt="" style="width:140px;height:56px;object-fit:cover;border-radius:6px"></td>
            <td><a href="<?= e(admin_url('banners.php', ['action' => 'edit', 'id' => $b['id']])) ?>"><strong><?= e($b['title'] ?: '(sans titre)') ?></strong></a><br><span class="muted small"><?= e(truncate($b['subtitle'], 80)) ?></span></td>
            <td><?= e($positions[$b['position']] ?? $b['position']) ?></td>
            <td class="small nowrap"><?= $b['start_at'] ? 'du ' . e(format_date($b['start_at'], true)) : 'immédiate' ?><br><?= $b['end_at'] ? 'au ' . e(format_date($b['end_at'], true)) : 'sans fin' ?></td>
            <td class="num"><?= (int)$b['sort'] ?></td>
            <td><?= $state ?></td>
            <td class="actions">
              <form method="post" class="inline" action="<?= e(admin_url('banners.php', ['id' => $b['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><button class="btn btn-sm" type="submit"><?= $b['active'] ? 'Désactiver' : 'Activer' ?></button></form>
              <a class="btn btn-sm" href="<?= e(admin_url('banners.php', ['action' => 'edit', 'id' => $b['id']])) ?>"><?= aicon('edit') ?></a>
              <form method="post" class="inline" action="<?= e(admin_url('banners.php', ['id' => $b['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer cette bannière ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucune bannière.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
