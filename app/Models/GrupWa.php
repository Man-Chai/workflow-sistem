<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrupWa extends Model
{
    use HasUuids;

    protected $table = 'grup_wa';

    protected $primaryKey = 'id_grup_wa';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_kelas',
        'nama',
        'id_grup',
    ];

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_grup_wa'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getGroupIdAttribute(): string
    {
        return (string) ($this->attributes['id_grup'] ?? '');
    }

    public function setGroupIdAttribute(?string $value): void
    {
        $this->attributes['id_grup'] = $value;
    }

    /** @return BelongsTo<ClassRoom, $this> */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'id_kelas', 'id_kelas');
    }
}
