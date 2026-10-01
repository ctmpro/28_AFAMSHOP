<?php
/**
 * Couche d'accès aux données (PDO, requêtes préparées uniquement).
 */
class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true, // autorise la réutilisation d'un même paramètre nommé (échappement sûr via charset utf8mb4 du DSN)
            ]);
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $key = is_int($k) ? $k + 1 : (str_starts_with($k, ':') ? $k : ':' . $k);
            $type = is_int($v) ? PDO::PARAM_INT : (is_null($v) ? PDO::PARAM_NULL : (is_bool($v) ? PDO::PARAM_BOOL : PDO::PARAM_STR));
            $stmt->bindValue($key, $v, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function val(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function col(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function exec(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (:' . implode(',:', $cols) . ')';
        self::query($sql, $data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        $params = [];
        foreach ($data as $k => $v) {
            $set[] = "`$k` = :set_$k";
            $params['set_' . $k] = $v;
        }
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE ' . $where;
        return self::query($sql, array_merge($params, $whereParams))->rowCount();
    }

    /** Construit "IN (:p0,:p1...)" et alimente $params. */
    public static function in(array $values, array &$params, string $prefix = 'in'): string
    {
        if (!$values) {
            return '(NULL)';
        }
        $ph = [];
        foreach (array_values($values) as $i => $v) {
            $ph[] = ':' . $prefix . $i;
            $params[$prefix . $i] = $v;
        }
        return '(' . implode(',', $ph) . ')';
    }

    public static function begin(): void { self::pdo()->beginTransaction(); }
    public static function commit(): void { self::pdo()->commit(); }
    public static function rollBack(): void { if (self::pdo()->inTransaction()) self::pdo()->rollBack(); }
}
