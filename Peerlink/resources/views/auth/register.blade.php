<x-guest-layout>
    <div class="profile-card" style="padding: 2.5rem;">
        <h2 class="page-title" style="text-align: center; font-size: 1.5rem;">Create an Account</h2>

        <form method="POST" action="{{ route('register') }}" class="modal-form">
            @csrf

            @if ($errors->any())
                <div style="color: var(--coral); font-size: 0.85rem; margin-bottom: 1rem;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="margin-bottom: 0.5rem;">
                <label>First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" required autofocus style="width: 100%;">
            </div>

            <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem;">
                <div style="flex: 1;">
                    <label>Middle Name <span style="color: var(--text-muted); font-size: 0.8rem;">(Optional)</span></label>
                    <!-- Notice there is NO "required" attribute here -->
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" style="width: 100%;">
                </div>
                <div style="flex: 1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required style="width: 100%;">
                </div>
            </div>

            <label>Degree Program</label>
            <select name="program_code" required>
                <option value="" disabled selected>Select Program</option>
                @foreach($programs as $divisionName => $divPrograms)
                    <optgroup label="{{ $divisionName }}">
                        @foreach($divPrograms as $program)
                            <option value="{{ $program->program_code }}" {{ old('program_code') == $program->program_code ? 'selected' : '' }}>
                                {{ $program->program_name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <label>Current Year Level (e.g., 1, 2, 3)</label>
            {{-- Bug: HTML max was 100 but the server-side rule is max:10, giving an
                 unexpected validation error for values 11–100 with no client-side hint.
                 Fix: align max with the server rule so the browser rejects out-of-range
                 values before the form is even submitted. --}}
            <input type="number" name="current_year_level" value="{{ old('current_year_level') }}" required min="1" max="10">
            
            <label>Contact Number (Format: +639xxxxxxxxx or 09xxxxxxxxx)</label>
            <input type="text" name="contact_number" value="{{ old('contact_number') }}" required placeholder="+63 912-345-6789" style="width: 100%; margin-bottom: 0.5rem;">

            <label>Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="student@up.edu.ph">
           
            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit" class="btn-primary full-width" style="margin-top: 1rem;">Register</button>

            <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                Already registered? <a href="{{ route('login') }}" style="color: var(--teal-dark); text-decoration: none; font-weight: 600;">Log in</a>
            </p>
        </form>
    </div>
</x-guest-layout>