<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    private ?User $user = null;

    protected function setUp(): void
    {
        // See RequestControllerTest::setUp() — phpunit.xml hard-codes sqlite
        // :memory:, we point at the real MySQL PeerLink DB before boot.
        putenv('DB_CONNECTION=mysql');
        putenv('DB_DATABASE=PeerLink');
        $_ENV['DB_CONNECTION']    = 'mysql';
        $_ENV['DB_DATABASE']      = 'PeerLink';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_DATABASE']   = 'PeerLink';

        parent::setUp();
        config([
            'database.default'                    => 'mysql',
            'database.connections.mysql.database' => 'PeerLink',
        ]);
        \DB::purge('mysql');

        $this->user = User::query()->whereNotNull('user_id')->inRandomOrder()->first();
    }

    protected function tearDown(): void
    {
        Notification::where('message', 'like', '[TEST]%')->delete();
        parent::tearDown();
    }

    public function test_authenticated_user_can_list_their_unread_notifications(): void
    {
        if (!$this->user) {
            $this->markTestSkipped('No users in database.');
        }

        Notification::create([
            'user_id' => $this->user->user_id,
            'type'    => 'test',
            'message' => '[TEST] unread one',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/notifications');

        $response->assertOk()->assertJsonStructure([
            'unread_count',
            'notifications' => [
                '*' => ['id', 'type', 'message', 'is_read', 'created_at'],
            ],
        ]);
    }

    public function test_marking_all_notifications_read_flips_them(): void
    {
        if (!$this->user) {
            $this->markTestSkipped('No users in database.');
        }

        Notification::create([
            'user_id' => $this->user->user_id,
            'type'    => 'test',
            'message' => '[TEST] mark me',
            'is_read' => false,
        ]);

        $this->actingAs($this->user)
            ->patchJson('/api/notifications/read')
            ->assertOk();

        $this->assertSame(0, Notification::where('user_id', $this->user->user_id)
            ->where('message', 'like', '[TEST]%')
            ->where('is_read', false)
            ->count());
    }
}
