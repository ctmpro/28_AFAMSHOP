<?php
/**
 * Google reCAPTCHA (v2 case à cocher ou v3). Utilise la librairie du dossier
 * utilitaires/ReCaptcha si présente, sinon un appel direct à l'API de vérification.
 * Désactivé automatiquement si les clés ne sont pas renseignées dans le .env.
 */
class Recaptcha
{
    public static function enabled(): bool
    {
        return (string)env('RECAPTCHA_SITE_KEY', '') !== '' && (string)env('RECAPTCHA_SECRET_KEY', '') !== '' && setting_bool('recaptcha_enabled', true);
    }

    public static function version(): string
    {
        return (string)env('RECAPTCHA_VERSION', 'v2') === 'v3' ? 'v3' : 'v2';
    }

    /** Script + widget à insérer dans un formulaire. */
    public static function widget(string $action = 'submit'): string
    {
        if (!self::enabled()) {
            return '';
        }
        $key = e((string)env('RECAPTCHA_SITE_KEY'));
        if (self::version() === 'v3') {
            return '<input type="hidden" name="g-recaptcha-response" class="recaptcha-v3" data-sitekey="' . $key . '" data-action="' . e($action) . '">'
                . '<script src="https://www.google.com/recaptcha/api.js?render=' . $key . '" async defer></script>';
        }
        return '<div class="g-recaptcha" data-sitekey="' . $key . '"></div><script src="https://www.google.com/recaptcha/api.js?hl=fr" async defer></script>';
    }

    public static function verify(): bool
    {
        if (!self::enabled()) {
            return true;
        }
        $response = (string)($_POST['g-recaptcha-response'] ?? '');
        if ($response === '') {
            return false;
        }
        $secret = (string)env('RECAPTCHA_SECRET_KEY');

        foreach (['ReCaptcha/src/autoload.php', 'ReCaptcha/autoload.php', 'recaptcha/src/autoload.php', 'recaptcha/autoload.php'] as $f) {
            if (is_file(UTILS_PATH . '/' . $f)) {
                require_once UTILS_PATH . '/' . $f;
                break;
            }
        }
        if (class_exists('ReCaptcha\\ReCaptcha')) {
            $rc = new ReCaptcha\ReCaptcha($secret);
            if (self::version() === 'v3') {
                $rc->setScoreThreshold((float)env('RECAPTCHA_MIN_SCORE', 0.5));
            }
            return $rc->verify($response, client_ip())->isSuccess();
        }

        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $response, 'remoteip' => client_ip()]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $res = json_decode((string)curl_exec($ch), true);
        curl_close($ch);
        if (empty($res['success'])) {
            return false;
        }
        return self::version() !== 'v3' || (float)($res['score'] ?? 0) >= (float)env('RECAPTCHA_MIN_SCORE', 0.5);
    }
}
