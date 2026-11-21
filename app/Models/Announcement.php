<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'class_id',
        'created_by',
        'title',
        'body',
        'priority',
        'is_pinned',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $classIds = $user->accessibleClassIds();

        return $query->where(function (Builder $q) use ($classIds) {
            $q->whereNull('class_id')->orWhereIn('class_id', $classIds);
        })->whereNotNull('published_at');
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'mendesak' => 'Mendesak',
            'penting' => 'Penting',
            default => 'Informasi',
        };
    }

    public function isGlobal(): bool
    {
        return $this->class_id === null;
    }
}
