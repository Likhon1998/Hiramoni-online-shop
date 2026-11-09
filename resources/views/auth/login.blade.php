<x-login-layout>
    @php
        $brandName = data_get($settings ?? null, 'store_name') ?: config('app.name', 'Hira Moni');
        $brandLogo = !empty(data_get($settings ?? null, 'logo_path'))
            ? public_storage_url($settings->logo_path)
            : null;
        if (! $brandLogo && is_file(public_path('images/hiramoni-logo-round.png'))) {
            $brandLogo = asset('images/hiramoni-logo-round.png');
        } elseif (! $brandLogo && is_file(public_path('images/hiramoni-logo.png'))) {
            $brandLogo = asset('images/hiramoni-logo.png');
        }
        $brandIcon = !empty(data_get($settings ?? null, 'favicon_path'))
            ? public_storage_url($settings->favicon_path)
            : $brandLogo;
        $iconVer = !empty(data_get($settings ?? null, 'favicon_path'))
            ? (@filemtime(public_storage_path($settings->favicon_path)) ?: time())
            : (@filemtime(public_path('images/hiramoni-logo-round.png')) ?: time());
    @endphp

    <div class="bl-stage">
        <section class="bl-card" aria-label="Admin sign in">
            <aside class="bl-side">
                <div class="bl-brand">
                    <div class="bl-brand__mark">
                        @if($brandIcon)
                            <img src="{{ $brandIcon }}?v={{ $iconVer }}" alt="{{ $brandName }}">
                        @else
                            {{ mb_strtoupper(mb_substr($brandName, 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <p class="bl-brand__name">{{ $brandName }}</p>
                        <p class="bl-brand__sub">Beauty studio</p>
                    </div>
                </div>

                <div class="bl-hello">
                    <p class="bl-hello__eyebrow">Everyday glow</p>
                    <h2 class="bl-hello__title">Beauty,<br><em>beautifully</em><br>managed.</h2>
                    <p class="bl-hello__text">Shades, stock and orders — all in one calm place.</p>
                </div>

                <div class="bl-side-foot">
                    <span class="bl-side-foot__dot" aria-hidden="true"></span>
                    Trusted beauty admin for Hira Moni
                </div>
            </aside>

            <div class="bl-main">
                <h1 class="bl-title">Welcome back</h1>
                <p class="bl-lead">Sign in to continue to your beauty dashboard.</p>

                @if ($errors->has('email') || $errors->has('password'))
                    <div class="bl-alert" role="alert">
                        <strong>Unable to sign in</strong>
                        {{ $errors->first('email') ?: $errors->first('password') }}
                    </div>
                @elseif (session('error'))
                    <div class="bl-alert" role="alert">
                        <strong>Session expired</strong>
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="bl-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="bl-form">
                    @csrf

                    <div>
                        <label for="email" class="bl-label">Email</label>
                        <div class="bl-field">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@email.com">
                            <svg class="bl-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="bl-label-row">
                            <label for="password" class="bl-label">Password</label>
                            @if (Route::has('password.request'))
                                <a class="bl-forgot" href="{{ route('password.request') }}">Forgot?</a>
                            @endif
                        </div>
                        <div class="bl-field">
                            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Your password">
                            <svg class="bl-field__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                    </div>

                    <label class="bl-check" for="remember_me">
                        <input id="remember_me" type="checkbox" name="remember">
                        Keep me signed in
                    </label>

                    <button type="submit" class="bl-submit">
                        Sign in
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </button>
                </form>

                <p class="bl-foot">
                    &copy; {{ date('Y') }} {{ $brandName }} · Powered by
                    <a href="{{ config('app.powered_by_url', 'https://bynnas.com') }}" target="_blank" rel="noopener noreferrer"><strong>{{ config('app.powered_by', 'Bynnas') }}</strong></a>
                </p>
            </div>
        </section>
    </div>
</x-login-layout>
