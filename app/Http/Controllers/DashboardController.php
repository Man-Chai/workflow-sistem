<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Announcement;
use App\Models\CashTransaction;
use App\Models\ClassRoom;
use App\Models\Due;
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
            ->with('coordinator')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $classRows = $classes->map(function (ClassRoom $class) {
            $target = (int) $class->dues()
                ->where('is_active', true)
                ->get()
                ->sum(fn (Due $due) => $due->targetAmount($class->students_count));

            $verified = (int) $class->payments()->where('payments.status', 'verified')->sum('payments.amount');

            return [
                'class' => $class,
                'students' => $class->students_count,
                'balance' => $class->cashBalance(),
                'target' => $target,
                'verified' => $verified,
                'rate' => $target > 0 ? min(100, (int) round($verified / $target * 100)) : 0,
            ];
        });

        $pendingPayments = Payment::query()
            ->with(['student', 'due.classRoom'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentTransactions = CashTransaction::query()
            ->with(['classRoom', 'recorder'])
            ->latest('transaction_date')
            ->latest('id')
            ->take(6)
            ->get();

        $announcements = Announcement::query()
            ->with(['creator', 'classRoom'])
            ->whereNotNull('published_at')
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->take(4)
            ->get();

        $agendas = Agenda::query()
            ->with('classRoom')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(4)
            ->get();

        $stats = [
            'classes' => $classes->count(),
            'students' => (int) $classes->sum('students_count'),
            'parents' => User::query()->where('role', User::ROLE_PARENT)->count(),
            'balance' => (int) $classRows->sum('balance'),
            'pending' => Payment::query()->where('status', 'pending')->count(),
        ];

        return view('dashboard.admin', [
            'classRows' => $classRows,
            'pendingPayments' => $pendingPayments,
            'recentTransactions' => $recentTransactions,
            'announcements' => $announcements,
            'agendas' => $agendas,
            'stats' => $stats,
            'series' => Cashflow::monthly($classes->pluck('id')->all()),
        ]);
    }

    private function coordinatorDashboard(User $user): View
    {
        $class = $user->classesCoordinated()->withCount('students')->first();

        if (! $class) {
            return view('dashboard.koordinator', ['class' => null]);
        }

        $dues = $class->dues()->where('is_active', true)->orderBy('due_date')->get();
        $target = (int) $dues->sum(fn (Due $due) => $due->targetAmount($class->students_count));
        $verified = (int) $class->payments()->where('payments.status', 'verified')->sum('payments.amount');
        $rate = $target > 0 ? min(100, (int) round($verified / $target * 100)) : 0;

        $pendingPayments = Payment::query()
            ->with(['student', 'due'])
            ->whereHas('due', fn ($query) => $query->where('class_id', $class->id))
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentTransactions = $class->cashTransactions()
            ->with('recorder')
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();

        $upcomingAgendas = Agenda::query()
            ->visibleTo($user)
            ->with('classRoom')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(4)
            ->get();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with(['creator', 'classRoom'])
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('dashboard.koordinator', [
            'class' => $class,
            'dues' => $dues,
            'target' => $target,
            'verified' => $verified,
            'rate' => $rate,
            'balance' => $class->cashBalance(),
            'pendingPayments' => $pendingPayments,
            'pendingCount' => Payment::query()
                ->whereHas('due', fn ($query) => $query->where('class_id', $class->id))
                ->where('status', 'pending')
                ->count(),
            'recentTransactions' => $recentTransactions,
            'upcomingAgendas' => $upcomingAgendas,
            'announcements' => $announcements,
            'series' => Cashflow::monthly([$class->id]),
        ]);
    }

    private function parentDashboard(User $user): View
    {
        $children = $user->children()->with('classRoom')->orderBy('name')->get();

        $upcomingAgendas = Agenda::query()
            ->visibleTo($user)
            ->with('classRoom')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(4)
            ->get();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with(['creator', 'classRoom'])
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('dashboard.orangtua', [
            'children' => $children,
            'classCount' => $children->pluck('class_id')->filter()->unique()->count(),
            'upcomingAgendas' => $upcomingAgendas,
            'announcements' => $announcements,
            'latestAnnouncement' => $announcements->first(),
        ]);
    }
}
