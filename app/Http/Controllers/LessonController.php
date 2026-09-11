<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LessonController extends Controller
{
    /**
     * Filterable lesson table. Defaults to the current month unless a
     * date range is given or the "all" flag removes the filter.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $showAll = $request->boolean('all');
        $from = $request->date('from') ?? ($showAll ? null : now()->startOfMonth());
        $to = $request->date('to') ?? ($showAll ? null : now()->endOfMonth());

        $lessons = Lesson::query()
            ->with(['course.discipline', 'course.room', 'attendances'])
            ->when(! $user->hasRole('admin'), fn ($q) => $q->whereHas(
                'course.instructors',
                fn ($q2) => $q2->whereKey($user->id),
            ))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('room_id'), fn ($q) => $q->whereHas(
                'course',
                fn ($q2) => $q2->where('room_id', $request->input('room_id')),
            ))
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->get();

        $courses = Course::with('discipline')
            ->when(! $user->hasRole('admin'), fn ($q) => $q->whereHas('instructors', fn ($q2) => $q2->whereKey($user->id)))
            ->get();
        $rooms = Room::orderBy('name')->get();

        return view('lessons.index', [
            'lessons' => $lessons,
            'courses' => $courses,
            'rooms' => $rooms,
            'showAll' => $showAll,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Course::class);

        $courses = Course::with('discipline')->orderBy('year')->get();
        $selectedCourseId = $request->integer('course_id') ?: null;

        return view('lessons.create', compact('courses', 'selectedCourseId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $course = Course::findOrFail($data['course_id']);
        $this->authorize('update', $course);

        Lesson::create($data);

        return redirect()->route('lessons.index', ['course_id' => $data['course_id']])->with('status', 'Lezione aggiunta.');
    }

    public function generateForm(): View
    {
        $this->authorize('create', Course::class);

        $courses = Course::with('discipline')->orderBy('year')->get();

        return view('lessons.generate', compact('courses'));
    }

    /**
     * Bulk-create lessons on a given weekday between two dates.
     */
    public function generateStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'weekday' => 'required|integer|between:0,6',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:255',
        ]);

        $course = Course::findOrFail($data['course_id']);
        $this->authorize('update', $course);

        $date = Carbon::parse($data['start_date']);
        while ($date->dayOfWeek !== (int) $data['weekday']) {
            $date->addDay();
        }

        $end = Carbon::parse($data['end_date']);
        $created = 0;

        while ($date->lte($end)) {
            Lesson::create([
                'course_id' => $course->id,
                'date' => $date->toDateString(),
                'note' => $data['note'] ?? null,
            ]);
            $date = $date->copy()->addWeek();
            $created++;
        }

        return redirect()
            ->route('lessons.index', ['course_id' => $course->id, 'all' => 1])
            ->with('status', "{$created} lezioni generate.");
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson->course);

        $lesson->delete();

        return redirect()->route('lessons.index')->with('status', 'Lezione eliminata.');
    }
}
