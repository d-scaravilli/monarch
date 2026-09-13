<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateUserAccount;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => ['admin', 'instructor', 'member'],
            'modules' => Module::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateUserAccount $creator): RedirectResponse
    {
        $data = $this->validateUser($request);

        [$user, $password] = $creator->handle($data['name'], $data['email'], $data['roles'] ?? [], $data['modules'] ?? []);

        return redirect()->route('admin.users.edit', $user)
            ->with('status', "Utente creato. Password iniziale: {$password}");
    }

    public function edit(User $user): View
    {
        $user->load('modules');

        return view('admin.users.edit', [
            'editUser' => $user,
            'roles' => ['admin', 'instructor', 'member'],
            'modules' => Module::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user->id);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles($data['roles'] ?? []);
        $user->modules()->sync($data['modules'] ?? []);

        // Spatie's permission cache lives in the app's default cache store
        // (database, here) — shared across every process, not just this
        // request. Without forgetting it, a role change can keep granting
        // (or keep denying) access based on stale data until the cache
        // entry expires on its own.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.users.edit', $user)->with('status', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, 'Non puoi eliminare il tuo stesso account da qui.');

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Utente eliminato.');
    }

    /**
     * Disabling both blocks future logins (checked at auth time) and
     * kills any session the user already has open (database sessions,
     * so this is a plain delete keyed by user_id).
     */
    public function disable(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403, 'Non puoi disabilitare il tuo stesso account.');

        $user->update(['disabled_at' => now()]);
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('status', 'Utente disabilitato.');
    }

    public function enable(User $user): RedirectResponse
    {
        $user->update(['disabled_at' => null]);

        return back()->with('status', 'Utente riabilitato.');
    }

    public function setPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Password aggiornata.');
    }

    /**
     * @return array{name: string, email: string, roles: array<int, string>, modules: array<int, int>}
     */
    private function validateUser(Request $request, ?int $ignoreUserId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.($ignoreUserId ?? 'NULL').',id',
            'roles' => 'nullable|array',
            'roles.*' => 'in:admin,instructor,member',
            'modules' => 'nullable|array',
            'modules.*' => 'exists:modules,id',
        ]);
    }
}
