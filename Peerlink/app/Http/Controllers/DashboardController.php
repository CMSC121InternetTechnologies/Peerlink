<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $courses = Course::with('topics')->orderBy('course_code')->get();
        $onboarding = session('onboarding', false);
        return view('dashboard', compact('courses', 'onboarding'));
    }
}