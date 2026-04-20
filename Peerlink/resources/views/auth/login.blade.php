<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerLink | Log In</title>
    @vite(['resources/css/style.css', 'resources/js/app.js'])
</head>
<body>
    <div class="profile-container"> 
        <div class="profile-card">
            <h2>Welcome Back to PeerLink</h2>

            @if (session('status'))
                <div style="color: green; margin-bottom: 1rem;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                    
                    @error('email')
                        <span style="color: red; font-size: 0.8rem;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    
                    @error('password')
                        <span style="color: red; font-size: 0.8rem;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group" style="display: flex; align-items: center; gap: 0.5rem; flex-direction: row;">
                    <input type="checkbox" id="remember_me" name="remember">
                    <label for="remember_me" style="margin: 0; font-weight: normal;">Remember me</label>
                </div>

                <div class="form-actions-spacer" style="margin-top: 1.5rem; align-items: center;">
                    
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 0.9rem; color: #555;">Forgot your password?</a>
                    @endif
                <a href = "{{ route('register') }}">Sign-up</a>
                
                    <button type="submit" class="btn-primary">Log in</button>
                    
                </div>
            </form>
            
        </div>
    </div>
</body>
</html>