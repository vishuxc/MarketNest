<x-layouts.marketplace title="Login | MarketNest">
    <div class="market-container auth-shell">
    <x-auth-intro />
    <section class="auth-panel" aria-labelledby="auth-heading">
        <h1 id="auth-heading">Log in to MarketNest</h1>

        <form class="auth-form" method="POST" action="{{ route('login.store') }}" data-auth-form>
            @csrf
            <x-auth-field name="email" label="Email address" type="email" autocomplete="email" maxlength="255" />
            <x-auth-field name="password" label="Password" type="password" autocomplete="current-password" maxlength="255" />

            <label class="auth-remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember') === '1')>
                Remember me
            </label>
            @error('remember')
                <p class="auth-error">{{ $message }}</p>
            @enderror

            <button class="market-button auth-submit" type="submit" data-loading-label="Logging in…">Log in</button>
        </form>

        <p class="auth-footer">New to MarketNest? <a href="{{ route('register') }}">Create an account</a></p>
    </section>
    </div>
</x-layouts.marketplace>
