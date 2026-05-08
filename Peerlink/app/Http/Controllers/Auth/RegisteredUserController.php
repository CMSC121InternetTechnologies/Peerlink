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
        $programs = \App\Models\Program::join('Divisions', 'Programs.division_id', '=', 'Divisions.division_id')
            ->select('Programs.*', 'Divisions.division_name')
            ->orderBy('Divisions.division_name')
            ->orderBy('Programs.program_name')
            ->get()
            ->groupBy('division_name');
        return view('auth.register', compact('programs'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Bug: validate()'s return value was discarded; $request->field_name accessed
        // the raw un-validated input bag instead of the sanitised, type-cast values.
        // Fix: capture the returned $validated array and use it exclusively below.
        $validated = $request->validate([
            'first_name'         => ['required', 'string', 'max:100'],
            'middle_name'        => ['nullable', 'string', 'max:100'],
            'last_name'          => ['required', 'string', 'max:100'],
            'contact_number'     => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'program_code'       => ['required', 'string', 'exists:Programs,program_code'],
            'current_year_level' => ['required', 'integer', 'min:1', 'max:10'],
            'email'              => ['required', 'string', 'lowercase', 'email', 'max:255', 'min:3', 'ends_with:@up.edu.ph', 'unique:' . User::class],
            'password'           => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ], [
            'email.ends_with'        => 'Please use a valid UP webmail address (@up.edu.ph) to register.',
            'contact_number.regex'   => 'The contact number must be in the format 09xxxxxxxxx or +639xxxxxxxxx.',
        ]);

        $cleaned_contact = str_replace([' ', '-'], '', $validated['contact_number']);

        $user = User::create([
            'first_name'         => $validated['first_name'],
            'middle_name'        => $validated['middle_name'] ?? null,
            'last_name'          => $validated['last_name'],
            'contact_number'     => $cleaned_contact,
            'program_code'       => $validated['program_code'],
            'current_year_level' => $validated['current_year_level'],
            'email'              => $validated['email'],
            'password_hash'      => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::login($user);
        session()->flash('onboarding', true);
        return redirect(route('dashboard', absolute: false));
    }
}