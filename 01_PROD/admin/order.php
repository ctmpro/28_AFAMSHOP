<?php
/**
 * Détail d'une commande : client, articles, totaux, paiements, historique,
 * changement de statut, notes internes, facture, annulation / remboursement, vue imprimable.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('orders');

$id = int_param('id');
$order = Orders::find($id);
if (!$order) {
    flash('error', 'Commande introuvable.');
    redirect(admin_url('orders.php'));
}
$self = admin_url('order.php', ['id' => $id]);

/** Paiement Stripe remboursable (payment_intent connu et payé). */
$stripePayment = DB::one("SELECT * FROM payments WHERE order_id = :o AND provider = 'stripe' AND status = 'paid' AND transaction_id LIKE 'pi\\_%' ORDER BY id DESC LIMIT 1", ['o' => $id]);

if (is_post()) {
    require_csrf();
    $do = (string)post('do');
    switch ($do) {
        case 'status':
            $new = (string)post('status');
            $comment = mb_substr((string)post('comment'), 0, 255) ?: null;
            if (!isset(order_statuses()[$new])) {
                flash('error', 'Statut invalide.');
            } elseif (Orders::setStatus($id, $new, $comment, post_bool('notify') === 1)) {
                AdminAuth::log('order_status', 'order', $id, ['from' => $order['status'], 'to' => $new, 'comment' => $comment]);
                flash('success', 'Statut mis à jour : ' . order_statuses()[$new] . '.');
            } else {
                flash('info', 'Le statut est inchangé.');
            }
            break;

        case 'notes':
            DB::update('orders', ['admin_notes' => (string)post('admin_notes') ?: null], 'id = :id', ['id' => $id]);
            AdminAuth::log('order_notes', 'order', $id);
            flash('success', 'Notes internes enregistrées.');
            break;

        case 'invoice':
            $number = Orders::assignInvoice($id);
            if ($number) {
                AdminAuth::log('order_invoice', 'order', $id, $number);
                flash('success', 'Facture ' . $number . ' générée.');
            } else {
                flash('error', 'Impossible de générer la facture.');
            }
            break;

        case 'cancel':
            if (in_array($order['status'], ['cancelled', 'refunded'], true)) {
                flash('info', 'Commande déjà annulée ou remboursée.');
            } else {
                Orders::setStatus($id, 'cancelled', mb_substr((string)post('comment'), 0, 255) ?: 'Annulée par l\'administration');
                AdminAuth::log('order_cancel', 'order', $id);
                flash('success', 'Commande annulée (stock remis à jour si nécessaire).');
            }
            break;

        case 'refund':
            if ($order['status'] === 'refunded') {
                flash('info', 'Commande déjà remboursée.');
                break;
            }
            $comment = mb_substr((string)post('comment'), 0, 255) ?: 'Remboursement';
            if ($stripePayment && post_bool('stripe_refund')) {
                try {
                    $res = StripeGateway::refund($stripePayment['transaction_id'], null, $stripePayment['currency']);
                    DB::update('payments', ['status' => 'refunded', 'raw_response' => json_encode(['refund' => $res], JSON_UNESCAPED_UNICODE)], 'id = :id', ['id' => $stripePayment['id']]);
                    $comment .= ' (Stripe : ' . ($res['id'] ?? 'ok') . ')';
                } catch (Throwable $e) {
                    AdminAuth::log('order_refund_failed', 'order', $id, $e->getMessage());
                    flash('error', 'Échec du remboursement Stripe : ' . $e->getMessage());
                    redirect($self);
                }
            }
            Orders::setStatus($id, 'refunded', $comment);
            AdminAuth::log('order_refund', 'order', $id, ['stripe' => (bool)($stripePayment && post_bool('stripe_refund'))]);
            flash('success', 'Commande marquée comme remboursée.');
            break;

        default:
            flash('error', 'Action inconnue.');
    }
    redirect($self);
}

$items = Orders::items($id);
$history = Orders::history($id);
$payments = Orders::payments($id);
$customer = $order['customer_id'] ? DB::one('SELECT * FROM customers WHERE id = :id', ['id' => $order['customer_id']]) : null;
$customerName = trim($order['first_name'] . ' ' . $order['last_name']);

// ---------------------------------------------------------------------
// Vue imprimable (bon de commande)
// ---------------------------------------------------------------------
if (query_param('print')) {
    ?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Commande <?= e($order['order_number']) ?></title>
<style>
  body { font: 13px/1.5 system-ui, Arial, sans-serif; color: #111; max-width: 820px; margin: 24px auto; padding: 0 16px; }
  h1 { font-size: 20px; margin: 0 0 4px; }
  .head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 16px; }
  .cols { display: flex; gap: 30px; margin-bottom: 18px; }
  .cols > div { flex: 1; }
  h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 6px; color: #555; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; }
  th { background: #f3f3f3; }
  .num { text-align: right; white-space: nowrap; }
  .totals td { border: 0; }
  .grand td { font-weight: 700; font-size: 15px; border-top: 2px solid #111; }
  .noprint { margin-bottom: 16px; }
  @media print { .noprint { display: none; } body { margin: 0; } }
</style>
</head>
<body>
  <div class="noprint"><button onclick="window.print()">Imprimer</button> <a href="<?= e($self) ?>">← Retour à la commande</a></div>
  <div class="head">
    <div>
      <strong style="font-size:18px"><?= e(setting('company_name', 'AFAM')) ?></strong><br>
      <?= nl2br(e(setting('company_address'))) ?><br><?= e(setting('contact_phone')) ?> · <?= e(setting('contact_email')) ?>
    </div>
    <div style="text-align:right">
      <h1>Bon de commande</h1>
      N° <strong><?= e($order['order_number']) ?></strong><br>
      Date : <?= e(format_date($order['created_at'], true)) ?><br>
      <?php if ($order['invoice_number']): ?>Facture : <?= e($order['invoice_number']) ?><br><?php endif; ?>
      Statut : <?= e(order_statuses()[$order['status']] ?? $order['status']) ?>
    </div>
  </div>
  <div class="cols">
    <div>
      <h2>Client</h2>
      <strong><?= e($customerName) ?></strong><?= $order['company'] ? '<br>' . e($order['company']) : '' ?><br>
      <?= e($order['email']) ?><br><?= e($order['phone']) ?>
    </div>
    <div>
      <h2>Livraison</h2>
      <?php if ($order['delivery_method'] === 'pickup'): ?>
        Retrait en magasin<br><?= e(setting('pickup_address')) ?>
      <?php else: ?>
        <?= e($order['ship_address']) ?><br><?= e($order['ship_city']) ?><br><?= e($order['zone_name']) ?>
      <?php endif; ?>
    </div>
    <div>
      <h2>Paiement</h2>
      <?= e(payment_method_label($order['payment_method'])) ?><br><?= e(payment_statuses()[$order['payment_status']] ?? $order['payment_status']) ?>
    </div>
  </div>
  <table>
    <thead><tr><th>SKU</th><th>Désignation</th><th class="num">Prix unitaire</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($items as $it): ?>
      <tr><td><?= e($it['sku']) ?></td><td><?= e($it['name']) ?></td><td class="num"><?= e(money_plain($it['unit_price'])) ?></td><td class="num"><?= (int)$it['qty'] ?></td><td class="num"><?= e(money_plain($it['line_total'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tbody class="totals">
      <tr><td colspan="4" class="num">Sous-total</td><td class="num"><?= e(money_plain($order['subtotal'])) ?></td></tr>
      <?php if ((float)$order['discount'] > 0): ?><tr><td colspan="4" class="num">Remise<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></td><td class="num">− <?= e(money_plain($order['discount'])) ?></td></tr><?php endif; ?>
      <tr><td colspan="4" class="num">Livraison</td><td class="num"><?= e(money_plain($order['delivery_fee'])) ?></td></tr>
      <?php if ((float)$order['tax_amount'] > 0): ?><tr><td colspan="4" class="num">TVA (<?= e((string)(float)$order['tax_rate']) ?> %)</td><td class="num"><?= e(money_plain($order['tax_amount'])) ?></td></tr><?php endif; ?>
      <tr class="grand"><td colspan="4" class="num">Total</td><td class="num"><?= e(money_plain($order['total'])) ?></td></tr>
    </tbody>
  </table>
  <?php if ($order['notes']): ?><p><strong>Note du client :</strong> <?= nl2br(e($order['notes'])) ?></p><?php endif; ?>
  <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
<?php
    exit;
}

$pageTitle = 'Commande ' . $order['order_number'];
$pageActions = '<a class="btn" href="' . e(admin_url('orders.php')) . '">← Commandes</a>'
    . '<a class="btn" target="_blank" href="' . e(admin_url('order.php', ['id' => $id, 'print' => 1])) . '">' . aicon('printer') . ' Imprimer</a>';
if ($order['invoice_number']) {
    $pageActions .= '<a class="btn btn-primary" href="' . e(admin_url('invoice.php', ['id' => $id])) . '">' . aicon('download') . ' Facture PDF</a>';
} else {
    $pageActions .= '<form method="post" class="inline">' . csrf_field() . '<input type="hidden" name="do" value="invoice"><button class="btn" type="submit" data-confirm="Générer un numéro de facture définitif pour cette commande ?">' . aicon('file') . ' Générer la facture</button></form>'
        . '<a class="btn" href="' . e(admin_url('invoice.php', ['id' => $id])) . '">' . aicon('download') . ' Bon de commande PDF</a>';
}
require __DIR__ . '/_inc/layout_top.php';
?>
<div class="kpis">
  <div class="kpi"><div class="kpi-label">Statut</div><div class="kpi-value" style="font-size:1rem"><?= Orders::statusBadge($order['status']) ?></div></div>
  <div class="kpi"><div class="kpi-label">Paiement</div><div class="kpi-value" style="font-size:1rem"><?= payment_badge($order['payment_status']) ?> <span class="small muted"><?= e(payment_method_label($order['payment_method'])) ?></span></div></div>
  <div class="kpi"><div class="kpi-label">Total</div><div class="kpi-value"><?= e(admin_money($order['total'])) ?></div></div>
  <div class="kpi"><div class="kpi-label">Date</div><div class="kpi-value" style="font-size:1rem"><?= e(format_date($order['created_at'], true)) ?></div></div>
</div>

<div class="grid grid-main">
  <div class="stack">
    <div class="card flush">
      <div class="card-head"><h2>Articles</h2></div>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>SKU</th><th>Produit</th><th class="num">Prix unitaire</th><th class="num">Qté</th><th class="num">Total</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><code><?= e($it['sku']) ?></code></td>
              <td><?= $it['product_id'] && AdminAuth::can('products') ? '<a href="' . e(admin_url('product_edit.php', ['id' => $it['product_id']])) . '">' . e($it['name']) . '</a>' : e($it['name']) ?></td>
              <td class="num"><?= e(admin_money($it['unit_price'])) ?></td>
              <td class="num"><?= (int)$it['qty'] ?></td>
              <td class="num"><?= e(admin_money($it['line_total'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="4" class="num">Sous-total</td><td class="num"><?= e(admin_money($order['subtotal'])) ?></td></tr>
            <?php if ((float)$order['discount'] > 0): ?><tr><td colspan="4" class="num">Remise<?= $order['coupon_code'] ? ' (code ' . e($order['coupon_code']) . ')' : '' ?></td><td class="num">− <?= e(admin_money($order['discount'])) ?></td></tr><?php endif; ?>
            <tr><td colspan="4" class="num">Livraison<?= $order['zone_name'] ? ' — ' . e($order['zone_name']) : '' ?></td><td class="num"><?= e(admin_money($order['delivery_fee'])) ?></td></tr>
            <?php if ((float)$order['tax_amount'] > 0): ?><tr><td colspan="4" class="num">TVA (<?= e((string)(float)$order['tax_rate']) ?> %)</td><td class="num"><?= e(admin_money($order['tax_amount'])) ?></td></tr><?php endif; ?>
            <tr><td colspan="4" class="num"><strong>Total</strong></td><td class="num"><strong><?= e(admin_money($order['total'])) ?></strong></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="card">
      <h2>Paiements</h2>
      <?php if (!$payments): ?><p class="muted">Aucune transaction enregistrée.</p><?php endif; ?>
      <?php foreach ($payments as $pay): ?>
        <div class="info-row">
          <span class="info-label"><?= e(format_date($pay['created_at'], true)) ?></span>
          <span class="info-value">
            <strong><?= e(payment_method_label($pay['provider'])) ?></strong> · <?= e(money($pay['amount'], $pay['currency'], false)) ?> · <?= payment_badge($pay['status']) ?>
            <?php if ($pay['transaction_id']): ?><br><span class="small muted">Transaction : <code><?= e($pay['transaction_id']) ?></code></span><?php endif; ?>
            <?php if ($pay['reference']): ?><br><span class="small muted">Référence : <code><?= e($pay['reference']) ?></code></span><?php endif; ?>
            <?php if ($pay['raw_response']):
                $decoded = json_decode($pay['raw_response'], true);
                $raw = $decoded !== null ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $pay['raw_response']; ?>
              <details class="mt"><summary>Réponse brute du prestataire</summary><pre class="raw"><?= e($raw) ?></pre></details>
            <?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <h2>Historique</h2>
      <ul class="timeline">
        <?php foreach ($history as $h): ?>
          <li>
            <strong><?= e(order_statuses()[$h['status']] ?? $h['status']) ?></strong>
            <span class="muted small"> · <?= e(format_date($h['created_at'], true)) ?><?= $h['admin_name'] ? ' · par ' . e($h['admin_name']) : '' ?></span>
            <?php if ($h['comment']): ?><br><span><?= e($h['comment']) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$history): ?><li class="muted">Aucun historique.</li><?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="stack">
    <form method="post" class="card">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="status">
      <h2>Changer le statut</h2>
      <?= field_select('status', 'Nouveau statut', order_statuses(), $order['status']) ?>
      <?= field_input('comment', 'Commentaire (visible dans l\'historique et l\'email)', '', ['attrs' => ['maxlength' => 255]]) ?>
      <?= field_checkbox('notify', 'Notifier le client par email', true) ?>
      <button class="btn btn-primary" type="submit">Mettre à jour</button>
      <p class="help">Les règles de stock et de facturation sont appliquées automatiquement (décrément au paiement, remise en stock à l'annulation).</p>
    </form>

    <div class="card">
      <h2>Client</h2>
      <?= info_row('Nom', e($customerName) . ($customer && AdminAuth::can('customers') ? ' · <a href="' . e(admin_url('customer.php', ['id' => $customer['id']])) . '">fiche client</a>' : ($order['customer_id'] ? '' : ' ' . badge('Invité')))) ?>
      <?= info_row('Société', e($order['company'])) ?>
      <?= info_row('Email', '<a href="mailto:' . e($order['email']) . '">' . e($order['email']) . '</a>') ?>
      <?= info_row('Téléphone', '<a href="tel:' . e($order['phone']) . '">' . e($order['phone']) . '</a>') ?>
      <?= info_row('Livraison', $order['delivery_method'] === 'pickup' ? 'Retrait en magasin' : e(trim($order['ship_address'] . ', ' . $order['ship_city'], ', ')) . ($order['zone_name'] ? '<br><span class="muted small">Zone : ' . e($order['zone_name']) . '</span>' : '')) ?>
      <?= info_row('Facture', $order['invoice_number'] ? e($order['invoice_number']) . ' <span class="muted small">(' . e(format_date($order['invoice_date'])) . ')</span>' : null) ?>
      <?php if ($order['notes']): ?><div class="alert alert-info mt"><span><strong>Note du client :</strong><br><?= nl2br(e($order['notes'])) ?></span></div><?php endif; ?>
    </div>

    <form method="post" class="card">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="notes">
      <h2>Notes internes</h2>
      <?= field_textarea('admin_notes', 'Visibles uniquement par l\'équipe', $order['admin_notes'], ['rows' => 4]) ?>
      <button class="btn" type="submit">Enregistrer les notes</button>
    </form>

    <?php if (!in_array($order['status'], ['cancelled', 'refunded'], true)): ?>
    <div class="card">
      <h2>Annulation / remboursement</h2>
      <form method="post" class="mb">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="cancel">
        <?= field_input('comment', 'Motif d\'annulation', '', ['id' => 'cancel_comment', 'attrs' => ['maxlength' => 255]]) ?>
        <button class="btn btn-danger" type="submit" data-confirm="Annuler cette commande ? Le stock sera remis à jour."><?= aicon('x') ?> Annuler la commande</button>
      </form>
      <?php if ($order['payment_status'] === 'paid'): ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="do" value="refund">
          <?= field_input('comment', 'Motif du remboursement', '', ['id' => 'refund_comment', 'attrs' => ['maxlength' => 255]]) ?>
          <?php if ($stripePayment): ?>
            <?= field_checkbox('stripe_refund', 'Rembourser automatiquement via Stripe (' . money($stripePayment['amount'], $stripePayment['currency'], false) . ')', true) ?>
          <?php else: ?>
            <p class="help">Paiement hors Stripe : effectuez le remboursement auprès du prestataire puis marquez la commande comme remboursée.</p>
          <?php endif; ?>
          <button class="btn btn-danger" type="submit" data-confirm="Confirmer le remboursement de cette commande ?"><?= aicon('refresh') ?> Rembourser</button>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/_inc/layout_bottom.php'; ?>
