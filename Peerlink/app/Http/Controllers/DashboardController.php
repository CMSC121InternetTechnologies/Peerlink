<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use function Laravel\Prompts\alert;

class DashboardController extends Controller
{
    public function index()
    {
        alert("Redirecting to dashboard"); 
    }
}
