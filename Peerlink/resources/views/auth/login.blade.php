<x-guest-layout>
    <div class="profile-card" style="padding: 2.5rem;">
        <h2 class="page-title" style="text-align: center; font-size: 1.5rem;">Welcome Back</h2>

        <form method="POST" action="{{ route('login') }}" class="modal-form">
            @csrf

            @if ($errors->any())
                <div style="color: var(--coral); font-size: 0.85rem; margin-bottom: 1rem;">
                    {{ $errors->first() }}
                </div>
            @endif

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn-primary full-width" style="margin-top: 1.5rem;">Log in</button>

            <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                Don't have an account? <a href="{{ route('register') }}" style="color: var(--teal-dark); text-decoration: none; font-weight: 600;">Register</a>
            </p>
        </form>
    </div>
</x-guest-layout>