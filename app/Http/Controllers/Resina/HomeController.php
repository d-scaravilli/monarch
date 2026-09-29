<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\PathStep;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\UserPaint;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $pathSteps = PathStep::orderBy('position')->get();
        $doneIds = $user->resinCompletedPathSteps()->pluck('resin_path_steps.id');

        return view('resina.home', [
            // Starter paints in shelf order, then the user's own; each opens the mixer.
            'paints' => $user->resinPaints()->with('paint')->get()
                ->sortBy(fn (UserPaint $userPaint) => [$userPaint->isCustom() ? 1 : 0, $userPaint->paint?->position ?? $userPaint->id])
                ->groupBy(fn (UserPaint $userPaint) => $userPaint->paint?->line ?? 'Aggiunti da te'),
            'brushCount' => $user->resinBrushes()->count(),
            'projects' => Project::withCount(['characters', 'armorTypes'])->orderBy('position')->get(),
            'recipeCount' => Recipe::catalog()->count(),
            'path' => [
                'done' => $doneIds->count(),
                'total' => $pathSteps->count(),
                'next' => $pathSteps->first(fn (PathStep $step) => ! $doneIds->contains($step->id)),
            ],
        ]);
    }
}
