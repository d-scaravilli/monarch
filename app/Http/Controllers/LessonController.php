<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LessonController extends Controller
{
    /**
     * Filterable, sortable, paginated lesson table (see the
     * lessons-table Livewire component for the actual query/filters).
     */
    public function index(Request $request): View
    {
        return view('lessons.index', [
            'selectedCourseId' => $request->integer('course_id') ?: null,
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

    public function generateForm(Request $request): View
    {
        $this->authorize('create', Course::class);

        $courses = Course::with(['discipline', 'schedules'])->orderBy('year')->get();
        $selectedCourseId = $request->integer('course_id') ?: null;

        return view('lessons.generate', compact('courses', 'selectedCourseId'));
    }

    /**
     * Bulk-create lessons over a date range, on whichever weekdays the
     * course's recurring schedule (see course_schedules) is configured
     * for — a course meeting Monday and Thursday gets a lesson on every
     * matching Monday and Thursday in the range.
     */
    public function generateStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $course = Course::with('schedules')->findOrFail($data['course_id']);
        $this->authorize('update', $course);

        $weekdays = $course->schedules->pluck('weekday')->unique();

        if ($weekdays->isEmpty()) {
            return back()->withErrors([
                'course_id' => 'Questo corso non ha fasce orarie configurate. Aggiungile nella pagina del corso prima di generare le lezioni.',
            ])->withInput();
        }

        $date = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $created = 0;

        while ($date->lte($end)) {
            if ($weekdays->contains($date->dayOfWeek)) {
                Lesson::firstOrCreate([
                    'course_id' => $course->id,
                    'date' => $date->toDateString(),
                ]);
                $created++;
            }
            $date = $date->copy()->addDay();
        }

        return redirect()
            ->route('courses.show', $course)
            ->with('status', "{$created} lezioni generate.");
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $lesson->course);

        $lesson->delete();

        return redirect()->route('lessons.index')->with('status', 'Lezione eliminata.');
    }
}
