<?php
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $instance = null;

    public static function conn(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function row(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function rows(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int {
        $cols   = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `$table` ($cols) VALUES ($places)", array_values($data));
        return (int) self::conn()->lastInsertId();
    }

    public static function update(string $table, array $data, array $where): int {
        $set   = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $cond  = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
        $stmt  = self::query("UPDATE `$table` SET $set WHERE $cond",
                             array_merge(array_values($data), array_values($where)));
        return $stmt->rowCount();
    }

    public static function delete(string $table, array $where): int {
        $cond = implode(' AND ', array_map(fn($c) => "`$c` = ?", array_keys($where)));
        return self::query("DELETE FROM `$table` WHERE $cond", array_values($where))->rowCount();
    }

    public static function paginate(string $sql, array $params, int $page, int $perPage): array {
        $countSql = "SELECT COUNT(*) FROM ($sql) AS _count";
        $total    = (int) self::value($countSql, $params);
        $pages    = (int) ceil($total / $perPage);
        $offset   = ($page - 1) * $perPage;
        $rows     = self::rows("$sql LIMIT $perPage OFFSET $offset", $params);
        return compact('rows', 'total', 'pages', 'page', 'perPage');
    }

    public static function beginTransaction(): void { self::conn()->beginTransaction(); }
    public static function commit(): void            { self::conn()->commit(); }
    public static function rollback(): void          { self::conn()->rollBack(); }
}
