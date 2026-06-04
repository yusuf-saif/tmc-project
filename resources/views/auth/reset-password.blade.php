@extends('layouts.auth', ['title' => 'Reset Password'])

@section('content')
    <h1 class="tmc-auth-heading">Choose a new password</h1>
    <p class="tmc-auth-copy">Set a new password for your TMC account.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="tmc-label">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus class="tmc-input">
            @error('email') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="tmc-label">New Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="tmc-input">
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="tmc-label">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="tmc-input">
        </div>

        <button type="submit" class="tmc-button-gold">Reset Password</button>
    </form>
@endsection
