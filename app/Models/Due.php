<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Due extends Model
{
    protected $fillable = [
        'class_id',
        'created_by',
        'title',
        'description',
        'amount',
        'period',
        'due_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function targetAmount(?int $studentCount = null): int
    {
        $studentCount ??= Student::query()->where('class_id', $this->class_id)->count();

        return (int) $this->amount * $studentCount;
    }

    public function verifiedAmount(): int
    {
        return (int) $this->payments()->where('status', 'verified')->sum('amount');
    }

    public function progressPercent(): int
    {
        $target = $this->targetAmount();

        if ($target <= 0) {
            return 0;
        }

        return min(100, (int) round($this->verifiedAmount() / $target * 100));
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->due_date->toDateString() !== today()->toDateString();
    }
}
