<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $courses    = Course::orderBy('course_code')->get();
        $programs   = Program::orderBy('program_code')->get();
        $onboarding = session('onboarding', false);
        return view('dashboard', compact('courses', 'programs', 'onboarding'));
    }
}