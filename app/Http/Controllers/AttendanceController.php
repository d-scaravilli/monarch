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
    /**
     * Opening a lesson: admin/assigned-instructor get the full management
     * view (toggle presence, add notes, edit description); anyone else
     * allowed to view the course (enrolled member, or anyone for an
     * evento) gets a read-only view with the same info minus other
     * members' notes.
     */
    public function edit(Course $course, Lesson $lesson): View
    {
        $this->authorize('view', $course);

        $canManage = auth()->user()->can('manageAttendance', $course);

        $course->load(['enrollments.user.notes.author']);

        // Someone who joined after this lesson took place couldn't have
        // attended it, so they don't belong on its roster at all.
        $enrollments = $course->enrollments->filter(fn ($e) => $e->enrollment_date->lte($lesson->date))->values();

        $attendances = Attendance::query()
            ->where('lesson_id', $lesson->id)
            ->pluck('present', 'enrollment_id');

        $totalEnrolled = $enrollments->count();
        $presentCount = $enrollments->filter(fn ($e) => (bool) ($attendances[$e->id] ?? false))->count();
        $attendanceRate = $totalEnrolled > 0 ? (int) round($presentCount / $totalEnrolled * 100) : 0;

        $absentees = $enrollments
            ->reject(fn ($e) => (bool) ($attendances[$e->id] ?? false))
            ->map(fn ($e) => $e->user);

        $presentees = $enrollments
            ->filter(fn ($e) => (bool) ($attendances[$e->id] ?? false))
            ->map(fn ($e) => $e->user);

        return view('courses.attendance', compact('course', 'lesson', 'canManage', 'enrollments', 'attendances', 'presentCount', 'totalEnrolled', 'attendanceRate', 'absentees', 'presentees'));
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

        $eligible = $course->enrollments->filter(fn ($e) => $e->enrollment_date->lte($lesson->date));

        foreach ($eligible as $enrollment) {
            Attendance::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id],
                ['present' => true],
            );
        }

        return redirect()
            ->route('courses.lessons.attendance.edit', [$course, $lesson])
            ->with('status', 'Tutti segnati presenti.');
    }

    /**
     * What happened during the lesson, editable by the same people who
     * can manage its attendance (admin, or an instructor assigned to
     * this course).
     */
    public function updateDescription(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $this->authorize('manageAttendance', $course);

        $data = $request->validate([
            'description' => 'nullable|string',
        ]);

        $lesson->update($data);

        return response()->json(['description' => $lesson->description]);
    }
}
