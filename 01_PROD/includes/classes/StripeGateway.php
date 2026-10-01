<?php
/**
 * Paiement par carte bancaire via Stripe Checkout (API REST, sans SDK).
 * La confirmation du paiement est faite côté serveur par webhook signé.
 */
class StripeGateway
{
    private const API = 'https://api.stripe.com/v1/';
    /** Devises sans décimales chez Stripe. */
    private const ZERO_DECIMAL = ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    public static function enabled(): bool
    {
        return (string)env('STRIPE_SECRET_KEY', '') !== '' && setting_bool('payment_stripe_enabled', true);
    }

    private static function request(string $method, string $path, array $params = []): array
    {
        $ch = curl_init(self::API . $path . ($method === 'GET' && $params ? '?' . http_build_query($params) : ''));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => env('STRIPE_SECRET_KEY') . ':',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Stripe-Version: 2024-06-20'],
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            throw new RuntimeException('Stripe: ' . $err);
        }
        $data = json_decode($body, true) ?: [];
        if ($code >= 400) {
            throw new RuntimeException('Stripe: ' . ($data['error']['message'] ?? 'erreur ' . $code));
        }
        return $data;
    }

    public static function toMinor(float $amount, string $currency): int
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? (int)round($amount) : (int)round($amount * 100);
    }

    public static function fromMinor(int $amount, string $currency): float
    {
        return in_array(strtoupper($currency), self::ZERO_DECIMAL, true) ? (float)$amount : $amount / 100;
    }

    /** Crée une session Stripe Checkout et retourne l'URL de paiement. */
    public static function createCheckout(array $order): string
    {
        $currency = strtolower($order['currency']);
        $back = ['n' => $order['order_number'], 't' => $order['access_token']];
        $session = self::request('POST', 'checkout/sessions', [
            'mode' => 'payment',
            'customer_email' => $order['email'],
            'client_reference_id' => $order['id'],
            'locale' => 'fr',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => self::toMinor((float)$order['total'], $currency),
                    'product_data' => ['name' => __('order') . ' ' . $order['order_number'] . ' — ' . setting('site_name', 'AFAMSHOP')],
                ],
            ]],
            'metadata' => ['order_id' => $order['id'], 'order_number' => $order['order_number']],
            'payment_intent_data' => ['metadata' => ['order_id' => $order['id'], 'order_number' => $order['order_number']]],
            'success_url' => url('commande/paiement-retour', $back + ['provider' => 'stripe']) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('commande/paiement-retour', $back + ['provider' => 'stripe', 'cancel' => 1]),
        ]);
        Orders::recordPayment((int)$order['id'], 'stripe', $session['id'], null, (float)$order['total'], 'pending', ['session_id' => $session['id']]);
        return $session['url'];
    }

    public static function retrieveSession(string $id): array
    {
        return self::request('GET', 'checkout/sessions/' . rawurlencode($id));
    }

    /** Vérifie un paiement à partir de l'identifiant de session (double contrôle au retour). */
    public static function confirmSession(string $sessionId): void
    {
        $s = self::retrieveSession($sessionId);
        $orderId = (int)($s['metadata']['order_id'] ?? 0);
        if ($orderId && ($s['payment_status'] ?? '') === 'paid') {
            Orders::markPaid($orderId, 'stripe', $s['payment_intent'] ?? $s['id'], self::fromMinor((int)$s['amount_total'], $s['currency']), $s);
        }
    }

    /** Vérification de la signature Stripe-Signature. */
    public static function verifySignature(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, '');
            $parts[$k][] = $v;
        }
        $t = (int)($parts['t'][0] ?? 0);
        if (!$t || abs(time() - $t) > $tolerance) {
            return false;
        }
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        foreach ($parts['v1'] ?? [] as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }
        return false;
    }

    /** Traitement d'un événement webhook validé. */
    public static function handleEvent(array $event): void
    {
        $obj = $event['data']['object'] ?? [];
        $orderId = (int)($obj['metadata']['order_id'] ?? 0);
        switch ($event['type'] ?? '') {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if ($orderId && ($obj['payment_status'] ?? '') === 'paid') {
                    Orders::markPaid($orderId, 'stripe', $obj['payment_intent'] ?? $obj['id'], self::fromMinor((int)$obj['amount_total'], $obj['currency']), $obj);
                }
                break;
            case 'checkout.session.async_payment_failed':
                if ($orderId) Orders::markPaymentFailed($orderId, 'stripe', 'failed', $obj);
                break;
            case 'checkout.session.expired':
                if ($orderId) Orders::markPaymentFailed($orderId, 'stripe', 'cancelled', $obj);
                break;
            case 'charge.refunded':
                $pi = $obj['payment_intent'] ?? null;
                if ($pi) {
                    $oid = (int)DB::val("SELECT order_id FROM payments WHERE provider = 'stripe' AND transaction_id = :t", ['t' => $pi]);
                    if ($oid && !empty($obj['refunded'])) {
                        DB::exec("UPDATE payments SET status = 'refunded' WHERE order_id = :o AND provider = 'stripe'", ['o' => $oid]);
                        Orders::setStatus($oid, 'refunded', 'Remboursement Stripe');
                    }
                }
                break;
        }
    }

    /** Remboursement depuis l'admin. */
    public static function refund(string $paymentIntent, ?float $amount = null, string $currency = 'XOF'): array
    {
        $params = ['payment_intent' => $paymentIntent];
        if ($amount !== null) {
            $params['amount'] = self::toMinor($amount, $currency);
        }
        return self::request('POST', 'refunds', $params);
    }
}
