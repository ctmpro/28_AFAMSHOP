<?php
/**
 * Demandes clients : devis, étude de location, maintenance, contact.
 */
class Requests
{
    /** Champs obligatoires par type de demande. */
    public const REQUIRED = [
        'quote' => ['name', 'phone', 'email'],
        'rental' => ['name', 'phone', 'email'],
        'maintenance' => ['name', 'phone', 'email', 'printer_model', 'message'],
        'contact' => ['name', 'email', 'message'],
    ];

    /**
     * Traite la soumission d'un formulaire. Retourne la liste des erreurs (vide si succès).
     */
    public static function handle(string $type): array
    {
        $d = [];
        foreach (['name' => 150, 'company' => 190, 'phone' => 40, 'email' => 190, 'product_label' => 255, 'printer_model' => 150, 'serial_number' => 120, 'subject' => 190, 'message' => 5000] as $k => $max) {
            $d[$k] = mb_substr(trim((string)post($k)), 0, $max);
        }
        $d['email'] = strtolower($d['email']);
        $errors = [];
        $labels = ['name' => __('full_name'), 'phone' => __('phone'), 'email' => __('email'), 'printer_model' => __('printer_model'), 'message' => __('message')];
        foreach (self::REQUIRED[$type] as $f) {
            if ($d[$f] === '') $errors[] = __('field_required', ['field' => $labels[$f] ?? $f]);
        }
        if ($d['email'] !== '' && !valid_email($d['email'])) $errors[] = __('invalid_email');
        // Champ piège anti-robot
        if (post('website') !== '') $errors[] = __('captcha_error');
        if (!$errors && !Recaptcha::verify()) $errors[] = __('captcha_error');

        $attachment = null;
        if (!$errors && $type !== 'contact') {
            try {
                $attachment = handle_upload($_FILES['attachment'] ?? null, 'private/requests', UPLOAD_DOC_TYPES);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($errors) {
            return $errors;
        }
        $productId = int_param('product_id') ?: null;
        if ($productId && ($p = Catalog::productById($productId, false))) {
            $d['product_label'] = $d['product_label'] ?: $p['name'] . ' (' . $p['sku'] . ')';
        } else {
            $productId = null;
        }
        $qty = int_param('quantity');
        $id = DB::insert('requests', [
            'type' => $type,
            'customer_id' => Auth::id(),
            'name' => $d['name'],
            'company' => $d['company'] ?: null,
            'phone' => $d['phone'] ?: null,
            'email' => $d['email'],
            'product_id' => $productId,
            'product_label' => $d['product_label'] ?: null,
            'quantity' => $qty > 0 ? $qty : null,
            'printer_model' => $d['printer_model'] ?: null,
            'serial_number' => $d['serial_number'] ?: null,
            'subject' => $d['subject'] ?: null,
            'message' => $d['message'] ?: null,
            'attachment' => $attachment,
        ]);
        $typeLabel = request_types()[$type];
        Mailer::notifyAdmin('new_request', array_filter([
            'type' => $typeLabel,
            'name' => $d['name'],
            'company' => $d['company'],
            'phone' => $d['phone'],
            'email' => $d['email'],
            'product' => $d['product_label'],
            'quantity' => $qty ?: '',
            'printer_model' => $d['printer_model'],
            'serial_number' => $d['serial_number'],
            'message' => $d['message'],
            'link' => admin_url('requests.php', ['id' => $id]),
        ]));
        Mailer::sendTemplate('request_ack', $d['email'], ['name' => $d['name'], 'type' => $typeLabel]);
        return [];
    }
}
