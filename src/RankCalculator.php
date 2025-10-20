<?php
namespace Eol\Edetabel;

use DateTimeImmutable;
use DateInterval;
use PDO;

class RankCalculator
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Load setting by discipline code (alakood). Returns associative array or null.
     */
    public function loadSettingByAlakood(string $alakood)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM edetabli_seaded WHERE alakood = :alakood ORDER BY aasta DESC LIMIT 1');
        $stmt->execute([':alakood' => $alakood]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Load setting by alakood and year
     */
    public function loadSettingByAlakoodAndYear(string $alakood, int $year)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM edetabli_seaded WHERE alakood = :alakood AND aasta = :aasta LIMIT 1');
        $stmt->execute([':alakood' => $alakood, ':aasta' => $year]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function loadPeriods(){
        $stmt = $this->pdo->prepare('SELECT DISTINCT aasta FROM edetabli_seaded ORDER BY aasta DESC');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $periods = [];
        foreach ($rows as $row) {
            $periods[] = (int)$row['aasta'];
        }
        return $periods;
    }

    /**
     * Convenience: compute for a discipline code and year (if setting exists).
     */
    public function computeForAlakoodYear(string $alakood, int $year): array
    {
        $setting = $this->loadSettingByAlakoodAndYear($alakood, $year);
        if (!$setting) return [];
        return $this->computeForSetting($setting);
    }

    /**
     * Compute rankings for a given setting row.
     * Returns array of rows: [ ['place'=>1,'iofId'=>..., 'firstname'=>..., 'lastname'=>..., 'totalPoints'=>..., 'events'=>[...]] , ... ]
     */
    public function computeForSetting(array $setting): array
    {
        $alakood = $setting['alakood'];
        $periodMonths = (int)($setting['periood_kuud'] ?? 12);
        $takeBest = (int)($setting['arvesse'] ?? 0);

        $endDate = $setting['periood_lopp'] ? new DateTimeImmutable($setting['periood_lopp']) : new DateTimeImmutable();
        $startDate = $endDate->sub(new DateInterval('P' . max(1, $periodMonths) . 'M'));

        // fetch relevant results in window
        // Note: Group (MEN/WOMEN) is stored per-result in iofresults as `Group`.
        // Prefer reading runner name from iofrunners and Group from iofresults.
        $stmt = $this->pdo->prepare(
            'SELECT ir.iofId, r.firstname, r.lastname, ir.`Group` AS runnerGroup, ir.RankPoints, e.eventorId, e.kuupaev, e.nimetus
             FROM iofresults ir
             JOIN iofevents e ON e.eventorId = ir.eventorId
             JOIN iofrunners r ON r.iofId = ir.iofId
             WHERE e.alatunnus = :alakood AND e.kuupaev BETWEEN :start AND :end'
        );
        $stmt->execute([':alakood' => $alakood, ':start' => $startDate->format('Y-m-d'), ':end' => $endDate->format('Y-m-d')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byAthlete = [];
        foreach ($rows as $row) {
            $id = (string)$row['iofId'];
            $points = isset($row['RankPoints']) ? (float)$row['RankPoints'] : 0.0;
            $event = [
                'eventorId' => $row['eventorId'],
                'date' => $row['kuupaev'],
                'name' => $row['nimetus'],
                'points' => $points,
            ];
            $byAthlete[$id]['iofId'] = (int)$row['iofId'];
            $byAthlete[$id]['firstname'] = $row['firstname'];
            $byAthlete[$id]['lastname'] = $row['lastname'];
            // map per-result Group into athlete-level sex/group; use first seen
            $byAthlete[$id]['sex'] = $row['runnerGroup'] ?? null;
            $byAthlete[$id]['events'][] = $event;
        }

        $rankings = [];
        foreach ($byAthlete as $id => $data) {
            // sort athlete events by points desc
            usort($data['events'], function ($a, $b) { return ($b['points'] <=> $a['points']); });
            // take top N if arvesse>0 else sum all
            $toTake = $takeBest > 0 ? array_slice($data['events'], 0, $takeBest) : $data['events'];
            $total = 0.0;
            foreach ($toTake as $ev) $total += (float)$ev['points'];

            $rankings[] = [
                'iofId' => $data['iofId'],
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'sex' => $data['sex'] ?? null,
                'totalPoints' => $total,
                'events' => $data['events'],
                // which events were counted towards the total (top N or all)
                'countedEvents' => $toTake,
            ];
        }

        usort($rankings, function ($a, $b) { return ($b['totalPoints'] <=> $a['totalPoints']); });

        // assign places with tie handling per sex/group (standard competition ranking): equal totals -> same place, next place skips
        $epsilon = 1e-6;
        // group rankings by sex (null/unknown grouped under empty string)
        $groups = [];
        foreach ($rankings as $r) {
            $key = $r['sex'] ?? '';
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][] = $r;
        }

        $final = [];
        foreach ($groups as $key => $list) {
            // sort each group's list by totalPoints desc to be safe
            usort($list, function ($a, $b) { return ($b['totalPoints'] <=> $a['totalPoints']); });
            $count = count($list);
            for ($i = 0; $i < $count; $i++) {
                if ($i === 0) {
                    $list[$i]['place'] = 1;
                } else {
                    $prev = $list[$i - 1]['totalPoints'];
                    $curr = $list[$i]['totalPoints'];
                    if (abs($curr - $prev) < $epsilon) {
                        $list[$i]['place'] = $list[$i - 1]['place'];
                    } else {
                        $list[$i]['place'] = $i + 1;
                    }
                }
            }
            // append group results to final list
            foreach ($list as $item) $final[] = $item;
        }

        // final contains all athletes with place assigned per their group
        $rankings = $final;

        return $rankings;
    }
}
