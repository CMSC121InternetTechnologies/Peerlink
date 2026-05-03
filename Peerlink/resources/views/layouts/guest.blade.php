<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerLink - Authentication</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style.css') }}">
</head>
<body class="mode-tutee" style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    
    <div class="page" style="width: 100%; max-width: 500px; padding: 2rem;">
        <div style="display: flex; justify-content: center; margin-bottom: 2rem;">
            <a class="logo" href="/">
                <div class="logo-icon"></div>
                <span class="logo-text" style="font-size: 1.5rem;">PEER<strong>LINK</strong></span>
            </a>
        </div>

        <!-- This is where Login/Register forms will be injected -->
        {{ $slot }}
    </div>

</body>
</html>