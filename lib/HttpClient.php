<?php

declare(strict_types=1);

namespace Pushpad;

class HttpClient
{
    private string $baseUrl;
    private string $authToken;
    private int $timeout;
    private string $userAgent;

    public function __construct(string $authToken, string $baseUrl = 'https://pushpad.xyz/api/v1', int $timeout = 30, ?string $userAgent = null)
    {
        if ($authToken === '') {
            throw new \InvalidArgumentException('Auth token must be a non-empty string.');
        }

        $this->authToken = $authToken;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->userAgent = $userAgent ?? 'pushpad-php-sdk/1.0';
    }

    /**
     * @param array{query?:array<string,mixed>, json?:mixed, body?:string, headers?:array<int,string>, timeout?:int} $options
     * @return array{status:int, body:mixed, headers:array<string, array<int, string>>, raw_body:?string}
     */
    public function request(string $method, string $path, array $options = []): array
    {
        $url = $this->buildUrl($path, $options['query'] ?? []);
        $payload = null;
        $headers = $this->defaultHeaders();

        if (isset($options['json'])) {
            $payload = json_encode($options['json']);
            if ($payload === false) {
                throw new \RuntimeException('Failed to encode JSON payload.');
            }
            $headers[] = 'Content-Type: application/json';
        } elseif (isset($options['body'])) {
            $payload = (string) $options['body'];
        }

        if (!empty($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : $this->timeout;

        $responseHeaders = [];
        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException('Unable to initialize cURL');
        }

        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function ($curl, string $line) use (&$responseHeaders): int {
            $trimmed = trim($line);
            if ($trimmed === '' || stripos($trimmed, 'HTTP/') === 0) {
                return strlen($line);
            }
            [$name, $value] = array_map('trim', explode(':', $trimmed, 2));
            $key = strtolower($name);
            $responseHeaders[$key] = $responseHeaders[$key] ?? [];
            $responseHeaders[$key][] = $value;
            return strlen($line);
        });

        if ($payload !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
        }

        $rawBody = curl_exec($handle);
        if ($rawBody === false) {
            $errorMessage = curl_error($handle);
            curl_close($handle);
            throw new \RuntimeException('cURL request error: ' . $errorMessage);
        }

        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        return [
            'status' => $status,
            'body' => $this->decode($rawBody),
            'headers' => $responseHeaders,
            'raw_body' => $rawBody === '' ? null : $rawBody,
        ];
    }

    private function defaultHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->authToken,
            'Accept: application/json',
        ];
    }

    private function buildUrl(string $path, array $query): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $queryString = $this->buildQueryString($query);
            if ($queryString !== '') {
                $url .= '?' . $queryString;
            }
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function buildQueryString(array $query): string
    {
        $parts = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null) {
                        continue;
                    }
                    $parts[] = rawurlencode($key . '[]') . '=' . rawurlencode((string) $item);
                }
                continue;
            }

            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    private function decode(string $rawBody)
    {
        $trimmed = trim($rawBody);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $trimmed;
    }
}
