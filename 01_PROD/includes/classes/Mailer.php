<?php
/**
 * Envoi des emails via PHPMailer (répertoire utilitaires/PHPMailer) en SMTP.
 * Les sujets et contenus des emails sont modifiables dans l'admin (Paramètres > Emails).
 */
class Mailer
{
    private static bool $loaded = false;

    private static function loadPhpMailer(): bool
    {
        if (self::$loaded) {
            return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
        }
        self::$loaded = true;
        foreach (['PHPMailer/src', 'PHPMailer', 'phpmailer/src', 'phpmailer', 'PHPMailer-master/src'] as $dir) {
            $base = UTILS_PATH . '/' . $dir;
            if (is_file($base . '/PHPMailer.php')) {
                require_once $base . '/Exception.php';
                require_once $base . '/PHPMailer.php';
                require_once $base . '/SMTP.php';
                return true;
            }
        }
        error_log('AFAMSHOP: PHPMailer introuvable dans ' . UTILS_PATH);
        return false;
    }

    /** Envoi brut. Retourne true si l'email est parti. */
    public static function send(string $to, string $subject, string $html, array $attachments = [], ?string $replyTo = null): bool
    {
        if (!valid_email($to)) {
            return false;
        }
        $fromEmail = env('MAIL_FROM', setting('contact_email', 'no-reply@localhost'));
        $fromName = env('MAIL_FROM_NAME', setting('site_name', 'AFAMSHOP'));
        $body = self::layout($subject, $html);

        if (env('MAIL_DRIVER', 'smtp') === 'log') {
            $dir = ROOT_PATH . '/uploads/private/mails';
            @mkdir($dir, 0755, true);
            file_put_contents($dir . '/' . date('Ymd-His') . '-' . substr(md5($to . $subject . microtime()), 0, 6) . '.html', "<!-- To: $to | $subject -->\n" . $body);
            return true;
        }

        if (!self::loadPhpMailer()) {
            return false;
        }
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $m->CharSet = 'UTF-8';
            $m->isSMTP();
            $m->Host = (string)env('SMTP_HOST', '');
            $m->Port = (int)env('SMTP_PORT', 587);
            $m->SMTPAuth = (string)env('SMTP_USER', '') !== '';
            $m->Username = (string)env('SMTP_USER', '');
            $m->Password = (string)env('SMTP_PASS', '');
            $secure = strtolower((string)env('SMTP_SECURE', 'tls'));
            if ($secure === 'ssl') {
                $m->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $m->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $m->setFrom($fromEmail, $fromName);
            $m->addAddress($to);
            if ($replyTo && valid_email($replyTo)) {
                $m->addReplyTo($replyTo);
            }
            $m->isHTML(true);
            $m->Subject = $subject;
            $m->Body = $body;
            $m->AltBody = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>#i', "\n", $html))));
            foreach ($attachments as $name => $content) {
                $m->addStringAttachment($content, $name);
            }
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('AFAMSHOP mail: ' . $e->getMessage());
            return false;
        }
    }

    /** Gabarit HTML commun des emails. */
    public static function layout(string $title, string $content): string
    {
        $color = setting('color_primary', '#0b4f8a');
        $logo = setting('logo') ? media_url(setting('logo')) : '';
        $site = e(setting('site_name', 'AFAMSHOP'));
        $footer = nl2br(e(render_vars(setting('email_footer', '{company_name} — {address} — {phone}'))));
        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>' . e($title) . '</title></head>'
            . '<body style="margin:0;background:#f3f5f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f8;padding:24px 0"><tr><td align="center">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:8px;overflow:hidden">'
            . '<tr><td style="background:' . e($color) . ';padding:18px 24px;color:#fff;font-size:20px;font-weight:bold">'
            . ($logo ? '<img src="' . e($logo) . '" alt="' . $site . '" style="max-height:40px">' : $site) . '</td></tr>'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6">' . $content . '</td></tr>'
            . '<tr><td style="padding:16px 24px;background:#f9fafb;color:#6b7280;font-size:12px">' . $footer . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    /** Remplace {variables} dans un modèle (valeurs échappées). */
    public static function fill(string $tpl, array $vars, bool $escape = true): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = $escape ? e($v) : (string)$v;
        }
        return render_vars(strtr($tpl, $map));
    }

    /** Envoie un email à partir d'un modèle administrable (settings email_{key}_subject / _body). */
    public static function sendTemplate(string $key, string $to, array $vars, string $extraHtml = '', array $attachments = []): bool
    {
        if (!setting_bool('email_' . $key . '_enabled', true)) {
            return false;
        }
        $subject = self::fill(setting('email_' . $key . '_subject', $key), $vars, false);
        $body = self::fill(setting('email_' . $key . '_body', ''), $vars);
        return self::send($to, strip_tags($subject), nl2br($body, false) . $extraHtml, $attachments);
    }

    /** Emails liés à une commande avec récapitulatif. */
    public static function orderEmail(string $key, ?array $order, array $extra = []): bool
    {
        if (!$order) return false;
        $items = Orders::items((int)$order['id']);
        $rows = '';
        foreach ($items as $it) {
            $rows .= '<tr><td style="padding:6px;border-bottom:1px solid #eee">' . e($it['name']) . ' <small style="color:#888">(' . e($it['sku']) . ')</small></td>'
                . '<td style="padding:6px;border-bottom:1px solid #eee;text-align:center">' . (int)$it['qty'] . '</td>'
                . '<td style="padding:6px;border-bottom:1px solid #eee;text-align:right">' . e(money_plain($it['line_total'])) . '</td></tr>';
        }
        $summary = '<table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;font-size:14px">'
            . '<tr style="background:#f3f5f8"><th align="left" style="padding:6px">' . e(__('product')) . '</th><th style="padding:6px">' . e(__('qty')) . '</th><th align="right" style="padding:6px">' . e(__('total')) . '</th></tr>'
            . $rows
            . '<tr><td colspan="2" align="right" style="padding:6px">' . e(__('subtotal')) . '</td><td align="right" style="padding:6px">' . e(money_plain($order['subtotal'])) . '</td></tr>'
            . ((float)$order['discount'] > 0 ? '<tr><td colspan="2" align="right" style="padding:6px">' . e(__('discount')) . '</td><td align="right" style="padding:6px">-' . e(money_plain($order['discount'])) . '</td></tr>' : '')
            . '<tr><td colspan="2" align="right" style="padding:6px">' . e(__('delivery')) . '</td><td align="right" style="padding:6px">' . e(money_plain($order['delivery_fee'])) . '</td></tr>'
            . '<tr><td colspan="2" align="right" style="padding:6px"><b>' . e(__('total')) . '</b></td><td align="right" style="padding:6px"><b>' . e(money_plain($order['total'])) . '</b></td></tr>'
            . '</table>';
        $link = url('commande/suivi', ['n' => $order['order_number'], 't' => $order['access_token']]);
        $summary .= '<p><a href="' . e($link) . '" style="display:inline-block;background:' . e(setting('color_primary', '#0b4f8a')) . ';color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">' . e(__('track_order')) . '</a></p>';

        $vars = array_merge([
            'first_name' => $order['first_name'],
            'last_name' => $order['last_name'],
            'order_number' => $order['order_number'],
            'total' => money_plain($order['total']),
            'payment_method' => payment_method_label($order['payment_method']),
            'status' => order_statuses()[$order['status']] ?? $order['status'],
            'link' => $link,
        ], $extra);

        $attachments = [];
        if ($key === 'payment_confirmation' && $order['invoice_number'] && setting_bool('attach_invoice', true)) {
            try {
                $attachments[$order['invoice_number'] . '.pdf'] = Invoice::pdf((int)$order['id']);
            } catch (Throwable $e) {
                error_log('invoice attach: ' . $e->getMessage());
            }
        }
        return self::sendTemplate($key, $order['email'], $vars, $summary, $attachments);
    }

    /** Notifications à l'administration (nouvelle commande, devis, stock faible...). */
    public static function notifyAdmin(string $key, array $vars): bool
    {
        $to = setting('admin_notification_email', setting('contact_email'));
        if (!$to) return false;
        $titles = [
            'new_order' => 'Nouvelle commande {order_number}',
            'new_request' => 'Nouvelle demande : {type}',
            'low_stock' => 'Stock faible : {product}',
            'new_review' => 'Nouvel avis à valider',
        ];
        $subject = self::fill($titles[$key] ?? $key, $vars, false);
        $html = '<h2 style="margin-top:0">' . e($subject) . '</h2><table cellpadding="4">';
        foreach ($vars as $k => $v) {
            if ($k === 'link') continue;
            $html .= '<tr><td style="color:#6b7280">' . e(ucfirst(str_replace('_', ' ', $k))) . '</td><td>' . nl2br(e($v)) . '</td></tr>';
        }
        $html .= '</table>';
        if (!empty($vars['link'])) {
            $html .= '<p><a href="' . e($vars['link']) . '">Ouvrir dans le back-office</a></p>';
        }
        return self::send($to, '[' . setting('site_name', 'AFAMSHOP') . '] ' . $subject, $html);
    }
}
