<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\GuideText;
use App\Models\Resina\StepTitle;
use App\Models\Resina\TechniqueGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The admin's editor for the painting-mode instructions: one text box
 * per field, one line per item. Only texts change: which steps a title
 * applies to (its pattern) and which technique a step uses stay as
 * they are.
 */
class TechniqueGuideController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('resina.technique-guides.edit', [
            'guides' => TechniqueGuide::orderBy('position')->get(),
            'titles' => StepTitle::orderBy('position')->get(),
            'texts' => GuideText::all()->keyBy('key'),
            'textLabels' => GuideText::LABELS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'guides' => 'required|array',
            'guides.*.name' => 'required|string|max:100',
            'guides.*.preparation' => 'nullable|string|max:5000',
            'guides.*.steps' => 'nullable|string|max:5000',
            'guides.*.wait_minutes' => 'nullable|integer|min:0|max:1440',
            'guides.*.result' => 'nullable|string|max:1000',
            'guides.*.mistakes' => 'nullable|string|max:5000',
            'titles' => 'nullable|array',
            'titles.*' => 'required|string|max:255',
            'texts' => 'nullable|array',
            'texts.*' => 'nullable|string|max:5000',
        ], [
            'guides.*.name.required' => 'Ogni tecnica ha bisogno di un nome.',
            'titles.*.required' => 'Ogni titolo semplice ha bisogno di un testo.',
        ]);

        DB::transaction(function () use ($data) {
            foreach (TechniqueGuide::all() as $guide) {
                $fields = $data['guides'][$guide->code] ?? null;
                if (! $fields) {
                    continue;
                }
                $guide->update([
                    'name' => $fields['name'],
                    'preparation' => self::lines($fields['preparation'] ?? ''),
                    'steps' => self::lines($fields['steps'] ?? ''),
                    'wait_minutes' => $fields['wait_minutes'] ?? null,
                    'result' => $fields['result'] ?? null,
                    'mistakes' => self::lines($fields['mistakes'] ?? ''),
                ]);
            }

            foreach (StepTitle::all() as $title) {
                if (isset($data['titles'][$title->id])) {
                    $title->update(['title' => $data['titles'][$title->id]]);
                }
            }

            foreach (GuideText::all() as $text) {
                if (array_key_exists($text->key, $data['texts'] ?? [])) {
                    $text->update(['lines' => self::lines($data['texts'][$text->key] ?? '')]);
                }
            }
        });

        return redirect()->route('resina.technique-guides.edit')->with('status', 'Istruzioni salvate.');
    }

    /**
     * One item per non-empty line.
     *
     * @return array<int, string>
     */
    private static function lines(string $text): array
    {
        return collect(preg_split('/\R/', $text))->map(fn (string $line) => trim($line))->filter()->values()->all();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
