<?php

class Database
{
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
                 . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::fail($e);
            }
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $work)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $work($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // readable message instead of a stack trace
    private static function fail(PDOException $e): void
    {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        $css = (defined('BASE_URL') ? BASE_URL : '') . '/public/css/global.css';
        echo '<!doctype html><meta charset="utf-8">'
           . '<title>Database unavailable</title>'
           . '<link rel="stylesheet" href="' . e($css) . '">'
           . '<div class="db-down">'
           . '<h1>Database unavailable</h1>'
           . '<p>TravelVista could not reach MySQL at <code>' . e(DB_HOST . ':' . DB_PORT)
           . '</code> using the database <code>' . e(DB_NAME) . '</code>.</p>'
           . '<p>Start MySQL, then import <code>database/schema.sql</code> and '
           . '<code>database/seed.sql</code>. Connection details live in '
           . '<code>config/config.php</code>.</p>'
           . '<p class="db-down__detail">Server said: ' . e($e->getMessage()) . '</p>'
           . '</div>';
        exit;
    }
}
