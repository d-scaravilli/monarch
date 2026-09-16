<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Same scoping as notes: admin, or an instructor assigned to this
     * specific enrollment's course.
     */
    public function store(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorize('manageAttendance', $enrollment->course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'starting_point' => 'nullable|string',
            'target' => 'nullable|string',
        ]);

        $enrollment->goals()->create([
            ...$data,
            'status' => 'in_progress',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Obiettivo aggiunto.');
    }

    /**
     * Handles both editing a goal's details and marking it
     * completed/reopening it — completion is just another field, not a
     * separate action, so there's one endpoint instead of several.
     */
    public function update(Request $request, Goal $goal): RedirectResponse
    {
        $this->authorize('manageAttendance', $goal->enrollment->course);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'starting_point' => 'nullable|string',
            'target' => 'nullable|string',
            'status' => 'required|in:in_progress,completed',
            'completed_at' => 'nullable|date',
            'completion_description' => 'nullable|string',
            'completion_references' => 'nullable|string',
            'completion_note' => 'nullable|string',
        ]);

        if ($data['status'] === 'completed') {
            $data['completed_at'] ??= now()->toDateString();
        } else {
            $data['completed_at'] = null;
        }

        $goal->update($data);

        return back()->with('status', 'Obiettivo aggiornato.');
    }

    /**
     * Notes linked to this goal aren't deleted with it — the
     * goal_id column nullOnDelete()s, so they simply become free notes.
     */
    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('manageAttendance', $goal->enrollment->course);

        $goal->delete();

        return back()->with('status', 'Obiettivo eliminato.');
    }
}
