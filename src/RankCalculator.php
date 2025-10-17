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
        $stmt = $this->pdo->prepare(
            'SELECT ir.iofId, r.firstname, r.lastname, ir.RankPoints, e.eventorId, e.kuupaev, e.nimetus
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
                'totalPoints' => $total,
                'events' => $data['events'],
            ];
        }

        usort($rankings, function ($a, $b) { return ($b['totalPoints'] <=> $a['totalPoints']); });
        // assign places
        $place = 1;
        foreach ($rankings as &$r) {
            $r['place'] = $place++;
        }

        return $rankings;
    }
}
