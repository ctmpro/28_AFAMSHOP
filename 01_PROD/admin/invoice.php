<?php
/**
 * Téléchargement de la facture (ou du bon de commande si aucune facture n'est encore émise) au format PDF.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
require __DIR__ . '/_inc/helpers.php';
$admin = AdminAuth::require('orders');

$id = int_param('id');
$order = Orders::find($id);
if (!$order) {
    http_response_code(404);
    exit('Commande introuvable.');
}
try {
    $pdf = Invoice::pdf($id);
} catch (Throwable $e) {
    error_log('admin invoice: ' . $e->getMessage());
    flash('error', 'Impossible de générer le PDF : ' . $e->getMessage());
    redirect(admin_url('order.php', ['id' => $id]));
}
$name = ($order['invoice_number'] ?: 'commande-' . $order['order_number']) . '.pdf';
AdminAuth::log('invoice_download', 'order', $id, $order['invoice_number'] ?: $order['order_number']);
header('Content-Type: application/pdf');
header('Content-Disposition: ' . (query_param('inline') ? 'inline' : 'attachment') . '; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $name) . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
