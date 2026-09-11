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
