<?php
/**
 * Fiche client : informations, adresses, commandes, demandes, blocage du compte.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('customers');

$id = int_param('id');
$c = DB::one('SELECT * FROM customers WHERE id = :id', ['id' => $id]);
if (!$c) {
    flash('error', 'Client introuvable.');
    redirect(admin_url('customers.php'));
}

if (is_post()) {
    require_csrf();
    if (post('do') === 'toggle_status') {
        $new = $c['status'] === 'active' ? 'blocked' : 'active';
        DB::update('customers', ['status' => $new], 'id = :id', ['id' => $id]);
        AdminAuth::log($new === 'blocked' ? 'customer_block' : 'customer_unblock', 'customer', $id, $c['email']);
        flash('success', $new === 'blocked' ? 'Compte bloqué : le client ne peut plus se connecter.' : 'Compte débloqué.');
    }
    redirect(admin_url('customer.php', ['id' => $id]));
}

$accountTypes = ['individual' => 'Particulier', 'business' => 'Entreprise', 'administration' => 'Administration'];
$addresses = DB::all('SELECT a.*, z.name AS zone_name FROM addresses a LEFT JOIN delivery_zones z ON z.id = a.zone_id WHERE a.customer_id = :id ORDER BY a.is_default DESC, a.id', ['id' => $id]);
$orders = DB::all('SELECT * FROM orders WHERE customer_id = :id OR (customer_id IS NULL AND email = :e) ORDER BY created_at DESC', ['id' => $id, 'e' => $c['email']]);
$requests = DB::all('SELECT * FROM requests WHERE customer_id = :id OR email = :e ORDER BY created_at DESC', ['id' => $id, 'e' => $c['email']]);
$stats = DB::one('SELECT COUNT(*) n, COALESCE(SUM(CASE WHEN ' . ADMIN_REVENUE_SQL . ' THEN o.total ELSE 0 END),0) spent, MAX(o.created_at) last_order FROM orders o WHERE o.customer_id = :id', ['id' => $id]);
$favorites = (int)DB::val('SELECT COUNT(*) FROM favorites WHERE customer_id = :id', ['id' => $id]);

$pageTitle = $c['first_name'] . ' ' . $c['last_name'];
$pageActions = '<a class="btn" href="' . e(admin_url('customers.php')) . '">← Clients</a>'
    . '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="do" value="toggle_status">'
    . ($c['status'] === 'active'
        ? '<button class="btn btn-danger" type="submit" data-confirm="Bloquer ce compte client ?">' . aicon('lock') . ' Bloquer le compte</button>'
        : '<button class="btn btn-success" type="submit">' . aicon('check') . ' Débloquer le compte</button>')
    . '</form>';
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="kpis">
  <div class="kpi"><div class="kpi-label">Commandes</div><div class="kpi-value"><?= (int)$stats['n'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Montant dépensé</div><div class="kpi-value"><?= e(admin_money($stats['spent'])) ?></div></div>
  <div class="kpi"><div class="kpi-label">Dernière commande</div><div class="kpi-value" style="font-size:1rem"><?= e(format_date($stats['last_order'])) ?: '—' ?></div></div>
  <div class="kpi"><div class="kpi-label">Favoris</div><div class="kpi-value"><?= $favorites ?></div></div>
</div>

<div class="grid grid-main">
  <div class="stack">
    <div class="card flush">
      <div class="card-head"><h2>Commandes</h2></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>N°</th><th>Date</th><th class="num">Total</th><th>Paiement</th><th>Statut</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= AdminAuth::can('orders') ? '<a href="' . e(admin_url('order.php', ['id' => $o['id']])) . '"><strong>' . e($o['order_number']) . '</strong></a>' : e($o['order_number']) ?><?= $o['customer_id'] ? '' : ' ' . badge('Invité') ?></td>
              <td class="nowrap"><?= e(format_date($o['created_at'], true)) ?></td>
              <td class="num"><?= e(admin_money($o['total'])) ?></td>
              <td><?= e(payment_method_label($o['payment_method'])) ?> <?= payment_badge($o['payment_status']) ?></td>
              <td><?= Orders::statusBadge($o['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$orders): ?><tr><td colspan="5" class="table-empty">Aucune commande.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card flush">
      <div class="card-head"><h2>Demandes (devis, location, maintenance, contact)</h2></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Type</th><th>Objet</th><th>Date</th><th>Statut</th></tr></thead>
          <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= badge(request_types()[$r['type']] ?? $r['type'], 'primary') ?></td>
              <td><?= AdminAuth::can('requests') ? '<a href="' . e(admin_url('requests.php', ['id' => $r['id']])) . '">' . e(truncate($r['subject'] ?: ($r['product_label'] ?: $r['message']), 70)) . '</a>' : e(truncate($r['subject'] ?: $r['message'], 70)) ?></td>
              <td class="nowrap"><?= e(format_date($r['created_at'], true)) ?></td>
              <td><?= request_status_badge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$requests): ?><tr><td colspan="4" class="table-empty">Aucune demande.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="stack">
    <div class="card">
      <div class="card-head"><h2>Informations</h2><?= $c['status'] === 'active' ? badge('Actif', 'success') : badge('Bloqué', 'danger') ?></div>
      <?= info_row('Nom', e($c['first_name'] . ' ' . $c['last_name'])) ?>
      <?= info_row('Type de compte', e($accountTypes[$c['account_type']] ?? $c['account_type'])) ?>
      <?= info_row('Société', e($c['company'])) ?>
      <?= info_row('Email', '<a href="mailto:' . e($c['email']) . '">' . e($c['email']) . '</a>') ?>
      <?= info_row('Téléphone', $c['phone'] ? '<a href="tel:' . e($c['phone']) . '">' . e($c['phone']) . '</a>' : null) ?>
      <?= info_row('Inscription', e(format_date($c['created_at'], true))) ?>
      <?= info_row('Dernière connexion', e(format_date($c['last_login'], true))) ?>
    </div>
    <div class="card">
      <h2>Adresses</h2>
      <?php foreach ($addresses as $a): ?>
        <div class="info-row">
          <span class="info-value">
            <strong><?= e($a['label'] ?: 'Adresse') ?></strong><?= $a['is_default'] ? ' ' . badge('Par défaut', 'primary') : '' ?><br>
            <?= e($a['full_name']) ?><br><?= e($a['address']) ?>, <?= e($a['city']) ?>
            <?= $a['zone_name'] ? '<br><span class="muted small">Zone : ' . e($a['zone_name']) . '</span>' : '' ?>
            <?= $a['phone'] ? '<br><span class="muted small">' . e($a['phone']) . '</span>' : '' ?>
          </span>
        </div>
      <?php endforeach; ?>
      <?php if (!$addresses): ?><p class="muted">Aucune adresse enregistrée.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
