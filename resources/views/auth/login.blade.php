<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Flowline</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <main class="grid min-h-screen lg:grid-cols-[0.9fr_1.1fr]">
        <section class="relative hidden overflow-hidden bg-[#142321] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div class="absolute -right-36 -top-32 h-[28rem] w-[28rem] rounded-full border-[44px] border-[#c8f3dc]/10"></div>
            <div class="relative"><a href="{{ route('login') }}" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-[14px] bg-[#c8f3dc] text-[#142321]"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="3" r="2"/><circle cx="6" cy="21" r="2"/><circle cx="18" cy="21" r="2"/></svg></span><span><span class="block text-[17px] font-semibold">Flowline</span><span class="block text-[10px] uppercase tracking-[0.2em] text-[#8fa09b]">Operations OS</span></span></a></div>
            <div class="relative max-w-lg"><p class="mb-5 text-xs font-semibold uppercase tracking-[0.2em] text-[#8ed8ae]">The work behind the work</p><h1 class="text-5xl font-semibold leading-[1.02] tracking-[-0.06em] xl:text-6xl">Move every request with confidence.</h1><p class="mt-6 max-w-md text-base leading-7 text-[#b3c5be]">A calm command center for workflows, approvals, forms, and the decisions that keep your organization moving.</p><div class="mt-10 flex items-center gap-3 text-sm text-[#d9ebe2]"><span class="grid h-9 w-9 place-items-center rounded-full bg-[#f5c96b] text-xs font-bold text-[#473718]">AC</span><span>Acme Corporation workspace</span></div></div>
            <p class="relative text-xs text-[#71857d]">Secure workspace access with role-based controls.</p>
        </section>
        <section class="flex min-h-screen items-center justify-center bg-[#f3f7f4] px-5 py-10 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-10 flex items-center justify-between lg:justify-end"><a href="{{ route('login') }}" class="flex items-center gap-2 lg:hidden"><span class="grid h-8 w-8 place-items-center rounded-xl bg-[#142321] text-[#c8f3dc]"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/></svg></span><span class="font-semibold text-[#142321]">Flowline</span></a><button id="theme-toggle" class="rounded-xl border border-[#dce6e1] bg-white p-2.5 text-[#526560]" title="Toggle theme"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg></button></div>
                <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#1d9a65]">Welcome back</p><h2 class="mt-2 text-3xl font-semibold tracking-[-0.05em] text-[#142321]">Sign in to Flowline</h2><p class="mt-3 text-sm leading-6 text-[#71817c]">Use your workspace account to see the workflows and actions assigned to you.</p></div>
                @if ($errors->any())<div class="mb-5 rounded-xl border border-[#f4c2b9] bg-[#fff5f2] p-3 text-sm text-[#a64d40]">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf<label class="block"><span class="text-sm font-semibold text-[#30443f]">Email address</span><input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm text-[#142321] outline-none transition focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]" placeholder="you@company.com"><span id="detected-role" class="mt-2 block text-xs text-[#91a09b]">Role is detected from your account.</span></label><label class="block"><span class="text-sm font-semibold text-[#30443f]">Password</span><input type="password" name="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm text-[#142321] outline-none transition focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]" placeholder="Enter your password"></label><div class="flex items-center justify-between"><label class="flex items-center gap-2 text-xs text-[#71817c]"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-[#cddbd5] text-[#1d9a65]">Remember me</label><span class="text-xs text-[#91a09b]">Protected by workspace policy</span></div><button class="w-full rounded-xl bg-[#142321] px-4 py-3.5 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(20,35,33,0.18)] transition hover:bg-[#24403a]" type="submit">Sign in</button></form>
                <p class="mt-8 text-center text-sm text-[#71817c]">New to Flowline? <a href="{{ route('register') }}" class="font-semibold text-[#1d9a65] hover:underline">Create an account</a></p>
            </div>
        </section>
    </main>
</body>
</html>
