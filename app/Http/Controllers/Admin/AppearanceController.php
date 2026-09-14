<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\AppIconGenerator;
use App\Support\ModuleTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('admin.appearance.edit', [
            'setting' => AppSetting::current(),
            'colorChoices' => ModuleTheme::colorKeys(),
            'gdAvailable' => AppIconGenerator::isAvailable(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless(AppIconGenerator::isAvailable(), 422, 'L\'estensione GD di PHP non è attiva su questo server.');

        $data = $request->validate([
            'icon_mode' => 'required|in:letter,image',
            'icon_letter' => 'nullable|string|max:2',
            'icon_color' => 'required|string|in:'.implode(',', ModuleTheme::colorKeys()),
            'icon_image' => 'nullable|image|max:2048',
        ]);

        $setting = AppSetting::current();

        if ($data['icon_mode'] === 'image') {
            if ($request->hasFile('icon_image')) {
                if ($setting->icon_image_path) {
                    Storage::disk('public')->delete($setting->icon_image_path);
                }
                $data['icon_image_path'] = $request->file('icon_image')->store('app-icon', 'public');
            } else {
                abort_unless($setting->icon_image_path, 422, 'Carica un\'immagine.');
                $data['icon_image_path'] = $setting->icon_image_path;
            }

            AppIconGenerator::generateFromImage(Storage::disk('public')->path($data['icon_image_path']));
        } else {
            $data['icon_letter'] = $data['icon_letter'] !== '' && $data['icon_letter'] !== null
                ? mb_strtoupper(mb_substr($data['icon_letter'], 0, 1))
                : $setting->icon_letter;

            AppIconGenerator::generateFromLetter($data['icon_letter'], ModuleTheme::hex($data['icon_color']));
        }

        $data['icon_version'] = (string) now()->timestamp;
        unset($data['icon_image']);

        $setting->update($data);

        return redirect()->route('admin.appearance.edit')->with('status', 'Icona dell\'applicazione aggiornata.');
    }
}
