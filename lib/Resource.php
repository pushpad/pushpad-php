<?php

declare(strict_types=1);

namespace Pushpad;

use Pushpad\Exception\ApiException;

/**
 * Lightweight base class for Pushpad API resources.
 */
abstract class Resource implements \JsonSerializable
{
    /** @var array<string, mixed> */
    protected array $attributes;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $this->filterStoredAttributes($attributes);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    public function __get(string $name)
    {
        if (!in_array($name, static::attributes(), true)) {
            throw new \InvalidArgumentException(sprintf('Unknown attribute "%s" for %s', $name, static::class));
        }

        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return in_array($name, static::attributes(), true) && array_key_exists($name, $this->attributes);
    }

    protected function setAttributes(array $attributes): void
    {
        $this->attributes = $this->filterStoredAttributes($attributes);
    }

    protected function requireId(): int
    {
        if (!isset($this->attributes['id'])) {
            throw new \LogicException('Resource does not have an id yet.');
        }

        return (int) $this->attributes['id'];
    }

    protected static function http(): HttpClient
    {
        return Pushpad::http();
    }

    /**
     * @return array{status:int, body:mixed, headers:array<string, array<int, string>>|null, raw_body:?string}
     */
    protected static function httpGet(string $path, array $options = []): array
    {
        return self::http()->request('GET', $path, $options);
    }

    /**
     * @return array{status:int, body:mixed, headers:array<string, array<int, string>>|null, raw_body:?string}
     */
    protected static function httpPost(string $path, array $options = []): array
    {
        return self::http()->request('POST', $path, $options);
    }

    /**
     * @return array{status:int, body:mixed, headers:array<string, array<int, string>>|null, raw_body:?string}
     */
    protected static function httpPatch(string $path, array $options = []): array
    {
        return self::http()->request('PATCH', $path, $options);
    }

    /**
     * @return array{status:int, body:mixed, headers:array<string, array<int, string>>|null, raw_body:?string}
     */
    protected static function httpDelete(string $path, array $options = []): array
    {
        return self::http()->request('DELETE', $path, $options);
    }

    /**
     * @param array{status:int} $response
     */
    protected static function ensureStatus(array $response, int $expectedStatusCode): void
    {
        $status = $response['status'] ?? 0;
        if ($status !== $expectedStatusCode) {
            throw ApiException::fromResponse($response, $expectedStatusCode);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected static function filterForCreatePayload(array $attributes): array
    {
        $allowed = array_diff(
            static::attributes(),
            static::readOnlyAttributes()
        );

        return self::filterToAllowedAttributes($attributes, $allowed, true);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected static function filterForUpdatePayload(array $attributes): array
    {
        $allowed = array_diff(
            static::attributes(),
            static::readOnlyAttributes(),
            static::immutableAttributes()
        );

        return self::filterToAllowedAttributes($attributes, $allowed, true);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function filterStoredAttributes(array $attributes): array
    {
        return self::filterToAllowedAttributes($attributes, static::attributes());
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, string> $allowed
     * @return array<string, mixed>
     */
    private static function filterToAllowedAttributes(array $attributes, array $allowed, bool $rejectUnknown = false): array
    {
        if ($allowed === []) {
            return $attributes;
        }

        $allowedMap = array_flip($allowed);
        $filtered = [];

        foreach ($attributes as $key => $value) {
            if (!isset($allowedMap[$key])) {
                if ($rejectUnknown) {
                    throw new \InvalidArgumentException(sprintf('Unknown attribute "%s" for %s', $key, static::class));
                }

                continue;
            }

            $filtered[$key] = $value;
            unset($allowedMap[$key]);
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    private static function attributes(): array
    {
        return defined('static::ATTRIBUTES') ? static::ATTRIBUTES : [];
    }

    private static function readOnlyAttributes(): array
    {
        return defined('static::READ_ONLY_ATTRIBUTES') ? static::READ_ONLY_ATTRIBUTES : [];
    }

    private static function immutableAttributes(): array
    {
        return defined('static::IMMUTABLE_ATTRIBUTES') ? static::IMMUTABLE_ATTRIBUTES : [];
    }

}
