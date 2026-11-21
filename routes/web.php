<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\DueController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('daftar', [RegisterController::class, 'show'])->name('register');
    Route::post('daftar', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('deploy/setup', [DeployController::class, 'setup'])->name('deploy.setup');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profil/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::middleware('role:admin,koordinator')->group(function (): void {
        Route::get('kas', [CashController::class, 'index'])->name('cash.index');
        Route::get('kas/export', [CashController::class, 'export'])->name('cash.export');

        Route::get('kas/pembayaran/baru', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('kas/pembayaran', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('kas/pembayaran/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('kas/pembayaran/{payment}/bukti', [PaymentController::class, 'proof'])->name('payments.proof');

        Route::get('kas/transaksi/baru', [CashTransactionController::class, 'create'])->name('cash.create');
        Route::post('kas/transaksi', [CashTransactionController::class, 'store'])->name('cash.store');
        Route::delete('kas/transaksi/{transaction}', [CashTransactionController::class, 'destroy'])->name('cash.destroy');

        Route::get('kas/iuran/baru', [DueController::class, 'create'])->name('dues.create');
        Route::post('kas/iuran', [DueController::class, 'store'])->name('dues.store');
        Route::get('kas/iuran/{due}/ubah', [DueController::class, 'edit'])->name('dues.edit');
        Route::put('kas/iuran/{due}', [DueController::class, 'update'])->name('dues.update');
        Route::delete('kas/iuran/{due}', [DueController::class, 'destroy'])->name('dues.destroy');

        Route::post('kas/pembayaran/{payment}/verifikasi', [PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('kas/pembayaran/{payment}/tolak', [PaymentController::class, 'reject'])->name('payments.reject');

        Route::get('kelas/{classRoom}', [ClassRoomController::class, 'show'])->name('classes.show');

        Route::get('siswa/{student}/ubah', [StudentController::class, 'edit'])->name('students.edit');
        Route::post('kelas/{classRoom}/siswa', [StudentController::class, 'store'])->name('students.store');
        Route::put('siswa/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('siswa/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });

    Route::get('agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('pemberitahuan', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('pemberitahuan/{announcement}', [AnnouncementController::class, 'show'])
        ->whereNumber('announcement')
        ->name('announcements.show');

    Route::middleware('role:admin,koordinator')->group(function (): void {
        Route::get('agenda/baru', [AgendaController::class, 'create'])->name('agenda.create');
        Route::post('agenda', [AgendaController::class, 'store'])->name('agenda.store');
        Route::get('agenda/{agenda}/ubah', [AgendaController::class, 'edit'])->name('agenda.edit');
        Route::put('agenda/{agenda}', [AgendaController::class, 'update'])->name('agenda.update');
        Route::delete('agenda/{agenda}', [AgendaController::class, 'destroy'])->name('agenda.destroy');

        Route::get('pemberitahuan/baru', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('pemberitahuan', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('pemberitahuan/{announcement}/ubah', [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('pemberitahuan/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('pemberitahuan/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    Route::middleware('role:admin')->group(function (): void {
        Route::get('admin/kelas', [ClassRoomController::class, 'index'])->name('classes.index');
        Route::get('admin/kelas/baru', [ClassRoomController::class, 'create'])->name('classes.create');
        Route::post('admin/kelas', [ClassRoomController::class, 'store'])->name('classes.store');
        Route::get('admin/kelas/{classRoom}/ubah', [ClassRoomController::class, 'edit'])->name('classes.edit');
        Route::put('admin/kelas/{classRoom}', [ClassRoomController::class, 'update'])->name('classes.update');
        Route::delete('admin/kelas/{classRoom}', [ClassRoomController::class, 'destroy'])->name('classes.destroy');

        Route::get('admin/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('admin/pengguna/baru', [UserController::class, 'create'])->name('users.create');
        Route::post('admin/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::get('admin/pengguna/{user}/ubah', [UserController::class, 'edit'])->name('users.edit');
        Route::put('admin/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('admin/pengguna/{user}/status', [UserController::class, 'toggle'])->name('users.toggle');
        Route::delete('admin/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
