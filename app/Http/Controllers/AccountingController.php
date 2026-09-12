<?php

namespace App\Http\Controllers;

use App\Services\AccountingService;
use App\Support\CourseYear;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function index(Request $request, AccountingService $accounting): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $year = $request->has('year') ? $request->input('year') : CourseYear::default();

        $enrollments = $accounting->enrollmentsForYear($year);

        return view('accounting.index', [
            'selectedYear' => $year,
            'years' => CourseYear::options(),
            'totals' => $accounting->totals($enrollments),
            'byCourse' => $accounting->byCourse($enrollments),
            'whoOwes' => $accounting->whoOwes($enrollments),
            'monthlyCollected' => $accounting->monthlyCollected($enrollments),
        ]);
    }
}
