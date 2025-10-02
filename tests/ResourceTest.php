<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pushpad\HttpClient;
use Pushpad\Pushpad;
use Pushpad\Resource;

class ResourceTest extends TestCase
{
    protected function tearDown(): void
    {
        Pushpad::setHttpClient(null);
        Pushpad::$auth_token = null;
    }

    public function testConstructorFiltersUnknownAttributes(): void
    {
        $resource = new DummyResource([
            'id' => 5,
            'name' => 'demo',
            'read_only' => 'keep',
            'extra' => 'ignored',
        ]);

        $this->assertSame(
            ['id' => 5, 'name' => 'demo', 'read_only' => 'keep'],
            $resource->toArray()
        );
    }

    public function testJsonSerializeReturnsStoredAttributes(): void
    {
        $resource = new DummyResource([
            'id' => 10,
            'name' => 'serializable',
        ]);

        $this->assertSame([
            'id' => 10,
            'name' => 'serializable',
        ], $resource->jsonSerialize());

        $this->assertSame('{"id":10,"name":"serializable"}', json_encode($resource));
    }

    public function testGetKnownAttributeReturnsValue(): void
    {
        $resource = new DummyResource(['name' => 'Ada']);

        $this->assertSame('Ada', $resource->name);
    }

    public function testGetUnknownAttributeThrows(): void
    {
        $resource = new DummyResource(['name' => 'Ada']);

        $this->expectException(\InvalidArgumentException::class);
        $unused = $resource->unknown;
    }

    public function testGetAllowedButMissingReturnsNull(): void
    {
        $resource = new DummyResource();

        $this->assertNull($resource->name);
    }

    public function testIssetReflectsPresenceOfStoredAttribute(): void
    {
        $resource = new DummyResource(['name' => 'Ada']);

        $this->assertTrue(isset($resource->name));
        $this->assertFalse(isset($resource->read_only));
        $this->assertFalse(isset($resource->unknown));
    }

    public function testSetAttributesReplacesStoredValues(): void
    {
        $resource = new DummyResource(['name' => 'first']);
        $resource->exposeSetAttributes([
            'name' => 'second',
            'extra' => 'ignored',
        ]);

        $this->assertSame(['name' => 'second'], $resource->toArray());
    }

    public function testRequireIdReturnsInt(): void
    {
        $resource = new DummyResource(['id' => '7']);

        $this->assertSame(7, $resource->exposeRequireId());
    }

    public function testRequireIdThrowsWhenMissing(): void
    {
        $resource = new DummyResource();

        $this->expectException(\LogicException::class);
        $resource->exposeRequireId();
    }

    public function testFilterForCreatePayloadReturnsAllowedAttributes(): void
    {
        $payload = DummyResource::exposeFilterForCreatePayload([
            'name' => 'New',
        ]);

        $this->assertSame(['name' => 'New'], $payload);
    }

    public function testFilterForCreatePayloadRejectsReadOnlyAttributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DummyResource::exposeFilterForCreatePayload([
            'name' => 'New',
            'read_only' => 'value',
        ]);
    }

    public function testFilterForCreatePayloadRejectsUnknownAttributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DummyResource::exposeFilterForCreatePayload([
            'name' => 'New',
            'unknown' => 'value',
        ]);
    }

    public function testFilterForUpdatePayloadReturnsAllowedAttributes(): void
    {
        $payload = DummyResource::exposeFilterForUpdatePayload([
            'name' => 'Updated',
        ]);

        $this->assertSame(['name' => 'Updated'], $payload);
    }

    public function testFilterForUpdatePayloadRejectsNonWritableAttributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DummyResource::exposeFilterForUpdatePayload([
            'name' => 'Updated',
            'immutable' => 'value',
        ]);
    }

    public function testFilterForUpdatePayloadRejectsUnknownAttributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DummyResource::exposeFilterForUpdatePayload([
            'name' => 'Updated',
            'unknown' => 'value',
        ]);
    }

    public function testEnsureStatusAcceptsExpectedCode(): void
    {
        DummyResource::exposeEnsureStatus(['status' => 204], 204);
        $this->addToAssertionCount(1);
    }

    public function testEnsureStatusThrowsForUnexpectedCode(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        DummyResource::exposeEnsureStatus(['status' => 500], 204);
    }

    public function testConstructorKeepsAttributesWhenNoListDefined(): void
    {
        $resource = new LooseResource(['foo' => 'bar', 'other' => 'value']);

        $this->assertSame([
            'foo' => 'bar',
            'other' => 'value',
        ], $resource->toArray());
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function testHttpHelpersProxyRequestsToHttpClient(string $method, string $wrapper): void
    {
        Pushpad::$auth_token = 'token';
        $httpClient = $this->createMock(HttpClient::class);
        $response = [
            'status' => 200,
            'body' => ['ok' => true],
            'headers' => ['Header' => ['Value']],
            'raw_body' => '{"ok":true}',
        ];

        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with($method, '/path', ['query' => ['page' => 1]])
            ->willReturn($response);

        Pushpad::setHttpClient($httpClient);

        $result = DummyResource::{$wrapper}('/path', ['query' => ['page' => 1]]);

        $this->assertSame($response, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function httpMethodProvider(): array
    {
        return [
            'get' => ['GET', 'exposeHttpGet'],
            'post' => ['POST', 'exposeHttpPost'],
            'patch' => ['PATCH', 'exposeHttpPatch'],
            'delete' => ['DELETE', 'exposeHttpDelete'],
        ];
    }
}

class DummyResource extends Resource
{
    public const ATTRIBUTES = ['id', 'name', 'read_only', 'immutable'];
    public const READ_ONLY_ATTRIBUTES = ['read_only'];
    public const IMMUTABLE_ATTRIBUTES = ['immutable'];

    public function exposeSetAttributes(array $attributes): void
    {
        $this->setAttributes($attributes);
    }

    public function exposeRequireId(): int
    {
        return $this->requireId();
    }

    public static function exposeFilterForCreatePayload(array $attributes): array
    {
        return self::filterForCreatePayload($attributes);
    }

    public static function exposeFilterForUpdatePayload(array $attributes): array
    {
        return self::filterForUpdatePayload($attributes);
    }

    public static function exposeEnsureStatus(array $response, int $expectedStatusCode): void
    {
        self::ensureStatus($response, $expectedStatusCode);
    }

    public static function exposeHttpGet(string $path, array $options = []): array
    {
        return self::httpGet($path, $options);
    }

    public static function exposeHttpPost(string $path, array $options = []): array
    {
        return self::httpPost($path, $options);
    }

    public static function exposeHttpPatch(string $path, array $options = []): array
    {
        return self::httpPatch($path, $options);
    }

    public static function exposeHttpDelete(string $path, array $options = []): array
    {
        return self::httpDelete($path, $options);
    }
}

class LooseResource extends Resource
{
}
