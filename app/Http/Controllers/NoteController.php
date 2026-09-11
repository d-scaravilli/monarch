<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\MemberNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Add a progress/injury note for one of the course's enrolled members,
     * from the lesson's attendance page. Gated by the same ability as
     * attendance itself (admin, or the instructor assigned to the course):
     * notes are not shown to members for now.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorize('manageAttendance', $course);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(MemberNote::TYPES))],
            'description' => ['required', 'string'],
        ]);

        MemberNote::create([
            'user_id' => $data['user_id'],
            'lesson_id' => $lesson->id,
            'created_by' => $request->user()->id,
            'type' => $data['type'],
            'description' => $data['description'],
        ]);

        return back()->with('status', 'Nota aggiunta.');
    }
}
