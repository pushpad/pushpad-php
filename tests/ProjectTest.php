<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pushpad\HttpClient;
use Pushpad\Project;
use Pushpad\Pushpad;

class ProjectTest extends TestCase
{
    protected function setUp(): void
    {
        Pushpad::$auth_token = 'token';
    }

    protected function tearDown(): void
    {
        Pushpad::setHttpClient(null);
        Pushpad::$auth_token = null;
    }

    public function testFindAllReturnsProjects(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    [
                        'id' => 12345,
                        'sender_id' => 98765,
                        'name' => 'My Project',
                        'website' => 'https://example.com',
                        'icon_url' => 'https://example.com/icon.png',
                        'badge_url' => 'https://example.com/badge.png',
                        'notifications_ttl' => 604800,
                        'notifications_require_interaction' => false,
                        'notifications_silent' => false,
                        'created_at' => '2025-09-14T10:30:00.123Z',
                    ],
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $projects = Project::findAll();

        $this->assertCount(1, $projects);
        $this->assertSame('My Project', $projects[0]->name);
    }

    public function testFindReturnsProject(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', '/projects/12345', [])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 12345,
                    'sender_id' => 98765,
                    'name' => 'My Project',
                    'website' => 'https://example.com',
                    'created_at' => '2025-09-14T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $project = Project::find(12345);

        $this->assertSame('My Project', $project->name);
    }

    public function testCreateProjectSendsPayload(): void
    {
        $payload = [
            'sender_id' => 98765,
            'name' => 'New Project',
            'website' => 'https://example.com/new',
            'icon_url' => 'https://example.com/icon.png',
            'badge_url' => 'https://example.com/badge.png',
            'notifications_ttl' => 604800,
            'notifications_require_interaction' => false,
            'notifications_silent' => false,
        ];

        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/projects',
                $this->callback(function (array $options) use ($payload): bool {
                    $this->assertSame($payload, $options['json']);
                    return true;
                })
            )
            ->willReturn([
                'status' => 201,
                'body' => $payload + [
                    'id' => 55555,
                    'created_at' => '2025-09-14T10:30:00.123Z',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $project = Project::create($payload);

        $this->assertSame(55555, $project->id);
        $this->assertSame('New Project', $project->name);
    }

    public function testCreateProjectRejectsReadOnlyAttribute(): void
    {
        $payload = [
            'sender_id' => 98765,
            'name' => 'Fails',
            'website' => 'https://example.com/fails',
            'id' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        Project::create($payload);
    }

    public function testUpdateProjectSendsPayload(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('PATCH', '/projects/12345', ['json' => ['name' => 'Updated Project']])
            ->willReturn([
                'status' => 200,
                'body' => [
                    'id' => 12345,
                    'sender_id' => 98765,
                    'name' => 'Updated Project',
                    'website' => 'https://example.com',
                ],
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $project = new Project([
            'id' => 12345,
            'sender_id' => 98765,
            'name' => 'My Project',
            'website' => 'https://example.com',
        ]);

        $project->update(['name' => 'Updated Project']);

        $this->assertSame('Updated Project', $project->name);
    }

    public function testUpdateProjectRejectsImmutableAttributes(): void
    {
        $project = new Project([
            'id' => 12345,
            'sender_id' => 98765,
            'name' => 'My Project',
            'website' => 'https://example.com',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $project->update(['sender_id' => 123]);
    }

    public function testDeleteProject(): void
    {
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('DELETE', '/projects/12345', [])
            ->willReturn([
                'status' => 202,
                'body' => null,
                'headers' => [],
                'raw_body' => null,
            ]);

        Pushpad::setHttpClient($httpClient);

        $project = new Project([
            'id' => 12345,
            'name' => 'My Project',
        ]);

        $project->delete();
    }
}
