<?php

declare(strict_types=1);

namespace Pushpad;

class Project extends Resource
{
    protected const ATTRIBUTES = [
        'id',
        'sender_id',
        'name',
        'website',
        'icon_url',
        'badge_url',
        'notifications_ttl',
        'notifications_require_interaction',
        'notifications_silent',
        'created_at',
    ];

    protected const READ_ONLY_ATTRIBUTES = [
        'id',
        'created_at',
    ];

    protected const IMMUTABLE_ATTRIBUTES = [
        'sender_id',
    ];

        
    /**
     * @return array<int, self>
     */
    public static function findAll(): array
    {
        $response = self::httpGet('/projects');
        self::ensureStatus($response, 200);
        $items = $response['body'];

        return array_map(fn (array $item) => new self($item), $items);
    }

    public static function find(int $projectId): self
    {
        $response = self::httpGet("/projects/{$projectId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self($data);
    }

    public static function create(array $payload): self
    {
        $response = self::httpPost('/projects', [
            'json' => self::filterForCreatePayload($payload),
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return new self($data);
    }

    public function refresh(): self
    {
        $response = self::httpGet("/projects/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

    public function update(array $payload): self
    {
        $response = self::httpPatch("/projects/{$this->requireId()}", [
            'json' => self::filterForUpdatePayload($payload),
        ]);
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

    public function delete(): void
    {
        $response = self::httpDelete("/projects/{$this->requireId()}");
        self::ensureStatus($response, 202);
    }
}
