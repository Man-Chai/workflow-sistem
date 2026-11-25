<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasUuids;

    protected $table = 'laporan_keuangan';

    protected $primaryKey = 'id_laporan_keuangan';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_kelas',
        'id_pembukuan',
        'tanggal',
        'keterangan',
        'debit',
        'kredit',
        'saldo',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'debit' => 'float',
            'kredit' => 'float',
            'saldo' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_laporan_keuangan'] ?? null;
    }

    public function getClassIdAttribute(): ?string
    {
        return $this->attributes['id_kelas'] ?? null;
    }

    public function getTypeAttribute(): string
    {
        return ((float) ($this->attributes['debit'] ?? 0)) > 0 ? 'masuk' : 'keluar';
    }

    public function getCategoryAttribute(): string
    {
        $desc = (string) ($this->attributes['keterangan'] ?? '');
        if (str_starts_with($desc, 'Pembayaran kas/iuran siswa:')) {
            return 'Pembukuan Siswa';
        }

        return $this->type === 'masuk' ? 'Pemasukan' : 'Pengeluaran';
    }

    public function getAmountAttribute(): int
    {
        $debit = (float) ($this->attributes['debit'] ?? 0);
        $kredit = (float) ($this->attributes['kredit'] ?? 0);

        return (int) round($debit > 0 ? $debit : $kredit);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['keterangan'] ?? null;
    }

    public function getTransactionDateAttribute(): mixed
    {
        return $this->tanggal;
    }

    public function getPaymentIdAttribute(): ?string
    {
        if (! empty($this->attributes['id_pembukuan'])) {
            return (string) $this->attributes['id_pembukuan'];
        }

        $desc = (string) ($this->attributes['keterangan'] ?? '');

        return str_starts_with($desc, 'Pembayaran kas/iuran siswa:') ? 'auto' : null;
    }

    public function getRecorderAttribute(): ?object
    {
        return $this->classRoom?->coordinator;
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'id_kelas', 'id_kelas');
    }
}
