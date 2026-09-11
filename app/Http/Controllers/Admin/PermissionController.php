<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    private const ROLES = ['admin', 'instructor', 'member'];

    private const ROLE_DESCRIPTIONS = [
        'admin' => 'Accesso completo a tutti i moduli e a tutte le funzioni, inclusa la gestione di utenti e permessi.',
        'instructor' => 'Vede e gestisce presenze/descrizione solo dei corsi a cui è assegnato come istruttore; non vede i pagamenti.',
        'member' => 'Vede solo i propri dati: le proprie iscrizioni, presenze, pagamenti e certificato medico.',
    ];

    /**
     * A compact matrix for bulk role toggling. Roles are the only form
     * of permission this app currently defines (no granular Spatie
     * Permission records exist yet beyond the three roles).
     */
    public function index(): View
    {
        $users = User::with('roles')->orderBy('name')->get();

        return view('admin.permissions.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'roleDescriptions' => self::ROLE_DESCRIPTIONS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,instructor,member',
            'enabled' => 'required|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);

        if ($data['enabled']) {
            $user->assignRole($data['role']);
        } else {
            $user->removeRole($data['role']);
        }

        return back()->with('status', 'Permessi aggiornati.');
    }
}
