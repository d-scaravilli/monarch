<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Enroll an existing member into a course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id|unique:enrollments,user_id,NULL,id,course_id,'.$course->id,
            'discount' => 'nullable|numeric|min:0',
        ]);

        $course->enrollments()->create([
            'user_id' => $data['user_id'],
            'enrollment_date' => now(),
            'discount' => $data['discount'] ?? 0,
            'status' => 'active',
        ]);

        return redirect()->route('courses.show', $course)->with('status', 'Iscrizione aggiunta.');
    }

    /**
     * Remove an enrollment from its course.
     */
    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('update', $enrollment->course);

        $course = $enrollment->course;
        $enrollment->delete();

        return redirect()->route('courses.show', $course)->with('status', 'Iscrizione rimossa.');
    }
}
