<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $password = Str::password(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($data['roles'] ?? []);
        $user->modules()->sync($data['modules'] ?? []);

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

        return redirect()->route('admin.users.edit', $user)->with('status', 'Utente aggiornato.');
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
