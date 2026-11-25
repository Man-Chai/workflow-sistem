<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ortu extends Model
{
    use HasUuids;

    protected $table = 'ortu';

    protected $primaryKey = 'id_ortu';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nama',
        'telepon',
        'email',
    ];

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_ortu'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['telepon'] ?? null;
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'id_ortu', 'id_ortu');
    }
}
