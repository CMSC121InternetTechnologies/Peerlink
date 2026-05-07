<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $programs = \App\Models\Program::orderBy('program_code')->get();
        return view('auth.register', compact('programs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'regex:/^\+63[\s\-]?9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}$/'],
            'program_code' => ['required', 'string', 'exists:Programs,program_code'],
            'current_year_level' => ['required', 'integer', 'min:1', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255','ends_with:@up.edu.ph', 'unique:'.User::class],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);
        
        $cleaned_contact = str_replace([' ', '-'], '', $request->contact_number);
        $user = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'contact_number' => $cleaned_contact,
            'program_code' => $request->program_code,
            'current_year_level' => $request->current_year_level,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
        ]);

        event(new Registered($user));
        Auth::login($user);
        session()->flash('onboarding', true);
        return redirect(route('dashboard', absolute: false));
    }
}