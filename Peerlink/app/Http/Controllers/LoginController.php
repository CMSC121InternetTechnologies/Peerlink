<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use function Laravel\Prompts\alert;

class LoginController extends Controller
{
    public function index()
    {
        alert("Redirecting to Login"); 
    }
}
