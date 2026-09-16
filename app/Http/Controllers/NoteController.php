<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Goal;
use App\Models\Lesson;
use App\Models\MemberNote;
use App\Notifications\NoteAddedNotification;
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
            'goal_id' => ['nullable', 'integer', 'exists:goals,id'],
        ]);

        // Optional, never required: a note stays valid as a generic entry
        // with no goal attached. When one is given, it must actually be
        // an in-progress goal belonging to this same member+course —
        // never trust the posted id blindly.
        $goalId = null;
        if (! empty($data['goal_id'])) {
            $goal = Goal::with('enrollment')->find($data['goal_id']);
            if ($goal
                && $goal->status === 'in_progress'
                && $goal->enrollment->user_id === (int) $data['user_id']
                && $goal->enrollment->course_id === $course->id) {
                $goalId = $goal->id;
            }
        }

        $note = MemberNote::create([
            'user_id' => $data['user_id'],
            'lesson_id' => $lesson->id,
            'goal_id' => $goalId,
            'created_by' => $request->user()->id,
            'type' => $data['type'],
            'description' => $data['description'],
        ]);

        $note->user->notify(new NoteAddedNotification($note));

        return back()->with('status', 'Nota aggiunta.');
    }

    public function destroy(MemberNote $note): RedirectResponse
    {
        $this->authorize('manageAttendance', $note->lesson->course);

        $note->delete();

        return back()->with('status', 'Nota eliminata.');
    }
}
