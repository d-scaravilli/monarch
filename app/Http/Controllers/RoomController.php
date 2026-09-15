<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('rooms.index', [
            'rooms' => Room::withCount('courses')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateRoom($request);

        Room::create($data);

        return back()->with('status', 'Sala aggiunta.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateRoom($request);

        $room->update($data);

        return back()->with('status', 'Sala aggiornata.');
    }

    /**
     * @return array{name: string, capacity: int, address: ?string}
     */
    private function validateRoom(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'address' => 'nullable|string',
        ]);
    }
}
