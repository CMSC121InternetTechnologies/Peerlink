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

            <div style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required autofocus style="width: 100%;">
                </div>
                <div style="flex: 1;">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required style="width: 100%;">
                </div>
            </div>

            <label>Degree Program</label>
            <select name="program_code" required style="width: 100%; padding: 0.6rem; border: 1.5px solid #e0d8c8; border-radius: 8px; margin-bottom: 0.5rem; font-family: 'DM Sans';">
                <option value="" disabled selected>Select Program</option>
                @foreach($programs as $program)
                    <option value="{{ $program->program_code }}" {{ old('program_code') == $program->program_code ? 'selected' : '' }}>
                        {{ $program->program_code }}
                    </option>
                @endforeach
            </select>

            <label>Current Year Level (e.g., 1, 2, 3)</label>
            <input type="number" name="current_year_level" value="{{ old('current_year_level') }}" required min="1" max="5">

            <label>Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

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