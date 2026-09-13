<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class EnrollmentController extends Controller
{
    /**
     * Enroll an existing user into a course — they don't need to already
     * carry the "member" role for this (an instructor can be enrolled in
     * a course they don't teach), but they do end up with it afterwards:
     * every place that lists "gli iscritti" (Team, dashboard stats) keys
     * off that role, not off which other roles someone also has.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'user_id' => 'required|exists:users,id|unique:enrollments,user_id,NULL,id,course_id,'.$course->id,
            'discount' => 'nullable|numeric|min:0',
            'billing_frequency' => 'nullable|in:annual,monthly',
        ]);

        $course->enrollments()->create([
            'user_id' => $data['user_id'],
            'enrollment_date' => now(),
            'discount' => $data['discount'] ?? 0,
            'status' => 'active',
            'billing_frequency' => $data['billing_frequency'] ?? 'annual',
        ]);

        $user = User::findOrFail($data['user_id']);
        if (! $user->hasRole('member')) {
            $user->assignRole('member');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

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
