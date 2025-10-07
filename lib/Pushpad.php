<?php

declare(strict_types=1);

namespace Pushpad;

use Pushpad\Exception\ConfigurationException;

/**
 * Static facade to configure the SDK and provide shared helpers.
 */
class Pushpad
{
    /** Current library version. */
    public const VERSION = '3.0.0';

    /**
     * API token used to authenticate every request performed by the SDK.
     */
    public static ?string $authToken = null;

    /**
     * Default project identifier used when a project id is not passed explicitly.
     */
    public static ?int $projectId = null;

    /**
     * Base URL for the Pushpad REST API.
     */
    public static string $baseUrl = 'https://pushpad.xyz/api/v1';

    /**
     * Default request timeout in seconds.
     */
    public static int $timeout = 30;

    /** @internal */
    private static ?HttpClient $httpClient = null;

    /**
     * Computes the HMAC signature that can be used to generate signed data.
     *
     * @param string $data
     * @return string
     *
     * @throws ConfigurationException When the authentication token has not been configured.
     */
    public static function signatureFor(string $data): string
    {
        if (!isset(self::$authToken)) {
            throw new ConfigurationException('Pushpad::$authToken must be set before calling signatureFor().');
        }

        return hash_hmac('sha256', $data, self::$authToken);
    }

    /**
     * Overrides the HTTP client instance used by the SDK, mainly for testing purposes.
     *
     * @param HttpClient|null $httpClient
     * @return void
     */
    public static function setHttpClient(?HttpClient $httpClient): void
    {
        self::$httpClient = $httpClient;
    }

    /**
     * Returns the configured HTTP client, instantiating a default one when needed.
     *
     * @return HttpClient
     *
     * @throws ConfigurationException When the authentication token has not been configured.
     */
    public static function http(): HttpClient
    {
        if (!isset(self::$authToken) || self::$authToken === '') {
            throw new ConfigurationException('Pushpad::$authToken must be a non-empty string.');
        }

        if (self::$httpClient instanceof HttpClient) {
            return self::$httpClient;
        }

        $userAgent = sprintf('pushpad-php/%s', self::VERSION);

        self::$httpClient = new HttpClient(
            self::$authToken,
            self::$baseUrl,
            self::$timeout,
            $userAgent
        );

        return self::$httpClient;
    }

    /**
     * Determines which project identifier should be used for an API call.
     *
     * @param int|null $projectId
     * @return int
     *
     * @throws ConfigurationException When neither the argument nor the global default is set.
     */
    public static function resolveProjectId(?int $projectId): int
    {
        if ($projectId !== null) {
            return $projectId;
        }

        if (self::$projectId !== null) {
            return self::$projectId;
        }

        throw new ConfigurationException('Pushpad::$projectId must be configured or provided explicitly.');
    }
}
