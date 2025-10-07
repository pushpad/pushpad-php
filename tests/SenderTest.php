<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pushpad\HttpClient;
use Pushpad\Pushpad;
use Pushpad\Sender;

class SenderTest extends TestCase
{
    protected function setUp(): void
    {
        Pushpad::$authToken = 'token';
    }

    protected function tearDown(): void
    {
        Pushpad::setHttpClient(null);
        Pushpad::$authToken = null;
    }

    public function testFindAllReturnsSenders(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/senders', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    [
                        'id' => 5,
                        'name' => 'Primary Sender',
                        'created_at' => '2025-09-13T10:30:00.123Z',
                    ],
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $senders = Sender::findAll();

        $this->assertCount(1, $senders);
        $this->assertSame('Primary Sender', $senders[0]->name);
    }

    public function testFindReturnsSender(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/senders/5', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 5,
                    'name' => 'Primary Sender',
                    'created_at' => '2025-09-13T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $sender = Sender::find(5);

        $this->assertSame('Primary Sender', $sender->name);
    }

    public function testCreateSenderSendsPayload(): void
    {
        $payload = [
            'name' => 'Marketing Sender',
        ];

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/senders',
                $this->callback(function (array $options) use ($payload): bool {
                    $this->assertSame($payload, $options['json']);
                    return true;
                })
            )
            ->willReturn([
                'status' => 201,
                'body' => [
                    'id' => 10,
                    'name' => 'Marketing Sender',
                    'created_at' => '2025-09-13T12:00:00.000Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $sender = Sender::create($payload);

        $this->assertSame(10, $sender->id);
        $this->assertSame('Marketing Sender', $sender->name);
    }

    public function testCreateSenderRejectsReadOnlyAttribute(): void
    {
        $payload = [
            'name' => 'Invalid Sender',
            'created_at' => '2025-09-13T10:30:00.123Z',
        ];

        $this->expectException(InvalidArgumentException::class);
        Sender::create($payload);
    }

    public function testUpdateSenderSendsPayload(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('PATCH', '/senders/5', ['json' => ['name' => 'Renamed Sender']])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 5,
                    'name' => 'Renamed Sender',
                    'created_at' => '2025-09-13T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $sender = new Sender([
            'id' => 5,
            'name' => 'Primary Sender',
            'created_at' => '2025-09-13T10:30:00.123Z',
        ]);

        $sender->update(['name' => 'Renamed Sender']);

        $this->assertSame('Renamed Sender', $sender->name);
    }

    public function testUpdateSenderRejectsImmutableAttributes(): void
    {
        $sender = new Sender([
            'id' => 5,
            'name' => 'Primary Sender',
            'created_at' => '2025-09-13T10:30:00.123Z',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $sender->update(['vapid_private_key' => 'forbidden']);
    }

    public function testDeleteSender(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', '/senders/5', [])
            ->willReturn([
                'status' => 204,
                'body' => null,
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $sender = new Sender([
            'id' => 5,
            'name' => 'Primary Sender',
        ]);

        $sender->delete();
    }
}
