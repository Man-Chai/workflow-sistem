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
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
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
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (int) round((float) $data['amount']);
        $desc = trim($data['category'].(! empty($data['description']) ? ' — '.$data['description'] : ''));

        CashTransaction::create([
            'id_kelas' => $classId,
            'tanggal' => $data['transaction_date'],
            'keterangan' => $desc,
            'debit' => $data['type'] === 'masuk' ? $amount : 0,
            'kredit' => $data['type'] === 'keluar' ? $amount : 0,
        ]);

        return redirect()
            ->route('cash.index', ['class' => $classId, 'tab' => 'buku'])
            ->with('success', 'Transaksi laporan keuangan berhasil dicatat.');
    }

    public function edit(CashTransaction $transaction): View
    {
        $this->assertCanManageClass($transaction->id_kelas);

        $user = auth()->user();
        $classes = ClassRoom::query()
            ->whereIn('id_kelas', $user->accessibleClassIds())
            ->orderBy('nama')
            ->get();

        return view('kas.transaction-form', [
            'classes' => $classes,
            'transaction' => $transaction,
        ]);
    }

    public function update(Request $request, CashTransaction $transaction): RedirectResponse
    {
        $this->assertCanManageClass($transaction->id_kelas);
        $classId = $this->managedClassId($request);

        $data = $request->validate([
            'type' => ['required', 'in:masuk,keluar'],
            'category' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (int) round((float) $data['amount']);

        if (! empty($transaction->id_pembukuan)) {
            $data['type'] = 'masuk';
            $desc = $transaction->keterangan;
        } else {
            $desc = trim($data['category'].(! empty($data['description']) ? ' — '.$data['description'] : ''));
        }

        $transaction->update([
            'id_kelas' => $classId,
            'tanggal' => $data['transaction_date'],
            'keterangan' => $desc,
            'debit' => $data['type'] === 'masuk' ? $amount : 0,
            'kredit' => $data['type'] === 'keluar' ? $amount : 0,
        ]);

        return redirect()
            ->route('cash.index', ['class' => $classId, 'tab' => 'buku'])
            ->with('success', 'Transaksi laporan keuangan berhasil diperbarui.');
    }

    public function destroy(CashTransaction $transaction): RedirectResponse
    {
        $this->assertCanManageClass($transaction->id_kelas);

        $transaction->delete();

        return back()->with('success', 'Transaksi laporan keuangan telah dihapus.');
    }
}
