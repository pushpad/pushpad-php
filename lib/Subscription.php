<?php

declare(strict_types=1);

namespace Pushpad;

class Subscription extends Resource
{
    protected const ATTRIBUTES = [
        'id',
        'endpoint',
        'p256dh',
        'auth',
        'uid',
        'tags',
        'last_click_at',
        'created_at',
        'project_id',
    ];

    protected const READ_ONLY_ATTRIBUTES = [
        'id',
        'last_click_at',
        'created_at',
        'project_id',
    ];

    protected const IMMUTABLE_ATTRIBUTES = [
        'endpoint',
        'p256dh',
        'auth',
    ];

        
    /**
     * @return array<int, self>
     */
    public static function findAll(array $query = [], ?int $projectId = null): array
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $response = self::httpGet("/projects/{$resolvedProjectId}/subscriptions", [
            'query' => $query,
        ]);

        self::ensureStatus($response, 200);
        $items = $response['body'];

        return array_map(
            fn (array $item) => new self(self::injectProjectId($item, $resolvedProjectId)),
            $items
        );
    }

    public static function count(array $query = [], ?int $projectId = null): int
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $response = self::httpGet("/projects/{$resolvedProjectId}/subscriptions", [
            'query' => $query,
        ]);

        self::ensureStatus($response, 200);
        $headers = $response['headers'] ?? [];
        $name = 'x-total-count';

        if (!isset($headers[$name][0]) || !is_numeric($headers[$name][0])) {
            throw new \UnexpectedValueException('Response is missing the X-Total-Count header.');
        }

        return (int) $headers[$name][0];
    }

    public static function find(int $subscriptionId, ?int $projectId = null): self
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $response = self::httpGet("/projects/{$resolvedProjectId}/subscriptions/{$subscriptionId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self(self::injectProjectId($data, $resolvedProjectId));
    }

    public static function create(array $payload, ?int $projectId = null): self
    {
        $resolvedProjectId = Pushpad::resolveProjectId($projectId);
        $body = self::filterForCreatePayload($payload);
        $response = self::httpPost("/projects/{$resolvedProjectId}/subscriptions", [
            'json' => $body,
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return new self(self::injectProjectId($data, $resolvedProjectId));
    }

    public function refresh(?int $projectId = null): self
    {
        $project = $this->determineProjectId($projectId);
        $response = self::httpGet("/projects/{$project}/subscriptions/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes(self::injectProjectId($data, $project));
        return $this;
    }

    public function update(array $payload, ?int $projectId = null): self
    {
        $project = $this->determineProjectId($projectId);
        $body = self::filterForUpdatePayload($payload);
        $response = self::httpPatch("/projects/{$project}/subscriptions/{$this->requireId()}", [
            'json' => $body,
        ]);
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes(self::injectProjectId($data, $project));
        return $this;
    }

    public function delete(?int $projectId = null): void
    {
        $project = $this->determineProjectId($projectId);
        $response = self::httpDelete("/projects/{$project}/subscriptions/{$this->requireId()}");
        self::ensureStatus($response, 204);
    }

    private function determineProjectId(?int $projectId = null): int
    {
        if ($projectId !== null) {
            return $projectId;
        }

        if (isset($this->attributes['project_id'])) {
            return (int) $this->attributes['project_id'];
        }

        return Pushpad::resolveProjectId(null);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function injectProjectId(array $data, int $projectId): array
    {
        if (!isset($data['project_id'])) {
            $data['project_id'] = $projectId;
        }

        return $data;
    }
}
