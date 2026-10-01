<?php
/**
 * Demandes (devis, location, maintenance, contact) : liste filtrable et fiche détaillée
 * (statut, notes internes, pièce jointe protégée).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('requests');

$id = int_param('id');
$req = $id ? DB::one('SELECT r.*, p.name AS product_name, p.sku AS product_sku FROM requests r LEFT JOIN products p ON p.id = r.product_id WHERE r.id = :id', ['id' => $id]) : null;
if ($id && !$req) {
    flash('error', 'Demande introuvable.');
    redirect(admin_url('requests.php'));
}

if (is_post()) {
    require_csrf();
    $do = (string)post('do');
    if ($do === 'update' && $req) {
        $status = (string)post('status');
        if (!isset(request_statuses()[$status])) {
            flash('error', 'Statut invalide.');
        } else {
            DB::update('requests', ['status' => $status, 'admin_notes' => (string)post('admin_notes') ?: null], 'id = :id', ['id' => $id]);
            AdminAuth::log('request_update', 'request', $id, ['status' => $status]);
            flash('success', 'Demande mise à jour.');
        }
        redirect(admin_url('requests.php', ['id' => $id]));
    }
    if ($do === 'delete' && $req) {
        DB::exec('DELETE FROM requests WHERE id = :id', ['id' => $id]);
        if ($req['attachment']) {
            $file = request_attachment_path($req['attachment']);
            if ($file) @unlink($file);
        }
        AdminAuth::log('request_delete', 'request', $id, $req['email']);
        flash('success', 'Demande supprimée.');
        redirect(admin_url('requests.php'));
    }
    if ($do === 'bulk_status') {
        $ids = post_ids('ids');
        $status = (string)post('status');
        if ($ids && isset(request_statuses()[$status])) {
            $p = ['st' => $status];
            DB::exec('UPDATE requests SET status = :st WHERE id IN ' . DB::in($ids, $p), $p);
            AdminAuth::log('request_bulk_status', 'request', null, ['ids' => $ids, 'status' => $status]);
            flash('success', count($ids) . ' demande(s) mise(s) à jour.');
        } else {
            flash('warning', 'Sélectionnez des demandes et un statut.');
        }
        back(admin_url('requests.php'));
    }
    redirect(admin_url('requests.php'));
}

if (!$req) {
    $type = (string)query_param('type');
    $status = (string)query_param('status');
    $q = (string)query_param('q');
    $where = ['1=1'];
    $params = [];
    if (isset(request_types()[$type])) {
        $where[] = 'r.type = :t';
        $params['t'] = $type;
    }
    if (isset(request_statuses()[$status])) {
        $where[] = 'r.status = :s';
        $params['s'] = $status;
    }
    if ($q !== '') {
        $where[] = '(r.name LIKE :q OR r.email LIKE :q OR r.phone LIKE :q OR r.company LIKE :q OR r.subject LIKE :q OR r.product_label LIKE :q OR r.printer_model LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    $w = implode(' AND ', $where);
    $total = (int)DB::val("SELECT COUNT(*) FROM requests r WHERE $w", $params);
    $pg = paginate($total, 30, (int)query_param('page', 1));
    $rows = DB::all("SELECT r.* FROM requests r WHERE $w ORDER BY (r.status = 'new') DESC, r.created_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}", $params);
    $byType = [];
    foreach (DB::all("SELECT type, COUNT(*) n FROM requests WHERE status = 'new' GROUP BY type") as $r) {
        $byType[$r['type']] = (int)$r['n'];
    }
}

$pageTitle = $req ? 'Demande #' . $req['id'] . ' — ' . (request_types()[$req['type']] ?? $req['type']) : 'Demandes';
$pageActions = $req ? '<a class="btn" href="' . e(admin_url('requests.php')) . '">← Demandes</a>' : '';
require __DIR__ . '/_inc/layout_top.php';
?>
<?php if ($req): ?>
  <div class="grid grid-main">
    <div class="card">
      <div class="card-head"><h2><?= e($req['subject'] ?: 'Demande de ' . mb_strtolower(request_types()[$req['type']] ?? $req['type'])) ?></h2><?= request_status_badge($req['status']) ?></div>
      <?= info_row('Reçue le', e(format_date($req['created_at'], true))) ?>
      <?= info_row('Nom', e($req['name'])) ?>
      <?= info_row('Société', e($req['company'])) ?>
      <?= info_row('Email', '<a href="mailto:' . e($req['email']) . '">' . e($req['email']) . '</a>') ?>
      <?= info_row('Téléphone', $req['phone'] ? '<a href="tel:' . e($req['phone']) . '">' . e($req['phone']) . '</a>' : null) ?>
      <?php if ($req['product_id'] || $req['product_label']): ?>
        <?= info_row('Produit', $req['product_id'] && $req['product_name']
            ? (AdminAuth::can('products') ? '<a href="' . e(admin_url('product_edit.php', ['id' => $req['product_id']])) . '">' . e($req['product_name']) . '</a>' : e($req['product_name'])) . ' <code class="small">' . e($req['product_sku']) . '</code>'
            : e($req['product_label'])) ?>
      <?php endif; ?>
      <?= $req['quantity'] ? info_row('Quantité', (string)(int)$req['quantity']) : '' ?>
      <?= $req['printer_model'] ? info_row('Modèle d\'imprimante', e($req['printer_model'])) : '' ?>
      <?= $req['serial_number'] ? info_row('N° de série', e($req['serial_number'])) : '' ?>
      <?= $req['customer_id'] && AdminAuth::can('customers') ? info_row('Compte client', '<a href="' . e(admin_url('customer.php', ['id' => $req['customer_id']])) . '">Voir la fiche client</a>') : '' ?>
      <?php if ($req['attachment']): ?>
        <?= info_row('Pièce jointe', '<a class="btn btn-sm" href="' . e(admin_url('request_file.php', ['id' => $req['id']])) . '">' . aicon('download') . ' Télécharger (' . e(strtoupper(pathinfo($req['attachment'], PATHINFO_EXTENSION))) . ')</a>') ?>
      <?php endif; ?>
      <h3 class="mt">Message</h3>
      <div class="card" style="background:#fafbfc;box-shadow:none"><?= nl2br(e($req['message'] ?? '')) ?: '<span class="muted">Aucun message.</span>' ?></div>
      <a class="btn btn-primary" href="mailto:<?= e($req['email']) ?>?subject=<?= rawurlencode('Re: ' . ($req['subject'] ?: (request_types()[$req['type']] ?? 'Votre demande'))) ?>"><?= aicon('external') ?> Répondre par email</a>
    </div>
    <div class="stack">
      <form method="post" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="update">
        <h2>Traitement</h2>
        <?= field_select('status', 'Statut', request_statuses(), $req['status']) ?>
        <?= field_textarea('admin_notes', 'Notes internes', $req['admin_notes'], ['rows' => 6]) ?>
        <button class="btn btn-primary" type="submit">Enregistrer</button>
      </form>
      <form method="post" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="delete">
        <h2>Suppression</h2>
        <p class="muted small">Supprime définitivement la demande et sa pièce jointe.</p>
        <button class="btn btn-danger" type="submit" data-confirm="Supprimer définitivement cette demande ?"><?= aicon('trash') ?> Supprimer</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="tabs">
    <a class="tab<?= $type === '' ? ' active' : '' ?>" href="<?= e(admin_url('requests.php', ['status' => $status])) ?>">Toutes</a>
    <?php foreach (request_types() as $k => $label): ?>
      <a class="tab<?= $type === $k ? ' active' : '' ?>" href="<?= e(admin_url('requests.php', ['type' => $k, 'status' => $status])) ?>"><?= e($label) ?><?= !empty($byType[$k]) ? ' <span class="badge badge-danger">' . $byType[$k] . '</span>' : '' ?></a>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <form method="get" class="filters">
      <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
      <div class="field wide"><label for="q">Recherche</label><input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom, email, téléphone, société, objet, modèle…"></div>
      <div class="field"><label for="status">Statut</label><select id="status" name="status"><?= select_options(request_statuses(), $status, 'Tous') ?></select></div>
      <button class="btn btn-primary" type="submit"><?= aicon('search') ?> Filtrer</button>
    </form>
  </div>
  <form method="post" class="card flush">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="bulk_status">
    <div class="card-head"><h2><?= $total ?> demande(s)</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th><input type="checkbox" data-check-all="ids[]" aria-label="Tout sélectionner"></th><th>Date</th><th>Type</th><th>Demandeur</th><th>Objet</th><th></th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="Sélectionner"></td>
            <td class="nowrap"><?= e(format_date($r['created_at'], true)) ?></td>
            <td><?= badge(request_types()[$r['type']] ?? $r['type'], 'primary') ?></td>
            <td><a href="<?= e(admin_url('requests.php', ['id' => $r['id']])) ?>"><strong><?= e($r['name']) ?></strong></a><?= $r['company'] ? ' <span class="muted small">(' . e($r['company']) . ')</span>' : '' ?><br><span class="muted small"><?= e($r['email']) ?><?= $r['phone'] ? ' · ' . e($r['phone']) : '' ?></span></td>
            <td><?= e(truncate($r['subject'] ?: ($r['product_label'] ?: ($r['printer_model'] ?: $r['message'])), 70)) ?></td>
            <td><?= $r['attachment'] ? '<span title="Pièce jointe">' . aicon('file') . '</span>' : '' ?></td>
            <td><?= request_status_badge($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="table-empty">Aucune demande.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="bulk-bar">
      <select name="status" aria-label="Nouveau statut"><?= select_options(request_statuses(), '', 'Changer le statut en…') ?></select>
      <button class="btn" type="submit">Appliquer</button>
    </div>
    <?= pagination_links($pg) ?>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
