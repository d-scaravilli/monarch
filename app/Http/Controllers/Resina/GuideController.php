<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Guide;
use App\Models\Resina\GuideStep;
use App\Models\Resina\Paint;
use App\Models\Resina\Project;
use App\Services\Resina\ClientPayload;
use App\Support\ResinaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A project's step-by-step procedures ("Passo passo"), admin only. Steps
 * keep the order they're given; a step may have a mix in drops or none.
 */
class GuideController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function create(Request $request, Project $project): View
    {
        $this->authorizeAdmin($request);

        return $this->form($request, $project, new Guide(['position' => ($project->guides()->max('position') ?? 0) + 1]));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateGuide($request);

        DB::transaction(function () use ($project, $data) {
            $guide = $project->guides()->create([
                ...$this->attributes($data),
                'slug' => ResinaSlug::unique($project->guides(), $data['title'], 'guida'),
            ]);
            $this->replaceSteps($guide, $data['steps']);
        });

        return redirect()->route('resina.projects.show', [$project, 'passo'])->with('status', 'Guida aggiunta.');
    }

    public function edit(Request $request, Project $project, Guide $guide): View
    {
        $this->authorizeAdmin($request);

        return $this->form($request, $project, $guide->load('steps.paints'));
    }

    public function update(Request $request, Project $project, Guide $guide): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateGuide($request);

        DB::transaction(function () use ($guide, $data) {
            $guide->update($this->attributes($data));
            $this->replaceSteps($guide, $data['steps']);
        });

        return redirect()->to(route('resina.projects.show', [$project, 'passo']).'#g-'.$guide->slug)->with('status', 'Guida aggiornata.');
    }

    /**
     * Armor types that pointed to it simply lose the link.
     */
    public function destroy(Request $request, Project $project, Guide $guide): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $guide->delete();

        return redirect()->route('resina.projects.show', $project->guides()->exists() ? [$project, 'passo'] : [$project])
            ->with('status', "«{$guide->title}» eliminata.");
    }

    private function form(Request $request, Project $project, Guide $guide): View
    {
        $steps = $guide->exists
            ? $guide->steps->map(fn (GuideStep $step) => [
                'title' => $step->title,
                'description' => $step->description,
                'paints' => $step->paints->map(fn (Paint $paint) => ['paint_id' => $paint->id, 'drops' => $paint->pivot->drops])->values()->all(),
            ])->all()
            : [];

        return view('resina.guides.form', [
            'project' => $project,
            'guide' => $guide,
            'payload' => [
                'paints' => $this->payload->paints($request->user()),
                'steps' => old('steps', $steps),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGuide(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'intro' => 'nullable|string|max:2000',
            'position' => 'required|integer|min:1|max:999',
            'steps' => 'required|array|min:1|max:40',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string|max:1000',
            'steps.*.paints' => 'nullable|array|max:6',
            'steps.*.paints.*.paint_id' => 'required|exists:resin_paints,id',
            'steps.*.paints.*.drops' => 'required|integer|min:1|max:40',
        ], [
            'steps.required' => 'Aggiungi almeno un passaggio.',
            'steps.*.title.required' => 'Ogni passaggio ha bisogno di un titolo.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'intro' => $data['intro'] ?? null,
            'position' => $data['position'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function replaceSteps(Guide $guide, array $steps): void
    {
        $guide->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $model = $guide->steps()->create([
                'position' => $index + 1,
                'title' => $step['title'],
                'description' => $step['description'] ?? null,
            ]);

            $mix = [];
            foreach ($step['paints'] ?? [] as $paint) {
                $mix[$paint['paint_id']] = ['drops' => ($mix[$paint['paint_id']]['drops'] ?? 0) + (int) $paint['drops']];
            }
            $model->paints()->attach($mix);
        }
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
