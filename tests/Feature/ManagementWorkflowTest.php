<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Due;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_coordinator_can_create_and_update_an_agenda_in_their_class(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $ownClass = $coordinator->classesCoordinated()->firstOrFail();
        $otherClass = ClassRoom::where('id', '!=', $ownClass->id)->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('agenda.store'), [
                'class_id' => $otherClass->id,
                'title' => 'Agenda koordinasi uji coba',
                'description' => 'Membahas persiapan kegiatan.',
                'location' => 'Ruang kelas',
                'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(3)->addHour()->format('Y-m-d H:i:s'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('agenda.index'));

        $agenda = Agenda::where('title', 'Agenda koordinasi uji coba')->firstOrFail();
        $this->assertSame($ownClass->id, $agenda->class_id);
        $this->assertSame($coordinator->id, $agenda->created_by);
        $this->assertTrue($agenda->is_published);

        $this->actingAs($coordinator)
            ->put(route('agenda.update', $agenda), [
                'class_id' => $otherClass->id,
                'title' => 'Agenda koordinasi diperbarui',
                'starts_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('agenda.index'));

        $agenda->refresh();
        $this->assertSame('Agenda koordinasi diperbarui', $agenda->title);
        $this->assertSame($ownClass->id, $agenda->class_id);
        $this->assertFalse($agenda->is_published);
    }

    public function test_admin_can_change_agenda_scope_from_global_to_class(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $class = ClassRoom::firstOrFail();

        $this->actingAs($admin)
            ->post(route('agenda.store'), [
                'class_id' => '',
                'title' => 'Agenda sekolah uji coba',
                'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('agenda.index'));

        $agenda = Agenda::where('title', 'Agenda sekolah uji coba')->firstOrFail();
        $this->assertNull($agenda->class_id);

        $this->actingAs($admin)
            ->put(route('agenda.update', $agenda), [
                'class_id' => $class->id,
                'title' => 'Agenda sekolah uji coba',
                'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('agenda.index'));

        $this->assertSame($class->id, $agenda->fresh()->class_id);
    }

    public function test_admin_can_change_announcement_scope_and_publish_state(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $firstClass = ClassRoom::orderBy('id')->firstOrFail();
        $secondClass = ClassRoom::where('id', '!=', $firstClass->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('announcements.store'), [
                'class_id' => $firstClass->id,
                'title' => 'Pemberitahuan uji coba',
                'body' => 'Isi pemberitahuan untuk pengujian alur kerja.',
                'priority' => 'penting',
                'publish' => '1',
            ])
            ->assertRedirect(route('announcements.index'));

        $announcement = Announcement::where('title', 'Pemberitahuan uji coba')->firstOrFail();
        $this->assertSame($firstClass->id, $announcement->class_id);
        $this->assertNotNull($announcement->published_at);

        $this->actingAs($admin)
            ->put(route('announcements.update', $announcement), [
                'class_id' => $secondClass->id,
                'title' => 'Pemberitahuan uji coba diperbarui',
                'body' => 'Isi pemberitahuan yang sudah diperbarui.',
                'priority' => 'mendesak',
            ])
            ->assertRedirect(route('announcements.index'));

        $announcement->refresh();
        $this->assertSame($secondClass->id, $announcement->class_id);
        $this->assertSame('mendesak', $announcement->priority);
        $this->assertNull($announcement->published_at);
    }

    public function test_coordinator_can_manage_a_due_without_moving_it_to_another_class(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $ownClass = $coordinator->classesCoordinated()->firstOrFail();
        $otherClass = ClassRoom::where('id', '!=', $ownClass->id)->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('dues.store'), [
                'class_id' => $otherClass->id,
                'title' => 'Iuran uji coba',
                'amount' => 25000,
                'period' => 'Uji',
                'due_date' => now()->addWeek()->toDateString(),
                'is_active' => '1',
            ])
            ->assertRedirect(route('cash.index', ['class' => $ownClass->id, 'tab' => 'iuran']));

        $due = Due::where('title', 'Iuran uji coba')->firstOrFail();
        $this->assertSame($ownClass->id, $due->class_id);

        $this->actingAs($coordinator)
            ->put(route('dues.update', $due), [
                'class_id' => $otherClass->id,
                'title' => 'Iuran uji coba diperbarui',
                'amount' => 30000,
                'period' => 'Uji',
                'due_date' => now()->addDays(10)->toDateString(),
                'is_active' => '1',
            ])
            ->assertRedirect(route('cash.index', ['class' => $ownClass->id, 'tab' => 'iuran']));

        $due->refresh();
        $this->assertSame($ownClass->id, $due->class_id);
        $this->assertSame('Iuran uji coba diperbarui', $due->title);
        $this->assertSame(30000, $due->amount);
    }

    public function test_admin_can_create_a_class_and_add_a_student(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $parent = User::where('role', User::ROLE_PARENT)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('classes.store'), [
                'name' => 'Kelas 1',
                'academic_year' => '2027/2028',
                'coordinator_id' => '',
                'description' => 'Kelas baru untuk pengujian.',
            ])
            ->assertRedirect(route('classes.index'));

        $class = ClassRoom::where('name', 'Kelas 1')->where('academic_year', '2027/2028')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('students.store', $class), [
                'nis' => '269999',
                'name' => 'Siswa Uji Coba',
                'gender' => 'P',
                'birth_date' => '2010-05-15',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect(route('classes.show', $class));

        $this->assertDatabaseHas('students', [
            'class_id' => $class->id,
            'parent_id' => $parent->id,
            'nis' => '269999',
        ]);
    }

    public function test_admin_can_create_an_active_user_with_hashed_password(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Pengguna Uji Coba',
                'email' => 'pengguna.uji@korlassync.test',
                'password' => 'rahasia123',
                'role' => User::ROLE_KOORDINATOR,
                'phone' => '081200000000',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'pengguna.uji@korlassync.test')->firstOrFail();
        $this->assertSame(User::ROLE_KOORDINATOR, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_admin_can_update_and_deactivate_another_user(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $user = User::where('email', 'dian@korlassync.test')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'Dian Saputra Diperbarui',
                'email' => $user->email,
                'role' => User::ROLE_PARENT,
                'phone' => $user->phone,
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Dian Saputra Diperbarui', $user->name);
        $this->assertFalse($user->is_active);
    }
}
