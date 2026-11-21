<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\Due;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_open_administration_pages(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $class = ClassRoom::firstOrFail();
        $student = Student::firstOrFail();
        $user = User::where('role', User::ROLE_PARENT)->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('users.edit', $user))->assertOk();
        $this->get(route('classes.index'))->assertOk();
        $this->get(route('classes.create'))->assertOk();
        $this->get(route('classes.show', $class))->assertOk();
        $this->get(route('classes.edit', $class))->assertOk();
        $this->get(route('students.edit', $student))->assertOk();
    }

    public function test_coordinator_is_limited_to_their_own_class(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $ownClass = $coordinator->classesCoordinated()->firstOrFail();
        $otherClass = ClassRoom::where('id', '!=', $ownClass->id)->firstOrFail();

        $this->actingAs($coordinator);

        $this->get(route('classes.show', $ownClass))->assertOk();
        $this->get(route('classes.show', $otherClass))->assertForbidden();
        $this->get(route('classes.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();

        $otherDue = Due::where('class_id', $otherClass->id)->firstOrFail();
        $this->get(route('dues.edit', $otherDue))->assertForbidden();
    }

    public function test_parent_cannot_open_management_pages(): void
    {
        $parent = User::where('email', 'orangtua@korlassync.test')->firstOrFail();
        $due = Due::firstOrFail();

        $this->actingAs($parent);

        $this->get(route('cash.index'))->assertForbidden();
        $this->get(route('cash.export', ['class' => $due->class_id]))->assertForbidden();
        $this->get(route('cash.create'))->assertForbidden();
        $this->get(route('dues.create'))->assertForbidden();
        $this->get(route('payments.create'))->assertForbidden();
        $this->post(route('cash.store'), [])->assertForbidden();
        $this->post(route('payments.store'), [])->assertForbidden();
        $this->get(route('agenda.create'))->assertForbidden();
        $this->get(route('announcements.create'))->assertForbidden();
        $this->get(route('classes.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->delete(route('dues.destroy', $due))->assertForbidden();
    }

    public function test_coordinator_cannot_write_to_another_class_through_request_payload(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $ownClass = $coordinator->classesCoordinated()->firstOrFail();
        $otherClass = ClassRoom::where('id', '!=', $ownClass->id)->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('cash.store'), [
                'class_id' => $otherClass->id,
                'type' => 'masuk',
                'category' => 'Donasi',
                'amount' => 10000,
                'transaction_date' => now()->toDateString(),
                'description' => 'Uji coba scoping kelas',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cash_transactions', [
            'class_id' => $ownClass->id,
            'description' => 'Uji coba scoping kelas',
        ]);
        $this->assertDatabaseMissing('cash_transactions', [
            'class_id' => $otherClass->id,
            'description' => 'Uji coba scoping kelas',
        ]);
    }

    public function test_coordinator_cannot_verify_payment_from_another_class(): void
    {
        $coordinator = User::where('email', 'sinta@korlassync.test')->firstOrFail();
        $payment = Payment::whereHas('due.classRoom', function ($query) use ($coordinator) {
            $query->where('coordinator_id', '!=', $coordinator->id);
        })->where('status', 'pending')->firstOrFail();

        $this->actingAs($coordinator)
            ->post(route('payments.verify', $payment))
            ->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseMissing('cash_transactions', ['payment_id' => $payment->id]);
    }
}
