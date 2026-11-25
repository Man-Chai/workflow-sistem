<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasUuids;

    protected $table = 'level';

    protected $primaryKey = 'id_level';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nama',
        'keterangan',
    ];

    /** @return HasMany<Role, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'id_level', 'id_level');
    }
}
