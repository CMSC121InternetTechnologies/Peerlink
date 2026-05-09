{{-- Inline-styled PeerLink logo. Mirrors the .logo-icon + .logo-text combo
     used in the dashboard nav and the guest layout, so anywhere this Blade
     component is rendered (currently navigation.blade.php) shows the brand
     mark instead of the default Laravel SVG.

     Rendered as an SVG so the existing classes that auto-size SVGs
     (`fill-current text-gray-800 h-9 w-auto`) keep working. The diamond
     uses the same coral→teal gradient as the CSS-styled version. --}}
<svg viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="peerlink-logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%"   stop-color="#e07060"/>
            <stop offset="100%" stop-color="#4ab3c3"/>
        </linearGradient>
    </defs>
    {{-- Rotated square (the "logo-icon" diamond) --}}
    <rect x="2" y="13" width="24" height="24" rx="4" ry="4"
          fill="url(#peerlink-logo-gradient)"
          transform="rotate(45 14 25)"/>
    {{-- "PEER" + bold "LINK" wordmark --}}
    <text x="36" y="33" font-family="Sora, 'DM Sans', sans-serif"
          font-size="22" font-weight="600" fill="#3d3d4a">PEER<tspan font-weight="700" fill="#e07060">LINK</tspan></text>
</svg>
