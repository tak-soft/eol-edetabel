<?php
namespace Eol\Edetabel;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(array $env)
    {
        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $name = $env['DB_NAME'] ?? 'edetabel';
        $user = $env['DB_USER'] ?? 'root';
        $pass = $env['DB_PASS'] ?? '';

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
