<?php /** Tableau des commandes du client — attend $orders. */ ?>
<div class="table-scroll">
<table class="table">
  <thead><tr><th><?= e(__('order')) ?></th><th><?= e(__('date')) ?></th><th><?= e(__('total')) ?></th><th><?= e(__('status')) ?></th><th><?= e(__('payment_status')) ?></th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><strong><?= e($o['order_number']) ?></strong></td>
      <td><?= e(format_date($o['created_at'])) ?></td>
      <td><?= e(money($o['total'], $o['currency'], false)) ?></td>
      <td><?= Orders::statusBadge($o['status']) ?></td>
      <td><?= Orders::statusBadge($o['payment_status']) ?></td>
      <td class="nowrap">
        <a class="btn btn-outline btn-sm" href="<?= e(url('compte/commande/' . strtolower($o['order_number']))) ?>"><?= e(__('details')) ?></a>
        <?php if ($o['invoice_number']): ?><a class="btn btn-link btn-sm" href="<?= e(url('commande/facture', ['n' => $o['order_number']])) ?>" target="_blank"><?= e(__('invoice')) ?></a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
