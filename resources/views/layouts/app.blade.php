<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="app-env" content="{{ app()->environment() }}">
    <title>@yield('title') - Video Chat App</title>

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />

    <style>
        /* Reset & base */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        header.site-header {
            background: #2563eb;
            color: white;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.25rem;
            user-select: none;
        }

        main.site-content {
            flex-grow: 1;
            padding: 2rem;
            max-width: 700px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        footer.site-footer {
            text-align: center;
            padding: 1rem 2rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
    </style>

    @yield('styles')

    <script src="https://cdn.jsdelivr.net/npm/dompurify@2.5.7/dist/purify.min.js"></script> <!-- Add DOMPurify -->
</head>
<body>
    <header class="site-header">
        Video Chat App
    </header>

    <main class="site-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        &copy; {{ date('Y') }} Video Chat App. All rights reserved.
    </footer>

    @yield('scripts')
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
