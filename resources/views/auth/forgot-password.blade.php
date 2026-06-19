@extends('layouts.auth', ['title' => 'Forgot Password'])

@section('content')
    <h1 class="tmc-auth-heading">Reset your password</h1>
    <p class="tmc-auth-copy">Enter your email and we will send you a secure reset link.</p>

    @if (session('status'))
        <p class="mt-6 rounded-sm bg-gold-pale px-4 py-3 text-sm text-teal-dk">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="tmc-label">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="tmc-input">
            @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="tmc-button-gold" x-data @click="$el.disabled=true;$el.textContent='Sending...'">Email Reset Link</button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-soft">
        Remembered it?
        <a href="{{ route('login') }}" class="tmc-link">Back to sign in</a>
    </p>
@endsection
