<?php

declare(strict_types=1);

namespace Pushpad\Exception;

/**
 * Represents an error response returned by the Pushpad API.
 */
class ApiException extends PushpadException
{
    private int $statusCode;

    /** @var mixed */
    private $responseBody;

    /**
     * @var array<string, array<int, string>>|null
     */
    private ?array $responseHeaders;

    private ?string $rawBody;

    /**
     * @param mixed $responseBody
     * @param array<string, array<int, string>>|null $responseHeaders
     */
    public function __construct(
        string $message,
        int $statusCode,
        $responseBody = null,
        ?array $responseHeaders = null,
        ?string $rawBody = null
    ) {
        parent::__construct($message, $statusCode);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
        $this->rawBody = $rawBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    public function getResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    /**
     * @param array{status?:int, body?:mixed, headers?:array<string, array<int, string>>, raw_body?:?string} $response
     */
    public static function fromResponse(array $response, int $expectedStatusCode): self
    {
        $status = isset($response['status']) ? (int) $response['status'] : 0;
        $body = $response['body'] ?? null;
        $headers = $response['headers'] ?? null;
        $rawBody = $response['raw_body'] ?? null;

        $message = self::buildMessage($status, $expectedStatusCode, $body);

        return new self($message, $status, $body, $headers, $rawBody);
    }

    /**
     * @param mixed $body
     */
    private static function buildMessage(int $status, int $expectedStatusCode, $body): string
    {
        $baseMessage = sprintf('Unexpected status code %d (expected %d).', $status, $expectedStatusCode);

        $details = '';

        if (is_array($body)) {
            foreach (['error_description', 'error', 'message'] as $key) {
                if (isset($body[$key]) && is_scalar($body[$key])) {
                    $details = (string) $body[$key];
                    break;
                }
            }

            if ($details === '' && isset($body['errors'])) {
                $encoded = json_encode($body['errors']);
                $details = $encoded !== false ? $encoded : '';
            }
        } elseif (is_scalar($body) && $body !== '') {
            $details = (string) $body;
        }

        if ($details === '' && $body !== null) {
            $encoded = json_encode($body);
            $details = $encoded !== false ? $encoded : '';
        }

        if ($details === '' || $details === 'null') {
            return $baseMessage;
        }

        return $baseMessage . ' ' . $details;
    }
}

