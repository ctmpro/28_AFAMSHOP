<?php
/**
 * Espace client : détail d'une commande (redirige vers la page de suivi sécurisée).
 */
$u = Auth::require();
$order = Orders::findByNumber(strtoupper((string)$slug));
if (!$order || (int)$order['customer_id'] !== (int)$u['id']) {
    http_response_code(404);
    require PAGES_PATH . '/404.php';
    return;
}
redirect(url('commande/suivi', ['n' => $order['order_number']]));
