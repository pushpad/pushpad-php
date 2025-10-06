<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pushpad\HttpClient;
use Pushpad\Notification;
use Pushpad\Pushpad;

class NotificationTest extends TestCase
{
    protected function setUp(): void
    {
        Pushpad::$auth_token = 'token';
        Pushpad::$project_id = 123;
    }

    protected function tearDown(): void
    {
        Pushpad::setHttpClient(null);
        Pushpad::$auth_token = null;
        Pushpad::$project_id = null;
    }

    public function testFindAllReturnsNotifications(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $responseBody = [
            [
                'id' => 197123,
                'project_id' => 123,
                'title' => 'Black Friday Deals',
                'body' => 'Enjoy 50% off on all items!',
                'target_url' => 'https://example.com/deals',
                'created_at' => '2025-09-14T10:30:00.123Z',
                'scheduled' => false,
            ],
            [
                'id' => 197124,
                'project_id' => 123,
                'title' => 'Cyber Monday',
                'body' => 'Exclusive online offers.',
                'target_url' => 'https://example.com/cyber',
                'created_at' => '2025-09-15T10:30:00.123Z',
                'scheduled' => false,
            ],
        ];

        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects/123/notifications', ['query' => ['page' => 1]])
            ->willReturn([
                'status' => 200,
                'body' => $responseBody,
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $notifications = Notification::findAll(['page' => 1]);

        $this->assertCount(2, $notifications);
        $this->assertSame('Black Friday Deals', $notifications[0]->title);
        $this->assertSame('Cyber Monday', $notifications[1]->title);

        $this->expectException(InvalidArgumentException::class);
        $unused = $notifications[0]->undefined_property;
    }

    public function testFindReturnsNotification(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/notifications/197123', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 197123,
                    'project_id' => 123,
                    'title' => 'Order Shipped',
                    'body' => 'Your order has been shipped.',
                    'created_at' => '2025-09-14T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $notification = Notification::find(197123);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame('Order Shipped', $notification->title);
    }

    public function testCreateNotificationSendsPayload(): void
    {
        $payload = [
            'title' => 'New Feature',
            'body' => 'Try our new feature today!',
            'target_url' => 'https://example.com/new-feature',
            'icon_url' => 'https://example.com/icon.png',
            'uids' => ['user1', 'user2'],
            'tags' => ['beta-testers'],
        ];

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/projects/123/notifications',
                $this->callback(function (array $options) use ($payload): bool {
                    $this->assertSame($payload, $options['json']);
                    return true;
                })
            )
            ->willReturn([
                'status' => 201,
                'body' => [
                    'id' => 200001,
                    'scheduled' => 5,
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $response = Notification::create($payload);

        $this->assertSame(200001, $response['id']);
        $this->assertSame(5, $response['scheduled']);
    }

    public function testSendNotificationUsesCreate(): void
    {
        $payload = [
            'title' => 'Weekly Update',
            'body' => 'A recap of the week.',
            'target_url' => 'https://example.com/update',
        ];

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/projects/123/notifications',
                $this->callback(function (array $options) use ($payload): bool {
                    $this->assertSame($payload, $options['json']);
                    return true;
                })
            )
            ->willReturn([
                'status' => 201,
                'body' => [
                    'id' => 210000,
                    'scheduled' => 1000,
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $response = Notification::send($payload);

        $this->assertSame(210000, $response['id']);
        $this->assertSame(1000, $response['scheduled']);
    }

    public function testCancelNotification(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', '/notifications/197123/cancel', [])
            ->willReturn([
                'status' => 204,
                'body' => null,
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $notification = new Notification([
            'id' => 197123,
            'title' => 'Sale Reminder',
            'cancelled' => false,
        ]);

        $notification->cancel();

        $this->assertTrue($notification->cancelled);
    }

    public function testRefreshNotificationUpdatesAttributes(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/notifications/197123', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 197123,
                    'project_id' => 123,
                    'title' => 'Updated Title',
                    'body' => 'Updated body copy.',
                    'target_url' => 'https://example.com/new',
                    'created_at' => '2025-09-14T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $notification = new Notification([
            'id' => 197123,
            'project_id' => 123,
            'title' => 'Old Title',
            'body' => 'Old body.',
        ]);

        $notification->refresh();

        $this->assertSame('Updated Title', $notification->title);
        $this->assertSame('Updated body copy.', $notification->body);
    }
}
