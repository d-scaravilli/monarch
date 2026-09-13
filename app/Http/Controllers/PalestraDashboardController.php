<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\AccountingService;
use App\Support\CourseYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PalestraDashboardController extends Controller
{
    /**
     * Overview for admins/instructors entering the Palestra module.
     * Instructors only ever see their own courses' data.
     */
    public function index(AccountingService $accounting): View
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

        $weekAttendances = $this->validAttendancesQuery($courseIds)
            ->whereBetween('lessons.date', [$weekStart, $weekEnd])
            ->get();
        $weekAttendanceRate = $weekAttendances->isEmpty()
            ? null
            : round($weekAttendances->where('present', true)->count() / $weekAttendances->count() * 100);

        // Weekly attendance rate for the last 10 weeks, for the area chart.
        $weeklyAttendanceTrend = [];
        for ($i = 9; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();

            $weekSet = $this->validAttendancesQuery($courseIds)
                ->whereBetween('lessons.date', [$start, $end])
                ->get();

            $weeklyAttendanceTrend[$start->translatedFormat('d M')] = $weekSet->isEmpty()
                ? 0
                : round($weekSet->where('present', true)->count() / $weekSet->count() * 100);
        }

        $topPresent = $this->topByAttendance($courseIds, present: true);
        $topAbsent = $this->topByAttendance($courseIds, present: false);

        $newMembersThisMonth = User::role('member')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $outstanding = $accounting->totals(
            $accounting->enrollmentsForYear(CourseYear::default(), $courseIds)
        )['missing'];

        [$miniCalendarDays, $miniCalendarLessons] = $this->miniMonthCalendar($courseIds);

        return view('palestra.dashboard', [
            'activeEnrollments' => $activeEnrollments,
            'lessonsThisWeek' => $lessonsThisWeek,
            'weekAttendanceRate' => $weekAttendanceRate,
            'weeklyAttendanceTrend' => $weeklyAttendanceTrend,
            'topPresent' => $topPresent,
            'topAbsent' => $topAbsent,
            'newMembersThisMonth' => $newMembersThisMonth,
            'outstanding' => $outstanding,
            'isAdmin' => $isAdmin,
            'miniCalendarDays' => $miniCalendarDays,
            'miniCalendarLessons' => $miniCalendarLessons,
            'miniCalendarAnchor' => now(),
        ]);
    }

    /**
     * Current month's grid + lessons, for the miniature calendar panel —
     * feeds the same reusable <x-month-calendar-grid> the full Calendario
     * page uses, just scoped to "this month" with no filters.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return array{0: Collection<int, Carbon>, 1: Collection<string, Collection<int, Lesson>>}
     */
    private function miniMonthCalendar(?Collection $courseIds): array
    {
        $gridStart = now()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = now()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = collect();
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        $lessons = Lesson::query()
            ->with('course.discipline')
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->get()
            ->groupBy(fn (Lesson $lesson) => $lesson->date->toDateString());

        return [$days, $lessons];
    }

    /**
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, object{user: User, count: int}>
     */
    private function topByAttendance(?Collection $courseIds, bool $present): Collection
    {
        $rows = $this->validAttendancesQuery($courseIds)
            ->where('attendances.present', $present)
            ->select('enrollments.user_id')
            ->selectRaw('count(*) as attendance_count')
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

    /**
     * Base query for attendance stats: joins in enrollments/lessons and
     * excludes any attendance row for a lesson that happened before the
     * member's enrollment_date (they couldn't have attended it, so it
     * must never inflate an absence count). Every attendance aggregate
     * on this dashboard builds on this one query.
     *
     * @param  Collection<int, int>|null  $courseIds
     */
    private function validAttendancesQuery(?Collection $courseIds): Builder
    {
        return Attendance::query()
            ->join('enrollments', 'attendances.enrollment_id', '=', 'enrollments.id')
            ->join('lessons', 'attendances.lesson_id', '=', 'lessons.id')
            ->whereColumn('lessons.date', '>=', 'enrollments.enrollment_date')
            ->when($courseIds, fn ($q) => $q->whereIn('lessons.course_id', $courseIds))
            ->select('attendances.*');
    }
}
