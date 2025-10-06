<?php

declare(strict_types=1);

namespace Pushpad;

class Notification extends Resource
{
    protected const ATTRIBUTES = [
        'id',
        'project_id',
        'title',
        'body',
        'target_url',
        'icon_url',
        'badge_url',
        'image_url',
        'ttl',
        'require_interaction',
        'silent',
        'urgent',
        'custom_data',
        'custom_metrics',
        'actions',
        'starred',
        'send_at',
        'uids',
        'tags',
        'created_at',
        'successfully_sent_count',
        'opened_count',
        'scheduled_count',
        'scheduled',
        'cancelled',
    ];

    protected const READ_ONLY_ATTRIBUTES = [
        'id',
        'project_id',
        'created_at',
        'successfully_sent_count',
        'opened_count',
        'scheduled_count',
        'scheduled',
        'cancelled',
    ];

        
    /**
     * @return array<int, self>
     */
    public static function findAll(array $query = [], ?int $projectId = null): array
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $response = self::httpGet("/projects/{$resolvedProjectId}/notifications", [
            'query' => $query,
        ]);
        self::ensureStatus($response, 200);

        $items = $response['body'];

        return array_map(fn (array $item) => new self($item), $items);
    }

    public static function find(int $notificationId): self
    {
        $response = self::httpGet("/notifications/{$notificationId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self($data);
    }

    /**
     * @return array<string, mixed>
     */
    public static function create(array $payload, ?int $projectId = null): array
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $response = self::httpPost("/projects/{$resolvedProjectId}/notifications", [
            'json' => self::filterForCreatePayload($payload),
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function send(array $payload, ?int $projectId = null): array
    {
        return static::create($payload, $projectId);
    }

    public function cancel(): void
    {
        $response = self::httpDelete("/notifications/{$this->requireId()}/cancel");
        self::ensureStatus($response, 204);
        $this->attributes['cancelled'] = true;
    }

    public function refresh(): self
    {
        $response = self::httpGet("/notifications/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }
}
