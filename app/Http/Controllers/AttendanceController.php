<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function edit(Course $course, Lesson $lesson): View
    {
        $this->authorize('manageAttendance', $course);

        $course->load(['enrollments.user']);

        $attendances = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->pluck('present', 'enrollment_id');

        return view('courses.attendance', compact('course', 'lesson', 'attendances'));
    }

    /**
     * Toggle one enrollment's presence for a lesson. Saves immediately —
     * no separate "save" step, called via fetch from the attendance list.
     */
    public function toggle(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $this->authorize('manageAttendance', $course);

        $data = $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'present' => 'required|boolean',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['enrollment_id' => $data['enrollment_id'], 'lesson_id' => $lesson->id],
            ['present' => $data['present']],
        );

        return response()->json(['present' => $attendance->present]);
    }

    public function markAllPresent(Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manageAttendance', $course);

        foreach ($course->enrollments as $enrollment) {
            Attendance::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
                ['present' => true],
            );
        }

        return redirect()
            ->route('courses.lessons.attendance.edit', [$course, $lesson])
            ->with('status', 'Tutti segnati presenti.');
    }
}
