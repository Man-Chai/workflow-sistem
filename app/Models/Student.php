<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasUuids;

    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $with = ['ortu'];

    protected $fillable = [
        'id_ortu',
        'id_kelas',
        'nama',
    ];

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_siswa'] ?? null;
    }

    public function getClassIdAttribute(): ?string
    {
        return $this->attributes['id_kelas'] ?? null;
    }

    public function setClassIdAttribute(?string $value): void
    {
        $this->attributes['id_kelas'] = $value;
    }

    public function getParentIdAttribute(): ?string
    {
        return $this->attributes['id_ortu'] ?? null;
    }

    public function setParentIdAttribute(?string $value): void
    {
        $this->attributes['id_ortu'] = $value;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getNisAttribute(): string
    {
        return strtoupper(substr((string) ($this->attributes['id_siswa'] ?? ''), 0, 8));
    }

    public function getGenderAttribute(): string
    {
        return 'L';
    }

    public function getBirthDateAttribute(): mixed
    {
        return null;
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'id_kelas', 'id_kelas');
    }

    /** @return BelongsTo<Ortu, $this> */
    public function ortu(): BelongsTo
    {
        return $this->belongsTo(Ortu::class, 'id_ortu', 'id_ortu');
    }

    /** @return BelongsTo<Ortu, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Ortu::class, 'id_ortu', 'id_ortu');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'id_siswa', 'id_siswa');
    }

    public function totalPaid(): int
    {
        return (int) round((float) $this->payments()->sum('jumlah'));
    }
}
