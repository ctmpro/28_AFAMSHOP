<?php
/**
 * Génération des factures (et bons de commande) au format PDF.
 */
class Invoice
{
    public static function pdf(int $orderId): string
    {
        $o = Orders::find($orderId);
        if (!$o) {
            throw new RuntimeException('Commande introuvable');
        }
        $items = Orders::items($orderId);
        $primary = setting('color_primary', '#0b4f8a');
        $isInvoice = (bool)$o['invoice_number'];

        $pdf = new Pdf();
        $pdf->addPage();
        $L = 40;
        $R = $pdf->pageWidth() - 40;

        // En-tête : logo + infos société
        $y = 40;
        $logo = setting('invoice_logo', setting('logo'));
        $hasLogo = $logo && !preg_match('#^https?://#', $logo) && $pdf->image(UPLOADS_PATH . '/' . $logo, $L, $y, 120);
        if (!$hasLogo) {
            $pdf->setFont(true, 20);
            $pdf->text($L, $y + 18, setting('company_name', 'AFAM'), $primary);
        }
        $pdf->setFont(true, 10);
        $info = [setting('company_legal_name', setting('company_name', 'AFAM'))];
        foreach (['company_address', 'contact_phone', 'contact_email', 'company_ids'] as $k) {
            foreach (preg_split('/\r?\n/', (string)setting($k)) as $l) {
                if (trim($l) !== '') $info[] = trim($l);
            }
        }
        $iy = $y + 8;
        foreach ($info as $i => $line) {
            $pdf->setFont($i === 0, $i === 0 ? 11 : 9);
            $pdf->text($R, $iy, $line, '#333333', 'R');
            $iy += 13;
        }

        // Titre document
        $y = max($iy, 120) + 15;
        $pdf->setFont(true, 18);
        $pdf->text($L, $y, $isInvoice ? 'FACTURE' : 'BON DE COMMANDE', $primary);
        $pdf->setFont(false, 10);
        $y += 20;
        $meta = $isInvoice
            ? ['N° facture' => $o['invoice_number'], 'Date' => format_date($o['invoice_date']), 'Commande' => $o['order_number']]
            : ['Commande' => $o['order_number'], 'Date' => format_date($o['created_at'])];
        $meta['Paiement'] = payment_method_label($o['payment_method']) . ' (' . (payment_statuses()[$o['payment_status']] ?? $o['payment_status']) . ')';
        $my = $y;
        foreach ($meta as $k => $v) {
            $pdf->setFont(true, 9);
            $pdf->text($L, $my, $k . ' :', '#555555');
            $pdf->setFont(false, 9);
            $pdf->text($L + 70, $my, (string)$v);
            $my += 13;
        }

        // Client
        $bx = 320;
        $pdf->rect($bx, $y - 12, $R - $bx, 78, '#f3f5f8');
        $pdf->setFont(true, 9);
        $pdf->text($bx + 10, $y + 2, 'CLIENT', $primary);
        $cy = $y + 16;
        $client = array_filter([
            trim($o['first_name'] . ' ' . $o['last_name']),
            $o['company'],
            $o['delivery_method'] === 'delivery' ? trim($o['ship_address'] . ', ' . $o['ship_city'], ', ') : 'Retrait en magasin',
            $o['phone'],
            $o['email'],
        ]);
        foreach ($client as $i => $line) {
            $pdf->setFont($i === 0, 9);
            $pdf->text($bx + 10, $cy, mb_substr($line, 0, 55));
            $cy += 12;
        }

        // Tableau des produits
        $y = max($my, $y + 70) + 20;
        $cols = ['ref' => $L, 'name' => $L + 85, 'qty' => 380, 'pu' => 460, 'total' => $R];
        $pdf->rect($L, $y - 12, $R - $L, 20, $primary);
        $pdf->setFont(true, 9);
        $pdf->text($cols['ref'] + 6, $y + 2, 'Référence', '#ffffff');
        $pdf->text($cols['name'], $y + 2, 'Désignation', '#ffffff');
        $pdf->text($cols['qty'], $y + 2, 'Qté', '#ffffff', 'R');
        $pdf->text($cols['pu'], $y + 2, 'P.U.', '#ffffff', 'R');
        $pdf->text($cols['total'] - 6, $y + 2, 'Total', '#ffffff', 'R');
        $y += 24;
        $pdf->setFont(false, 9);
        foreach ($items as $it) {
            if ($y > 720) {
                $pdf->addPage();
                $y = 50;
            }
            $lines = $pdf->wrap($it['name'], $cols['qty'] - $cols['name'] - 40);
            $pdf->text($cols['ref'] + 6, $y, mb_substr($it['sku'], 0, 16));
            foreach ($lines as $i => $l) {
                $pdf->text($cols['name'], $y + $i * 11, $l);
            }
            $pdf->text($cols['qty'], $y, (string)$it['qty'], '#000000', 'R');
            $pdf->text($cols['pu'], $y, money_plain($it['unit_price'], $o['currency']), '#000000', 'R');
            $pdf->text($cols['total'] - 6, $y, money_plain($it['line_total'], $o['currency']), '#000000', 'R');
            $y += max(1, count($lines)) * 11 + 6;
            $pdf->line($L, $y - 9, $R, $y - 9, '#e5e7eb');
        }

        // Totaux
        $y += 8;
        $rows = [['Sous-total', money_plain($o['subtotal'], $o['currency'])]];
        if ((float)$o['discount'] > 0) {
            $rows[] = ['Remise' . ($o['coupon_code'] ? ' (' . $o['coupon_code'] . ')' : ''), '-' . money_plain($o['discount'], $o['currency'])];
        }
        $rows[] = ['Livraison' . ($o['zone_name'] ? ' (' . $o['zone_name'] . ')' : ''), money_plain($o['delivery_fee'], $o['currency'])];
        if ((float)$o['tax_rate'] > 0) {
            $rows[] = ['TVA ' . rtrim(rtrim(number_format((float)$o['tax_rate'], 2, ',', ''), '0'), ',') . ' %' . (setting_bool('prices_include_tax', true) ? ' (incluse)' : ''), money_plain($o['tax_amount'], $o['currency'])];
        }
        foreach ($rows as $r) {
            $pdf->setFont(false, 10);
            $pdf->text(460, $y, $r[0], '#333333', 'R');
            $pdf->text($R - 6, $y, $r[1], '#000000', 'R');
            $y += 15;
        }
        $pdf->rect(330, $y - 10, $R - 330, 22, '#f3f5f8');
        $pdf->setFont(true, 12);
        $pdf->text(460, $y + 5, 'TOTAL', $primary, 'R');
        $pdf->text($R - 6, $y + 5, money_plain($o['total'], $o['currency']), $primary, 'R');
        $y += 40;

        // Mentions
        $footer = render_vars(setting('invoice_footer', 'Merci pour votre confiance.'));
        if ($footer) {
            $pdf->setFont(false, 8);
            $pdf->paragraph($L, max($y, 760), $R - $L, $footer, 11, '#666666');
        }
        return $pdf->output();
    }
}
