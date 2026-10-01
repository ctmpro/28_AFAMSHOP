<?php
/**
 * Notification instantanée de paiement (IPN) PayDunya.
 * Le hash est contrôlé puis le statut est revérifié auprès de l'API PayDunya (confirm).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$data = $_POST['data'] ?? null;
if (!is_array($data)) {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json['data'] ?? $json;
}
if (!is_array($data) || !PayDunyaGateway::validIpnHash($data['hash'] ?? null)) {
    http_response_code(400);
    exit('Invalid');
}
$token = $data['invoice']['token'] ?? null;
if (!$token) {
    http_response_code(400);
    exit('Missing token');
}
try {
    PayDunyaGateway::confirm((string)$token);
} catch (Throwable $e) {
    error_log('PayDunya IPN: ' . $e->getMessage());
    http_response_code(500);
    exit('Error');
}
echo 'ok';
