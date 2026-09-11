<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CourseEdition;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function edit(CourseEdition $courseEdition, Lesson $lesson): View
    {
        $this->authorize('manageAttendance', $courseEdition);

        $courseEdition->load(['enrollments.user']);

        $attendances = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->pluck('present', 'enrollment_id');

        return view('editions.attendance', compact('courseEdition', 'lesson', 'attendances'));
    }

    public function update(Request $request, CourseEdition $courseEdition, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manageAttendance', $courseEdition);

        $present = collect($request->input('present', []))->keys();

        foreach ($courseEdition->enrollments as $enrollment) {
            Attendance::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
                ['present' => $present->contains((string) $enrollment->id)],
            );
        }

        return redirect()
            ->route('editions.show', $courseEdition)
            ->with('status', 'Presenze registrate.');
    }
}
