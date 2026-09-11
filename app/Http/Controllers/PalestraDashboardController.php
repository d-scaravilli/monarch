<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PalestraDashboardController extends Controller
{
    /**
     * Overview for admins/instructors entering the Palestra module.
     * Instructors only ever see their own courses' data.
     */
    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'instructor']), 403);

        $isAdmin = $user->hasRole('admin');
        $courseIds = $isAdmin ? null : $user->instructedCourses()->pluck('courses.id');

        $activeEnrollments = Enrollment::query()
            ->where('status', 'active')
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->count();

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $lessonsThisWeek = Lesson::query()
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->count();

        $weekAttendances = Attendance::query()
            ->whereHas('lesson', function ($q) use ($weekStart, $weekEnd, $courseIds) {
                $q->whereBetween('date', [$weekStart, $weekEnd]);
                if ($courseIds) {
                    $q->whereIn('course_id', $courseIds);
                }
            })
            ->get();
        $weekAttendanceRate = $weekAttendances->isEmpty()
            ? null
            : round($weekAttendances->where('present', true)->count() / $weekAttendances->count() * 100);

        // Weekly attendance rate for the last 10 weeks, for the area chart.
        $weeklyAttendanceTrend = [];
        for ($i = 9; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();

            $weekSet = Attendance::query()
                ->whereHas('lesson', function ($q) use ($start, $end, $courseIds) {
                    $q->whereBetween('date', [$start, $end]);
                    if ($courseIds) {
                        $q->whereIn('course_id', $courseIds);
                    }
                })
                ->get();

            $weeklyAttendanceTrend[$start->translatedFormat('d M')] = $weekSet->isEmpty()
                ? 0
                : round($weekSet->where('present', true)->count() / $weekSet->count() * 100);
        }

        $upcomingLessons = Lesson::query()
            ->whereBetween('date', [today(), today()->addDays(30)])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with(['course.discipline', 'course.room'])
            ->orderBy('date')
            ->take(20)
            ->get();

        $topPresent = $this->topByAttendance($courseIds, present: true);
        $topAbsent = $this->topByAttendance($courseIds, present: false);

        return view('palestra.dashboard', [
            'activeEnrollments' => $activeEnrollments,
            'lessonsThisWeek' => $lessonsThisWeek,
            'weekAttendanceRate' => $weekAttendanceRate,
            'weeklyAttendanceTrend' => $weeklyAttendanceTrend,
            'upcomingLessons' => $upcomingLessons,
            'topPresent' => $topPresent,
            'topAbsent' => $topAbsent,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, object{user: User, count: int}>
     */
    private function topByAttendance(?Collection $courseIds, bool $present): Collection
    {
        $rows = Attendance::query()
            ->join('enrollments', 'attendances.enrollment_id', '=', 'enrollments.id')
            ->join('lessons', 'attendances.lesson_id', '=', 'lessons.id')
            ->when($courseIds, fn ($q) => $q->whereIn('lessons.course_id', $courseIds))
            ->where('attendances.present', $present)
            ->selectRaw('enrollments.user_id, count(*) as attendance_count')
            ->groupBy('enrollments.user_id')
            ->orderByDesc('attendance_count')
            ->take(5)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->map(fn ($row) => (object) [
            'user' => $users->get($row->user_id),
            'count' => $row->attendance_count,
        ])->filter(fn ($row) => $row->user !== null)->values();
    }
}
