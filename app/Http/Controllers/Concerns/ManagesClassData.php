<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ManagesClassData
{
    /**
     * Resolve the class UUID a manager is allowed to write to.
     */
    protected function managedClassId(Request $request, bool $allowGlobal = false): ?string
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $classId = $request->string('class_id')->trim()->toString() ?: null;

            if ($classId !== null) {
                $exists = ClassRoom::query()->whereKey($classId)->exists();
                if (! $exists) {
                    throw ValidationException::withMessages(['class_id' => 'Kelas tidak ditemukan.']);
                }
            }

            if ($classId === null && ! $allowGlobal) {
                $classId = ClassRoom::query()->orderBy('nama')->value('id_kelas');
                if ($classId === null) {
                    throw ValidationException::withMessages(['class_id' => 'Pilih kelas terlebih dahulu.']);
                }
            }

            return $classId;
        }

        abort_unless($user->isCoordinator(), 403, 'Hanya koordinator kelas yang dapat mengelola data ini.');

        $requestedClassId = $request->string('class_id')->trim()->toString() ?: null;
        if ($requestedClassId && $user->canManageClass($requestedClassId)) {
            return $requestedClassId;
        }

        $classId = $user->classesCoordinated()->value('kelas.id_kelas');

        abort_if($classId === null, 403, 'Anda belum ditugaskan sebagai koordinator kelas.');

        return (string) $classId;
    }

    protected function assertCanManageClass(string|int $classId): void
    {
        abort_unless(auth()->user()->canManageClass((string) $classId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }
}
