<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pushpad\HttpClient;
use Pushpad\Pushpad;
use Pushpad\Subscription;

class SubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        Pushpad::$auth_token = 'token';
        Pushpad::$project_id = 321;
    }

    protected function tearDown(): void
    {
        Pushpad::setHttpClient(null);
        Pushpad::$auth_token = null;
        Pushpad::$project_id = null;
    }

    public function testFindAllReturnsSubscriptions(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects/321/subscriptions', ['query' => ['page' => 2]])
            ->willReturn([
                'status' => 200,
                'body' => [
                    [
                        'id' => 7,
                        'endpoint' => 'https://example.com/push/f7Q1Eyf',
                        'uid' => 'user-123',
                        'tags' => ['tag1', 'tag2'],
                        'project_id' => 321,
                    ],
                    [
                        'id' => 8,
                        'endpoint' => 'https://example.com/push/abC1dEf',
                        'uid' => 'user-456',
                        'tags' => ['tag3'],
                        'project_id' => 321,
                    ],
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $subscriptions = Subscription::findAll(null, ['page' => 2]);

        $this->assertCount(2, $subscriptions);
        $this->assertSame('user-123', $subscriptions[0]->uid);
        $this->assertSame(['tag3'], $subscriptions[1]->tags);
    }

    public function testCountSubscriptionsUsesHeader(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects/321/subscriptions', ['query' => []])
            ->willReturn([
                'status' => 200,
                'body' => [],
                'headers' => ['x-total-count' => ['42']],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $this->assertSame(42, Subscription::count());
    }

    public function testFindReturnsSubscription(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects/321/subscriptions/7', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 7,
                    'endpoint' => 'https://example.com/push/f7Q1Eyf',
                    'uid' => 'user-123',
                    'tags' => ['tag1'],
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $subscription = Subscription::find(7);

        $this->assertSame('user-123', $subscription->uid);
        $this->assertSame(321, $subscription->project_id);
    }

    public function testCreateSubscriptionSendsPayload(): void
    {
        $payload = [
            'endpoint' => 'https://example.com/push/newEndpoint',
            'p256dh' => 'BCQVDTlYWdl05lal3lG5SKr3',
            'auth' => 'cdKMlhgVeSPz',
            'uid' => 'user-789',
            'tags' => ['vip', 'beta'],
        ];

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/projects/321/subscriptions',
                $this->callback(function (array $options) use ($payload): bool {
                    $this->assertSame($payload, $options['json']);
                    return true;
                })
            )
            ->willReturn([
                'status' => 201,
                'body' => $payload + ['id' => 99, 'project_id' => 321],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $subscription = Subscription::create($payload);

        $this->assertSame(99, $subscription->id);
        $this->assertSame('user-789', $subscription->uid);
    }

    public function testCreateSubscriptionRejectsReadOnlyAttribute(): void
    {
        $payload = [
            'endpoint' => 'https://example.com/push/newEndpoint',
            'p256dh' => 'BCQVDTlYWdl05lal3lG5SKr3',
            'auth' => 'cdKMlhgVeSPz',
            'uid' => 'user-789',
            'tags' => ['vip', 'beta'],
            'created_at' => '2025-09-14T10:30:00.123Z',
        ];

        $this->expectException(InvalidArgumentException::class);
        Subscription::create($payload);
    }

    public function testUpdateSubscriptionSendsPayload(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('PATCH', '/projects/321/subscriptions/7', ['json' => ['uid' => 'user-updated']])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 7,
                    'uid' => 'user-updated',
                    'endpoint' => 'https://example.com/push/f7Q1Eyf',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $subscription = new Subscription([
            'id' => 7,
            'project_id' => 321,
            'uid' => 'user-123',
        ]);

        $subscription->update(['uid' => 'user-updated']);

        $this->assertSame('user-updated', $subscription->uid);
    }

    public function testUpdateSubscriptionRejectsImmutableAttributes(): void
    {
        $subscription = new Subscription([
            'id' => 7,
            'project_id' => 321,
            'uid' => 'user-123',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $subscription->update(['endpoint' => 'https://example.com/push/new']);
    }

    public function testDeleteSubscription(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', '/projects/321/subscriptions/7', [])
            ->willReturn([
                'status' => 204,
                'body' => null,
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $subscription = new Subscription([
            'id' => 7,
            'project_id' => 321,
        ]);

        $subscription->delete();
    }
}
