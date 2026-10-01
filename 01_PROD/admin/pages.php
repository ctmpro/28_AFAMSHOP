<?php
/**
 * Pages de contenu (CMS) : à propos, CGV, mentions légales, aide…
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('pages');

$groups = ['' => 'Hors pied de page', 'company' => 'Pied de page — Entreprise', 'help' => 'Pied de page — Aide', 'legal' => 'Pied de page — Informations légales'];
$action = (string)query_param('action');
$id = int_param('id');
$page = $id ? DB::one('SELECT * FROM pages WHERE id = :id', ['id' => $id]) : null;
if ($id && !$page) {
    flash('error', 'Page introuvable.');
    redirect(admin_url('pages.php'));
}
$errors = [];
$form = $page ?: ['slug' => '', 'title' => '', 'content' => '', 'meta_title' => '', 'meta_description' => '', 'footer_group' => '', 'published' => 1, 'sort' => 0];

if (is_post()) {
    require_csrf();
    $do = (string)post('do', 'save');
    if ($do === 'delete' && $page) {
        DB::exec('DELETE FROM pages WHERE id = :id', ['id' => $id]);
        AdminAuth::log('page_delete', 'page', $id, $page['slug']);
        flash('success', 'Page supprimée.');
        redirect(admin_url('pages.php'));
    }

    $in = [
        'title' => (string)post('title'),
        'content' => clean_html((string)($_POST['content'] ?? '')),
        'meta_title' => (string)post('meta_title') ?: null,
        'meta_description' => (string)post('meta_description') ?: null,
        'footer_group' => (string)post('footer_group') ?: null,
        'published' => post_bool('published'),
        'sort' => (int)post('sort'),
    ];
    $slug = (string)post('slug');
    if ($in['title'] === '') $errors[] = 'Le titre est obligatoire.';
    if ($in['footer_group'] !== null && !isset($groups[$in['footer_group']])) $errors[] = 'Groupe de pied de page invalide.';
    if ($slug !== '' && slugify($slug) !== $slug) $errors[] = 'Le slug ne doit contenir que des minuscules, chiffres et tirets (suggestion : « ' . slugify($slug) . ' »).';
    elseif ($slug !== '' && DB::val('SELECT id FROM pages WHERE slug = :s AND id <> :id', ['s' => $slug, 'id' => $id])) $errors[] = 'Ce slug est déjà utilisé par une autre page.';

    if ($errors) {
        $form = array_merge($form, $in, ['slug' => $slug]);
        $action = $page ? 'edit' : 'new';
    } else {
        $in['slug'] = $slug !== '' ? $slug : unique_slug('pages', $in['title'], $id ?: null);
        if ($page) {
            DB::update('pages', $in, 'id = :id', ['id' => $id]);
        } else {
            $id = DB::insert('pages', $in);
        }
        AdminAuth::log($page ? 'page_update' : 'page_create', 'page', $id, $in['slug']);
        flash('success', 'Page enregistrée.');
        redirect(admin_url('pages.php', ['action' => 'edit', 'id' => $id]));
    }
}

$rows = DB::all('SELECT id, slug, title, footer_group, published, sort, updated_at FROM pages ORDER BY footer_group IS NULL, footer_group, sort, title');
$showForm = in_array($action, ['new', 'edit'], true) || $errors;
$pageTitle = $showForm ? ($page ? 'Page : ' . $page['title'] : 'Nouvelle page') : 'Pages';
$pageActions = $showForm ? '<a class="btn" href="' . e(admin_url('pages.php')) . '">← Liste</a>' . ($page && $page['published'] ? '<a class="btn" target="_blank" rel="noopener" href="' . e(url('page/' . $page['slug'])) . '">' . aicon('eye') . ' Voir</a>' : '')
    : '<a class="btn btn-primary" href="' . e(admin_url('pages.php', ['action' => 'new'])) . '">' . aicon('plus') . ' Nouvelle page</a>';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($showForm): ?>
  <form method="post" action="<?= e(admin_url('pages.php', ['id' => $page['id'] ?? null])) ?>">
    <?= csrf_field() ?>
    <div class="grid grid-main">
      <div class="card">
        <?= field_input('title', 'Titre', $form['title'], ['required' => true, 'attrs' => ['maxlength' => 190]]) ?>
        <?= field_textarea('content', 'Contenu (HTML)', $form['content'], ['rows' => 22, 'html' => true, 'help' => 'Variables disponibles : {site_name}, {company_name}, {phone}, {email}, {address}, {domain}, {year}.']) ?>
      </div>
      <div class="stack">
        <div class="card">
          <h2>Publication</h2>
          <?= field_checkbox('published', 'Publiée', (bool)$form['published']) ?>
          <?= field_input('slug', 'Adresse (slug)', $form['slug'], ['help' => 'Ex. « cgv », « mentions-legales ». Vide = généré depuis le titre.', 'attrs' => ['maxlength' => 120, 'data-slug-from' => 'f_title']]) ?>
          <?= field_select('footer_group', 'Lien dans le pied de page', $groups, (string)$form['footer_group']) ?>
          <?= field_input('sort', 'Ordre', $form['sort'], ['type' => 'number']) ?>
          <?php if ($page): ?><p class="muted small">Dernière modification : <?= e(format_date($page['updated_at'], true)) ?></p><?php endif; ?>
        </div>
        <div class="card">
          <h2>Référencement</h2>
          <?= field_input('meta_title', 'Titre SEO', $form['meta_title'], ['attrs' => ['maxlength' => 255]]) ?>
          <?= field_textarea('meta_description', 'Méta-description', $form['meta_description'], ['rows' => 3, 'attrs' => ['maxlength' => 255]]) ?>
        </div>
      </div>
    </div>
    <div class="form-actions sticky">
      <button class="btn btn-primary" type="submit"><?= aicon('check') ?> Enregistrer</button>
      <a class="btn" href="<?= e(admin_url('pages.php')) ?>">Annuler</a>
    </div>
  </form>
<?php else: ?>
  <div class="card flush">
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Titre</th><th>Adresse</th><th>Pied de page</th><th class="num">Ordre</th><th>Statut</th><th>Modifiée</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
          <tr class="<?= $p['published'] ? '' : 'is-muted' ?>">
            <td><a href="<?= e(admin_url('pages.php', ['action' => 'edit', 'id' => $p['id']])) ?>"><strong><?= e($p['title']) ?></strong></a></td>
            <td><code>/page/<?= e($p['slug']) ?></code></td>
            <td><?= e($groups[(string)$p['footer_group']] ?? $p['footer_group']) ?></td>
            <td class="num"><?= (int)$p['sort'] ?></td>
            <td><?= bool_badge($p['published'], 'Publiée', 'Brouillon') ?></td>
            <td class="nowrap small"><?= e(format_date($p['updated_at'], true)) ?></td>
            <td class="actions">
              <a class="btn btn-sm" href="<?= e(admin_url('pages.php', ['action' => 'edit', 'id' => $p['id']])) ?>"><?= aicon('edit') ?> Modifier</a>
              <form method="post" class="inline" action="<?= e(admin_url('pages.php', ['id' => $p['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-sm btn-danger" type="submit" data-confirm="Supprimer la page « <?= e($p['title']) ?> » ?"><?= aicon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucune page.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
