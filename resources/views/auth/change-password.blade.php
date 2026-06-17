@extends('layouts.auth', ['title' => 'Change Password'])

@section('content')
    <h1 class="tmc-auth-heading">Change your password</h1>
    <p class="tmc-auth-copy">Enter your current password and choose a new one.</p>

    @if (session('status') === 'password-updated')
        <p class="mt-6 rounded-sm bg-gold-pale px-4 py-3 text-sm text-teal-dk">Your password has been updated.</p>
    @endif

    <form method="POST" action="{{ route('user-password.update') }}" class="mt-8 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="tmc-label">Current Password</label>
            <input id="current_password" name="current_password" type="password" required autofocus class="tmc-input">
            @error('current_password', 'updatePassword') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="tmc-label">New Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="tmc-input">
            @error('password', 'updatePassword') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="tmc-label">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="tmc-input">
        </div>

        <button type="submit" class="tmc-button-gold">Update Password</button>
    </form>
@endsection
