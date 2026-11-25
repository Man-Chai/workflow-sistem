<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ClassRoom extends Model
{
    use HasUuids;

    protected $table = 'kelas';

    protected $primaryKey = 'id_kelas';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nama',
    ];

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_kelas'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getAcademicYearAttribute(): string
    {
        return '2026/2027';
    }

    public function getDescriptionAttribute(): ?string
    {
        $waGroups = $this->relationLoaded('grupWa') ? $this->grupWa : $this->grupWa()->get();
        if ($waGroups->isEmpty()) {
            return null;
        }

        return 'Grup WA: '.$waGroups->pluck('nama')->implode(', ');
    }

    /** @return BelongsToMany<User, $this> */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'kelas_user',
            'id_kelas',
            'id_user',
            'id_kelas',
            'id_user'
        );
    }

    public function getCoordinatorAttribute(): ?User
    {
        if ($this->relationLoaded('coordinators')) {
            return $this->coordinators->first();
        }

        return $this->coordinators()->first();
    }

    public function getCoordinatorIdAttribute(): ?string
    {
        return $this->coordinator?->id_user;
    }

    /** @return HasMany<GrupWa, $this> */
    public function grupWa(): HasMany
    {
        return $this->hasMany(GrupWa::class, 'id_kelas', 'id_kelas');
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'id_kelas', 'id_kelas');
    }

    /** @return HasMany<CashTransaction, $this> */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'id_kelas', 'id_kelas');
    }

    /** @return HasManyThrough<Payment, Student, $this> */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Student::class,
            'id_kelas',
            'id_siswa',
            'id_kelas',
            'id_siswa'
        );
    }

    /** @return HasMany<Agenda, $this> */
    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'id_kelas', 'id_kelas');
    }

    public function cashBalance(): int
    {
        $income = (float) $this->cashTransactions()->sum('debit');
        $expense = (float) $this->cashTransactions()->sum('kredit');

        return (int) round($income - $expense);
    }

    public function label(): string
    {
        return $this->name.' · '.$this->academic_year;
    }
}
