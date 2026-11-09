<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In · {{ data_get($settings ?? null, 'store_name') ?: config('app.name', 'Hira Moni') }}</title>
    @include('partials.favicon', ['settings' => $settings ?? null])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --ink: #2b1522;
            --muted: #8d6b7a;
            --line: #f0d5e2;
            --blush: #fff4f8;
            --rose: #e91e63;
            --rose-deep: #c2185b;
            --display: 'Cormorant Garamond', Georgia, serif;
            --sans: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        body.bl-body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--sans);
            color: var(--ink);
            background:
                radial-gradient(ellipse 42% 36% at 8% 12%, rgba(244, 143, 177, .34), transparent 70%),
                radial-gradient(ellipse 38% 40% at 92% 88%, rgba(194, 24, 91, .12), transparent 68%),
                linear-gradient(165deg, #fff7fa 0%, #ffe9f1 42%, #fff 100%);
            -webkit-font-smoothing: antialiased;
        }
        .bl-stage {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px;
        }
        .bl-card {
            width: min(100%, 920px);
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            border-radius: 32px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(233, 30, 99, .08);
            box-shadow:
                0 1px 0 rgba(255,255,255,.8) inset,
                0 28px 70px rgba(136, 14, 79, .12);
        }
        .bl-side {
            position: relative;
            padding: 36px 34px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 36px;
            min-height: 520px;
            background:
                radial-gradient(circle at 18% 18%, rgba(255,255,255,.7), transparent 42%),
                radial-gradient(circle at 88% 78%, rgba(233, 30, 99, .16), transparent 46%),
                linear-gradient(155deg, #fff5f8 0%, #fce4ec 52%, #f8bbd0 100%);
            overflow: hidden;
        }
        .bl-side::before {
            content: '';
            position: absolute;
            width: 220px;
            height: 220px;
            right: -70px;
            top: -50px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.55);
            background: radial-gradient(circle, rgba(255,255,255,.35), transparent 68%);
            pointer-events: none;
        }
        .bl-side::after {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            left: -48px;
            bottom: 48px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.4);
            pointer-events: none;
        }
        .bl-brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .bl-brand__mark {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(194, 24, 91, .16);
            display: grid;
            place-items: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .bl-brand__mark img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
        }
        .bl-brand__name {
            margin: 0;
            font-family: var(--display);
            font-size: 26px;
            font-weight: 600;
            line-height: 1;
            letter-spacing: -.01em;
            color: var(--ink);
        }
        .bl-brand__sub {
            margin: 5px 0 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--rose-deep);
        }
        .bl-hello {
            position: relative;
            z-index: 1;
            max-width: 18ch;
        }
        .bl-hello__eyebrow {
            margin: 0 0 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--rose-deep);
        }
        .bl-hello__title {
            margin: 0;
            font-family: var(--display);
            font-size: clamp(2.4rem, 4vw, 3.15rem);
            font-weight: 600;
            line-height: .98;
            letter-spacing: -.02em;
            color: var(--ink);
        }
        .bl-hello__title em {
            font-style: italic;
            color: var(--rose-deep);
        }
        .bl-hello__text {
            margin: 16px 0 0;
            font-size: 14.5px;
            font-weight: 500;
            line-height: 1.55;
            color: var(--muted);
            max-width: 28ch;
        }
        .bl-side-foot {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12.5px;
            font-weight: 600;
            color: #7a5566;
        }
        .bl-side-foot__dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--rose);
            box-shadow: 0 0 0 4px rgba(233, 30, 99, .12);
        }

        .bl-main {
            padding: 42px 40px 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(180deg, #fff 0%, #fffafc 100%);
        }
        .bl-title {
            margin: 0;
            font-family: var(--display);
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: -.01em;
            color: var(--ink);
        }
        .bl-lead {
            margin: 6px 0 26px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
            color: var(--muted);
        }
        .bl-alert,
        .bl-status {
            margin-bottom: 16px;
            border-radius: 14px;
            padding: 11px 13px;
            font-size: 13px;
            line-height: 1.45;
        }
        .bl-alert { border: 1px solid #f8bbd0; background: #fff5f8; color: #ad1457; }
        .bl-alert strong { display: block; margin-bottom: 2px; color: #880e4f; }
        .bl-status { border: 1px solid #f8bbd0; background: #fff5f8; color: var(--rose-deep); text-align: center; }

        .bl-form { display: grid; gap: 16px; }
        .bl-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 7px;
        }
        .bl-label {
            display: block;
            margin-bottom: 7px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .bl-label-row .bl-label { margin-bottom: 0; }
        .bl-forgot {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--rose-deep);
            text-decoration: none;
        }
        .bl-forgot:hover { text-decoration: underline; }

        .bl-field { position: relative; }
        .bl-field input {
            width: 100%;
            height: 50px;
            padding: 0 44px 0 16px;
            border: 1.5px solid var(--line);
            border-radius: 16px;
            background: var(--blush);
            color: var(--ink);
            font: 500 14.5px/1.2 var(--sans);
            outline: none;
        }
        .bl-field input::placeholder { color: #b48ea0; }
        .bl-field input:focus {
            border-color: var(--rose);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(233, 30, 99, .11);
        }
        .bl-field__icon {
            position: absolute;
            right: 15px;
            top: 50%;
            width: 18px;
            height: 18px;
            transform: translateY(-50%);
            color: #c095a8;
            pointer-events: none;
        }
        .bl-field input:focus + .bl-field__icon { color: var(--rose); }

        .bl-check {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }
        .bl-check input {
            width: 17px;
            height: 17px;
            border-radius: 5px;
            accent-color: var(--rose);
            cursor: pointer;
        }

        .bl-submit {
            width: 100%;
            height: 52px;
            margin-top: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #e91e63, #c2185b);
            color: #fff;
            font: 700 15px/1 var(--sans);
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(194, 24, 91, .22);
        }
        .bl-submit:hover { filter: brightness(1.03); }
        .bl-submit svg { width: 16px; height: 16px; }

        .bl-foot {
            margin: 22px 0 0;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }
        .bl-foot a { color: inherit; text-decoration: none; }
        .bl-foot strong { color: var(--rose-deep); font-weight: 700; }

        @media (max-width: 820px) {
            .bl-card { grid-template-columns: 1fr; width: min(100%, 440px); }
            .bl-side {
                min-height: 0;
                gap: 18px;
                padding: 26px 24px 22px;
            }
            .bl-hello { max-width: none; }
            .bl-hello__title { font-size: 2.2rem; }
            .bl-side-foot { display: none; }
            .bl-main { padding: 28px 24px 24px; }
        }
    </style>
</head>
<body class="bl-body">
    {{ $slot }}
</body>
</html>
