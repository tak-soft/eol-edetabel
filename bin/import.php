#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Eol\Edetabel\Importer;
use Eol\Edetabel\Database;

$rootEnvDir = realpath(__DIR__ . '/../');
$rootEnvFile = $rootEnvDir . DIRECTORY_SEPARATOR . '.env';
$configEnvDir = realpath(__DIR__ . '/../config');
$configEnvFile = $configEnvDir ? $configEnvDir . DIRECTORY_SEPARATOR . '.env' : null;

if (file_exists($rootEnvFile)) {
    $dotenv = Dotenv::createImmutable($rootEnvDir);
} elseif ($configEnvFile && file_exists($configEnvFile)) {
    $dotenv = Dotenv::createImmutable($configEnvDir);
} else {
    $dotenv = Dotenv::createImmutable($rootEnvDir);
}
$dotenv->safeLoad();

$from = $argv[1] ?? (new DateTimeImmutable('-1 month'))->format('Y-m-d');
$to = $argv[2] ?? (new DateTimeImmutable())->format('Y-m-d');

$apiKey = $_ENV['RANKING_API_KEY'] ?? '';
if (empty($apiKey)) {
    fwrite(STDERR, "RANKING_API_KEY not set. Fill config/.env.example and copy to .env\n");
    exit(2);
}

try {
    $db = new Database($_ENV);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . PHP_EOL);
    $db = null;
}

$importer = new Importer($apiKey, null, $db);
$data = $importer->fetchFederationRankings('EST', $from, $to);

if ($db && count($data) > 0) {
    $persisted = $importer->persistResults($data);
    echo "Persisted: $persisted rows\n";
} else {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
