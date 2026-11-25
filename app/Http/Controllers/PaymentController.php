<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\ClassRoom;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ManagesClassData;

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        $requestedClassId = $request->string('class')->trim()->toString() ?: $request->string('due')->trim()->toString();
        $classRoom = ($requestedClassId ? $classes->firstWhere('id_kelas', $requestedClassId) : null) ?? $classes->first();

        abort_if($classRoom === null, 404, 'Kelas tidak ditemukan.');
        $this->assertCanManageClass($classRoom->id_kelas);

        $students = Student::query()
            ->where('id_kelas', $classRoom->id_kelas)
            ->orderBy('nama')
            ->get();

        $requestedStudentId = $request->string('student')->trim()->toString();
        $student = ($requestedStudentId ? $students->firstWhere('id_siswa', $requestedStudentId) : null) ?? $students->first();

        return view('kas.payment-form', [
            'classes' => $classes,
            'classRoom' => $classRoom,
            'students' => $students,
            'student' => $student,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('siswa', 'id_siswa')],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $student = Student::query()->with('classRoom')->findOrFail($data['student_id']);
        $this->assertCanManageClass($student->id_kelas);

        // Trigger trg_pembukuan_ke_keuangan on `pembukuan` automatically inserts into `laporan_keuangan`
        Payment::create([
            'id_siswa' => $student->id_siswa,
            'jumlah' => (int) round((float) $data['amount']),
        ]);

        return redirect()
            ->route('cash.index', ['class' => $student->id_kelas, 'tab' => 'pembayaran'])
            ->with('success', 'Pembayaran pembukuan '.$student->name.' berhasil dicatat dan otomatis masuk ke laporan keuangan.');
    }

    public function edit(Request $request, Payment $payment): View
    {
        $payment->load('student.classRoom');
        abort_unless($payment->student && $request->user()->canManageClass($payment->student->id_kelas), 403);

        $classRoom = $payment->student->classRoom;
        abort_if($classRoom === null, 404, 'Kelas tidak ditemukan.');

        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $request->user()->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        $students = Student::query()
            ->where('id_kelas', $classRoom->id_kelas)
            ->orderBy('nama')
            ->get();

        return view('kas.payment-form', [
            'classes' => $classes,
            'classRoom' => $classRoom,
            'students' => $students,
            'student' => $payment->student,
            'payment' => $payment,
        ]);
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load('student');
        abort_unless($payment->student && $request->user()->canManageClass($payment->student->id_kelas), 403);

        $data = $request->validate([
            'student_id' => ['required', Rule::exists('siswa', 'id_siswa')],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $newStudent = Student::query()->with('classRoom')->findOrFail($data['student_id']);
        $this->assertCanManageClass($newStudent->id_kelas);

        $payment->update([
            'id_siswa' => $newStudent->id_siswa,
            'jumlah' => (int) round((float) $data['amount']),
        ]);

        return redirect()
            ->route('cash.index', ['class' => $newStudent->id_kelas, 'tab' => 'pembayaran'])
            ->with('success', 'Data pembukuan '.$newStudent->name.' berhasil diperbarui dan tersinkronisasi dengan laporan keuangan.');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $payment->load('student');
        abort_unless($payment->student && $request->user()->canManageClass($payment->student->id_kelas), 403);

        $name = $payment->student->name;
        $payment->delete();

        return back()->with('success', 'Data pembukuan '.$name.' telah dihapus dari pembukuan dan laporan keuangan.');
    }
}
