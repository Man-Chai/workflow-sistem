<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesClassData;
use App\Models\CashTransaction;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashTransactionController extends Controller
{
    use ManagesClassData;

    public function create(): View
    {
        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id', $user->accessibleClassIds())
            ->orderBy('name')
            ->get();

        abort_if($classes->isEmpty(), 403, 'Belum ada kelas yang dapat dikelola.');

        return view('kas.transaction-form', ['classes' => $classes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classId = $this->managedClassId($request);

        $data = $request->validate([
            'type' => ['required', 'in:masuk,keluar'],
            'category' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        CashTransaction::create([
            'class_id' => $classId,
            'recorded_by' => $request->user()->id,
            'type' => $data['type'],
            'category' => $data['category'],
            'amount' => $data['amount'],
            'transaction_date' => $data['transaction_date'],
            'description' => $data['description'] ?? null,
        ]);

        return redirect()
            ->route('cash.index', ['class' => $classId, 'tab' => 'buku'])
            ->with('success', 'Transaksi kas berhasil dicatat.');
    }

    public function destroy(CashTransaction $transaction): RedirectResponse
    {
        $this->assertCanManageClass($transaction->class_id);

        abort_if($transaction->payment_id !== null, 403, 'Transaksi dari iuran tidak dapat dihapus manual.');

        $transaction->delete();

        return back()->with('success', 'Transaksi kas telah dihapus.');
    }
}
