<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('resina.home', [
            'paints' => $user->resinPaints()->with('paint')->get()
                ->sortBy(fn ($userPaint) => [$userPaint->isCustom() ? 1 : 0, $userPaint->paint?->position ?? $userPaint->id])
                ->groupBy(fn ($userPaint) => $userPaint->paint?->line ?? 'Aggiunti da te'),
            'brushCount' => $user->resinBrushes()->count(),
            'projects' => Project::withCount(['characters', 'armorTypes'])->orderBy('position')->get(),
        ]);
    }
}
