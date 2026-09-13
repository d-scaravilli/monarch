<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Document;
use App\Models\MemberNote;
use App\Models\Module;
use App\Models\User;
use App\Support\ModuleTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * "Zona pericolosa": wipes every piece of data the Palestra module
     * generates — enrollments, courses/eventi (and, via cascade, their
     * schedules/lessons/attendances/payments), documents and notes for
     * its members. Never touches user accounts (Amministrazione's job),
     * rooms, or the module's own settings (name/color/image).
     */
    public function resetData(Request $request, Module $module): RedirectResponse
    {
        $this->authorizeModuleManagement($request, $module);
        abort_unless($module->slug === 'palestra', 404);

        $data = $request->validate([
            'confirm_name' => 'required|string',
        ]);

        abort_unless($data['confirm_name'] === $module->name, 422);

        $memberIds = $module->users()->pluck('users.id');

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

        AuditLog::record('module.reset', "Dati del modulo \"{$module->name}\" azzerati da {$request->user()->name}.");

        return redirect()->route('modules.settings.edit', $module)->with('status', 'Dati del modulo azzerati.');
    }

    private function authorizeModuleManagement(Request $request, Module $module): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_if($module->slug === 'amministrazione', 404);
    }
}
