<?php
/**
 * Espace client : demandes de devis, location, maintenance.
 */
$u = Auth::require();
$requests = DB::all('SELECT * FROM requests WHERE customer_id = :c OR email = :e ORDER BY created_at DESC LIMIT 100', ['c' => $u['id'], 'e' => $u['email']]);
$active = 'requests';
$pageTitle = __('my_quotes');
$noIndex = true;
require INCLUDES_PATH . '/layout/header.php';
?>
<div class="container account-layout">
  <?php require INCLUDES_PATH . '/layout/account-nav.php'; ?>
  <section class="account-main">
    <h1><?= e(__('my_quotes')) ?></h1>
    <div class="card">
      <?php if (!$requests): ?><p class="muted"><?= e(__('no_requests')) ?></p><?php else: ?>
      <div class="table-scroll"><table class="table">
        <thead><tr><th><?= e(__('date')) ?></th><th><?= e(__('type')) ?></th><th><?= e(__('details')) ?></th><th><?= e(__('status')) ?></th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= e(format_date($r['created_at'])) ?></td>
            <td><?= e(request_types()[$r['type']]) ?></td>
            <td><?= e($r['product_label'] ?: $r['printer_model'] ?: $r['subject'] ?: truncate($r['message'], 80)) ?></td>
            <td><span class="badge badge-info"><?= e(request_statuses()[$r['status']]) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
      <p><a class="btn btn-primary" href="<?= e(url('devis')) ?>"><?= e(__('request_quote')) ?></a></p>
    </div>
  </section>
</div>
<?php require INCLUDES_PATH . '/layout/footer.php'; ?>
