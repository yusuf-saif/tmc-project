@extends('layouts.auth', ['title' => 'Register'])

@section('content')
    <script>
        window.location.href = '{{ route("membership.signup") }}{{ request()->has("ref") ? "?ref=" . request("ref") : "" }}';
    </script>
    <h1 class="tmc-auth-heading">Join the club</h1>
    <p class="tmc-auth-copy">Redirecting to signup...</p>
    <p class="mt-6 text-center text-sm text-ink-soft">
        <a href="{{ route('membership.signup') }}" class="tmc-link">Click here if not redirected</a>
    </p>
@endsection
