<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $classIds = $user->accessibleClassIds();

        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $classIds)
            ->with('coordinators')
            ->withCount('students')
            ->orderBy('nama')
            ->get();

        $requestedClassId = $request->string('class')->trim()->toString();
        $class = ($requestedClassId ? $classes->firstWhere('id_kelas', $requestedClassId) : null) ?? $classes->first();

        if (! $class) {
            return view('kas.index', [
                'classes' => collect(),
                'class' => null,
            ]);
        }

        $tab = in_array($request->query('tab'), ['buku', 'pembayaran'], true)
            ? $request->query('tab')
            : 'buku';

        $month = (string) $request->query('month', '');
        $period = null;

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        $transactions = $class->cashTransactions()
            ->with('classRoom.coordinators')
            ->when($period, fn (Builder $query) => $query->whereBetween('tanggal', [
                $period->toDateString(),
                $period->copy()->endOfMonth()->toDateString(),
            ]))
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $income = (int) round((float) $class->cashTransactions()->sum('debit'));
        $expense = (int) round((float) $class->cashTransactions()->sum('kredit'));

        $activePeriod = $period ?? now();
        $monthIncome = (int) round((float) $class->cashTransactions()
            ->whereBetween('tanggal', [
                $activePeriod->copy()->startOfMonth()->toDateString(),
                $activePeriod->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('debit'));
        $monthExpense = (int) round((float) $class->cashTransactions()
            ->whereBetween('tanggal', [
                $activePeriod->copy()->startOfMonth()->toDateString(),
                $activePeriod->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('kredit'));

        $payments = Payment::query()
            ->with(['student.classRoom'])
            ->whereHas('student', fn (Builder $query) => $query->where('id_kelas', $class->id_kelas))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'payment_page')
            ->withQueryString();

        return view('kas.index', [
            'classes' => $classes,
            'class' => $class,
            'tab' => $tab,
            'month' => $month,
            'transactions' => $transactions,
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'monthIncome' => $monthIncome,
            'monthExpense' => $monthExpense,
            'dueRows' => collect(),
            'outstanding' => 0,
            'payments' => $payments,
            'pendingCount' => 0,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $classId = $request->string('class')->trim()->toString();
        $class = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->findOrFail($classId);

        $transactions = $class->cashTransactions()
            ->with('classRoom.coordinators')
            ->orderBy('tanggal')
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-keuangan-'.Str::slug($class->name).'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Keterangan', 'Debit', 'Kredit', 'Saldo']);

            foreach ($transactions as $transaction) {
                fputcsv($output, [
                    $transaction->tanggal->format('Y-m-d'),
                    $transaction->description,
                    (int) round((float) $transaction->debit),
                    (int) round((float) $transaction->kredit),
                    (int) round((float) $transaction->saldo),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
