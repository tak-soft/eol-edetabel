#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Eol\Edetabel\Importer;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$from = $argv[1] ?? (new DateTimeImmutable('-1 month'))->format('Y-m-d');
$to = $argv[2] ?? (new DateTimeImmutable())->format('Y-m-d');

$apiKey = $_ENV['RANKING_API_KEY'] ?? '';
if (empty($apiKey)) {
    fwrite(STDERR, "RANKING_API_KEY not set. Fill config/.env.example and copy to .env\n");
    exit(2);
}

$importer = new Importer($apiKey);
$data = $importer->fetchFederationRankings('EST', $from, $to);

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
