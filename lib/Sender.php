<?php

declare(strict_types=1);

namespace Pushpad;

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
     * @return array<int, self>
     */
    public static function findAll(): array
    {
        $response = self::httpGet('/senders');
        self::ensureStatus($response, 200);
        $items = $response['body'];

        return array_map(fn (array $item) => new self($item), $items);
    }

    public static function find(int $senderId): self
    {
        $response = self::httpGet("/senders/{$senderId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self($data);
    }

    public static function create(array $payload): self
    {
        $response = self::httpPost('/senders', [
            'json' => self::filterForCreatePayload($payload),
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return new self($data);
    }

    public function refresh(): self
    {
        $response = self::httpGet("/senders/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

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

    public function delete(): void
    {
        $response = self::httpDelete("/senders/{$this->requireId()}");
        self::ensureStatus($response, 204);
    }
}
