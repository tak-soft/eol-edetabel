<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// load Composer autoloader early so classes like Dotenv are available
require_once __DIR__ . '/../vendor/autoload.php';

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

// try to connect to DB; if unavailable, fall back to sample data
/*
$dataStore = ['rankings' => [], 'athletes' => []];
try {
    if (!empty($_ENV['DB_HOST'] ?? '') && !empty($_ENV['DB_NAME'] ?? '')) {
        $db = new Eol\Edetabel\Database($_ENV);
        $pdo = $db->getPdo();

        // Simple aggregation: select top runners by sum of RankPoints grouped by iofId
        // Group (MEN/WOMEN) is stored per-result in iofresults; take any value (MAX) as representative
        $stmt = $pdo->prepare('SELECT r.iofId, r.firstname, r.lastname, MAX(ir.`Group`) AS group, SUM(ir.RankPoints) AS points FROM iofrunners r JOIN iofresults ir ON r.iofId = ir.iofId GROUP BY r.iofId ORDER BY points DESC LIMIT 100');
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $rankings = [];
        $place = 1;
        foreach ($rows as $row) {
            $rankings[] = ['place' => $place++, 'firstname' => $row['firstname'], 'lastname' => $row['lastname'], 'iofId' => (int)$row['iofId'], 'points' => (float)$row['points'], 'group' => $row['group']];
        }

        // athletes: map iofId -> events
        $stmt2 = $pdo->query('SELECT ir.iofId, r.firstname, r.lastname, e.eventorId, e.nimetus, e.alatunnus, e.kuupaev, ir.tulemus, ir.koht, ir.RankPoints, ir.`Group` as `group` FROM iofresults ir JOIN iofevents e ON e.eventorId = ir.eventorId JOIN iofrunners r ON r.iofId = ir.iofId ORDER BY e.kuupaev DESC');
        $events = $stmt2->fetchAll();
        $athletes = [];
        foreach ($events as $ev) {
            $id = (string)$ev['iofId'];
            // set runner name from iofrunners
            $athletes[$id]['firstname'] = $ev['firstname'] ?? ($athletes[$id]['firstname'] ?? '');
            $athletes[$id]['lastname'] = $ev['lastname'] ?? ($athletes[$id]['lastname'] ?? '');
            $athletes[$id]['events'][] = ['eventorId' => $ev['eventorId'] ?? null, 'alatunnus' => $ev['alatunnus'] ?? null, 'date' => $ev['kuupaev'], 'name' => $ev['nimetus'], 'result' => $ev['tulemus'], 'place' => $ev['koht'], 'points' => $ev['RankPoints'], 'group' => $ev['group'] ?? null];
        }

        $dataStore = ['rankings' => $rankings, 'athletes' => $athletes];
    }
} catch (Throwable $e) {
    // fallback to sample data file
    $file = __DIR__ . '/../data/sample.json';
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        $dataStore = is_array($data) ? $data : $dataStore;
    }
}
    */
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
        /*
        // fallback to sample or precomputed dataStore
        $rows = $dataStore['rankings'] ?? [];
        $filtered = array_filter($rows, function ($r) use ($discipline, $group) {
            if ($discipline && (!isset($r['discipline']) || $r['discipline'] !== $discipline)) return false;
            if ($group && (!isset($r['group']) || $r['group'] !== $group)) return false;
            return true;
        });
        // sort by place
        usort($filtered, function ($a, $b) {
            return ($a['place'] ?? 0) <=> ($b['place'] ?? 0);
        });
        $paged = array_slice($filtered, $offset, $limit);
        echo json_encode(array_values($paged));
*/
        echo [];
        exit;
    }

    // athlete endpoint: /api/athlete/{iofId}
    if (preg_match('#^/api/athlete/(\d+)$#', $path, $m)) {
        $iofId = $m[1];
        // Tudu: Loeme jooksja andmed EOLi tabelist eolkoodid ja iofrunners kui seal ei ole
        // Peab arvutama tema edetabeli punktid RankCalculatoriga?
        // Jooksja tulemused loeb samuti DBst.

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

    $groups = [ 'WOMEN', 'MEN'];
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
    } /* else {
        // fallback to sample dataStore grouping
        $disciplines = ['F','FS','M','S'];
        $disciplineNames = ['F' => 'Orienteerumisjooks', 'FS' => 'Sprint', 'M' => 'Rattaorienteerumine', 'S' => 'Suusaorienteerumine'];
        foreach ($disciplines as $d) {
            foreach ($groupes as $s) {
                $rows = array_filter($dataStore['rankings'] ?? [], function($r) use ($d, $s) { return ($r['discipline'] ?? $d) === $d && ($r['group'] ?? $s) === $s; });
                usort($rows, function($a,$b){ return ($b['points'] <=> $a['points']); });
                $overview[$d][$s] = array_slice(array_values($rows), 0, 10);
            }
        }
    }*/

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
    //  if (isset($pdo)) {
    // Tudu: See tuleb ümber teha RankCalculatorisse!
    $calc = new Eol\Edetabel\RankCalculator($pdo);
    $rankings = $calc->computeForAlakoodYear($discipline, $year);
    /*
        $stmt = $pdo->prepare('SELECT nimetus FROM edetabli_seaded WHERE alakood = :alakood AND aasta = :aasta LIMIT 1');
        $stmt->execute([':alakood' => $code, ':aasta' => $year]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['nimetus'])) {
            $disciplineName = $row['nimetus'];
        }
            */
    //  }
    $disciplineName= $edetabliSeadedByYear[$year][$discipline]['nimetus'] ?? $discipline;
    $viewData = ['discipline'=>$discipline, 'disciplineName' => $disciplineName, 'rankings' => $rankings ?? []];
    include __DIR__ . '/templates/discipline.php';
    exit;
}

if (preg_match('#^/athlete/(\d+)$#', $path, $m)) {
    $iofId = $m[1];
    $page = 'athlete';
    // Tudu: Loeme jooksja andmed EOLi tabelist eolkoodid ja iofrunners kui seal ei ole
        $stmt2 = $pdo->prepare('SELECT ir.iofId, r.firstname, r.lastname, e.eventorId, e.nimetus, e.alatunnus, e.kuupaev, ir.tulemus, ir.koht, ir.RankPoints, ir.`Group` as `group` FROM iofresults ir JOIN iofevents e ON e.eventorId = ir.eventorId JOIN iofrunners r ON r.iofId = ir.iofId WHERE r.iofId = :iofID ORDER BY e.kuupaev DESC');
        $stmt2->execute([':iofID' => $iofId]);
        $events = $stmt2->fetchAll();
        $athletes = [];
        foreach ($events as $ev) {
            $id = (string)$ev['iofId'];
            // set runner name from iofrunners
            $athletes[$id]['firstname'] = $ev['firstname'] ?? ($athletes[$id]['firstname'] ?? '');
            $athletes[$id]['lastname'] = $ev['lastname'] ?? ($athletes[$id]['lastname'] ?? '');
            $athletes[$id]['events'][] = ['eventorId' => $ev['eventorId'] ?? null, 'alatunnus' => $ev['alatunnus'] ?? null, 'date' => $ev['kuupaev'], 'name' => $ev['nimetus'], 'result' => $ev['tulemus'], 'place' => $ev['koht'], 'points' => $ev['RankPoints'], 'group' => $ev['group'] ?? null];
        }
        // tudu: võistleja andmed eolkoodid tabelist


    $viewData = ['iofId' => $iofId, 'athlete' => ($athletes[$iofId] ?? null)];
    include __DIR__ . '/templates/athlete.php';
    exit;
}

// fallback
http_response_code(404);
header('Content-Type: text/plain');
echo "Not found\n";
exit;
