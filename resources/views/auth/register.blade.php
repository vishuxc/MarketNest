<x-layouts.marketplace title="Register | MarketNest">
    <div class="market-container auth-shell">
    <x-auth-intro />
    <section class="auth-panel" aria-labelledby="auth-heading">
        <h1 id="auth-heading">Create your account</h1>

        <form class="auth-form" method="POST" action="{{ route('register.store') }}" data-auth-form>
            @csrf
            <x-auth-field name="name" label="Full name" autocomplete="name" maxlength="255" />
            <x-auth-field name="email" label="Email address" type="email" autocomplete="email" maxlength="255" />
            <x-auth-field name="password" label="Password" type="password" autocomplete="new-password" minlength="8" maxlength="72" />
            <x-auth-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" maxlength="72" />

            <button class="market-button auth-submit" type="submit" data-loading-label="Creating account…">Create account</button>
        </form>

        <p class="auth-footer">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </section>
    </div>
</x-layouts.marketplace>
