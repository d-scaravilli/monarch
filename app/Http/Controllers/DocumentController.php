<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys(Document::TYPES)),
            'custom_label' => 'nullable|string|max:255|required_if:type,altro',
            'file' => 'required|file|max:10240',
            'uploaded_at' => 'required|date',
            'expiry_date' => 'nullable|date',
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $member->documents()->create([
            'type' => $data['type'],
            'custom_label' => $data['type'] === 'altro' ? $data['custom_label'] : null,
            'file_path' => $path,
            'uploaded_at' => $data['uploaded_at'],
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        return back()->with('status', 'Documento caricato.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $member = $document->user;
        $document->delete();

        return redirect()->route('members.show', $member)->with('status', 'Documento eliminato.');
    }
}
