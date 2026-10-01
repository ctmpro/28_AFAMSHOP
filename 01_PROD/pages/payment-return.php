<?php
/**
 * Retour du client après paiement. Le statut définitif est fixé par webhook / IPN ;
 * une vérification serveur supplémentaire est faite auprès du prestataire.
 */
$order = Orders::findByNumber((string)query_param('n'));
if (!$order || !Orders::canView($order, (string)query_param('t'))) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
$back = ['n' => $order['order_number'], 't' => $order['access_token']];
if (query_param('cancel')) {
    flash('warning', __('payment_cancelled_text'));
    redirect(url('commande/confirmation', $back));
}
try {
    if (query_param('provider') === 'stripe' && query_param('session_id') && StripeGateway::enabled()) {
        $ref = DB::val("SELECT id FROM payments WHERE order_id = :o AND provider = 'stripe' AND reference = :r", ['o' => $order['id'], 'r' => query_param('session_id')]);
        if ($ref) StripeGateway::confirmSession((string)query_param('session_id'));
    } elseif (query_param('provider') === 'paydunya' && PayDunyaGateway::enabled()) {
        $token = query_param('token') ?: DB::val("SELECT reference FROM payments WHERE order_id = :o AND provider = 'paydunya' ORDER BY id DESC", ['o' => $order['id']]);
        if ($token && DB::val("SELECT id FROM payments WHERE order_id = :o AND provider = 'paydunya' AND reference = :r", ['o' => $order['id'], 'r' => $token])) {
            PayDunyaGateway::confirm((string)$token);
        }
    }
} catch (Throwable $e) {
    error_log('payment-return: ' . $e->getMessage());
}
redirect(url('commande/confirmation', $back));
