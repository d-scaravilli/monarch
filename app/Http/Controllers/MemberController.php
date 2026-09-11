<?php

namespace App\Http\Controllers;

use App\Actions\CreateUserAccount;
use App\Models\Course;
use App\Models\MemberProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $members = User::role('member')
            ->with('memberProfile')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->whereHas('enrollments', fn ($q2) => $q2->where('status', 'active')))
            ->when($request->input('status') === 'inactive', fn ($q) => $q->whereDoesntHave('enrollments', fn ($q2) => $q2->where('status', 'active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('members.index', compact('members'));
    }

    /**
     * Quick card view of members, filterable by course/year — a faster
     * visual complement to the searchable index above, not a replacement.
     * Defaults to the most recently *created* course's year (not the
     * calendar year), so a member from a past-year course only shows
     * up once you explicitly pick that year or "Tutti".
     */
    public function team(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $defaultYear = Course::query()->latest('created_at')->value('year');
        $year = $request->has('year') ? $request->input('year') : $defaultYear;

        $members = User::role('member')
            ->with(['memberProfile', 'enrollments.course.discipline'])
            ->when($request->filled('course_id'), fn ($q) => $q->whereHas(
                'enrollments',
                fn ($q2) => $q2->where('course_id', $request->input('course_id')),
            ))
            ->when($year, fn ($q) => $q->whereHas(
                'enrollments.course',
                fn ($q2) => $q2->where('year', $year),
            ))
            ->orderBy('name')
            ->get();

        return view('members.team', [
            'members' => $members,
            'selectedYear' => $year,
            'courses' => Course::with('discipline')->orderBy('year')->get(),
            'years' => Course::query()->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $linkableUsers = User::whereDoesntHave('memberProfile')->orderBy('name')->get();

        return view('members.create', compact('linkableUsers'));
    }

    public function store(Request $request, CreateUserAccount $creator): RedirectResponse
    {
        $this->authorize('create', User::class);

        $palestra = Module::where('slug', 'palestra')->first();

        if ($request->input('mode') === 'link') {
            return $this->storeLinkedMember($request, $palestra);
        }

        $data = $this->validateMember($request);

        [$member, $password] = $creator->handle(
            $data['name'],
            $data['email'],
            ['member'],
            $palestra ? [$palestra->id] : [],
        );

        MemberProfile::create([
            'user_id' => $member->id,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('members.show', $member)
            ->with('status', "Iscritto creato. Password iniziale: {$password}");
    }

    /**
     * "Collega utente esistente": attach the member role/profile to an
     * account that already exists (e.g. an instructor enrolling as a
     * student too), instead of creating a brand new login.
     */
    private function storeLinkedMember(Request $request, ?Module $palestra): RedirectResponse
    {
        $data = $request->validate([
            'existing_user_id' => 'required|exists:users,id|unique:member_profiles,user_id',
            'fiscal_code' => 'nullable|string|max:32',
            'emergency_contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $member = User::findOrFail($data['existing_user_id']);
        $member->assignRole('member');

        if ($palestra) {
            $member->modules()->syncWithoutDetaching([$palestra->id]);
        }

        MemberProfile::create([
            'user_id' => $member->id,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('members.show', $member)
            ->with('status', 'Utente collegato come iscritto.');
    }

    public function show(User $member): View
    {
        $this->authorize('view', $member);

        $member->load([
            'memberProfile',
            'medicalCertificates' => fn ($q) => $q->orderByDesc('expiry_date'),
            'enrollments' => fn ($q) => $q->with(['course.discipline', 'payments', 'attendances.lesson'])->orderByDesc('enrollment_date'),
            'notes' => fn ($q) => $q->with(['author', 'lesson.course.discipline']),
        ]);

        $recentInjury = $member->notes
            ->where('type', 'infortunio')
            ->first(fn ($note) => $note->created_at->diffInDays(now()) <= 30);

        return view('members.show', [
            'member' => $member,
            'latestCertificate' => $member->medicalCertificates->first(),
            'recentInjury' => $recentInjury,
        ]);
    }

    public function edit(User $member): View
    {
        $this->authorize('update', $member);

        $member->load('memberProfile');

        return view('members.edit', compact('member'));
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $data = $this->validateMember($request, $member->id);

        $member->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $member->memberProfile()->updateOrCreate(['user_id' => $member->id], [
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('members.show', $member)->with('status', 'Anagrafica aggiornata.');
    }

    public function destroy(User $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return redirect()->route('members.index')->with('status', 'Iscritto eliminato.');
    }

    private function validateMember(Request $request, ?int $ignoreUserId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.($ignoreUserId ?? 'NULL').',id',
            'fiscal_code' => 'nullable|string|max:32',
            'emergency_contact' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
    }
}
