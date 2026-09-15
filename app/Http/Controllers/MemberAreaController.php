<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberAreaController extends Controller
{
    /**
     * "La mia area": the same card/anagrafica/documents/payments/
     * attendance structure as the admin-facing scheda iscritto
     * (members/show.blade.php), scoped to the logged-in member's own
     * data. Kept as a separate view rather than reusing members/show so
     * the admin page's edit/delete abilities are never at risk of
     * leaking into the member-facing one.
     */
    public function index(): View
    {
        $member = Auth::user();

        $member->load([
            'memberProfile',
            'documents',
            'enrollments' => fn ($q) => $q->with(['course.discipline', 'payments', 'attendances.lesson', 'goals.notes.author'])->orderByDesc('enrollment_date'),
            'notes' => fn ($q) => $q->with(['author', 'lesson.course.discipline']),
        ]);

        $recentInjury = $member->notes
            ->where('type', 'infortunio')
            ->first(fn ($note) => $note->created_at->diffInDays(now()) <= 30);

        $activeEnrollments = $member->enrollments->where('status', 'active');
        $totalDue = round($activeEnrollments->sum(fn ($e) => $e->dueAmount()), 2);
        $totalPaidForDue = round($activeEnrollments->sum(fn ($e) => $e->paidAmount()), 2);
        $paymentCompletionPercent = $totalDue > 0
            ? (int) min(100, round($totalPaidForDue / $totalDue * 100))
            : ($totalPaidForDue > 0 ? 100 : 0);

        return view('member.area', [
            'member' => $member,
            'recentInjury' => $recentInjury,
            'paymentCompletionPercent' => $paymentCompletionPercent,
        ]);
    }

    /**
     * The only field of their own profile a member may edit directly —
     * everything else (fiscal code, equipment, notes) stays admin-only.
     */
    public function updatePhone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\s().-]+$/'],
        ]);

        $user = Auth::user();
        $user->memberProfile()->updateOrCreate(['user_id' => $user->id], ['phone' => $data['phone'] ?? null]);

        return back()->with('status', 'Contatto aggiornato.');
    }
}
