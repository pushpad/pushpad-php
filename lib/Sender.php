<?php

declare(strict_types=1);

namespace Pushpad;

/**
 * Represents a Pushpad sender resource which holds the VAPID credentials.
 */
class Sender extends Resource
{
    protected const ATTRIBUTES = [
        'id',
        'name',
        'vapid_private_key',
        'vapid_public_key',
        'created_at',
    ];

    protected const READ_ONLY_ATTRIBUTES = [
        'id',
        'created_at',
    ];

    protected const IMMUTABLE_ATTRIBUTES = [
        'vapid_private_key',
        'vapid_public_key',
    ];
    /**
     * Lists all senders configured in the account.
     *
     * @return list<self>
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function findAll(): array
    {
        $response = self::httpGet('/senders');
        self::ensureStatus($response, 200);
        $items = $response['body'];

        return array_map(fn (array $item) => new self($item), $items);
    }

    /**
     * Fetches a sender by its identifier.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function find(int $senderId): self
    {
        $response = self::httpGet("/senders/{$senderId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self($data);
    }

    /**
     * Creates a new sender.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function create(array $payload): self
    {
        $response = self::httpPost('/senders', [
            'json' => self::filterForCreatePayload($payload),
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return new self($data);
    }

    /**
     * Refreshes the local representation with the API state.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public function refresh(): self
    {
        $response = self::httpGet("/senders/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

    /**
     * Updates the sender with the provided attributes.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public function update(array $payload): self
    {
        $response = self::httpPatch("/senders/{$this->requireId()}", [
            'json' => self::filterForUpdatePayload($payload),
        ]);
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

    /**
     * Deletes the sender.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public function delete(): void
    {
        $response = self::httpDelete("/senders/{$this->requireId()}");
        self::ensureStatus($response, 204);
    }
}
