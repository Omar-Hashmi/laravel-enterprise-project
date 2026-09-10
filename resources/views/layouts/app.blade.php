<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Flowline' }} | Operations OS</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-theme="light">
    <div class="app-shell min-h-screen lg:flex">
        @include('partials.sidebar')

        <div id="sidebar-scrim" class="fixed inset-0 z-30 hidden bg-[#142321]/40 lg:hidden"></div>

        <main class="min-w-0 flex-1">
            @include('partials.header', ['title' => $title ?? 'Overview'])

            <div class="mx-auto max-w-[1500px] p-5 md:p-8">
                @if(session('success'))
                    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-[#bfe8d0] bg-[#f1fff6] p-4 text-sm font-medium text-[#197a51] shadow-sm">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 flex items-center gap-3 rounded-2xl border border-[#f4c2b9] bg-[#fff5f2] p-4 text-sm font-medium text-[#a64d40] shadow-sm">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Reusable Modal Container -->
    <div id="modal" class="modal-backdrop fixed inset-0 z-50 hidden overflow-y-auto p-4">
        <div class="flex min-h-full items-center justify-center">
            <div id="modal-content" class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl md:p-8"></div>
        </div>
    </div>

    <script>
        window.flowlineContext = @json([
            'role' => auth()->user()?->getRoleNames()->first(),
            'permissions' => auth()->user()?->getAllPermissions()->pluck('name')->values() ?? []
        ]);
    </script>
    @stack('scripts')
</body>
</html>
