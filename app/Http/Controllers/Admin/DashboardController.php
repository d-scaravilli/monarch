<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $disabledUsers = User::whereNotNull('disabled_at')->count();
        $activeUsers = $totalUsers - $disabledUsers;

        $roleDistribution = collect(['admin', 'instructor', 'member'])
            ->mapWithKeys(fn (string $role) => [$role => User::role($role)->count()]);

        $activeModules = Module::where('is_active', true)->count();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'disabledUsers' => $disabledUsers,
            'roleDistribution' => $roleDistribution,
            'activeModules' => $activeModules,
        ]);
    }
}
