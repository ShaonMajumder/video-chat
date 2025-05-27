@extends('layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    .login-container {
        background: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-width: 400px;
        margin: 3rem auto;
    }

    h1 {
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #111827;
        text-align: center;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    input[type="email"],
    input[type="password"] {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 1rem;
        transition: border-color 0.2s ease;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }

    button[type="submit"] {
        background-color: #2563eb;
        color: white;
        font-weight: 700;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background-color 0.3s ease;
    }

    button[type="submit"]:hover {
        background-color: #1e40af;
    }

    .error-messages {
        background: #fee2e2;
        color: #b91c1c;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div class="login-container" role="main" aria-labelledby="login-title">
    <h1 id="login-title">Login to Your Account</h1>

    <form id="loginForm" novalidate>
        <input type="email" name="email" placeholder="Email address" required autocomplete="email" />
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <button type="submit">Login</button>
    </form>

    <div id="error-container" class="error-messages" style="display: none;" role="alert" aria-live="assertive"></div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = e.target;
    const email = form.email.value;
    const password = form.password.value;
    const errorContainer = document.getElementById('error-container');

    errorContainer.style.display = 'none';
    errorContainer.innerHTML = '';

    try {
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ email, password }),
            credentials: 'same-origin' // allow Laravel to set the HttpOnly cookie
        });

        const data = await response.json();
        if (response.ok) {
            window.location.href = '/';
        } else {
            const message = data.message || 'Login failed';
            errorContainer.innerHTML = `<p>${message}</p>`;
            errorContainer.style.display = 'block';
        }
    } catch (error) {
        errorContainer.innerHTML = `<p>Something went wrong. Please try again.</p>`;
        errorContainer.style.display = 'block';
    }
});

</script>
@endsection
