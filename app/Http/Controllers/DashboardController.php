<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Send each role to its home screen: members manage their own
     * membership, admins/instructors manage editions.
     */
    public function index(): RedirectResponse
    {
        if (Auth::user()->hasRole('member')) {
            return redirect()->route('member.area');
        }

        return redirect()->route('editions.index');
    }
}
