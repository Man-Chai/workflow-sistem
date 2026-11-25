<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasUuids;

    protected $table = 'role';

    protected $primaryKey = 'id_role';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_level',
        'nama',
    ];

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'id_level', 'id_level');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_role', 'id_role');
    }
}
