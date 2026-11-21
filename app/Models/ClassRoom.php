<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ClassRoom extends Model
{
    protected $table = 'classes';

    /**
     * Daftar kelas yang digunakan aplikasi: Kelas 1 sampai Kelas 6.
     *
     * @var list<string>
     */
    public const LEVELS = [
        'Kelas 1',
        'Kelas 2',
        'Kelas 3',
        'Kelas 4',
        'Kelas 5',
        'Kelas 6',
    ];

    protected $fillable = [
        'name',
        'academic_year',
        'coordinator_id',
        'description',
    ];

    /** @return BelongsTo<User, $this> */
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /** @return HasMany<Due, $this> */
    public function dues(): HasMany
    {
        return $this->hasMany(Due::class, 'class_id');
    }

    /** @return HasMany<CashTransaction, $this> */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'class_id');
    }

    /** @return HasManyThrough<Payment, Due, $this> */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Due::class, 'class_id', 'due_id');
    }

    /** @return HasMany<Agenda, $this> */
    public function agendas(): HasMany
    {
        return $this->hasMany(Agenda::class, 'class_id');
    }

    /** @return HasMany<Announcement, $this> */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'class_id');
    }

    public function cashBalance(): int
    {
        $income = (int) $this->cashTransactions()->where('type', 'masuk')->sum('amount');
        $expense = (int) $this->cashTransactions()->where('type', 'keluar')->sum('amount');

        return $income - $expense;
    }

    public function label(): string
    {
        return $this->name.' · '.$this->academic_year;
    }
}
