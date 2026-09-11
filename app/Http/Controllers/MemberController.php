<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\MemberProfile;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
     */
    public function team(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $members = User::role('member')
            ->with(['memberProfile', 'enrollments.course.discipline'])
            ->when($request->filled('course_id'), fn ($q) => $q->whereHas(
                'enrollments',
                fn ($q2) => $q2->where('course_id', $request->input('course_id')),
            ))
            ->when($request->filled('year'), fn ($q) => $q->whereHas(
                'enrollments.course',
                fn ($q2) => $q2->where('year', $request->input('year')),
            ))
            ->orderBy('name')
            ->get();

        return view('members.team', [
            'members' => $members,
            'courses' => Course::with('discipline')->orderBy('year')->get(),
            'years' => Course::query()->distinct()->orderByDesc('year')->pluck('year'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('members.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $this->validateMember($request);

        $password = Str::password(12);

        $member = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $member->assignRole('member');

        MemberProfile::create([
            'user_id' => $member->id,
            'fiscal_code' => $data['fiscal_code'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $palestra = Module::where('slug', 'palestra')->first();
        if ($palestra) {
            $member->modules()->syncWithoutDetaching([$palestra->id]);
        }

        return redirect()->route('members.show', $member)
            ->with('status', "Iscritto creato. Password iniziale: {$password}");
    }

    public function show(User $member): View
    {
        $this->authorize('view', $member);

        $member->load([
            'memberProfile',
            'medicalCertificates' => fn ($q) => $q->orderByDesc('expiry_date'),
            'enrollments' => fn ($q) => $q->with(['course.discipline', 'payments', 'attendances.lesson'])->orderByDesc('enrollment_date'),
        ]);

        return view('members.show', [
            'member' => $member,
            'latestCertificate' => $member->medicalCertificates->first(),
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
