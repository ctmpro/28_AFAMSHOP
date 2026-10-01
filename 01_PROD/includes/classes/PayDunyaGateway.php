<?php
/**
 * Paiement mobile money (Wave, Orange Money, Free Money...) et carte via PayDunya.
 * Confirmation fiable : chaque notification IPN est revérifiée par l'API "confirm".
 */
class PayDunyaGateway
{
    public static function enabled(): bool
    {
        return (string)env('PAYDUNYA_MASTER_KEY', '') !== '' && (string)env('PAYDUNYA_PRIVATE_KEY', '') !== ''
            && (string)env('PAYDUNYA_TOKEN', '') !== '' && setting_bool('payment_paydunya_enabled', true);
    }

    private static function base(): string
    {
        return strtolower((string)env('PAYDUNYA_MODE', 'test')) === 'live'
            ? 'https://app.paydunya.com/api/v1/'
            : 'https://app.paydunya.com/sandbox-api/v1/';
    }

    private static function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::base() . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'PAYDUNYA-MASTER-KEY: ' . env('PAYDUNYA_MASTER_KEY'),
                'PAYDUNYA-PRIVATE-KEY: ' . env('PAYDUNYA_PRIVATE_KEY'),
                'PAYDUNYA-TOKEN: ' . env('PAYDUNYA_TOKEN'),
            ],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) {
            throw new RuntimeException('PayDunya: ' . $err);
        }
        return json_decode($res, true) ?: [];
    }

    /** Crée une facture PayDunya et retourne l'URL de paiement. */
    public static function createInvoice(array $order): string
    {
        $back = ['n' => $order['order_number'], 't' => $order['access_token'], 'provider' => 'paydunya'];
        $items = [];
        foreach (Orders::items((int)$order['id']) as $i => $it) {
            $items['item_' . $i] = [
                'name' => mb_substr($it['name'], 0, 100),
                'quantity' => (int)$it['qty'],
                'unit_price' => (string)round((float)$it['unit_price']),
                'total_price' => (string)round((float)$it['line_total']),
            ];
        }
        $payload = [
            'invoice' => [
                'items' => $items,
                'total_amount' => (int)round((float)$order['total']),
                'description' => __('order') . ' ' . $order['order_number'],
            ],
            'store' => [
                'name' => setting('site_name', 'AFAMSHOP'),
                'tagline' => setting('site_tagline', ''),
                'phone' => setting('contact_phone', ''),
                'website_url' => APP_URL,
            ],
            'custom_data' => ['order_id' => (int)$order['id'], 'order_number' => $order['order_number']],
            'actions' => [
                'cancel_url' => url('commande/paiement-retour', $back + ['cancel' => 1]),
                'return_url' => url('commande/paiement-retour', $back),
                'callback_url' => url('api/paydunya-ipn.php'),
            ],
        ];
        if ((float)$order['delivery_fee'] > 0) {
            $payload['invoice']['taxes']['tax_0'] = ['name' => __('delivery'), 'amount' => (int)round((float)$order['delivery_fee'])];
        }
        $channels = array_filter(array_map('trim', explode(',', setting('paydunya_channels', ''))));
        if ($channels) {
            $payload['invoice']['channels'] = array_values($channels);
        }
        $res = self::request('POST', 'checkout-invoice/create', $payload);
        if (($res['response_code'] ?? '') !== '00' || empty($res['token'])) {
            throw new RuntimeException('PayDunya: ' . ($res['response_text'] ?? $res['description'] ?? 'erreur de création de facture'));
        }
        Orders::recordPayment((int)$order['id'], 'paydunya', $res['token'], null, (float)$order['total'], 'pending', $res);
        return $res['response_text'];
    }

    /** Interroge PayDunya sur l'état d'une facture et met à jour la commande. */
    public static function confirm(string $token): ?string
    {
        $pay = DB::one("SELECT * FROM payments WHERE provider = 'paydunya' AND reference = :r ORDER BY id DESC", ['r' => $token]);
        if (!$pay) {
            return null;
        }
        $res = self::request('GET', 'checkout-invoice/confirm/' . rawurlencode($token));
        if (($res['response_code'] ?? '') !== '00') {
            return null;
        }
        $status = $res['status'] ?? 'pending';
        $orderId = (int)$pay['order_id'];
        $customOrder = (int)($res['custom_data']['order_id'] ?? 0);
        if ($customOrder && $customOrder !== $orderId) {
            error_log('PayDunya: incohérence commande pour le jeton ' . $token);
            return null;
        }
        if ($status === 'completed') {
            $receipt = $res['receipt_identifier'] ?? ($res['invoice']['token'] ?? $token);
            Orders::markPaid($orderId, 'paydunya', $receipt, (float)($res['invoice']['total_amount'] ?? 0), $res);
        } elseif ($status === 'cancelled') {
            Orders::markPaymentFailed($orderId, 'paydunya', 'cancelled', $res);
        } elseif ($status === 'failed') {
            Orders::markPaymentFailed($orderId, 'paydunya', 'failed', $res);
        }
        return $status;
    }

    /** Vérifie le hash IPN (SHA-512 de la clé principale). */
    public static function validIpnHash(?string $hash): bool
    {
        return $hash && hash_equals(hash('sha512', (string)env('PAYDUNYA_MASTER_KEY')), $hash);
    }
}
