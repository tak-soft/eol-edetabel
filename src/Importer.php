<?php
namespace Eol\Edetabel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Importer
{
    private Client $http;
    private string $apiKey;
    private ?Database $db = null;

    public function __construct(string $apiKey, ?Client $client = null, ?Database $db = null)
    {
        $this->apiKey = $apiKey;
        $this->http = $client ?? new Client([
            'base_uri' => 'https://ranking.orienteering.org',
            'timeout' => 10.0,
        ]);
        $this->db = $db;
    }

    /**
     * Fetch federation rankings for a country and date range.
     * Returns decoded JSON array as found in dok/viip_edetabel.md
     *
     * @return array
     */
    public function fetchFederationRankings(string $countryCode, string $from, string $to): array
    {
        $url = sprintf('/api/export/federationranks/%s?fromDate=%s&toDate=%s', urlencode($countryCode), urlencode($from), urlencode($to));

        try {
            $resp = $this->http->request('GET', $url, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $body = (string)$resp->getBody();
            $data = json_decode($body, true);
            // echo "Fetched " . count($data) . " records from API\n";
            return is_array($data) ? $data : [];
        } catch (GuzzleException $e) {
            // In a real implementation log the exception
           // echo "Error fetching data: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * Persist IOF API rows into database. Expects array of items matching dok/viip_edetabel.md
     * Does basic upsert for events, runners and results. Requires $this->db to be set.
     *
     * @param array $items
     * @return int Count of persisted results
     */
    public function persistResults(array $items): int
    {
        if (!$this->db) return 0;

        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();
        $count = 0;
        try {
            $stmtEvent = $pdo->prepare('INSERT INTO iofevents (eventorId, kuupaev, nimetus, distants, riik, alatunnus) VALUES (:eventorId, :kuupaev, :nimetus, :distants, :riik, :alatunnus) ON DUPLICATE KEY UPDATE nimetus=VALUES(nimetus), distants=VALUES(distants), riik=VALUES(riik), alatunnus=VALUES(alatunnus)');
            $stmtRunner = $pdo->prepare('INSERT INTO iofrunners (iofId, firstname, lastname, sex) VALUES (:iofId, :firstname, :lastname, :sex) ON DUPLICATE KEY UPDATE firstname=VALUES(firstname), lastname=VALUES(lastname), sex=VALUES(sex)');
            $stmtResult = $pdo->prepare('INSERT INTO iofresults (eventorId, iofId, tulemus, koht, RankPoints) VALUES (:eventorId, :iofId, :tulemus, :koht, :RankPoints) ON DUPLICATE KEY UPDATE tulemus=VALUES(tulemus), koht=VALUES(koht), RankPoints=VALUES(RankPoints)');

            foreach ($items as $it) {
                // Map fields from API
                $eventId = $it['EventId'] ?? null;
                $iofId = $it['IofId'] ?? null;
                if (!$eventId || !$iofId) continue;

                $stmtEvent->execute([
                    ':eventorId' => $eventId,
                    ':kuupaev' => isset($it['EventDate']) ? substr($it['EventDate'], 0, 10) : null,
                    ':nimetus' => $it['EventName'] ?? null,
                    ':distants' => $it['Distance'] ?? null,
                    ':riik' => $it['EventCountry'] ?? null,
                    ':alatunnus' => $it['Discipline'] ?? null,
                ]);

                $stmtRunner->execute([
                    ':iofId' => $iofId,
                    ':firstname' => $it['FirstName'] ?? null,
                    ':lastname' => $it['LastName'] ?? null,
                    ':sex' => $it['Group'] ?? null,
                ]);

                $stmtResult->execute([
                    ':eventorId' => $eventId,
                    ':iofId' => $iofId,
                    ':tulemus' => $it['RaceTimeSeconds'] ?? null,
                    ':koht' => $it['Position'] ?? null,
                    ':RankPoints' => $it['RankPoints'] ?? null,
                ]);
                $count++;
            }

            $pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            // log or rethrow in real code
            return 0;
        }
    }
}
