<?php
namespace Eol\Edetabel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Importer
{
    private Client $http;
    private string $apiKey;

    public function __construct(string $apiKey, ?Client $client = null)
    {
        $this->apiKey = $apiKey;
        $this->http = $client ?? new Client([
            'base_uri' => 'https://ranking.orienteering.org',
            'timeout' => 10.0,
        ]);
    }

    /**
     * Fetch federation rankings for a country and date range.
     * Returns decoded JSON array as found in dok/viip_edetabel.md
     *
     * @return array
     */
    public function fetchFederationRankings(string $countryCode, string $from, string $to): array
    {
        $url = sprintf('/api/exports/federationrankings/%s?fromD=%s&toD=%s', urlencode($countryCode), urlencode($from), urlencode($to));

        try {
            $resp = $this->http->request('GET', $url, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $body = (string)$resp->getBody();
            $data = json_decode($body, true);
            return is_array($data) ? $data : [];
        } catch (GuzzleException $e) {
            // In a real implementation log the exception
            return [];
        }
    }
}
