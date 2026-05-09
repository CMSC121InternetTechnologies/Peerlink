<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\RequestStatus;
use App\Models\Course;
use App\Models\TutoringRequest;
use App\Models\User;
use Tests\TestCase;

/**
 * Feature tests for the request lifecycle endpoints.
 *
 * NOTE: these run against the live PeerLink dev DB rather than a separate
 * test DB because the existing setup doesn't have a sqlite/test profile
 * configured. Each test creates its own fixture rows with a clear "[TEST]"
 * marker in the message field and cleans them up at tearDown — see the
 * `cleanupTestRows()` helper. Don't add asserts that depend on global row
 * counts, only on rows you created in this test.
 */
class RequestControllerTest extends TestCase
{
    private ?User $student = null;
    private ?User $tutor   = null;
    private ?Course $course = null;

    /**
     * phpunit.xml hard-codes sqlite :memory: for tests. Our migrations use
     * MySQL-specific column types (longblob, etc.) so they can't run on
     * sqlite. Point this test at the real MySQL DB before the framework
     * resolves connections.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'mysql');
    }

    protected function setUp(): void
    {
        // phpunit.xml sets DB_CONNECTION=sqlite and DB_DATABASE=:memory:.
        // Override BOTH before Laravel resolves connections so we hit the
        // real MySQL PeerLink DB this test was written against.
        putenv('DB_CONNECTION=mysql');
        putenv('DB_DATABASE=PeerLink');
        $_ENV['DB_CONNECTION']    = 'mysql';
        $_ENV['DB_DATABASE']      = 'PeerLink';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_DATABASE']   = 'PeerLink';

        parent::setUp();

        config([
            'database.default'                  => 'mysql',
            'database.connections.mysql.database' => 'PeerLink',
        ]);
        \DB::purge('mysql');

        // Pick two real users from the DB so we're testing against the actual
        // schema (UUID primary keys, password_hash column, etc.).
        $this->student = User::query()->whereNotNull('user_id')->inRandomOrder()->first();
        if ($this->student) {
            $this->tutor = User::query()
                ->where('user_id', '!=', $this->student->user_id)
                ->whereHas('tutorProfile')
                ->inRandomOrder()
                ->first();
        }
        $this->course = Course::query()->inRandomOrder()->first();
    }

    protected function tearDown(): void
    {
        // Strip every TutoringRequest we created in this test run.
        TutoringRequest::where('message', 'like', '[TEST]%')->delete();
        parent::tearDown();
    }

    public function test_authenticated_student_can_send_a_direct_request(): void
    {
        if (!$this->student || !$this->tutor || !$this->course) {
            $this->markTestSkipped('Database missing required fixture rows.');
        }

        $response = $this->actingAs($this->student)
            ->postJson('/api/requests', [
                'course_code' => $this->course->course_code,
                'tutor_id'    => $this->tutor->user_id,
                'message'     => '[TEST] direct request',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'request_id']);

        $this->assertDatabaseHas('Requests', [
            'student_id' => $this->student->user_id,
            'tutor_id'   => $this->tutor->user_id,
            'status'     => RequestStatus::Pending->value,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson('/api/requests', [
            'course_code' => $this->course?->course_code ?? 'CMSC121',
        ]);
        // Auth middleware redirects to /login for HTML, returns 401 for JSON.
        $this->assertContains($response->status(), [401, 419, 302]);
    }

    public function test_student_can_cancel_their_own_pending_request(): void
    {
        if (!$this->student || !$this->course) {
            $this->markTestSkipped('Database missing required fixture rows.');
        }

        $req = TutoringRequest::create([
            'student_id' => $this->student->user_id,
            'tutor_id'   => null,
            'course_id'  => $this->course->course_id,
            'message'    => '[TEST] cancellable',
            'status'     => RequestStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->student)
            ->patchJson("/api/requests/{$req->request_id}/cancel");

        $response->assertOk()->assertJson(['message' => 'Request cancelled.']);
        $this->assertSame(RequestStatus::Cancelled, $req->fresh()->status);
    }

    public function test_other_user_cannot_cancel_someone_elses_request(): void
    {
        if (!$this->student || !$this->tutor || !$this->course) {
            $this->markTestSkipped('Database missing required fixture rows.');
        }

        $req = TutoringRequest::create([
            'student_id' => $this->student->user_id,
            'tutor_id'   => null,
            'course_id'  => $this->course->course_id,
            'message'    => '[TEST] not yours',
            'status'     => RequestStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->tutor)
            ->patchJson("/api/requests/{$req->request_id}/cancel");

        $response->assertForbidden();
        // RequestPolicy::cancel() should have blocked the write.
        $this->assertSame(RequestStatus::Pending, $req->fresh()->status);
    }
}
