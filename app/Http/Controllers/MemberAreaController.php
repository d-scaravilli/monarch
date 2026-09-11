<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MemberAreaController extends Controller
{
    /**
     * The member's own enrollments, lessons/attendance, payments,
     * and medical certificate status.
     */
    public function index(): View
    {
        $user = Auth::user();

        $enrollments = $user->enrollments()
            ->with([
                'course.discipline',
                'course.room',
                'attendances.lesson',
                'payments',
            ])
            ->get();

        $medicalCertificate = $user->medicalCertificates()->latest('expiry_date')->first();

        return view('member.area', compact('enrollments', 'medicalCertificate'));
    }
}
