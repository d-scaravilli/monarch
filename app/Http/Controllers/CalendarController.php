<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Room;
use App\Support\VisibleCourses;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /**
     * Month or week grid of all lessons, filterable by course/room.
     * Lessons only carry a date (no time-of-day), so this is a
     * day-cell calendar rather than an hour-by-hour time grid. Scoped to
     * whatever the viewer is allowed to see: everything for admin, only
     * assigned courses for an instructor, only enrolled courses (plus
     * every evento) for a member.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $courseIds = VisibleCourses::idsFor($user);

        $view = $request->input('view') === 'week' ? 'week' : 'month';
        $anchor = $request->filled('date') ? Carbon::parse($request->string('date')) : now();

        if ($view === 'week') {
            $gridStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
            $prevAnchor = $anchor->copy()->subWeek();
            $nextAnchor = $anchor->copy()->addWeek();
        } else {
            $gridStart = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $prevAnchor = $anchor->copy()->subMonthNoOverflow();
            $nextAnchor = $anchor->copy()->addMonthNoOverflow();
        }

        $lessons = Lesson::query()
            ->with(['course.discipline', 'course.room'])
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->when($request->filled('room_id'), fn ($q) => $q->whereHas('course', fn ($q2) => $q2->where('room_id', $request->input('room_id'))))
            ->orderBy('date')
            ->get()
            ->groupBy(fn (Lesson $lesson) => $lesson->date->toDateString());

        $days = collect();
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return view('palestra.calendar', [
            'view' => $view,
            'anchor' => $anchor,
            'prevAnchor' => $prevAnchor,
            'nextAnchor' => $nextAnchor,
            'days' => $days,
            'lessonsByDate' => $lessons,
            'courses' => Course::with('discipline')
                ->when($courseIds, fn ($q) => $q->whereIn('id', $courseIds))
                ->orderBy('year')
                ->get(),
            'rooms' => Room::orderBy('name')->get(),
        ]);
    }
}
