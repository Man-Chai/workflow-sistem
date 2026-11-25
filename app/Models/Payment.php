<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $table = 'pembukuan';

    protected $primaryKey = 'id_pembukuan';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_siswa',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_pembukuan'] ?? null;
    }

    public function getStudentIdAttribute(): ?string
    {
        return $this->attributes['id_siswa'] ?? null;
    }

    public function getAmountAttribute(): int
    {
        return (int) round((float) ($this->attributes['jumlah'] ?? 0));
    }

    public function getPaidAtAttribute(): mixed
    {
        return $this->created_at ?? now();
    }

    public function getMethodAttribute(): string
    {
        return 'kas';
    }

    public function getStatusAttribute(): string
    {
        return 'verified';
    }

    public function getNoteAttribute(): ?string
    {
        return null;
    }

    public function getProofPathAttribute(): ?string
    {
        return null;
    }

    public function getProofDataAttribute(): ?string
    {
        return null;
    }

    public function getDueAttribute(): object
    {
        $classRoom = $this->student?->classRoom;

        return (object) [
            'id' => $classRoom?->id_kelas,
            'class_id' => $classRoom?->id_kelas,
            'title' => 'Kas / Pembukuan Kelas',
            'period' => $classRoom?->name,
            'classRoom' => $classRoom,
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'id_siswa', 'id_siswa');
    }

    public function statusLabel(): string
    {
        return 'Terverifikasi';
    }
}
