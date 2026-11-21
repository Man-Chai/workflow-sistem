<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KOORDINATOR = 'koordinator';

    public const ROLE_PARENT = 'orang_tua';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
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
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_KOORDINATOR => 'Koordinator Kelas',
            default => 'Orang Tua',
        };
    }

    /** @return HasMany<Student, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    /** @return HasMany<ClassRoom, $this> */
    public function classesCoordinated(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'coordinator_id');
    }

    /** @return HasMany<Announcement, $this> */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * Class IDs that the current user may read.
     *
     * @return array<int, int>
     */
    public function accessibleClassIds(): array
    {
        return match ($this->role) {
            self::ROLE_ADMIN => ClassRoom::query()->pluck('id')->all(),
            self::ROLE_KOORDINATOR => $this->classesCoordinated()->pluck('id')->all(),
            default => $this->children()->pluck('class_id')->unique()->values()->all(),
        };
    }

    public function canManageClass(int $classId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isCoordinator()
            && $this->classesCoordinated()->whereKey($classId)->exists();
    }
}
