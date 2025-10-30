<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// load Composer autoloader early so classes like Dotenv are available
require_once __DIR__ . '/../vendor/autoload.php';

// Set UTF-8 headers
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Basic router + server-side plain PHP templates for smoke testing.
// In a real project use a microframework but keep this simple for now.

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && file_exists($file)) {
        return false; // let the built-in server serve the file
    }
}

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

if (!empty($_ENV['DB_HOST'] ?? '') && !empty($_ENV['DB_NAME'] ?? '')) {
    $db = new Eol\Edetabel\Database($_ENV);
    $pdo = $db->getPdo();

    $stmt = $pdo->prepare('SELECT aasta, nimetus, alakood, periood_lopp, periood_kuud, arvesse FROM edetabli_seaded ORDER BY aasta DESC');
    $stmt->execute();
    $rows = $stmt->fetchAll();
    // tehtud: Loeme DBst edetabli_seaded valitud aasta kohta ja teeme dictionary aasta põhjal
    $edetabliSeadedByYear = [];
    $edetabliAvailableYears = [];

    if (is_array($rows)) {
        foreach ($rows as $row) {
            // expect column "aasta"
            if (!isset($row['aasta'])) continue;
            $aasta = (int)$row['aasta'];
            $d = $row['alakood'] ?? null;
            $edetabliSeadedByYear[$aasta][$d] = $row;
            $edetabliAvailableYears[$aasta] = $aasta;
        }
        // sort years descending (most recent first)
        krsort($edetabliSeadedByYear);
        $edetabliAvailableYears = array_values($edetabliAvailableYears);
    }
}

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
        // query params: discipline, period, group, year, limit, offset
        $q = $_GET;
        $discipline = $q['discipline'] ?? null;
        $group = $q['group'] ?? null;
        $limit = isset($q['limit']) ? (int)$q['limit'] : 50;
        $offset = isset($q['offset']) ? (int)$q['offset'] : 0;
        $year = isset($q['year']) ? (int)$q['year'] : (int)date('Y');

        // If DB is configured and discipline provided, compute using RankCalculator and edetabli_seaded
        if (isset($pdo) && $discipline) {
            $calc = new Eol\Edetabel\RankCalculator($pdo);
            $setting = $calc->loadSettingByAlakoodAndYear($discipline, $year);
            if ($setting) {
                $rankings = $calc->computeForSetting($setting);
                // optionally filter by group
                if ($group) {
                    $rankings = array_values(array_filter($rankings, function ($r) use ($group) {
                        return ($r['group'] ?? null) === $group;
                    }));
                }
                $paged = array_slice($rankings, $offset, $limit);
                echo json_encode(array_values($paged));
                exit;
            }
        }
        echo [];
        exit;
    }

    // athlete endpoint: /api/athlete/{iofId}
    if (preg_match('#^/api/athlete/(\d+)$#', $path, $m)) {
        $iofId = $m[1];
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
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

    $groups = ['WOMEN', 'MEN'];
    $overview = [];

    if (isset($pdo)) {
        // load disciplines for the selected year from edetabli_seaded
        $stmtDisc = $pdo->prepare('SELECT DISTINCT alakood, nimetus FROM edetabli_seaded WHERE aasta = :aasta ORDER BY nimetus ASC');
        $stmtDisc->execute([':aasta' => $year]);
        $disciplines = [];
        $disciplineNames = [];
        while ($row = $stmtDisc->fetch(PDO::FETCH_ASSOC)) {
            $code = $row['alakood'] ?? null;
            if ($code) {
                $disciplines[] = $code;
                $disciplineNames[$code] = $row['nimetus'] ?? $code;
            }
        }
        // fallback if settings table is empty
        if (empty($disciplines)) {
            $disciplines = ['F', 'FS', 'M', 'S'];
            $disciplineNames = ['F' => 'Orienteerumisjooks', 'FS' => 'Sprint', 'M' => 'Rattaorienteerumine', 'S' => 'Suusaorienteerumine'];
        }

        $calc = new Eol\Edetabel\RankCalculator($pdo);
        foreach ($disciplines as $d) {
            $rankings = $calc->computeForAlakoodYear($d, $year);
            foreach ($groups as $s) {
                // filter by group/group if available
                $filtered = array_values(array_filter($rankings, function ($r) use ($s) {
                    return ($r['group'] ?? null) === $s;
                }));
                $overview[$d][$s] = array_slice($filtered, 0, 10);
            }
        }
    } 
    $viewData = [
        'overview' => $overview,
        'year' => $year,
        'disciplineNames' => $disciplineNames ?? [],
        'periods' => $edetabliAvailableYears
    ];
    include __DIR__ . '/templates/overview.php';
    exit;
}

if (preg_match('#^/discipline/([A-Z]{1,3})$#', $path, $m)) {
    $discipline = $m[1];
    $page = 'discipline';
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    // Tehtud: See tuleb ümber teha RankCalculatorisse!
    $calc = new Eol\Edetabel\RankCalculator($pdo);
    $rankings = $calc->computeForAlakoodYear($discipline, $year);

    $disciplineName = $edetabliSeadedByYear[$year][$discipline]['nimetus'] ?? $discipline;
    $viewData = ['discipline' => $discipline, 'disciplineName' => $disciplineName, 'rankings' => $rankings ?? []];
    include __DIR__ . '/templates/discipline.php';
    exit;
}

if (preg_match('#^/athlete/(\d+)$#', $path, $m)) {
    $iofId = $m[1];
    $page = 'athlete';
    // Tehtud: Loeme jooksja andmed EOLi tabelist eolkoodid ja iofrunners kui seal ei ole'
    $stmt = $pdo->prepare("SELECT 
            COALESCE(eolk.EESNIMI, r.firstname) AS firstname,
            COALESCE(eolk.PERENIMI, r.lastname) AS lastname,
            COALESCE(eolk.KLUBI, '') AS clubname,
            COALESCE(eolk.SYNNIKUUP, NULL) AS birthdate,
            COALESCE(eolk.FOTO, '') AS photo,
            COALESCE(eolk.KOOD, '') AS eolKood
            FROM iofrunners r  LEFT JOIN eolkoodid eolk ON eolk.IOFKOOD = r.iofId WHERE r.iofId = :iofID LIMIT 1");
    $stmt->execute([':iofID' => $iofId]);
    $athleteData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($athleteData) {
        $athlete = [
            'iofId' => $iofId,
            'eolKood' => $athleteData['eolKood'] ?? '',
            'firstname' => mb_convert_encoding(($athleteData['firstname'] ?? ''), "ISO-8859-1", "UTF-8"),
            'lastname' => mb_convert_encoding(($athleteData['lastname'] ?? ''), "ISO-8859-1", "UTF-8"),
            'clubname' => mb_convert_encoding(($athleteData['clubname'] ?? ''), "ISO-8859-1", "UTF-8"),
            'birthdate' => $athleteData['birthdate'] ?? '',
            'photoUrl' => $athleteData['photo'] ?? '',
            'age' => $athleteData['birthdate'] && $athleteData['birthdate']!='0000-00-00' ? (int)(date('Y') - (int)substr($athleteData['birthdate'], 0, 4)) : ''
        ];
    }
    $stmt2 = $pdo->prepare('SELECT ir.iofId, e.eventorId, e.nimetus, e.alatunnus, e.kuupaev, ir.tulemus, ir.koht, ir.RankPoints, ir.`Group` as `group` FROM iofresults ir JOIN iofevents e ON e.eventorId = ir.eventorId  WHERE ir.iofId = :iofID ORDER BY e.kuupaev DESC');
    $stmt2->execute([':iofID' => $iofId]);
    $eventsAll = $stmt2->fetchAll();
    $events = [];
    foreach ($eventsAll as $ev) {
        $id = (string)$ev['iofId'];
        $events[] = ['eventorId' => $ev['eventorId'] ?? null, 'alatunnus' => $ev['alatunnus'] ?? null, 'date' => $ev['kuupaev'], 'name' => $ev['nimetus'], 'result' => $ev['tulemus'], 'place' => $ev['koht'], 'points' => $ev['RankPoints'], 'group' => $ev['group'] ?? null];
    }

    $viewData = ['iofId' => $athlete['iofId'], 'athlete' => ($athlete ?? null), 'events' => $events ?? []];
    include __DIR__ . '/templates/athlete.php';
    exit;
}

// fallback
http_response_code(404);
header('Content-Type: text/plain');
echo "Not found\n";
exit;
