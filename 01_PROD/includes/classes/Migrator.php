<?php
/**
 * Migrations de base de données exécutées depuis le back-office.
 *
 * - Fichiers .sql déposés dans sql/migrations/ (exécutés une seule fois, dans l'ordre alphabétique)
 * - SQL saisi ou envoyé manuellement depuis la page Admin › Migration
 * - Sauvegarde automatique de la base avant exécution (uploads/private/backups)
 * - Historique complet dans la table `migrations`
 */
class Migrator
{
    public const DIR = ROOT_PATH . '/sql/migrations';
    public const BACKUP_DIR = UPLOADS_PATH . '/private/backups';
    public const KEEP_BACKUPS = 20;

    /** Crée la table d'historique si elle n'existe pas encore. */
    public static function ensureTable(): void
    {
        DB::pdo()->exec("CREATE TABLE IF NOT EXISTS migrations (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name         VARCHAR(190) NOT NULL,
            source       ENUM('file','manual') NOT NULL DEFAULT 'file',
            checksum     CHAR(64) NULL,
            sql_content  MEDIUMTEXT NULL,
            status       ENUM('success','error','marked') NOT NULL,
            statements   INT NOT NULL DEFAULT 0,
            error        TEXT NULL,
            duration_ms  INT NOT NULL DEFAULT 0,
            backup_file  VARCHAR(190) NULL,
            admin_id     INT UNSIGNED NULL,
            executed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /** Fichiers de migration disponibles : [nom => chemin]. */
    public static function files(): array
    {
        $out = [];
        foreach (glob(self::DIR . '/*.sql') ?: [] as $f) {
            $out[basename($f)] = $f;
        }
        ksort($out, SORT_NATURAL);
        return $out;
    }

    /** Noms des fichiers déjà exécutés avec succès (ou marqués comme exécutés). */
    public static function appliedNames(): array
    {
        return DB::col("SELECT DISTINCT name FROM migrations WHERE source = 'file' AND status IN ('success','marked')");
    }

    /** Fichiers en attente d'exécution. */
    public static function pending(): array
    {
        $applied = array_flip(self::appliedNames());
        return array_filter(self::files(), fn($name) => !isset($applied[$name]), ARRAY_FILTER_USE_KEY);
    }

    /** Marque des fichiers comme exécutés sans les lancer (ex. déjà passés dans phpMyAdmin). */
    public static function markApplied(string $name): void
    {
        $file = self::files()[$name] ?? null;
        if (!$file) {
            throw new RuntimeException('Fichier de migration introuvable.');
        }
        $sql = (string)file_get_contents($file);
        DB::insert('migrations', [
            'name' => $name, 'source' => 'file', 'checksum' => hash('sha256', $sql), 'sql_content' => $sql,
            'status' => 'marked', 'admin_id' => $_SESSION['admin_id'] ?? null,
        ]);
    }

    /**
     * Découpe un script SQL en requêtes (gère chaînes, commentaires et la directive DELIMITER).
     */
    public static function split(string $sql): array
    {
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', str_replace("\r\n", "\n", $sql));
        $statements = [];
        $delimiter = ';';
        $buffer = '';
        $len = strlen($sql);
        $quote = null;
        $i = 0;
        while ($i < $len) {
            $ch = $sql[$i];
            // Directive DELIMITER (en début de ligne, hors chaîne)
            if ($quote === null && ($i === 0 || $sql[$i - 1] === "\n") && preg_match('/\GDELIMITER\s+(\S+)[^\n]*\n?/i', $sql, $m, 0, $i)) {
                if (trim($buffer) !== '') $statements[] = trim($buffer);
                $buffer = '';
                $delimiter = $m[1];
                $i += strlen($m[0]);
                continue;
            }
            if ($quote !== null) {
                $buffer .= $ch;
                if ($ch === '\\' && $quote !== '`' && $i + 1 < $len) {
                    $buffer .= $sql[++$i];
                } elseif ($ch === $quote) {
                    if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                        $buffer .= $sql[++$i]; // guillemet doublé
                    } else {
                        $quote = null;
                    }
                }
                $i++;
                continue;
            }
            // Commentaires
            if ($ch === '#' || ($ch === '-' && substr($sql, $i, 2) === '--' && ($i + 2 >= $len || ctype_space($sql[$i + 2])))) {
                $end = strpos($sql, "\n", $i);
                $i = $end === false ? $len : $end + 1;
                $buffer .= "\n";
                continue;
            }
            if ($ch === '/' && substr($sql, $i, 2) === '/*' && substr($sql, $i, 3) !== '/*!') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 2;
                $buffer .= ' ';
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $quote = $ch;
                $buffer .= $ch;
                $i++;
                continue;
            }
            if (substr($sql, $i, strlen($delimiter)) === $delimiter) {
                if (trim($buffer) !== '') $statements[] = trim($buffer);
                $buffer = '';
                $i += strlen($delimiter);
                continue;
            }
            $buffer .= $ch;
            $i++;
        }
        if (trim($buffer) !== '') $statements[] = trim($buffer);
        return $statements;
    }

    /**
     * Exécute un script. S'arrête à la première erreur.
     * Retourne ['ok'=>bool, 'results'=>[[sql, rows|null, affected|null, error|null]], 'error'=>?, 'statements'=>n, 'duration_ms'=>n]
     */
    public static function execute(string $sql, string $name, string $source = 'manual', ?string $backup = null): array
    {
        @set_time_limit(600);
        $statements = self::split($sql);
        $results = [];
        $error = null;
        $start = microtime(true);
        $pdo = DB::pdo();
        foreach ($statements as $n => $stmt) {
            try {
                if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $stmt)) {
                    $q = $pdo->query($stmt);
                    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                    $q->closeCursor();
                    $results[] = ['sql' => $stmt, 'rows' => array_slice($rows, 0, 200), 'count' => count($rows), 'affected' => null, 'error' => null];
                } else {
                    $affected = $pdo->exec($stmt);
                    $results[] = ['sql' => $stmt, 'rows' => null, 'count' => null, 'affected' => (int)$affected, 'error' => null];
                }
            } catch (Throwable $e) {
                $error = 'Requête n° ' . ($n + 1) . ' : ' . $e->getMessage();
                $results[] = ['sql' => $stmt, 'rows' => null, 'count' => null, 'affected' => null, 'error' => $e->getMessage()];
                if ($pdo->inTransaction()) $pdo->rollBack();
                break;
            }
        }
        $duration = (int)round((microtime(true) - $start) * 1000);
        DB::insert('migrations', [
            'name' => mb_substr($name, 0, 190),
            'source' => $source,
            'checksum' => hash('sha256', $sql),
            'sql_content' => $sql,
            'status' => $error ? 'error' : 'success',
            'statements' => count(array_filter($results, fn($r) => $r['error'] === null)),
            'error' => $error,
            'duration_ms' => $duration,
            'backup_file' => $backup,
            'admin_id' => $_SESSION['admin_id'] ?? null,
        ]);
        return ['ok' => $error === null, 'results' => $results, 'error' => $error, 'statements' => count($statements), 'duration_ms' => $duration];
    }

    // -----------------------------------------------------------------
    // Sauvegardes
    // -----------------------------------------------------------------
    /** Sauvegarde complète de la base (structure + données) compressée en .sql.gz. Retourne le nom du fichier. */
    public static function backup(): string
    {
        @set_time_limit(600);
        if (!is_dir(self::BACKUP_DIR) && !mkdir(self::BACKUP_DIR, 0755, true)) {
            throw new RuntimeException('Impossible de créer le dossier de sauvegarde.');
        }
        // Protection supplémentaire : aucun accès web direct aux sauvegardes
        if (!is_file(UPLOADS_PATH . '/private/.htaccess')) {
            @file_put_contents(UPLOADS_PATH . '/private/.htaccess', "Require all denied\n");
        }
        $name = 'sauvegarde-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.sql.gz';
        $gz = gzopen(self::BACKUP_DIR . '/' . $name, 'wb6');
        if (!$gz) {
            throw new RuntimeException('Impossible d\'écrire la sauvegarde.');
        }
        $pdo = DB::pdo();
        gzwrite($gz, "-- Sauvegarde " . DB_NAME . " du " . date('d/m/Y H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$table, $type]) {
            if ($type !== 'BASE TABLE') continue;
            $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS `$table`;\n$create;\n\n");
            $stmt = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
            $batch = [];
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
                if (count($batch) >= 200) {
                    gzwrite($gz, "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) gzwrite($gz, "INSERT INTO `$table` VALUES\n" . implode(",\n", $batch) . ";\n");
            gzwrite($gz, "\n");
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
        gzclose($gz);
        self::pruneBackups();
        return $name;
    }

    /** Liste des sauvegardes (les plus récentes d'abord). */
    public static function backups(): array
    {
        $out = [];
        foreach (glob(self::BACKUP_DIR . '/sauvegarde-*.sql.gz') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** Chemin sûr d'une sauvegarde à partir de son nom (null si invalide). */
    public static function backupPath(string $name): ?string
    {
        if (!preg_match('/^sauvegarde-\d{8}-\d{6}-[a-f0-9]{6}\.sql\.gz$/', $name)) {
            return null;
        }
        $path = self::BACKUP_DIR . '/' . $name;
        return is_file($path) ? $path : null;
    }

    private static function pruneBackups(): void
    {
        foreach (array_slice(self::backups(), self::KEEP_BACKUPS) as $old) {
            @unlink(self::BACKUP_DIR . '/' . $old['name']);
        }
    }
}
