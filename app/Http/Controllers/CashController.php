<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Due;
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
            ->whereIn('id', $classIds)
            ->with('coordinator')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $class = $classes->firstWhere('id', $request->integer('class')) ?? $classes->first();

        if (! $class) {
            return view('kas.index', [
                'classes' => collect(),
                'class' => null,
            ]);
        }

        $tab = in_array($request->query('tab'), ['buku', 'iuran', 'pembayaran'], true)
            ? $request->query('tab')
            : 'buku';

        $month = (string) $request->query('month', '');
        $period = null;

        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        $transactions = $class->cashTransactions()
            ->with(['recorder', 'payment'])
            ->when($period, fn (Builder $query) => $query->whereBetween('transaction_date', [
                $period->toDateString(),
                $period->copy()->endOfMonth()->toDateString(),
            ]))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $income = (int) $class->cashTransactions()->where('type', 'masuk')->sum('amount');
        $expense = (int) $class->cashTransactions()->where('type', 'keluar')->sum('amount');

        $activePeriod = $period ?? now();
        $monthIncome = (int) $class->cashTransactions()
            ->where('type', 'masuk')
            ->whereBetween('transaction_date', [
                $activePeriod->copy()->startOfMonth()->toDateString(),
                $activePeriod->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('amount');
        $monthExpense = (int) $class->cashTransactions()
            ->where('type', 'keluar')
            ->whereBetween('transaction_date', [
                $activePeriod->copy()->startOfMonth()->toDateString(),
                $activePeriod->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('amount');

        $dues = Due::query()
            ->where('class_id', $class->id)
            ->with(['creator', 'payments.student', 'payments.verifier'])
            ->orderByDesc('due_date')
            ->get();

        $dueRows = $dues->map(function (Due $due) use ($class) {
            $target = $due->targetAmount($class->students_count);
            $verified = $due->verifiedAmount();

            return [
                'due' => $due,
                'target' => $target,
                'verified' => $verified,
                'remaining' => max(0, $target - $verified),
                'percent' => $target > 0 ? min(100, (int) round($verified / $target * 100)) : 0,
            ];
        })->values();

        $outstanding = (int) $dueRows->sum('remaining');

        $payments = Payment::query()
            ->with(['student', 'due', 'verifier'])
            ->whereHas('due', fn (Builder $query) => $query->where('class_id', $class->id))
            ->latest()
            ->paginate(10, ['*'], 'payment_page')
            ->withQueryString();

        $pendingCount = Payment::query()
            ->whereHas('due', fn (Builder $query) => $query->where('class_id', $class->id))
            ->where('status', 'pending')
            ->count();

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
            'dueRows' => $dueRows,
            'outstanding' => $outstanding,
            'payments' => $payments,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $class = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->findOrFail($request->integer('class'));

        $transactions = $class->cashTransactions()
            ->with(['recorder'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $filename = 'kas-'.Str::slug($class->name).'-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($transactions): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Nominal', 'Dicatat oleh']);

            foreach ($transactions as $transaction) {
                fputcsv($output, [
                    $transaction->transaction_date->format('Y-m-d'),
                    ucfirst($transaction->type),
                    $transaction->category,
                    $transaction->description,
                    $transaction->amount,
                    $transaction->recorder?->name ?? 'Sistem',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
