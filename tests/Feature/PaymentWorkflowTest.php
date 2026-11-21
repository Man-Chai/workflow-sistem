<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\ClassRoom;
use App\Models\Due;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinator;

    private User $otherCoordinator;

    private User $parent;

    private ClassRoom $class;

    private Student $student;

    private Due $due;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $this->otherCoordinator = User::where('email', 'sinta@korlassync.test')->firstOrFail();
        $this->parent = User::where('email', 'orangtua@korlassync.test')->firstOrFail();
        $this->class = $this->coordinator->classesCoordinated()->firstOrFail();
        $this->student = $this->class->students()->orderBy('id')->firstOrFail();

        $this->due = Due::create([
            'class_id' => $this->class->id,
            'created_by' => $this->coordinator->id,
            'title' => 'Iuran Uji Coba',
            'amount' => 100000,
            'period' => 'Uji',
            'due_date' => now()->addWeek(),
            'is_active' => true,
        ]);
    }

    public function test_coordinator_can_record_payment_and_it_immediately_enters_cash(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(40000))
            ->assertRedirect(route('cash.index', ['class' => $this->class->id, 'tab' => 'iuran']));

        $payment = Payment::where('due_id', $this->due->id)->firstOrFail();
        $this->assertSame('verified', $payment->status);
        $this->assertSame(40000, $payment->amount);
        $this->assertSame($this->coordinator->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);

        $transaction = CashTransaction::where('payment_id', $payment->id)->firstOrFail();
        $this->assertSame($this->class->id, $transaction->class_id);
        $this->assertSame('masuk', $transaction->type);
        $this->assertSame(40000, $transaction->amount);
        $this->assertStringContainsString($this->student->name, $transaction->description);
    }

    public function test_coordinator_can_record_partial_payments_until_fully_paid(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(60000))
            ->assertRedirect();

        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(40000))
            ->assertRedirect();

        $this->assertSame(100000, (int) Payment::where('due_id', $this->due->id)->where('status', 'verified')->sum('amount'));
        $this->assertSame(2, CashTransaction::where('description', 'like', 'Iuran Iuran Uji Coba%')->count());
    }

    public function test_payment_cannot_exceed_remaining_bill(): void
    {
        $countBefore = Payment::count();

        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(100001))
            ->assertSessionHasErrors('amount');

        $this->assertSame($countBefore, Payment::count());
    }

    public function test_payment_form_is_accessible_for_coordinator(): void
    {
        $this->actingAs($this->coordinator)
            ->get(route('payments.create', ['due' => $this->due->id, 'student' => $this->student->id]))
            ->assertOk()
            ->assertSee('Catat pembayaran iuran')
            ->assertSee('Iuran Uji Coba');
    }

    public function test_parent_cannot_access_payment_routes(): void
    {
        $payment = Payment::create([
            'due_id' => $this->due->id,
            'student_id' => $this->student->id,
            'amount' => 40000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'status' => 'pending',
        ]);

        $this->actingAs($this->parent)
            ->get(route('payments.create', ['due' => $this->due->id, 'student' => $this->student->id]))
            ->assertForbidden();

        $this->actingAs($this->parent)
            ->post(route('payments.store'), $this->paymentPayload(40000))
            ->assertForbidden();

        $this->actingAs($this->parent)
            ->delete(route('payments.destroy', $payment))
            ->assertForbidden();
    }

    public function test_coordinator_cannot_record_payment_for_another_class(): void
    {
        $otherClass = $this->otherCoordinator->classesCoordinated()->firstOrFail();
        $otherStudent = $otherClass->students()->firstOrFail();
        $otherDue = Due::where('class_id', $otherClass->id)->firstOrFail();

        $this->actingAs($this->coordinator)
            ->get(route('payments.create', ['due' => $otherDue->id, 'student' => $otherStudent->id]))
            ->assertForbidden();

        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), [
                'due_id' => $otherDue->id,
                'student_id' => $otherStudent->id,
                'amount' => 10000,
                'paid_at' => now()->toDateString(),
                'method' => 'transfer',
            ])
            ->assertForbidden();
    }

    public function test_coordinator_can_delete_verified_payment_and_its_cash_transaction(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(40000));

        $payment = Payment::where('due_id', $this->due->id)->firstOrFail();

        $this->actingAs($this->coordinator)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('cash_transactions', ['payment_id' => $payment->id]);
    }

    public function test_legacy_pending_payment_can_still_be_verified_by_coordinator(): void
    {
        $payment = Payment::create([
            'due_id' => $this->due->id,
            'student_id' => $this->student->id,
            'amount' => 40000,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'status' => 'pending',
        ]);

        $this->actingAs($this->coordinator)
            ->post(route('payments.verify', $payment))
            ->assertRedirect();

        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertDatabaseHas('cash_transactions', ['payment_id' => $payment->id]);
    }

    public function test_payment_proof_is_private_to_class_managers(): void
    {
        $this->actingAs($this->coordinator)
            ->post(route('payments.store'), $this->paymentPayload(40000) + [
                'proof' => UploadedFile::fake()->createWithContent('bukti-transfer.pdf', "%PDF-1.4\nBukti transfer"),
            ])
            ->assertRedirect();

        $payment = Payment::where('due_id', $this->due->id)->firstOrFail();
        $this->assertNotNull($payment->proof_data);
        $this->assertSame('bukti-transfer.pdf', $payment->proof_name);

        $this->actingAs($this->coordinator)
            ->get(route('payments.proof', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->otherCoordinator)
            ->get(route('payments.proof', $payment))
            ->assertForbidden();

        $this->actingAs($this->parent)
            ->get(route('payments.proof', $payment))
            ->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(int $amount): array
    {
        return [
            'due_id' => $this->due->id,
            'student_id' => $this->student->id,
            'amount' => $amount,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
            'note' => 'Pembayaran dicatat oleh koordinator.',
        ];
    }
}
