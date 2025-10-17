<?php
use PHPUnit\Framework\TestCase;
use Eol\Edetabel\Importer;

class ImporterTest extends TestCase
{
    public function testFetchFederationRankingsReturnsArray()
    {
        $mockClient = $this->createMock(GuzzleHttp\Client::class);
    $mockResponse = $this->createMock(Psr\Http\Message\ResponseInterface::class);
    $stream = $this->createMock(Psr\Http\Message\StreamInterface::class);
    $stream->method('__toString')->willReturn(json_encode([['EventId' => 1]]));
    $mockResponse->method('getBody')->willReturn($stream);

    $mockClient->method('request')->willReturn($mockResponse);

        $importer = new Importer('fake-key', $mockClient);
        $data = $importer->fetchFederationRankings('EST', '2025-09-16', '2025-10-16');

        $this->assertIsArray($data);
        $this->assertEquals(1, $data[0]['EventId']);
    }
}
