<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\CashTransaction;
use App\Models\ClassRoom;
use App\Models\Ortu;
use App\Models\Payment;
use App\Models\User;
use App\Support\Cashflow;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return match ($user->role) {
            User::ROLE_ADMIN => $this->adminDashboard($user),
            User::ROLE_KOORDINATOR => $this->coordinatorDashboard($user),
            default => $this->parentDashboard($user),
        };
    }

    private function adminDashboard(User $user): View
    {
        $classes = ClassRoom::query()
            ->with(['coordinators', 'grupWa'])
            ->withCount('students')
            ->orderBy('nama')
            ->get();

        $classRows = $classes->map(function (ClassRoom $class) {
            $verified = (int) round((float) $class->payments()->sum('pembukuan.jumlah'));
            $balance = $class->cashBalance();

            return [
                'class' => $class,
                'students' => $class->students_count,
                'balance' => $balance,
                'target' => $verified,
                'verified' => $verified,
                'rate' => $verified > 0 ? 100 : 0,
            ];
        });

        $recentTransactions = CashTransaction::query()
            ->with('classRoom.coordinators')
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $announcements = Announcement::query()
            ->with('classRoom.coordinators')
            ->orderByDesc('tanggal')
            ->orderByDesc('mulai')
            ->take(4)
            ->get();

        $agendas = Agenda::query()
            ->with('classRoom.coordinators')
            ->where('tanggal', '>=', today()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('mulai')
            ->take(4)
            ->get();

        $stats = [
            'classes' => $classes->count(),
            'students' => (int) $classes->sum('students_count'),
            'parents' => Ortu::query()->count(),
            'balance' => (int) $classRows->sum('balance'),
            'pending' => Payment::query()->count(),
        ];

        return view('dashboard.admin', [
            'classRows' => $classRows,
            'pendingPayments' => collect(),
            'recentTransactions' => $recentTransactions,
            'announcements' => $announcements,
            'agendas' => $agendas,
            'stats' => $stats,
            'series' => Cashflow::monthly($classes->pluck('id_kelas')->all()),
        ]);
    }

    private function coordinatorDashboard(User $user): View
    {
        $class = $user->classesCoordinated()->with('grupWa')->withCount('students')->first();

        if (! $class) {
            return view('dashboard.koordinator', ['class' => null]);
        }

        $verified = (int) round((float) $class->payments()->sum('pembukuan.jumlah'));
        $target = $verified;
        $rate = $verified > 0 ? 100 : 0;

        $recentTransactions = $class->cashTransactions()
            ->with('classRoom.coordinators')
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $upcomingAgendas = Agenda::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators')
            ->where('tanggal', '>=', today()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('mulai')
            ->take(4)
            ->get();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators')
            ->orderByDesc('tanggal')
            ->orderByDesc('mulai')
            ->take(4)
            ->get();

        return view('dashboard.koordinator', [
            'class' => $class,
            'dues' => collect(),
            'target' => $target,
            'verified' => $verified,
            'rate' => $rate,
            'balance' => $class->cashBalance(),
            'pendingPayments' => collect(),
            'pendingCount' => 0,
            'recentTransactions' => $recentTransactions,
            'upcomingAgendas' => $upcomingAgendas,
            'announcements' => $announcements,
            'series' => Cashflow::monthly([$class->id_kelas]),
        ]);
    }

    private function parentDashboard(User $user): View
    {
        $children = $user->children;

        $upcomingAgendas = Agenda::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators')
            ->where('tanggal', '>=', today()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('mulai')
            ->take(4)
            ->get();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with('classRoom.coordinators')
            ->orderByDesc('tanggal')
            ->orderByDesc('mulai')
            ->take(4)
            ->get();

        return view('dashboard.orangtua', [
            'children' => $children,
            'classCount' => $children->pluck('id_kelas')->filter()->unique()->count(),
            'upcomingAgendas' => $upcomingAgendas,
            'announcements' => $announcements,
            'latestAnnouncement' => $announcements->first(),
        ]);
    }
}
