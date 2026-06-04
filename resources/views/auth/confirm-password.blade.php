@extends('layouts.auth', ['title' => 'Confirm Password'])

@section('content')
    <h1 class="tmc-auth-heading">Confirm your password</h1>
    <p class="tmc-auth-copy">Please confirm your password before continuing to this secure area.</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="password" class="tmc-label">Password</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="tmc-input">
            @error('password') <p class="tmc-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="tmc-button-gold">Confirm Password</button>
    </form>
@endsection
