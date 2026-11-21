<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'class_id',
        'parent_id',
        'nis',
        'name',
        'gender',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /** @return BelongsTo<User, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function verifiedPaidFor(Due $due): int
    {
        return (int) $this->payments()
            ->where('due_id', $due->id)
            ->where('status', 'verified')
            ->sum('amount');
    }

    public function remainingFor(Due $due): int
    {
        return max(0, (int) $due->amount - $this->verifiedPaidFor($due));
    }
}
