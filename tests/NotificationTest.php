<?php

use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use Pushpad\Pushpad;
use Pushpad\Notification;
use Pushpad\NotificationDeliveryError;

class NotificationTest extends TestCase {
  use PHPMock;
  
  private $notification;

  protected function setUp(): void {
    Pushpad::$auth_token = 'test_token';
    Pushpad::$project_id = 12345;

    $this->notification = new Notification([
      'body' => 'Test body',
      'title' => 'Test title',
      'target_url' => 'https://example.com'
    ]);
  }

  public function testNotificationInitialization() {
    $this->assertEquals('Test body', $this->notification->body);
    $this->assertEquals('Test title', $this->notification->title);
    $this->assertEquals('https://example.com', $this->notification->target_url);
  }

  public function testBroadcastNotification() {
    $mockResponse = json_encode(['id' => 123, 'scheduled' => 5000]);
    $this->mockCurl($mockResponse, 201);

    $response = $this->notification->broadcast();
    $this->assertArrayHasKey('id', $response);
    $this->assertEquals(123, $response['id']);
    $this->assertArrayHasKey('scheduled', $response);
    $this->assertEquals(5000, $response['scheduled']);
  }
  
  public function testBroadcastNotificationWithTags() {
    $mockResponse = json_encode(['id' => 789, 'scheduled' => 1000]);
    $this->mockCurl($mockResponse, 201);

    $response = $this->notification->broadcast(['tags' => ['segment1', 'segment2']]);
    $this->assertArrayHasKey('id', $response);
    $this->assertEquals(789, $response['id']);
    $this->assertArrayHasKey('scheduled', $response);
    $this->assertEquals(1000, $response['scheduled']);
  }

  public function testDeliverToSpecificUsers() {
    $mockResponse = json_encode(['id' => 456, 'scheduled' => 1, 'uids' => ['user1']]);
    $this->mockCurl($mockResponse, 201);

    $response = $this->notification->deliver_to(['user1', 'user2']);
    $this->assertArrayHasKey('id', $response);
    $this->assertEquals(456, $response['id']);
    $this->assertArrayHasKey('scheduled', $response);
    $this->assertEquals(1, $response['scheduled']);
    $this->assertArrayHasKey('uids', $response);
    $this->assertEquals(['user1'], $response['uids']);
  }

  public function testMissingAuthTokenThrowsException() {
    Pushpad::$auth_token = null;

    $this->expectException(Exception::class);
    $this->notification->broadcast();
  }

  public function testMissingProjectIdThrowsException() {
    Pushpad::$project_id = null;

    $this->expectException(Exception::class);
    $this->notification->broadcast();
  }

  public function testInvalidResponseCodeThrowsError() {
    $this->mockCurl('Error message', 400);

    $this->expectException(NotificationDeliveryError::class);
    $this->notification->broadcast();
  }

  private function mockCurl($responseBody, $statusCode) {
    $mockCurl = $this->getFunctionMock('Pushpad', 'curl_exec');
    $mockCurl->expects($this->any())->willReturn($responseBody);

    $mockInfo = $this->getFunctionMock('Pushpad', 'curl_getinfo');
    $mockInfo->expects($this->any())->willReturn($statusCode);

    $mockClose = $this->getFunctionMock('Pushpad', 'curl_close');
    $mockClose->expects($this->any())->willReturn(null);
  }
}
