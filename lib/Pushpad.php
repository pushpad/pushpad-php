<?php

declare(strict_types=1);

namespace Pushpad;

class Pushpad
{
    public static ?string $auth_token = null;
    public static ?int $project_id = null;
    public static string $base_url = 'https://pushpad.xyz/api/v1';
    public static int $timeout = 30;
    private static ?HttpClient $httpClient = null;

    public static function signature_for(string $data): string
    {
        if (!isset(self::$auth_token)) {
            throw new \Exception('You must set Pushpad\\Pushpad::$auth_token');
        }

        return hash_hmac('sha256', $data, self::$auth_token);
    }

    public static function setHttpClient(?HttpClient $httpClient): void
    {
        self::$httpClient = $httpClient;
    }

    public static function http(): HttpClient
    {
        if (!isset(self::$auth_token) || self::$auth_token === '') {
            throw new \Exception('You must set Pushpad\\Pushpad::$auth_token');
        }

        if (self::$httpClient instanceof HttpClient) {
            return self::$httpClient;
        }

        self::$httpClient = new HttpClient(
            self::$auth_token,
            self::$base_url,
            self::$timeout
        );

        return self::$httpClient;
    }

    public static function resolveProjectId(?int $projectId): int
    {
        if ($projectId !== null) {
            return $projectId;
        }

        if (self::$project_id !== null) {
            return self::$project_id;
        }

        throw new \InvalidArgumentException('A project ID is required but was not provided.');
    }
}
