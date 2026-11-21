<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\CashTransaction;
use App\Models\Due;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ManagesClassData;

    public function create(Request $request): View|RedirectResponse
    {
        $due = Due::query()
            ->with('classRoom')
            ->find($request->integer('due'));

        if ($due === null) {
            return redirect()
                ->route('cash.index', ['tab' => 'iuran'])
                ->with('info', 'Pilih iuran terlebih dahulu untuk mencatat pembayaran.');
        }

        $this->assertCanManageClass($due->class_id);

        $students = Student::query()
            ->where('class_id', $due->class_id)
            ->orderBy('name')
            ->get();

        abort_if($students->isEmpty(), 404, 'Belum ada siswa di kelas ini.');

        $requestedStudent = $students->firstWhere('id', $request->integer('student'));
        abort_if($request->filled('student') && $requestedStudent === null, 404, 'Siswa tidak ditemukan di kelas ini.');

        $student = $requestedStudent
            ?? $students->first(fn (Student $candidate) => $candidate->remainingFor($due) > 0)
            ?? $students->first();

        return view('kas.payment-form', [
            'students' => $students,
            'due' => $due,
            'student' => $student,
            'remaining' => $student->remainingFor($due),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'due_id' => ['required', 'exists:dues,id'],
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'integer', 'min:1000'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'in:transfer,tunai,qris'],
            'note' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $due = Due::query()->with('classRoom')->findOrFail($data['due_id']);
        $student = Student::query()->findOrFail($data['student_id']);

        $this->assertCanManageClass($due->class_id);

        if ($student->class_id !== $due->class_id) {
            throw ValidationException::withMessages(['student_id' => 'Siswa tidak berada di kelas iuran ini.']);
        }

        $remaining = $student->remainingFor($due);

        if ($remaining <= 0) {
            throw ValidationException::withMessages(['amount' => 'Iuran ini sudah lunas untuk '.$student->name.'.']);
        }

        if ($data['amount'] > $remaining) {
            throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa tagihan ('.rupiah($remaining).').']);
        }

        $hasPending = Payment::query()
            ->where('due_id', $due->id)
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'amount' => 'Masih ada pembayaran yang menunggu verifikasi untuk iuran ini. Verifikasi atau tolak terlebih dahulu.',
            ]);
        }

        $proof = $request->file('proof');

        DB::transaction(function () use ($data, $due, $student, $request, $proof): void {
            $payment = Payment::create([
                'due_id' => $due->id,
                'student_id' => $student->id,
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'status' => 'verified',
                'note' => $data['note'] ?? null,
                'proof_data' => $proof ? base64_encode($proof->get()) : null,
                'proof_mime' => $proof?->getMimeType(),
                'proof_name' => $proof?->getClientOriginalName(),
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            CashTransaction::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'class_id' => $due->class_id,
                    'recorded_by' => $request->user()->id,
                    'type' => 'masuk',
                    'category' => 'Iuran',
                    'amount' => $payment->amount,
                    'description' => 'Iuran '.$due->title.' — '.$student->name,
                    'transaction_date' => $payment->paid_at,
                ]
            );
        });

        return redirect()
            ->route('cash.index', ['class' => $due->class_id, 'tab' => 'iuran'])
            ->with('success', 'Pembayaran '.$student->name.' berhasil dicatat dan masuk ke kas kelas.');
    }

    public function proof(Request $request, Payment $payment): Response
    {
        $payment->loadMissing(['due', 'student']);

        abort_unless($request->user()->canManageClass($payment->due->class_id), 403, 'Anda tidak berhak melihat bukti pembayaran ini.');

        if ($payment->proof_data) {
            $name = str_replace(['"', "\r", "\n"], '', basename((string) ($payment->proof_name ?: 'bukti-pembayaran')));

            return response(base64_decode($payment->proof_data), 200, [
                'Content-Type' => $payment->proof_mime ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]);
        }

        abort_if($payment->proof_path === null || ! Storage::disk('public')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('public')->path($payment->proof_path));
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load(['due', 'student']);
        abort_unless($request->user()->canManageClass($payment->due->class_id), 403);

        if ($payment->status === 'verified') {
            return back()->with('info', 'Pembayaran ini sudah terverifikasi.');
        }

        if ($payment->status !== 'pending') {
            return back()->with('info', 'Hanya pembayaran yang menunggu verifikasi dapat diproses.');
        }

        DB::transaction(function () use ($request, $payment): void {
            $payment->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            CashTransaction::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'class_id' => $payment->due->class_id,
                    'recorded_by' => $request->user()->id,
                    'type' => 'masuk',
                    'category' => 'Iuran',
                    'amount' => $payment->amount,
                    'description' => 'Iuran '.$payment->due->title.' — '.$payment->student->name,
                    'transaction_date' => $payment->paid_at,
                ]
            );
        });

        return back()->with('success', 'Pembayaran '.$payment->student->name.' berhasil diverifikasi dan masuk ke kas.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load(['due']);
        abort_unless($request->user()->canManageClass($payment->due->class_id), 403);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        if ($payment->status === 'verified') {
            return back()->with('info', 'Pembayaran yang sudah terverifikasi tidak dapat ditolak.');
        }

        if ($payment->status !== 'pending') {
            return back()->with('info', 'Hanya pembayaran yang menunggu verifikasi dapat ditolak.');
        }

        DB::transaction(function () use ($payment, $data): void {
            $payment->update([
                'status' => 'rejected',
                'note' => $data['note'],
                'verified_by' => null,
                'verified_at' => null,
            ]);

            CashTransaction::query()->where('payment_id', $payment->id)->delete();
        });

        return back()->with('success', 'Pembayaran ditolak dengan catatan.');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load(['due', 'student']);
        abort_unless($request->user()->canManageClass($payment->due->class_id), 403);

        $name = $payment->student?->name ?? 'Siswa';

        DB::transaction(function () use ($payment): void {
            CashTransaction::query()->where('payment_id', $payment->id)->delete();
            $payment->delete();
        });

        return back()->with('success', 'Pembayaran '.$name.' telah dihapus dari kas.');
    }
}
