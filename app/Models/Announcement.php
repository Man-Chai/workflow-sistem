<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    use HasUuids;

    protected $table = 'agenda';

    protected $primaryKey = 'id_agenda';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_kelas',
        'tanggal',
        'agenda',
        'mulai',
        'selesai',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_agenda'] ?? null;
    }

    public function getClassIdAttribute(): ?string
    {
        return $this->attributes['id_kelas'] ?? null;
    }

    public function getTitleAttribute(): string
    {
        return (string) ($this->attributes['agenda'] ?? '');
    }

    public function getBodyAttribute(): string
    {
        $parts = [];
        if (! empty($this->attributes['tanggal'])) {
            $dateStr = Carbon::parse($this->attributes['tanggal'])->translatedFormat('l, d F Y');
            $timeStr = ! empty($this->attributes['mulai']) ? ' pukul '.substr((string) $this->attributes['mulai'], 0, 5).' WIB' : '';
            $parts[] = 'Jadwal: '.$dateStr.$timeStr;
        }
        if (! empty($this->attributes['keterangan'])) {
            $parts[] = $this->attributes['keterangan'];
        }

        return implode("\n\n", $parts);
    }

    public function getPriorityAttribute(): string
    {
        if (! empty($this->attributes['tanggal'])) {
            $date = Carbon::parse($this->attributes['tanggal']);
            if ($date->isToday() || ($date->isFuture() && now()->diffInDays($date) <= 2)) {
                return 'penting';
            }
        }

        return 'normal';
    }

    public function getIsPinnedAttribute(): bool
    {
        return ! empty($this->attributes['tanggal'])
            && Carbon::parse($this->attributes['tanggal'])->isToday();
    }

    public function getPublishedAtAttribute(): ?Carbon
    {
        return $this->created_at ?? now();
    }

    public function getCreatorAttribute(): ?object
    {
        return $this->classRoom?->coordinator;
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'id_kelas', 'id_kelas');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $classIds = array_values(array_filter($user->accessibleClassIds()));

        return $query->where(function (Builder $sub) use ($classIds): void {
            $sub->whereNull('id_kelas');
            if (! empty($classIds)) {
                $sub->orWhereIn('id_kelas', $classIds);
            }
        });
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
        return false;
    }
}
