<?php
/**
 * Logique métier des commandes : création, statuts, paiement, stock, facture.
 */
class Orders
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM orders WHERE id = :id', ['id' => $id]);
    }

    public static function findByNumber(string $number): ?array
    {
        return DB::one('SELECT * FROM orders WHERE order_number = :n', ['n' => $number]);
    }

    public static function items(int $orderId): array
    {
        return DB::all('SELECT * FROM order_items WHERE order_id = :id ORDER BY id', ['id' => $orderId]);
    }

    public static function history(int $orderId): array
    {
        return DB::all('SELECT h.*, a.name AS admin_name FROM order_status_history h LEFT JOIN admins a ON a.id = h.admin_id WHERE h.order_id = :id ORDER BY h.created_at, h.id', ['id' => $orderId]);
    }

    public static function payments(int $orderId): array
    {
        return DB::all('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC', ['id' => $orderId]);
    }

    /** Accès invité / client à une commande (jeton secret ou propriétaire). */
    public static function canView(array $order, ?string $token = null): bool
    {
        if ($token && hash_equals($order['access_token'], $token)) {
            return true;
        }
        return Auth::id() && (int)$order['customer_id'] === Auth::id();
    }

    private static function nextNumber(): string
    {
        $prefix = setting('order_prefix', 'AF');
        do {
            $n = $prefix . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (DB::val('SELECT id FROM orders WHERE order_number = :n', ['n' => $n]));
        return $n;
    }

    /**
     * Crée la commande à partir des totaux du panier.
     * $c : first_name, last_name, company, email, phone, address, city, notes
     */
    public static function create(array $c, array $totals, string $paymentMethod): array
    {
        DB::begin();
        try {
            $orderId = DB::insert('orders', [
                'order_number' => self::nextNumber(),
                'access_token' => random_token(24),
                'customer_id' => Auth::id(),
                'email' => strtolower($c['email']),
                'phone' => $c['phone'],
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
                'company' => $c['company'] ?: null,
                'delivery_method' => $totals['delivery_method'],
                'ship_address' => $totals['delivery_method'] === 'delivery' ? $c['address'] : null,
                'ship_city' => $totals['delivery_method'] === 'delivery' ? $c['city'] : null,
                'zone_id' => $totals['zone']['id'] ?? null,
                'zone_name' => $totals['delivery_method'] === 'pickup' ? __('pickup') : ($totals['zone']['name'] ?? null),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'coupon_code' => $totals['coupon']['code'] ?? null,
                'delivery_fee' => $totals['delivery_fee'],
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax'],
                'total' => $totals['total'],
                'currency' => base_currency(),
                'payment_method' => $paymentMethod,
                'notes' => $c['notes'] ?: null,
            ]);
            foreach ($totals['items'] as $it) {
                DB::insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $it['product']['id'],
                    'sku' => $it['product']['sku'],
                    'name' => $it['product']['name'],
                    'unit_price' => $it['unit_price'],
                    'qty' => $it['qty'],
                    'line_total' => $it['line_total'],
                ]);
            }
            if ($totals['coupon']) {
                DB::exec('UPDATE coupons SET uses = uses + 1 WHERE id = :id', ['id' => $totals['coupon']['id']]);
                DB::insert('coupon_usages', [
                    'coupon_id' => $totals['coupon']['id'],
                    'order_id' => $orderId,
                    'customer_id' => Auth::id(),
                    'email' => strtolower($c['email']),
                ]);
            }
            DB::insert('order_status_history', ['order_id' => $orderId, 'status' => 'received', 'comment' => __('order_created')]);
            if (setting('stock_decrement_on', 'paid') === 'order') {
                self::decrementStock($orderId);
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
        $order = self::find($orderId);
        Mailer::orderEmail('order_confirmation', $order);
        Mailer::notifyAdmin('new_order', [
            'order_number' => $order['order_number'],
            'customer' => $order['first_name'] . ' ' . $order['last_name'],
            'total' => money_plain($order['total']),
            'payment' => payment_method_label($order['payment_method']),
            'link' => admin_url('order.php', ['id' => $orderId]),
        ]);
        return $order;
    }

    /** Décrémente le stock (une seule fois par commande). */
    public static function decrementStock(int $orderId): void
    {
        $affected = DB::exec('UPDATE orders SET stock_decremented = 1 WHERE id = :id AND stock_decremented = 0', ['id' => $orderId]);
        if (!$affected) {
            return;
        }
        foreach (self::items($orderId) as $it) {
            if ($it['product_id']) {
                Stock::adjust((int)$it['product_id'], -(int)$it['qty'], 'order', $orderId);
                DB::exec('UPDATE products SET sales_count = sales_count + :q WHERE id = :id', ['q' => (int)$it['qty'], 'id' => $it['product_id']]);
            }
        }
    }

    /** Remet en stock (annulation / remboursement). */
    public static function restock(int $orderId): void
    {
        $affected = DB::exec('UPDATE orders SET stock_decremented = 0 WHERE id = :id AND stock_decremented = 1', ['id' => $orderId]);
        if (!$affected) {
            return;
        }
        foreach (self::items($orderId) as $it) {
            if ($it['product_id'] && DB::val('SELECT id FROM products WHERE id = :id', ['id' => $it['product_id']])) {
                Stock::adjust((int)$it['product_id'], (int)$it['qty'], 'cancel', $orderId);
                DB::exec('UPDATE products SET sales_count = GREATEST(0, sales_count - :q) WHERE id = :id', ['q' => (int)$it['qty'], 'id' => $it['product_id']]);
            }
        }
    }

    /** Change le statut d'une commande, applique les règles métier et notifie le client. */
    public static function setStatus(int $orderId, string $status, ?string $comment = null, bool $notify = true): bool
    {
        if (!isset(order_statuses()[$status])) {
            return false;
        }
        $order = self::find($orderId);
        if (!$order || $order['status'] === $status) {
            return false;
        }
        $data = ['status' => $status];
        if ($status === 'paid') {
            $data['payment_status'] = 'paid';
        }
        if ($status === 'refunded') {
            $data['payment_status'] = 'refunded';
        }
        if ($status === 'cancelled' && $order['payment_status'] === 'pending') {
            $data['payment_status'] = 'cancelled';
        }
        DB::update('orders', $data, 'id = :id', ['id' => $orderId]);
        DB::insert('order_status_history', [
            'order_id' => $orderId,
            'status' => $status,
            'comment' => $comment,
            'admin_id' => $_SESSION['admin_id'] ?? null,
        ]);

        if (in_array($status, ['paid', 'preparing', 'shipped', 'delivered'], true)) {
            self::decrementStock($orderId);
        }
        if (in_array($status, ['cancelled', 'refunded'], true)) {
            self::restock($orderId);
        }
        if (in_array($status, ['paid', 'delivered'], true) && !$order['invoice_number']) {
            self::assignInvoice($orderId);
        }
        if ($notify) {
            $map = [
                'paid' => 'payment_confirmation',
                'preparing' => 'order_preparing',
                'shipped' => 'order_shipped',
                'delivered' => 'order_delivered',
                'cancelled' => 'order_cancelled',
                'refunded' => 'order_refunded',
            ];
            if (isset($map[$status])) {
                Mailer::orderEmail($map[$status], self::find($orderId), ['comment' => (string)$comment]);
            }
        }
        return true;
    }

    /** Confirmation de paiement (appelée par les webhooks / IPN uniquement). */
    public static function markPaid(int $orderId, string $provider, ?string $transactionId, float $amount, $raw = null): void
    {
        $order = self::find($orderId);
        if (!$order || $order['payment_status'] === 'paid') {
            return;
        }
        if (round($amount, 2) + 0.01 < round((float)$order['total'], 2)) {
            self::recordPayment($orderId, $provider, null, $transactionId, $amount, 'failed', ['error' => 'amount_mismatch', 'raw' => $raw]);
            error_log("AFAMSHOP: montant payé inférieur au total pour la commande {$order['order_number']}");
            return;
        }
        DB::exec("UPDATE payments SET status = 'paid', transaction_id = COALESCE(:t, transaction_id), raw_response = :r WHERE order_id = :o AND provider = :p AND status = 'pending'", [
            't' => $transactionId, 'r' => is_string($raw) ? $raw : json_encode($raw), 'o' => $orderId, 'p' => $provider,
        ]);
        if (!DB::val("SELECT id FROM payments WHERE order_id = :o AND status = 'paid'", ['o' => $orderId])) {
            self::recordPayment($orderId, $provider, null, $transactionId, $amount, 'paid', $raw);
        }
        DB::update('orders', ['payment_status' => 'paid'], 'id = :id', ['id' => $orderId]);
        if ($order['status'] === 'received') {
            self::setStatus($orderId, 'paid', __('payment_confirmed_by', ['provider' => payment_method_label($provider)]));
        } else {
            self::decrementStock($orderId);
            if (!$order['invoice_number']) self::assignInvoice($orderId);
            Mailer::orderEmail('payment_confirmation', self::find($orderId));
        }
    }

    public static function markPaymentFailed(int $orderId, string $provider, string $status = 'failed', $raw = null): void
    {
        DB::exec("UPDATE payments SET status = :s, raw_response = :r WHERE order_id = :o AND provider = :p AND status = 'pending'", [
            's' => $status, 'r' => is_string($raw) ? $raw : json_encode($raw), 'o' => $orderId, 'p' => $provider,
        ]);
        DB::exec("UPDATE orders SET payment_status = :s WHERE id = :id AND payment_status = 'pending'", ['s' => $status, 'id' => $orderId]);
    }

    public static function recordPayment(int $orderId, string $provider, ?string $reference, ?string $transactionId, float $amount, string $status = 'pending', $raw = null): int
    {
        return DB::insert('payments', [
            'order_id' => $orderId,
            'provider' => $provider,
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => base_currency(),
            'status' => $status,
            'raw_response' => $raw === null ? null : (is_string($raw) ? $raw : json_encode($raw, JSON_UNESCAPED_UNICODE)),
        ]);
    }

    /** Attribue un numéro de facture séquentiel (verrouillage pour éviter les doublons). */
    public static function assignInvoice(int $orderId): ?string
    {
        DB::begin();
        try {
            $order = DB::one('SELECT invoice_number FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);
            if (!$order || $order['invoice_number']) {
                DB::commit();
                return $order['invoice_number'] ?? null;
            }
            DB::exec("INSERT IGNORE INTO settings (skey, svalue, sgroup, label, type) VALUES ('invoice_counter', '0', 'system', 'Compteur de factures', 'number')");
            $counter = (int)DB::val("SELECT svalue FROM settings WHERE skey = 'invoice_counter' FOR UPDATE") + 1;
            DB::exec("UPDATE settings SET svalue = :v WHERE skey = 'invoice_counter'", ['v' => (string)$counter]);
            $number = setting('invoice_prefix', 'FAC') . '-' . date('Y') . '-' . str_pad((string)$counter, 5, '0', STR_PAD_LEFT);
            DB::update('orders', ['invoice_number' => $number, 'invoice_date' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $orderId]);
            DB::commit();
            return $number;
        } catch (Throwable $e) {
            DB::rollBack();
            error_log('invoice: ' . $e->getMessage());
            return null;
        }
    }

    public static function statusBadge(string $status): string
    {
        $cls = [
            'received' => 'info', 'paid' => 'success', 'preparing' => 'warning', 'shipped' => 'primary',
            'delivered' => 'success', 'cancelled' => 'muted', 'refunded' => 'danger',
            'pending' => 'warning', 'failed' => 'danger',
        ][$status] ?? 'muted';
        $label = order_statuses()[$status] ?? payment_statuses()[$status] ?? $status;
        return '<span class="badge badge-' . $cls . '">' . e($label) . '</span>';
    }
}
