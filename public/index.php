<?php
declare(strict_types=1);

// Basic router + server-side plain PHP templates for smoke testing.
// In a real project use a microframework but keep this simple for now.

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && file_exists($file)) {
        return false; // let the built-in server serve the file
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

function load_sample_data(): array
{
    $file = __DIR__ . '/../data/sample.json';
    if (!file_exists($file)) {
        return ['rankings' => [], 'athletes' => []];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : ['rankings' => [], 'athletes' => []];
}

$dataStore = load_sample_data();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = rtrim($rawPath, '/');
if ($path === '') $path = '/';

// Health check
if ($path === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

// API routes
if (str_starts_with($path, '/api/')) {
    header('Content-Type: application/json');
    if ($path === '/api/edetabel') {
        // query params: discipline, period, sex, year, limit, offset
        $q = $_GET;
        $discipline = $q['discipline'] ?? null;
        $sex = $q['sex'] ?? null;
        $limit = isset($q['limit']) ? (int)$q['limit'] : 50;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;

        $rows = $dataStore['rankings'] ?? [];
        $filtered = array_filter($rows, function ($r) use ($discipline, $sex) {
            if ($discipline && (!isset($r['discipline']) || $r['discipline'] !== $discipline)) return false;
            if ($sex && (!isset($r['sex']) || $r['sex'] !== $sex)) return false;
            return true;
        });
        // sort by place
        usort($filtered, function ($a, $b) {
            return ($a['place'] ?? 0) <=> ($b['place'] ?? 0);
        });
        $paged = array_slice($filtered, $offset, $limit);
        echo json_encode(array_values($paged));
        exit;
    }

    // athlete endpoint: /api/athlete/{iofId}
    if (preg_match('#^/api/athlete/(\d+)$#', $path, $m)) {
        $iofId = $m[1];
        $athletes = $dataStore['athletes'] ?? [];
        $ath = $athletes[$iofId] ?? null;
        if (!$ath) {
            echo json_encode([]);
            exit;
        }
        echo json_encode($ath['events'] ?? []);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    exit;
}

// Page routes (server-side rendered templates)
if ($path === '/') {
    $page = 'overview';
    $viewData = ['rankings' => $dataStore['rankings'] ?? []];
    include __DIR__ . '/templates/overview.php';
    exit;
}

if (preg_match('#^/discipline/([A-Z]{1,3})$#', $path, $m)) {
    $code = $m[1];
    $page = 'discipline';
    $viewData = ['code' => $code, 'rankings' => $dataStore['rankings'] ?? [], 'athletes' => $dataStore['athletes'] ?? []];
    include __DIR__ . '/templates/discipline.php';
    exit;
}

if (preg_match('#^/athlete/(\d+)$#', $path, $m)) {
    $iofId = $m[1];
    $page = 'athlete';
    $viewData = ['iofId' => $iofId, 'athlete' => ($dataStore['athletes'][$iofId] ?? null)];
    include __DIR__ . '/templates/athlete.php';
    exit;
}

// fallback
http_response_code(404);
header('Content-Type: text/plain');
echo "Not found\n";
exit;
