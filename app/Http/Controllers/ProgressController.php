<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        return view('progress.index', compact('members'));
    }
}
