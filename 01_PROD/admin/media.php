<?php
/**
 * Médiathèque : envoi d'images, vidéos et PDF dans uploads/media, URL copiable, suppression.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('media');

$mediaTypes = UPLOAD_IMAGE_TYPES + UPLOAD_VIDEO_TYPES + ['application/pdf' => 'pdf'];

if (is_post()) {
    require_csrf();
    $do = (string)post('do');
    if ($do === 'upload') {
        $ok = 0;
        foreach (files_list($_FILES['files'] ?? null) as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            try {
                $tmpMime = ($file['error'] === UPLOAD_ERR_OK && is_file($file['tmp_name'])) ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
                $isVideo = isset(UPLOAD_VIDEO_TYPES[$tmpMime]);
                $path = handle_upload($file, 'media', $mediaTypes, $isVideo ? 64 * 1024 * 1024 : null);
                if (!$path) continue;
                $full = UPLOADS_PATH . '/' . $path;
                $mid = DB::insert('media', [
                    'path' => $path,
                    'original' => mb_substr(basename((string)$file['name']), 0, 255),
                    'mime' => (new finfo(FILEINFO_MIME_TYPE))->file($full) ?: null,
                    'size' => (int)filesize($full),
                    'admin_id' => AdminAuth::id(),
                ]);
                AdminAuth::log('media_upload', 'media', $mid, $path);
                $ok++;
            } catch (RuntimeException $e) {
                flash('error', ($file['name'] ?? 'Fichier') . ' : ' . $e->getMessage());
            }
        }
        if ($ok) flash('success', $ok . ' fichier(s) ajouté(s) à la médiathèque.');
        elseif (!isset($_SESSION['_flash'])) flash('warning', 'Aucun fichier envoyé.');
    } elseif ($do === 'delete') {
        $m = DB::one('SELECT * FROM media WHERE id = :id', ['id' => (int)post('id')]);
        if ($m) {
            DB::exec('DELETE FROM media WHERE id = :id', ['id' => $m['id']]);
            delete_upload($m['path']);
            AdminAuth::log('media_delete', 'media', (int)$m['id'], $m['path']);
            flash('success', 'Fichier supprimé. Pensez à retirer les liens qui l\'utilisaient.');
        }
    }
    back(admin_url('media.php'));
}

$type = (string)query_param('type');
$q = (string)query_param('q');
$where = ['1=1'];
$params = [];
if ($type === 'image') $where[] = "m.mime LIKE 'image/%'";
elseif ($type === 'video') $where[] = "m.mime LIKE 'video/%'";
elseif ($type === 'pdf') $where[] = "m.mime = 'application/pdf'";
if ($q !== '') {
    $where[] = '(m.original LIKE :q OR m.path LIKE :q)';
    $params['q'] = '%' . $q . '%';
}
$w = implode(' AND ', $where);
$total = (int)DB::val("SELECT COUNT(*) FROM media m WHERE $w", $params);
$pg = paginate($total, 48, (int)query_param('page', 1));
$rows = DB::all("SELECT m.*, a.name AS admin_name FROM media m LEFT JOIN admins a ON a.id = m.admin_id WHERE $w ORDER BY m.id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);

$fmtSize = fn($b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', ' ') . ' Mo' : number_format($b / 1024, 0, ',', ' ') . ' Ko';

$pageTitle = 'Médiathèque';
require __DIR__ . '/_inc/layout_top.php';
?>
<form method="post" enctype="multipart/form-data" class="card">
  <?= csrf_field() ?>
  <input type="hidden" name="do" value="upload">
  <div class="card-head"><h2>Ajouter des fichiers</h2><span class="muted small">Images (JPEG, PNG, WebP, GIF), vidéos (MP4, WebM), PDF · max. <?= round(UPLOAD_MAX_SIZE / 1048576) ?> Mo par image/PDF</span></div>
  <div class="btn-group">
    <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,application/pdf" data-multi-preview="media-previews" required>
    <button class="btn btn-primary" type="submit"><?= aicon('upload') ?> Envoyer</button>
  </div>
  <div class="upload-previews" id="media-previews"></div>
</form>

<div class="card">
  <form method="get" class="filters">
    <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom du fichier"></div>
    <div class="field"><label for="type">Type</label><select id="type" name="type"><?= select_options(['image' => 'Images', 'video' => 'Vidéos', 'pdf' => 'PDF'], $type, 'Tous') ?></select></div>
    <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
  </form>
</div>

<p class="muted"><?= $total ?> fichier(s). Copiez l'URL d'un fichier pour l'insérer dans une page, un service ou un contenu HTML.</p>
<div class="media-grid">
  <?php foreach ($rows as $m):
      $u = media_url($m['path']);
      $mime = (string)$m['mime']; ?>
    <div class="media-item">
      <a class="media-thumb" href="<?= e($u) ?>" target="_blank" rel="noopener">
        <?php if (str_starts_with($mime, 'image/')): ?><img src="<?= e($u) ?>" alt="" loading="lazy">
        <?php elseif (str_starts_with($mime, 'video/')): ?><video src="<?= e($u) ?>" muted preload="metadata"></video>
        <?php else: ?><?= aicon('file') ?><?php endif; ?>
      </a>
      <div class="media-body">
        <div class="media-name" title="<?= e($m['original']) ?>"><?= e($m['original'] ?: basename($m['path'])) ?></div>
        <div class="muted"><?= e($fmtSize((int)$m['size'])) ?> · <?= e(format_date($m['created_at'])) ?><?= $m['admin_name'] ? ' · ' . e($m['admin_name']) : '' ?></div>
        <input type="text" value="<?= e($u) ?>" readonly aria-label="URL du fichier" onclick="this.select()">
        <div class="btn-group">
          <button type="button" class="btn btn-xs" data-copy="<?= e($u) ?>"><?= aicon('copy') ?> Copier l'URL</button>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn btn-xs btn-danger" type="submit" data-confirm="Supprimer définitivement ce fichier ?"><?= aicon('trash') ?></button></form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$rows): ?><div class="card table-empty">La médiathèque est vide.</div><?php endif; ?>
<?= pagination_links($pg) ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
