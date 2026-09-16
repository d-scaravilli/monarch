<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    /**
     * Bulk-editable member profile table (see the bulk-profile-table
     * Livewire component for the actual query/instant-save fields).
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('directory.index');
    }
}
