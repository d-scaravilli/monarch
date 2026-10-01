<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Paint;
use App\Models\Resina\PathStep;
use App\Services\Resina\ClientPayload;
use App\Support\ResinaRows;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Percorso principiante": steps ticked per user, and the general order
 * for painting a figure. The admin edits the steps.
 */
class PathController extends Controller
{
    /**
     * Pages a step can point to.
     */
    public const LINK_ROUTES = [
        'resina.techniques.index' => 'Tecniche',
        'resina.brushes.index' => 'I miei pennelli',
        'resina.recipes.index' => 'Ricettario',
        'resina.paints.index' => 'I miei colori',
        'resina.projects.index' => 'Progetti',
        'resina.finder' => 'Trova colore',
        'resina.mixer' => 'Mixer',
        'resina.shop.index' => 'Da comprare',
        'resina.tutorials.index' => 'Tutorial',
    ];

    /**
     * The prototype's "Ordine per dipingere una figura": title,
     * description and a sample mix (paint code → drops).
     */
    private const PAINTING_ORDER = [
        ['Preparazione e primer', 'Vedi Tecniche.', []],
        ['Sottofondo chiaro', 'Bianco Osso o Grigio Muraglia diluiti su pelle e parti chiare.', ['72.034' => 1]],
        ['Pelle e occhi', 'Base, occhi, ombre, luci.', ['72.004' => 1]],
        ["Tuta o vestiti sotto l'armatura", 'Base, ombra, luce.', ['72.022' => 1]],
        ['Armatura', 'Oro, argento o metallo colorato.', ['77.123' => 1]],
        ['Capelli', 'Base, ombra, luce, drybrush sulle ciocche.', ['72.040' => 1]],
        ['Dettagli', 'Gemme, cinghie, cuoio.', ['72.010' => 1]],
        ['Basetta', 'Terra o rocce con drybrush.', ['72.049' => 1]],
        ['Vernice', 'Opaca su pelle e stoffa, lucida o satinata (o niente) sui metalli.', []],
    ];

    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $paintIds = Paint::pluck('id', 'code');
        $steps = PathStep::orderBy('position')->get();

        return view('resina.path.index', [
            'steps' => $steps,
            'payload' => [
                'stepIds' => $steps->pluck('id')->all(),
                'done' => $user->resinCompletedPathSteps()->pluck('resin_path_steps.id')->all(),
                'toggleUrl' => route('resina.path.toggle', '__ID__'),
                'paints' => $this->payload->paints($user),
                'brushes' => $this->payload->brushes($user),
                'urls' => $this->payload->urls(),
                'order' => collect(self::PAINTING_ORDER)->map(fn (array $step) => [
                    'title' => $step[0],
                    'description' => $step[1],
                    'mix' => (object) collect($step[2])->mapWithKeys(fn (int $drops, string $code) => ['p'.$paintIds[$code] => $drops])->all(),
                ])->all(),
            ],
        ]);
    }

    public function toggle(Request $request, PathStep $pathStep): JsonResponse
    {
        $data = $request->validate(['done' => 'required|boolean']);
        $steps = $request->user()->resinCompletedPathSteps();

        if ($data['done']) {
            $steps->syncWithoutDetaching([$pathStep->id => ['done_at' => now()]]);
        } else {
            $steps->detach($pathStep->id);
        }

        return response()->json(['done' => $data['done']]);
    }

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('resina.path.edit', [
            'rows' => old('steps', PathStep::orderBy('position')->get()->map(fn (PathStep $step) => [
                'id' => $step->id,
                'title' => $step->title,
                'description' => $step->description,
                'link_route' => $step->link_route,
                'link_anchor' => $step->link_anchor,
            ])->all()),
            'routes' => self::LINK_ROUTES,
            'anchors' => TechniqueController::ANCHORS,
        ]);
    }

    /**
     * Rows in order: those with an id are updated, new ones created,
     * missing ones deleted (with everyone's tick on them).
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'steps' => 'nullable|array|max:50',
            'steps.*.id' => 'nullable|integer',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.description' => 'nullable|string|max:1000',
            'steps.*.link_route' => ['nullable', Rule::in(array_keys(self::LINK_ROUTES))],
            'steps.*.link_anchor' => ['nullable', Rule::in(array_keys(TechniqueController::ANCHORS))],
        ], ['steps.*.title.required' => 'Ogni passo ha bisogno di un titolo.']);

        DB::transaction(function () use ($data) {
            $rows = ResinaRows::ordered($data['steps'] ?? null);
            // Positions are unique: park the current ones out of the way first
            // (and only then load the rows, so they know their parked position).
            PathStep::query()->update(['position' => DB::raw('position + 10000')]);
            $existing = PathStep::all()->keyBy('id');
            $kept = [];

            foreach ($rows as $index => $row) {
                $attributes = [
                    'position' => $index + 1,
                    'title' => $row['title'],
                    'description' => $row['description'] ?? null,
                    'link_route' => $row['link_route'] ?? null,
                    // An anchor only makes sense on the techniques page.
                    'link_anchor' => ($row['link_route'] ?? null) === 'resina.techniques.index' ? ($row['link_anchor'] ?? null) : null,
                ];
                $step = isset($row['id']) ? $existing->get((int) $row['id']) : null;
                $step ? $step->update($attributes) : $step = PathStep::create($attributes);
                $kept[] = $step->id;
            }

            PathStep::whereNotIn('id', $kept)->delete();
        });

        return redirect()->route('resina.path.index')->with('status', 'Percorso aggiornato.');
    }

    /**
     * Where a step's «Come si fa» points.
     */
    public static function linkFor(PathStep $step): ?string
    {
        if (! $step->link_route || ! Route::has($step->link_route)) {
            return null;
        }

        return route($step->link_route).($step->link_anchor ? '#'.$step->link_anchor : '');
    }
}
