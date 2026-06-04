<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TMC - Welcome</title>
  <style>
    body {
      font-family: sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      background: #FAF8F3;
      color: #1C1A17;
    }
    .card {
      text-align: center;
      padding: 3rem;
      background: white;
      border-radius: 8px;
      border: 1px solid #E2E8F0;
    }
    h1 { color: #1A6B72; font-size: 1.5rem; margin-bottom: 0.5rem; }
    p { color: #6B6760; font-size: 0.9rem; }
    a { color: #C8A84B; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Welcome to The Muhsinat Club</h1>
    <p>Logged in as: {{ auth()->user()->name }}</p>
    <p style="margin-top:1rem">
      Member app coming soon.
      <a href="/admin">Admin panel</a>
    </p>
    <form method="POST" action="/logout" style="margin-top:1rem">
      @csrf
      <button type="submit"
        style="background:#1A6B72;color:white;border:none;
               padding:8px 20px;border-radius:4px;cursor:pointer">
        Logout
      </button>
    </form>
  </div>
</body>
</html>
