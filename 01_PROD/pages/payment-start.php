<?php
/**
 * Démarrage du paiement d'une commande (redirection vers Stripe ou PayDunya).
 */
$order = Orders::findByNumber((string)query_param('n'));
if (!$order || !Orders::canView($order, (string)query_param('t'))) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$back = ['n' => $order['order_number'], 't' => $order['access_token']];
if ($order['payment_status'] === 'paid' || in_array($order['status'], ['cancelled', 'refunded'], true)
    || !in_array($order['payment_method'], ['stripe', 'paydunya'], true)) {
    redirect(url('commande/confirmation', $back));
}
try {
    $payUrl = $order['payment_method'] === 'stripe'
        ? (StripeGateway::enabled() ? StripeGateway::createCheckout($order) : null)
        : (PayDunyaGateway::enabled() ? PayDunyaGateway::createInvoice($order) : null);
    if (!$payUrl) {
        throw new RuntimeException('gateway disabled');
    }
    redirect($payUrl);
} catch (Throwable $e) {
    error_log('payment-start ' . $order['order_number'] . ': ' . $e->getMessage());
    flash('error', __('payment_unavailable'));
    redirect(url('commande/confirmation', $back));
}
