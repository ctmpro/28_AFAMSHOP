<?php
/**
 * Téléchargement de la facture PDF (client propriétaire ou lien sécurisé).
 */
$order = Orders::findByNumber((string)query_param('n'));
if (!$order || !Orders::canView($order, (string)query_param('t')) || !$order['invoice_number']) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$pdf = Invoice::pdf((int)$order['id']);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $order['invoice_number'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store');
echo $pdf;
exit;
