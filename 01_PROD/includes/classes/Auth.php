<?php
/**
 * Authentification des clients.
 */
class Auth
{
    private static ?array $user = null;

    public static function id(): ?int
    {
        return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && self::id()) {
            self::$user = DB::one("SELECT * FROM customers WHERE id = :id AND status = 'active'", ['id' => self::id()]);
            if (!self::$user) {
                unset($_SESSION['customer_id']);
            }
        }
        return self::$user;
    }

    public static function require(): array
    {
        $u = self::user();
        if (!$u) {
            flash('info', __('login_required'));
            redirect(url('compte/connexion', ['retour' => $_SERVER['REQUEST_URI'] ?? '']));
        }
        return $u;
    }

    /** Retourne null si OK, sinon un message d'erreur. */
    public static function attempt(string $email, string $password): ?string
    {
        $email = strtolower(trim($email));
        if (too_many_attempts('customer', $email)) {
            return __('too_many_attempts');
        }
        $u = DB::one('SELECT * FROM customers WHERE email = :e', ['e' => $email]);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            record_attempt('customer', $email);
            return __('bad_credentials');
        }
        if ($u['status'] !== 'active') {
            return __('account_blocked');
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('customers', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        }
        clear_attempts('customer', $email);
        self::login((int)$u['id']);
        return null;
    }

    public static function login(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $id;
        self::$user = null;
        DB::update('customers', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
    }

    public static function logout(): void
    {
        unset($_SESSION['customer_id']);
        self::$user = null;
        session_regenerate_id(true);
    }

    /** Validation commune du mot de passe. */
    public static function passwordError(string $pwd, string $confirm): ?string
    {
        if (mb_strlen($pwd) < 8 || !preg_match('/[A-Za-z]/', $pwd) || !preg_match('/\d/', $pwd)) {
            return __('password_rules');
        }
        if ($pwd !== $confirm) {
            return __('password_mismatch');
        }
        return null;
    }
}
