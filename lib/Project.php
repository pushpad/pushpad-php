<?php

declare(strict_types=1);

namespace Pushpad;

/**
 * Models a Pushpad project which groups notifications and subscriptions.
 */
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
     * Lists all projects available to the configured account.
     *
     * @return list<self>
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function findAll(): array
    {
        $response = self::httpGet('/projects');
        self::ensureStatus($response, 200);
        $items = $response['body'];

        return array_map(fn (array $item) => new self($item), $items);
    }

    /**
     * Fetches a single project by its identifier.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function find(int $projectId): self
    {
        $response = self::httpGet("/projects/{$projectId}");
        self::ensureStatus($response, 200);
        $data = $response['body'];

        return new self($data);
    }

    /**
     * Creates a new project.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public static function create(array $payload): self
    {
        $response = self::httpPost('/projects', [
            'json' => self::filterForCreatePayload($payload),
        ]);
        self::ensureStatus($response, 201);
        $data = $response['body'];

        return new self($data);
    }

    /**
     * Refreshes the local project attributes with the API state.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public function refresh(): self
    {
        $response = self::httpGet("/projects/{$this->requireId()}");
        self::ensureStatus($response, 200);
        $data = $response['body'];
        $this->setAttributes($data);
        return $this;
    }

    /**
     * Updates the project with the provided attributes.
     *
     * @param array<string, mixed> $payload
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
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

    /**
     * Deletes the project.
     *
     * @throws \Pushpad\Exception\ApiException When the API response has an unexpected status.
     */
    public function delete(): void
    {
        $response = self::httpDelete("/projects/{$this->requireId()}");
        self::ensureStatus($response, 202);
    }
}
