<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#000000">
    <title>403 · Kassensystem</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #000000;
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        main {
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 1.25rem;
        }
        section {
            width: min(34rem, 100%);
            padding: clamp(1.5rem, 5vw, 2.25rem);
            border: 1px solid #242424;
            border-top: .2rem solid #c79a38;
            border-radius: 1.5rem;
            background: #0d0d0d;
        }
        .code {
            margin: 0;
            color: #c8c8c8;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }
        h1 {
            margin: .6rem 0 0;
            color: #ffffff;
            font-size: clamp(2rem, 7vw, 2.75rem);
            line-height: 1.15;
        }
        .copy {
            margin: 1rem 0 0;
            color: #c8c8c8;
            line-height: 1.55;
        }
        a {
            display: inline-flex;
            min-height: 3rem;
            align-items: center;
            margin-top: 1.5rem;
            padding: .65rem 1rem;
            border: 1px solid #ffffff;
            border-radius: .75rem;
            background: #ffffff;
            color: #000000;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body data-error-surface="403">
<main>
    <section>
        <p class="code">Fehler 403</p>
        <h1>Zugriff verweigert</h1>
        <p class="copy">Du hast keine Berechtigung für diese Seite oder Aktion.</p>
        <a href="/">Zur Startseite</a>
    </section>
</main>
</body>
</html>
