<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ManagesClassData
{
    /**
     * Resolve the class a manager is allowed to write to.
     * Administrators may pick a class (or null for global records), coordinators are locked to their class.
     */
    protected function managedClassId(Request $request, bool $allowGlobal = false): ?int
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $classId = $request->integer('class_id') ?: null;

            if ($classId !== null) {
                $exists = ClassRoom::query()->whereKey($classId)->exists();
                if (! $exists) {
                    throw ValidationException::withMessages(['class_id' => 'Kelas tidak ditemukan.']);
                }
            }

            if ($classId === null && ! $allowGlobal) {
                throw ValidationException::withMessages(['class_id' => 'Pilih kelas terlebih dahulu.']);
            }

            return $classId;
        }

        abort_unless($user->isCoordinator(), 403, 'Hanya koordinator kelas yang dapat mengelola data ini.');

        $classId = $user->classesCoordinated()->value('id');

        abort_if($classId === null, 403, 'Anda belum ditugaskan sebagai koordinator kelas.');

        return (int) $classId;
    }

    protected function assertCanManageClass(int $classId): void
    {
        abort_unless(auth()->user()->canManageClass($classId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }
}
