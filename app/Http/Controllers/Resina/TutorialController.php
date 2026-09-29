<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Tutorial;
use App\Support\ResinaRows;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Tutorial video": ready-made YouTube searches. The admin edits them.
 */
class TutorialController extends Controller
{
    public function index(): View
    {
        return view('resina.tutorials.index', ['tutorials' => Tutorial::orderBy('position')->get()]);
    }

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('resina.tutorials.edit', [
            'rows' => old('tutorials', Tutorial::orderBy('position')->get(['id', 'title', 'query', 'description'])->toArray()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'tutorials' => 'nullable|array|max:50',
            'tutorials.*.id' => 'nullable|integer',
            'tutorials.*.title' => 'required|string|max:255',
            'tutorials.*.query' => 'required|string|max:255',
            'tutorials.*.description' => 'nullable|string|max:255',
        ], [
            'tutorials.*.title.required' => 'Ogni tutorial ha bisogno di un titolo.',
            'tutorials.*.query.required' => 'Ogni tutorial ha bisogno di una ricerca.',
        ]);

        DB::transaction(function () use ($data) {
            // Positions are unique: park the current ones out of the way first
            // (and only then load the rows, so they know their parked position).
            Tutorial::query()->update(['position' => DB::raw('position + 10000')]);
            $existing = Tutorial::all()->keyBy('id');
            $kept = [];

            foreach (ResinaRows::ordered($data['tutorials'] ?? null) as $index => $row) {
                $attributes = ['position' => $index + 1, 'title' => $row['title'], 'query' => $row['query'], 'description' => $row['description'] ?? null];
                $tutorial = isset($row['id']) ? $existing->get((int) $row['id']) : null;
                $tutorial ? $tutorial->update($attributes) : $tutorial = Tutorial::create($attributes);
                $kept[] = $tutorial->id;
            }

            Tutorial::whereNotIn('id', $kept)->delete();
        });

        return redirect()->route('resina.tutorials.index')->with('status', 'Tutorial aggiornati.');
    }
}
