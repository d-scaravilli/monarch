<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MedicalCertificate;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PalestraDashboardController extends Controller
{
    /**
     * Overview for admins/instructors entering the Palestra module.
     * Instructors never see revenue or certificate data (same rule as
     * everywhere else in the module) and only their own courses.
     */
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'instructor']), 403);

        $isAdmin = $user->hasRole('admin');

        $courseIds = $isAdmin
            ? null
            : $user->instructedCourses()->pluck('courses.id');

        $activeEnrollments = Enrollment::query()
            ->where('status', 'active')
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->count();

        $lessonsToday = Lesson::query()
            ->whereDate('date', today())
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->count();

        $recentAttendances = Attendance::query()
            ->whereHas('lesson', function ($q) use ($courseIds) {
                $q->where('date', '>=', now()->subWeeks(4))->where('date', '<=', today());
                if ($courseIds) {
                    $q->whereIn('course_id', $courseIds);
                }
            })
            ->get();
        $avgAttendanceRate = $recentAttendances->isEmpty()
            ? null
            : round($recentAttendances->where('present', true)->count() / $recentAttendances->count() * 100);

        $upcomingLessons = Lesson::query()
            ->whereBetween('date', [today(), today()->addDays(7)])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with(['course.discipline', 'course.room'])
            ->orderBy('date')
            ->get();

        $topCourses = Course::query()
            ->withCount(['enrollments' => fn ($q) => $q->where('status', 'active')])
            ->when($courseIds, fn ($q) => $q->whereIn('id', $courseIds))
            ->with(['discipline', 'room'])
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        $revenueThisMonth = null;
        $revenueTrend = null;
        $monthlyRevenue = [];
        $expiringCertificates = collect();

        if ($isAdmin) {
            $revenueThisMonth = Payment::whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount');
            $revenueLastMonth = Payment::whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year)->sum('amount');

            if ($revenueLastMonth > 0) {
                $revenueTrend = [
                    'direction' => $revenueThisMonth >= $revenueLastMonth ? 'up' : 'down',
                    'label' => round(abs($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100).'%',
                ];
            }

            $rows = Payment::query()
                ->select(DB::raw("DATE_FORMAT(date, '%Y-%m') as ym"), DB::raw('SUM(amount) as total'))
                ->where('date', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('ym')
                ->pluck('total', 'ym');

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthlyRevenue[$month->translatedFormat('M')] = (float) ($rows[$month->format('Y-m')] ?? 0);
            }

            $expiringCertificates = MedicalCertificate::query()
                ->whereBetween('expiry_date', [today(), today()->addDays(30)])
                ->with('user')
                ->orderBy('expiry_date')
                ->get();
        }

        return view('palestra.dashboard', [
            'activeEnrollments' => $activeEnrollments,
            'lessonsToday' => $lessonsToday,
            'avgAttendanceRate' => $avgAttendanceRate,
            'revenueThisMonth' => $revenueThisMonth,
            'revenueTrend' => $revenueTrend,
            'monthlyRevenue' => $monthlyRevenue,
            'upcomingLessons' => $upcomingLessons,
            'topCourses' => $topCourses,
            'expiringCertificates' => $expiringCertificates,
            'isAdmin' => $isAdmin,
        ]);
    }
}
