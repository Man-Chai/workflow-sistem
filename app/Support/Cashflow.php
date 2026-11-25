<?php

namespace App\Support;

use App\Models\CashTransaction;
use Illuminate\Support\Carbon;

class Cashflow
{
    /**
     * Build a six-month income, expense, and running-balance series for the given classes.
     *
     * @param  array<int, string>  $classIds
     * @return array{
     *     labels: array<int, string>,
     *     income: array<int, int>,
     *     expense: array<int, int>,
     *     balance: array<int, int>,
     *     max: int,
     *     min: int,
     *     change: int,
     *     change_percent: float
     * }
     */
    public static function monthly(array $classIds, int $months = 6): array
    {
        $labels = [];
        $income = [];
        $expense = [];

        $firstMonth = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $openingIncome = (int) round((float) CashTransaction::query()
            ->whereIn('id_kelas', $classIds)
            ->where('tanggal', '<', $firstMonth->toDateString())
            ->sum('debit'));

        $openingExpense = (int) round((float) CashTransaction::query()
            ->whereIn('id_kelas', $classIds)
            ->where('tanggal', '<', $firstMonth->toDateString())
            ->sum('kredit'));

        $runningBalance = $openingIncome - $openingExpense;
        $balance = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $labels[] = $month->translatedFormat('M');

            $base = CashTransaction::query()
                ->whereIn('id_kelas', $classIds)
                ->whereBetween('tanggal', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ]);

            $monthIncome = (int) round((float) (clone $base)->sum('debit'));
            $monthExpense = (int) round((float) (clone $base)->sum('kredit'));

            $income[] = $monthIncome;
            $expense[] = $monthExpense;

            $runningBalance += $monthIncome - $monthExpense;
            $balance[] = $runningBalance;
        }

        $change = $runningBalance - ($openingIncome - $openingExpense);
        $previous = abs($openingIncome - $openingExpense);

        return [
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'max' => max(1, ...$income, ...$expense, ...$balance),
            'min' => min(0, ...$income, ...$expense, ...$balance),
            'change' => $change,
            'change_percent' => $previous > 0 ? round($change / $previous * 100, 1) : 0.0,
        ];
    }

    public static function percent(int $value, int $max): int
    {
        if ($max <= 0) {
            return 0;
        }

        return max(3, (int) round($value / $max * 100));
    }
}
