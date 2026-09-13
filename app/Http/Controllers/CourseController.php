<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Discipline;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\User;
use App\Support\VisibleCourses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Active courses: all of them for the admin, only the assigned ones
     * for an instructor, and only the enrolled ones (plus every evento)
     * for a member.
     */
    public function index(): View
    {
        $user = Auth::user();
        $courseIds = VisibleCourses::idsFor($user);

        $courses = Course::query()
            ->with(['discipline', 'room', 'schedules'])
            ->withCount('enrollments')
            ->when($courseIds !== null, fn ($q) => $q->whereIn('id', $courseIds))
            ->orderBy('year')
            ->get();

        return view('courses.index', compact('courses'));
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        $disciplines = Discipline::orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $instructors = User::role('instructor')->orderBy('name')->get();

        return view('courses.create', compact('disciplines', 'rooms', 'instructors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $data = $this->validateCourse($request);

        $discipline = $this->resolveDiscipline($request);
        $room = $this->resolveRoom($request);

        $course = Course::create([
            'discipline_id' => $discipline->id,
            'room_id' => $room->id,
            'type' => $data['type'],
            'year' => $data['year'],
            'description' => $data['description'] ?? null,
            'annual_cost' => $data['annual_cost'],
            'monthly_cost' => $data['monthly_cost'],
            'enrollment_cost' => $data['enrollment_cost'] ?? null,
        ]);

        $course->instructors()->sync($data['instructors'] ?? []);
        $this->syncScheduleOrDates($course, $data);

        return redirect()->route('courses.show', $course)->with('status', 'Corso creato.');
    }

    public function show(Course $course): View
    {
        $this->authorize('view', $course);

        $course->load([
            'discipline',
            'room',
            'instructors',
            'schedules',
            'enrollments' => fn ($query) => $query->with('user')->orderBy('enrollment_date', 'desc'),
        ]);

        $user = Auth::user();
        $canManage = $user->hasRole('admin');
        $isAssignedInstructor = $user->hasRole('instructor') && $course->instructors->contains($user->id);
        $isStaffForCourse = $canManage || $isAssignedInstructor;
        $compactRoster = $course->isEvento() || ! $isStaffForCourse;

        $availableMembers = $canManage
            ? User::role('member')->whereNotIn('id', $course->enrollments->pluck('user_id'))->orderBy('name')->get()
            : collect();

        $fillPercent = $course->room->capacity > 0
            ? min(100, round($course->enrollments->count() / $course->room->capacity * 100))
            : 0;

        // An "evento" has its own lightweight layout (see courses/show.blade.php)
        // built around its few specific dates rather than the full lessons
        // table/weekly trend chart a periodic "corso" uses.
        if ($course->isEvento()) {
            $course->load(['lessons' => fn ($q) => $q->orderBy('date')->with('attendances')]);
            $attendanceTrend = [];
        } else {
            $attendanceTrend = $this->weeklyAttendanceTrend($course);
        }

        return view('courses.show', compact('course', 'canManage', 'compactRoster', 'availableMembers', 'fillPercent', 'attendanceTrend'));
    }

    /**
     * Weekly attendance rate for this course over the last 10 weeks, for
     * the course-page chart — same shape/logic as the Palestra dashboard's
     * chart, just scoped to a single course.
     *
     * @return array<string, int>
     */
    private function weeklyAttendanceTrend(Course $course): array
    {
        $trend = [];

        for ($i = 9; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();

            // A lesson before the member's enrollment_date is one they
            // couldn't have attended, so it must not count as an absence.
            $weekSet = Attendance::query()
                ->join('enrollments', 'attendances.enrollment_id', '=', 'enrollments.id')
                ->join('lessons', 'attendances.lesson_id', '=', 'lessons.id')
                ->where('lessons.course_id', $course->id)
                ->whereBetween('lessons.date', [$start, $end])
                ->whereColumn('lessons.date', '>=', 'enrollments.enrollment_date')
                ->select('attendances.*')
                ->get();

            $trend[$start->translatedFormat('d M')] = $weekSet->isEmpty()
                ? 0
                : (int) round($weekSet->where('present', true)->count() / $weekSet->count() * 100);
        }

        return $trend;
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', Course::class);

        $disciplines = Discipline::orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $instructors = User::role('instructor')->orderBy('name')->get();
        $course->load(['instructors', 'schedules']);

        return view('courses.edit', compact('course', 'disciplines', 'rooms', 'instructors'));
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', Course::class);

        $data = $this->validateCourse($request);

        $discipline = $this->resolveDiscipline($request);
        $room = $this->resolveRoom($request);

        $course->update([
            'discipline_id' => $discipline->id,
            'room_id' => $room->id,
            'type' => $data['type'],
            'year' => $data['year'],
            'description' => $data['description'] ?? null,
            'annual_cost' => $data['annual_cost'],
            'monthly_cost' => $data['monthly_cost'],
            'enrollment_cost' => $data['enrollment_cost'] ?? null,
        ]);

        $course->instructors()->sync($data['instructors'] ?? []);
        $this->syncScheduleOrDates($course, $data);

        return redirect()->route('courses.show', $course)->with('status', 'Corso aggiornato.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('update', Course::class);

        $course->delete();

        return redirect()->route('courses.index')->with('status', 'Corso eliminato.');
    }

    /**
     * @return array{room_id: ?int, type: string, year: string, description: ?string, annual_cost: float, monthly_cost: float, enrollment_cost: ?float, instructors: array<int>, schedules: array<int, array{weekday: int, start_time: string, end_time: string}>, event_dates: array<int, string>}
     */
    private function validateCourse(Request $request): array
    {
        return $request->validate([
            'discipline_id' => 'nullable|exists:disciplines,id|required_without:new_discipline',
            'new_discipline' => 'nullable|string|max:255|required_without:discipline_id',
            'room_id' => 'nullable|exists:rooms,id|required_without:new_room_name',
            'new_room_name' => 'nullable|string|max:255|required_without:room_id',
            'new_room_capacity' => 'nullable|integer|min:1|required_with:new_room_name',
            'type' => 'required|in:corso,evento',
            'year' => 'required|string|max:100',
            'description' => 'nullable|string',
            'annual_cost' => 'required|numeric|min:0',
            'monthly_cost' => 'required|numeric|min:0',
            'enrollment_cost' => 'nullable|numeric|min:0',
            'instructors' => 'nullable|array',
            'instructors.*' => 'exists:users,id',
            'schedules' => 'nullable|array',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
            'event_dates' => 'nullable|array',
            'event_dates.*' => 'date',
        ]);
    }

    private function resolveDiscipline(Request $request): Discipline
    {
        if ($request->filled('new_discipline')) {
            return Discipline::firstOrCreate(['name' => trim($request->string('new_discipline'))]);
        }

        return Discipline::findOrFail($request->input('discipline_id'));
    }

    private function resolveRoom(Request $request): Room
    {
        if ($request->filled('new_room_name')) {
            return Room::create([
                'name' => trim($request->string('new_room_name')),
                'capacity' => $request->input('new_room_capacity'),
            ]);
        }

        return Room::findOrFail($request->input('room_id'));
    }

    /**
     * A "corso" keeps its recurring weekly schedule (used by "genera
     * lezioni"); an "evento" has no recurring schedule at all — instead,
     * the specific dates picked at creation become lessons directly.
     * Switching a course's type clears whichever of the two no longer
     * applies.
     *
     * @param  array{schedules?: array<int, array{weekday: int, start_time: string, end_time: string}>, event_dates?: array<int, string>}  $data
     */
    private function syncScheduleOrDates(Course $course, array $data): void
    {
        $course->schedules()->delete();

        if ($data['type'] === 'corso') {
            foreach ($data['schedules'] ?? [] as $schedule) {
                $course->schedules()->create($schedule);
            }

            return;
        }

        foreach ($data['event_dates'] ?? [] as $date) {
            Lesson::firstOrCreate(['course_id' => $course->id, 'date' => $date]);
        }
    }
}
