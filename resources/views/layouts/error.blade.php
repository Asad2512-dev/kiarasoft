<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · KiraSoft</title>
    {{-- This page works without compiled styles or JavaScript. --}}
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: system-ui,-apple-system,'Segoe UI',sans-serif;
            background: #f6f8fa;
            color: #1d2939;
            padding: 1rem;
        }
        .error-page {
            text-align: center;
            max-width: 420px;
        }
        .error-code {
            font-size: 4.5rem;
            font-weight: 800;
            letter-spacing: -.04em;
            background: linear-gradient(135deg,#059669,#f59e0b);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            line-height: 1;
        }
        h1 {
            font-size: 1.4rem;
            margin: .75rem 0 .5rem;
        }
        p {
            color: #667085;
            margin: 0 0 1.5rem;
        }
        a {
            display: inline-block;
            background: #059669;
            color: #fff;
            text-decoration: none;
            padding: .7rem 1.3rem;
            border-radius: .6rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="error-page">
        <div class="error-code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Go to home</a>
    </main>
</body>
</html>
