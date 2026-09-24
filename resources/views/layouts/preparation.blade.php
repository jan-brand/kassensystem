<!doctype html>
<html lang="de" data-theme="gastro-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#000000">
    <title>Zubereitung · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-[100dvh] bg-black text-white antialiased">
    {{ $slot }}
    @livewireScripts
    <script>
        (() => {
            let audioContext = null;

            const context = () => {
                if (!audioContext) {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) return null;
                    audioContext = new AudioContextClass();
                }

                if (audioContext.state === 'suspended') audioContext.resume();
                return audioContext;
            };

            document.addEventListener('pointerdown', context, { once: true });

            window.playPreparationSound = (sound) => {
                if (!sound || sound === 'none') return;
                const ctx = context();
                if (!ctx) return;

                const play = (frequency, start, duration) => {
                    const oscillator = ctx.createOscillator();
                    const gain = ctx.createGain();
                    oscillator.frequency.value = frequency;
                    gain.gain.setValueAtTime(0.0001, start);
                    gain.gain.exponentialRampToValueAtTime(0.18, start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
                    oscillator.connect(gain);
                    gain.connect(ctx.destination);
                    oscillator.start(start);
                    oscillator.stop(start + duration + 0.03);
                };

                const now = ctx.currentTime;
                if (sound === 'chime') {
                    play(660, now, 0.18);
                    play(880, now + 0.16, 0.24);
                    return;
                }

                play(880, now, 0.14);
                play(880, now + 0.2, 0.14);
            };
        })();
    </script>
</body>
</html>
