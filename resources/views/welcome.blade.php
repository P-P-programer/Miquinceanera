<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @viteReactRefresh
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body>
        <div id="app"></div>

        <script>
            (function () {
                const STAR_COUNT  = 90;
                const DRIFT_COUNT = 18;
                const variants    = ['star-a', 'star-b', 'star-c', 'star-d'];
                const frag        = document.createDocumentFragment();

                function rand(min, max) {
                    return Math.random() * (max - min) + min;
                }

                for (let i = 0; i < STAR_COUNT; i++) {
                    const img     = document.createElement('img');
                    const size    = rand(10, 34);
                    const vw      = rand(0, 100);
                    const vh      = rand(0, 100);
                    const dur     = rand(2.4, 6.5).toFixed(2);
                    const del     = rand(0, 7).toFixed(2);
                    const opacity = rand(0.5, 1).toFixed(2);
                    const variant = i < DRIFT_COUNT
                        ? 'star-drift'
                        : variants[i % variants.length];

                    img.src = '/str.png';
                    img.alt = '';
                    img.setAttribute('aria-hidden', 'true');
                    img.className = 'star ' + variant;
                    img.style.cssText = [
                        `left:${vw}vw`,
                        `top:${vh}vh`,
                        `width:${size}px`,
                        `height:${size}px`,
                        `--dur:${dur}s`,
                        `--del:-${del}s`,
                        `--del2:-${rand(0, 12).toFixed(2)}s`,
                        `--sc:${rand(0.85, 1.15).toFixed(2)}`,
                        `opacity:${opacity}`,
                    ].join(';');

                    frag.appendChild(img);
                }

                document.body.appendChild(frag);
            })();
        </script>
    </body>
</html>