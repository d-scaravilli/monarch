<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index');
    }

    /**
     * Delete one notification — distinct from "Elimina tutte le
     * notifiche" in Gestisci modulo (Fase 37), which wipes everything at
     * once for a full reset. This is the row-level counterpart.
     */
    public function destroy(DatabaseNotification $notification): RedirectResponse
    {
        $notification->delete();

        return back()->with('status', 'Notifica eliminata.');
    }
}
