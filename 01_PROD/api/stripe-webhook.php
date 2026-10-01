<?php
/**
 * Webhook Stripe : confirmation serveur des paiements (signature vérifiée).
 * URL à déclarer dans le tableau de bord Stripe : https://<domaine>/api/stripe-webhook.php
 * Événements : checkout.session.completed, checkout.session.async_payment_succeeded,
 *              checkout.session.async_payment_failed, checkout.session.expired, charge.refunded
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret = (string)env('STRIPE_WEBHOOK_SECRET', '');

if ($secret === '' || !StripeGateway::verifySignature($payload, $sig, $secret)) {
    http_response_code(400);
    exit('Invalid signature');
}
$event = json_decode($payload, true);
if (!is_array($event)) {
    http_response_code(400);
    exit('Invalid payload');
}
try {
    StripeGateway::handleEvent($event);
} catch (Throwable $e) {
    error_log('Stripe webhook: ' . $e->getMessage());
    http_response_code(500);
    exit('Error');
}
http_response_code(200);
echo 'ok';
