<?php

namespace App\Http\Controllers;

use App\Models\CourseEdition;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseEditionController extends Controller
{
    /**
     * Active editions: all of them for the admin, only the assigned
     * ones for an instructor.
     */
    public function index(): View
    {
        $user = Auth::user();

        $editions = CourseEdition::query()
            ->with(['course', 'room'])
            ->withCount('enrollments')
            ->when(! $user->hasRole('admin'), function ($query) use ($user) {
                $query->whereHas('instructors', fn ($q) => $q->whereKey($user->id));
            })
            ->orderBy('year')
            ->get();

        return view('editions.index', compact('editions'));
    }

    public function show(CourseEdition $courseEdition): View
    {
        $this->authorize('view', $courseEdition);

        $courseEdition->load([
            'course',
            'room',
            'instructors',
            'enrollments.user',
            'lessons' => fn ($query) => $query->orderBy('date'),
        ]);

        return view('editions.show', compact('courseEdition'));
    }
}
