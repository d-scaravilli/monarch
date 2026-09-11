<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Discipline;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Active courses: all of them for the admin, only the assigned
     * ones for an instructor.
     */
    public function index(): View
    {
        $user = Auth::user();

        $courses = Course::query()
            ->with(['discipline', 'room'])
            ->withCount('enrollments')
            ->when(! $user->hasRole('admin'), function ($query) use ($user) {
                $query->whereHas('instructors', fn ($q) => $q->whereKey($user->id));
            })
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
            'year' => $data['year'],
            'description' => $data['description'] ?? null,
            'annual_cost' => $data['annual_cost'],
            'monthly_cost' => $data['monthly_cost'],
        ]);

        $course->instructors()->sync($data['instructors'] ?? []);
        $this->syncSchedules($course, $data['schedules'] ?? []);

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

        $canManage = Auth::user()->hasRole('admin');

        $availableMembers = $canManage
            ? User::role('member')->whereNotIn('id', $course->enrollments->pluck('user_id'))->orderBy('name')->get()
            : collect();

        return view('courses.show', compact('course', 'canManage', 'availableMembers'));
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
            'year' => $data['year'],
            'description' => $data['description'] ?? null,
            'annual_cost' => $data['annual_cost'],
            'monthly_cost' => $data['monthly_cost'],
        ]);

        $course->instructors()->sync($data['instructors'] ?? []);
        $this->syncSchedules($course, $data['schedules'] ?? []);

        return redirect()->route('courses.show', $course)->with('status', 'Corso aggiornato.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('update', Course::class);

        $course->delete();

        return redirect()->route('courses.index')->with('status', 'Corso eliminato.');
    }

    /**
     * @return array{room_id: ?int, year: string, description: ?string, annual_cost: float, monthly_cost: float, instructors: array<int>, schedules: array<int, array{weekday: int, start_time: string, end_time: string}>}
     */
    private function validateCourse(Request $request): array
    {
        return $request->validate([
            'discipline_id' => 'nullable|exists:disciplines,id|required_without:new_discipline',
            'new_discipline' => 'nullable|string|max:255|required_without:discipline_id',
            'room_id' => 'nullable|exists:rooms,id|required_without:new_room_name',
            'new_room_name' => 'nullable|string|max:255|required_without:room_id',
            'new_room_capacity' => 'nullable|integer|min:1|required_with:new_room_name',
            'year' => 'required|string|max:20',
            'description' => 'nullable|string',
            'annual_cost' => 'required|numeric|min:0',
            'monthly_cost' => 'required|numeric|min:0',
            'instructors' => 'nullable|array',
            'instructors.*' => 'exists:users,id',
            'schedules' => 'nullable|array',
            'schedules.*.weekday' => 'required|integer|between:0,6',
            'schedules.*.start_time' => 'required',
            'schedules.*.end_time' => 'required|after:schedules.*.start_time',
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
     * @param  array<int, array{weekday: int, start_time: string, end_time: string}>  $schedules
     */
    private function syncSchedules(Course $course, array $schedules): void
    {
        $course->schedules()->delete();

        foreach ($schedules as $schedule) {
            $course->schedules()->create($schedule);
        }
    }
}
