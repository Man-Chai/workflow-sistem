<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KOORDINATOR = 'koordinator';

    public const ROLE_PARENT = 'orang_tua';

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $with = ['roleRelation'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id_role',
        'nama',
        'telepon',
        'lid',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function getIdAttribute(): ?string
    {
        return $this->attributes['id_user'] ?? null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['nama'] ?? '');
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['telepon'] ?? null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['telepon'] = $value ?: null;
    }

    public function getIsActiveAttribute(): bool
    {
        return true;
    }

    public function getLastLoginAtAttribute(): mixed
    {
        return $this->updated_at;
    }

    /** @return BelongsTo<Role, $this> */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function getRoleAttribute(): string
    {
        $roleName = strtolower((string) ($this->roleRelation?->nama ?? ''));

        if (str_contains($roleName, 'admin') || str_contains($roleName, 'operator')) {
            return self::ROLE_ADMIN;
        }

        if (str_contains($roleName, 'korlas') || str_contains($roleName, 'pengurus') || str_contains($roleName, 'koordinator')) {
            return self::ROLE_KOORDINATOR;
        }

        return self::ROLE_PARENT;
    }

    public static function resolveRoleId(string $roleKey): ?string
    {
        $pattern = match ($roleKey) {
            self::ROLE_ADMIN => '%admin%',
            self::ROLE_KOORDINATOR => '%korlas%',
            default => '%user%',
        };

        return Role::query()->where('nama', 'ilike', $pattern)->value('id_role')
            ?? Role::query()->value('id_role');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCoordinator(): bool
    {
        return $this->role === self::ROLE_KOORDINATOR;
    }

    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin / Operator',
            self::ROLE_KOORDINATOR => 'Korlas / Pengurus',
            default => 'Orang Tua / User',
        };
    }

    /** @return BelongsToMany<ClassRoom, $this> */
    public function classesCoordinated(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassRoom::class,
            'kelas_user',
            'id_user',
            'id_kelas',
            'id_user',
            'id_kelas'
        );
    }

    public function childrenQuery()
    {
        $email = trim((string) $this->email);
        $name = trim((string) $this->nama);
        $phoneDigits = preg_replace('/\D+/', '', (string) $this->telepon);
        $corePhone = '';

        if ($phoneDigits !== '') {
            if (str_starts_with($phoneDigits, '62')) {
                $corePhone = substr($phoneDigits, 2);
            } elseif (str_starts_with($phoneDigits, '0')) {
                $corePhone = substr($phoneDigits, 1);
            } else {
                $corePhone = $phoneDigits;
            }
        }

        if ($email === '' && $name === '' && $corePhone === '') {
            return Student::query()->whereRaw('1 = 0');
        }

        $ortuIds = Ortu::query()
            ->where(function ($q) use ($email, $name, $corePhone): void {
                if ($email !== '') {
                    $q->orWhereRaw('LOWER(TRIM(email)) = LOWER(?)', [$email]);
                }
                if ($corePhone !== '' && strlen($corePhone) >= 7) {
                    $q->orWhereRaw("regexp_replace(COALESCE(telepon, ''), '\\D', '', 'g') LIKE ?", ['%'.$corePhone]);
                }
                if ($name !== '') {
                    $q->orWhereRaw('LOWER(TRIM(nama)) = LOWER(?)', [$name]);
                }
            })
            ->pluck('id_ortu');

        return Student::query()->whereIn('id_ortu', $ortuIds);
    }

    /** @return Collection<int, Student> */
    public function getChildrenAttribute(): Collection
    {
        return $this->childrenQuery()->with('classRoom')->orderBy('nama')->get();
    }

    public function getChildrenCountAttribute(): int
    {
        return $this->childrenQuery()->count();
    }

    /**
     * Class IDs (UUID strings) that the current user may read.
     *
     * @return array<int, string>
     */
    public function accessibleClassIds(): array
    {
        if ($this->isAdmin()) {
            return ClassRoom::query()->pluck('id_kelas')->all();
        }

        if ($this->isCoordinator()) {
            $assigned = $this->classesCoordinated()->pluck('kelas.id_kelas')->all();

            return ! empty($assigned) ? $assigned : ClassRoom::query()->pluck('id_kelas')->all();
        }

        $childClassIds = $this->childrenQuery()
            ->whereNotNull('id_kelas')
            ->pluck('id_kelas')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $assignedIds = $this->classesCoordinated()
            ->pluck('kelas.id_kelas')
            ->filter()
            ->all();

        $ids = array_values(array_unique([...$childClassIds, ...$assignedIds]));

        return ! empty($ids) ? $ids : ClassRoom::query()->pluck('id_kelas')->all();
    }

    public function canManageClass(string|int $classId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isCoordinator()
            && $this->classesCoordinated()->where('kelas.id_kelas', (string) $classId)->exists();
    }
}
