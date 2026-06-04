@extends('layouts.auth', ['title' => 'Login'])

@section('content')
    <h1 class="tmc-auth-heading">Welcome back</h1>
    <p class="tmc-auth-copy">Sign in to continue your journey with The Muhsinat Club.</p>

    @if (session('status'))
        <p class="mt-6 rounded-sm bg-gold-pale px-4 py-3 text-sm text-teal-dk">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ showPassword: false }">
        @csrf

        <div>
            <label for="email" class="tmc-label">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="tmc-input">
            @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <label for="password" class="tmc-label mb-0">Password</label>
                <button type="button" class="text-xs font-semibold uppercase tracking-[1px] text-teal" @click="showPassword = ! showPassword">
                    <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                </button>
            </div>
            <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="tmc-input">
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between gap-4 text-sm text-ink-soft">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="remember" class="h-4 w-4 border-slate-300 text-teal focus:ring-teal" @checked(old('remember'))>
                <span>Remember me</span>
            </label>

            <a href="{{ route('password.request') }}" class="tmc-link">Forgot password?</a>
        </div>

        <button type="submit" class="tmc-button-gold">Sign In</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        New here?
        <a href="{{ route('register') }}" class="tmc-link">Create an account</a>
    </p>
@endsection
