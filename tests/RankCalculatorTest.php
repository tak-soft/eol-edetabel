<?php
use PHPUnit\Framework\TestCase;
use Eol\Edetabel\RankCalculator;

class RankCalculatorTest extends TestCase
{
    private function createSchema(PDO $pdo)
    {
        $pdo->exec("CREATE TABLE iofevents (eventorId INTEGER PRIMARY KEY, kuupaev TEXT, nimetus TEXT, distants TEXT, riik TEXT, alatunnus TEXT);");
    $pdo->exec("CREATE TABLE iofrunners (iofId INTEGER PRIMARY KEY, firstname TEXT, lastname TEXT);");
    // include Group column per-result
    $pdo->exec("CREATE TABLE iofresults (id INTEGER PRIMARY KEY AUTOINCREMENT, eventorId INTEGER, iofId INTEGER, tulemus INTEGER, koht INTEGER, RankPoints REAL, `Group` TEXT);");
        $pdo->exec("CREATE TABLE edetabli_seaded (id INTEGER PRIMARY KEY AUTOINCREMENT, aasta INTEGER, nimetus TEXT, alakood TEXT, periood_lopp TEXT, periood_kuud INTEGER, arvesse INTEGER);");
    }

    public function testTieHandlingStandardCompetitionRanking()
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite not available');
        }
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema($pdo);

        // insert setting for discipline F year 2025
        $stmt = $pdo->prepare('INSERT INTO edetabli_seaded (aasta, nimetus, alakood, periood_lopp, periood_kuud, arvesse) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([2025, 'Test F 2025', 'F', '2025-12-31', 12, 0]);

        // create one event in window
        $pdo->exec("INSERT INTO iofevents (eventorId, kuupaev, nimetus, alatunnus) VALUES (1, '2025-06-01', 'Event 1', 'F');");

        // runners
    $pdo->exec("INSERT INTO iofrunners (iofId, firstname, lastname) VALUES (101, 'Alice', 'A');");
    $pdo->exec("INSERT INTO iofrunners (iofId, firstname, lastname) VALUES (102, 'Bob', 'B');");
    $pdo->exec("INSERT INTO iofrunners (iofId, firstname, lastname) VALUES (103, 'Chris', 'C');");

        // results: Alice 50, Bob 50 -> tie for first; Chris 30 -> third
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (1, 101, 3600, 1, 50, 'WOMEN');");
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (1, 102, 3700, 2, 50, 'MEN');");
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (1, 103, 4200, 3, 30, 'MEN');");

        $calc = new RankCalculator($pdo);
        $rankings = $calc->computeForAlakoodYear('F', 2025);

        // we expect 3 athletes
        $this->assertCount(3, $rankings);

        // find by iofId
        $byId = [];
        foreach ($rankings as $r) $byId[$r['iofId']] = $r;

        $this->assertEquals(1, $byId[101]['place']);
        $this->assertEquals(1, $byId[102]['place']);
        $this->assertEquals(3, $byId[103]['place']);
    }

    public function testArvesseTakeBestBehavior()
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers())) {
            $this->markTestSkipped('pdo_sqlite not available');
        }
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->createSchema($pdo);

        // setting with arvesse = 1 (take best 1)
        $stmt = $pdo->prepare('INSERT INTO edetabli_seaded (aasta, nimetus, alakood, periood_lopp, periood_kuud, arvesse) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([2025, 'Test F 2025', 'F', '2025-12-31', 12, 1]);

        // two events
        $pdo->exec("INSERT INTO iofevents (eventorId, kuupaev, nimetus, alatunnus) VALUES (1, '2025-05-01', 'E1', 'F');");
        $pdo->exec("INSERT INTO iofevents (eventorId, kuupaev, nimetus, alatunnus) VALUES (2, '2025-06-01', 'E2', 'F');");

        // runners
    $pdo->exec("INSERT INTO iofrunners (iofId, firstname, lastname) VALUES (201, 'Ann', 'A');");
    $pdo->exec("INSERT INTO iofrunners (iofId, firstname, lastname) VALUES (202, 'Ben', 'B');");

        // Ann: 40 & 30 -> best 40; Ben: 35 & 35 -> best 35 -> Ann wins
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (1, 201, 3600, 1, 40, 'WOMEN');");
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (2, 201, 3800, 2, 30, 'WOMEN');");
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (1, 202, 3700, 1, 35, 'MEN');");
    $pdo->exec("INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints, `Group`) VALUES (2, 202, 3600, 2, 35, 'MEN');");

        $calc = new RankCalculator($pdo);
        $rankings = $calc->computeForAlakoodYear('F', 2025);

        $this->assertCount(2, $rankings);
        $byId = [];
        foreach ($rankings as $r) $byId[$r['iofId']] = $r;

        $this->assertGreaterThan($byId[202]['totalPoints'], $byId[201]['totalPoints']);
        $this->assertEquals(1, $byId[201]['place']);
    }
}
