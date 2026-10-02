<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\MemberNote;
use App\Models\Module;
use App\Models\User;
use App\Services\Resina\CatalogImporter as ResinaCatalogImporter;
use App\Services\Resina\ModuleDataReset as ResinaModuleDataReset;
use App\Services\Resina\TechniqueGuideImporter as ResinaTechniqueGuideImporter;
use App\Support\ModuleTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModuleSettingsController extends Controller
{
    /**
     * Per-module admin settings (name, icon/image, accent color, active
     * state, who has access). Amministrazione manages itself elsewhere.
     */
    public function edit(Request $request, Module $module): View
    {
        $this->authorizeModuleManagement($request, $module);

        $module->load('users');
        $availableUsers = User::whereNotIn('id', $module->users->pluck('id'))->orderBy('name')->get();

        return view('modules.settings', [
            'module' => $module,
            'availableUsers' => $availableUsers,
            'iconChoices' => ModuleTheme::iconChoices(),
            'colorChoices' => ModuleTheme::colorKeys(),
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'icon' => 'required|string|in:'.implode(',', ModuleTheme::iconChoices()),
            'color' => 'required|string|in:'.implode(',', ModuleTheme::colorKeys()),
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'member_cover_image' => 'nullable|image|max:2048',
            'remove_member_cover_image' => 'nullable|boolean',
            'member_cover_style' => 'nullable|string|in:color,image,transparent',
        ]);

        if ($request->hasFile('image')) {
            if ($module->image_path) {
                Storage::disk('public')->delete($module->image_path);
            }
            $data['image_path'] = $request->file('image')->store('modules', 'public');
        }

        if ($request->hasFile('member_cover_image')) {
            if ($module->member_cover_image_path) {
                Storage::disk('public')->delete($module->member_cover_image_path);
            }
            $data['member_cover_image_path'] = $request->file('member_cover_image')->store('modules', 'public');
        } elseif ($request->boolean('remove_member_cover_image') && $module->member_cover_image_path) {
            Storage::disk('public')->delete($module->member_cover_image_path);
            $data['member_cover_image_path'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['member_cover_style'] = $data['member_cover_style'] ?? 'color';
        unset($data['image'], $data['member_cover_image'], $data['remove_member_cover_image']);

        $module->update($data);

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Modulo aggiornato.');
    }

    public function grantAccess(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);

        $data = $request->validate(['user_id' => 'required|exists:users,id']);

        $module->users()->syncWithoutDetaching([$data['user_id']]);

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Accesso concesso.');
    }

    public function revokeAccess(Request $request, Module $module, User $user): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);

        $module->users()->detach($user->id);

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Accesso rimosso.');
    }

    /**
     * "Zona pericolosa": wipes the data a module generates, never user
     * accounts (Amministrazione's job) or the module's own settings
     * (name/color/image). What "data" means is up to each module.
     */
    public function resetData(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);
        abort_unless(in_array($module->slug, ['palestra', 'resina'], true), 404);

        $data = $request->validate([
            'confirm_name' => 'required|string',
        ]);

        abort_unless($data['confirm_name'] === $module->name, 422);

        match ($module->slug) {
            'palestra' => $this->resetPalestraData($module),
            'resina' => app(ResinaModuleDataReset::class)->reset(),
        };

        AuditLog::record('module.reset', "Dati del modulo \"{$module->name}\" azzerati da {$request->user()->name}.");

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Dati del modulo azzerati.');
    }

    /**
     * "3D - Resina" only: loads database/data/resina again over the
     * shared catalog. Overwrites every edit made to the imported rows;
     * personal data stays untouched.
     */
    public function reimportCatalog(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);
        abort_unless($module->slug === 'resina', 404);

        $data = $request->validate([
            'confirm_name' => 'required|string',
        ]);

        abort_unless($data['confirm_name'] === $module->name, 422);

        app(ResinaCatalogImporter::class)->import();
        app(ResinaTechniqueGuideImporter::class)->import();

        AuditLog::record('module.reimport', "Catalogo iniziale del modulo \"{$module->name}\" reimportato da {$request->user()->name}.");

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Catalogo iniziale reimportato.');
    }

    /**
     * Palestra: enrollments, courses/eventi (and, via cascade, their
     * schedules/lessons/attendances/payments), documents and notes for
     * its members. Rooms stay.
     */
    private function resetPalestraData(Module $module): void
    {
        // $module->users() is who was *granted access* to the module (an
        // admin decision, made from the "Accessi" tab) — not who actually
        // has data in it. A member enrolled the normal way, through
        // "Aggiungi iscritto" on a course, never ends up in that pivot
        // (EnrollmentController::store only assigns the "member" role),
        // so scoping the wipe to it alone silently skipped their
        // documents and notes. Union in everyone who's actually enrolled
        // or teaching, computed before the transaction deletes those rows.
        $memberIds = $module->users()->pluck('users.id')
            ->merge(Enrollment::pluck('user_id'))
            ->merge(DB::table('course_instructor')->pluck('user_id'))
            ->unique()
            ->values();

        DB::transaction(function () use ($memberIds) {
            Document::whereIn('user_id', $memberIds)->get()->each(function (Document $document) {
                if ($document->file_path) {
                    Storage::disk('public')->delete($document->file_path);
                }
                $document->delete();
            });

            MemberNote::whereIn('user_id', $memberIds)->delete();

            // Cascades at the DB level to schedules, lessons, enrollments,
            // attendances and payments — see their migrations.
            Course::withTrashed()->forceDelete();
        });
    }

    /**
     * "Zona pericolosa": wipes every stored notification (the rows
     * feeding the bell dropdown), for every user. The notifications
     * table has no module scoping column at all, so this is a genuine
     * global reset, not limited to Palestra data — messages, payments,
     * notes and every other piece of data are left untouched.
     */
    public function resetNotifications(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);
        abort_unless($module->slug === 'palestra', 404);

        DatabaseNotification::query()->delete();

        AuditLog::record('notifications.reset', "Tutte le notifiche azzerate da {$request->user()->name}.");

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Notifiche eliminate.');
    }

    private function authorizeModuleManagement(Request $request, Module $module): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_if($module->slug === 'amministrazione', 404);
    }
}
