<?php
/**
 * Authentification et permissions du back-office (contrôle côté serveur).
 */
class AdminAuth
{
    /** Modules accessibles par rôle. super_admin a accès à tout (dont admins, logs, settings, migrations). */
    public const PERMISSIONS = [
        'manager' => ['dashboard', 'products', 'categories', 'brands', 'compatibility', 'stock', 'orders', 'payments', 'import', 'promotions', 'coupons', 'reviews', 'delivery', 'stats'],
        'sales'   => ['dashboard', 'customers', 'requests', 'orders', 'payments', 'coupons', 'stats'],
        'editor'  => ['dashboard', 'banners', 'pages', 'services', 'content', 'media', 'reviews'],
    ];

    public const ROLES = [
        'super_admin' => 'Super Admin',
        'manager' => 'Gestionnaire',
        'sales' => 'Commercial',
        'editor' => 'Éditeur',
    ];

    private static ?array $admin = null;

    public static function user(): ?array
    {
        if (self::$admin === null && !empty($_SESSION['admin_id'])) {
            self::$admin = DB::one('SELECT * FROM admins WHERE id = :id AND active = 1', ['id' => (int)$_SESSION['admin_id']]);
            if (!self::$admin) {
                unset($_SESSION['admin_id']);
            }
            // Expiration d'inactivité (2 h)
            if (self::$admin && time() - ($_SESSION['admin_last_seen'] ?? time()) > 7200) {
                self::logout();
                return null;
            }
            $_SESSION['admin_last_seen'] = time();
        }
        return self::$admin;
    }

    public static function id(): ?int
    {
        return self::user() ? (int)self::user()['id'] : null;
    }

    public static function can(string $module): bool
    {
        $a = self::user();
        if (!$a) return false;
        if ($a['role'] === 'super_admin') return true;
        return in_array($module, self::PERMISSIONS[$a['role']] ?? [], true);
    }

    /** À appeler en tête de chaque page admin. */
    public static function require(string $module): array
    {
        $a = self::user();
        if (!$a) {
            if (is_ajax()) json_response(['ok' => false, 'error' => 'auth'], 401);
            redirect(admin_url('login.php', ['retour' => $_SERVER['REQUEST_URI'] ?? '']));
        }
        if (!self::can($module)) {
            http_response_code(403);
            if (is_ajax()) json_response(['ok' => false, 'error' => 'forbidden'], 403);
            flash('error', "Vous n'avez pas les droits nécessaires pour accéder à ce module.");
            redirect(admin_url('index.php'));
        }
        return $a;
    }

    public static function attempt(string $email, string $password): ?string
    {
        $email = strtolower(trim($email));
        if (too_many_attempts('admin', $email, 5, 15)) {
            return 'Trop de tentatives. Réessayez dans 15 minutes.';
        }
        $a = DB::one('SELECT * FROM admins WHERE email = :e', ['e' => $email]);
        if (!$a || !$a['active'] || !password_verify($password, $a['password_hash'])) {
            record_attempt('admin', $email);
            self::log('login_failed', 'admin', null, $email);
            return 'Identifiants incorrects.';
        }
        clear_attempts('admin', $email);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$a['id'];
        $_SESSION['admin_last_seen'] = time();
        self::$admin = null;
        DB::update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $a['id']]);
        self::log('login', 'admin', (int)$a['id']);
        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_last_seen']);
        self::$admin = null;
        session_regenerate_id(true);
    }

    /** Journal administratif. */
    public static function log(string $action, ?string $entity = null, ?int $entityId = null, $details = null): void
    {
        try {
            DB::insert('admin_logs', [
                'admin_id' => $_SESSION['admin_id'] ?? null,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
                'ip' => client_ip(),
            ]);
        } catch (Throwable $e) {
            error_log('admin log: ' . $e->getMessage());
        }
    }
}
