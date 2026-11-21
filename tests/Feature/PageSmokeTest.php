<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Due;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_pages_render_without_errors(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $class = ClassRoom::firstOrFail();
        $student = Student::firstOrFail();
        $due = Due::firstOrFail();
        $agenda = Agenda::firstOrFail();
        $announcement = Announcement::firstOrFail();
        $parent = User::where('role', User::ROLE_PARENT)->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('cash.index'))->assertOk();
        $this->get(route('cash.index', ['class' => $class->id, 'tab' => 'iuran']))->assertOk();
        $this->get(route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran']))->assertOk();
        $this->get(route('cash.create'))->assertOk();
        $this->get(route('dues.create'))->assertOk();
        $this->get(route('dues.edit', $due))->assertOk();
        $this->get(route('payments.create', ['due' => $due->id, 'student' => $student->id]))->assertOk();
        $this->get(route('agenda.index'))->assertOk();
        $this->get(route('agenda.index', ['status' => 'past']))->assertOk();
        $this->get(route('agenda.create'))->assertOk();
        $this->get(route('agenda.edit', $agenda))->assertOk();
        $this->get(route('announcements.index'))->assertOk();
        $this->get(route('announcements.create'))->assertOk();
        $this->get(route('announcements.show', $announcement))->assertOk();
        $this->get(route('announcements.edit', $announcement))->assertOk();
        $this->get(route('classes.index'))->assertOk();
        $this->get(route('classes.create'))->assertOk();
        $this->get(route('classes.show', $class))->assertOk();
        $this->get(route('classes.edit', $class))->assertOk();
        $this->get(route('students.edit', $student))->assertOk();
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('users.edit', $parent))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_coordinator_pages_render_without_errors(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $class = $coordinator->classesCoordinated()->firstOrFail();
        $due = Due::where('class_id', $class->id)->firstOrFail();
        $agenda = Agenda::where('class_id', $class->id)->firstOrFail();
        $announcement = Announcement::where('class_id', $class->id)->firstOrFail();
        $student = Student::where('class_id', $class->id)->firstOrFail();

        $this->actingAs($coordinator);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('cash.index'))->assertOk();
        $this->get(route('cash.index', ['class' => $class->id, 'tab' => 'iuran']))->assertOk();
        $this->get(route('cash.index', ['class' => $class->id, 'tab' => 'pembayaran', 'month' => now()->format('Y-m')]))->assertOk();
        $this->get(route('cash.create'))->assertOk();
        $this->get(route('dues.create'))->assertOk();
        $this->get(route('dues.edit', $due))->assertOk();
        $this->get(route('payments.create', ['due' => $due->id, 'student' => $student->id]))->assertOk();
        $this->get(route('agenda.index'))->assertOk();
        $this->get(route('agenda.create'))->assertOk();
        $this->get(route('agenda.edit', $agenda))->assertOk();
        $this->get(route('announcements.index'))->assertOk();
        $this->get(route('announcements.create'))->assertOk();
        $this->get(route('announcements.show', $announcement))->assertOk();
        $this->get(route('announcements.edit', $announcement))->assertOk();
        $this->get(route('classes.show', $class))->assertOk();
        $this->get(route('students.edit', $student))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_parent_pages_render_without_errors(): void
    {
        $parent = User::where('email', 'orangtua@korlassync.test')->firstOrFail();
        $child = $parent->children()->firstOrFail();
        $announcement = Announcement::where('class_id', $child->class_id)->whereNotNull('published_at')->firstOrFail();

        $this->actingAs($parent);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('cash.index'))->assertForbidden();
        $this->get(route('agenda.index'))->assertOk();
        $this->get(route('announcements.index'))->assertOk();
        $this->get(route('announcements.show', $announcement))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
    }
}
