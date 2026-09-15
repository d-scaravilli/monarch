<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProgressController extends Controller
{
    /**
     * One card per member (skipping anyone with no notes at all, so the
     * page stays quick to read rather than sprawling), each listing only
     * that member's own notes. Admin sees everyone; instructor is
     * hard-scoped to members enrolled in their own assigned courses —
     * the same unremovable base filter Team uses.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $user = Auth::user();
        $instructorCourseIds = $user->hasRole('instructor') ? $user->instructedCourses()->pluck('courses.id') : null;

        $members = User::role('member')
            ->whereHas('notes')
            ->with(['notes' => fn ($q) => $q->with(['author', 'lesson.course.discipline'])])
            ->when($instructorCourseIds !== null, fn ($q) => $q->whereHas(
                'enrollments',
                fn ($q2) => $q2->whereIn('course_id', $instructorCourseIds),
            ))
            ->orderBy('name')
            ->get();

        // Compact "who has goals" list at the top of the page — a
        // different cut of the same members (goal-driven rather than
        // note-driven), so it needs its own query rather than filtering
        // $members above.
        $membersWithGoals = User::role('member')
            ->whereHas('enrollments', fn ($q) => $this->enrollmentsWithGoals($q, $instructorCourseIds))
            ->with(['enrollments' => fn ($q) => $this->enrollmentsWithGoals($q, $instructorCourseIds)->with('course.discipline')])
            ->orderBy('name')
            ->get();

        return view('progress.index', compact('members', 'membersWithGoals'));
    }

    /**
     * Shared filter reused both as a whereHas() existence check and as
     * a with() eager-load constraint — the two pass different builder
     * types in (a plain query builder vs. the enrollments relation
     * itself), hence the union type.
     *
     * @param  ?Collection<int, int>  $instructorCourseIds
     */
    private function enrollmentsWithGoals(Builder|Relation $query, ?Collection $instructorCourseIds): Builder|Relation
    {
        return $query->whereHas('goals')
            ->when($instructorCourseIds !== null, fn ($q) => $q->whereIn('course_id', $instructorCourseIds));
    }

    /**
     * The full timeline for one member. Reachable by admin/instructor
     * under the same scope as index() (via UserPolicy::view, which
     * already checks the instructor's course overlap), and additionally
     * by the member themselves — for their own page only, linked from
     * "La mia area".
     */
    public function show(User $member): View
    {
        $viewer = Auth::user();
        abort_unless($viewer->id === $member->id || $viewer->can('view', $member), 403);

        $member->load([
            'notes' => fn ($q) => $q->with(['author', 'lesson.course.discipline']),
            'enrollments' => fn ($q) => $q->with(['course.discipline', 'goals.notes.author']),
        ]);

        return view('progress.show', compact('member'));
    }
}
