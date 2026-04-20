<!DOCTYPE html>
<html lang = "en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PeerLink | Register</title>
         @vite(['resources/css/style.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class = "profile-container">
            <div class = "profile-card">
                <h2>Sign up to Peerlink</h2>
                 @if (session('status'))
                <div style="color: green; margin-bottom: 1rem;">
                    {{ session('status') }}
                </div>
                 @endif

               <form method="POST" action="{{ route('register') }}">
                @csrf

                <!--name-->
                    <div class = "input-group">
                        <label for ="name">Name</label>
                        <input type = "text" id = "name" name = "name" :value="old('name')" required autofocus autocomplete="name">
                    </div>

                    <div class = "input-group">
                        <label for = "email">Email Address</label>
                        <input type = "email" id="email" name="email" value="{{ old('name') }}"  required autocomplete="username" />
                        @error('email')
                            <span style="color: red; font-size: 0.8rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class = "input-group">
                        <label for = "password">Password</label>
                        <input id="password" 
                                type="password"
                                name="password"
                                required autocomplete="new-password" />
                        @error('password')
                            <span style="color: red; font-size: 0.8rem;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class = "input-group">
                        <label for = "password_confirmation">Re-enter Password</label>
                        <input id="password_confirmation" 
                                type="password"
                                name="password_confirmation"
                                required autocomplete="new-password" />
                        @error('password_confirmation')
                            <span style="color: red; font-size: 0.8rem;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                            Already Registered?
                        </a>

                         <button type="submit" class="btn-primary">Sign Up</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
