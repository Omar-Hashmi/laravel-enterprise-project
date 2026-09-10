@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $isDashboard = request()->routeIs('dashboard');
    $currentStatus = request()->query('status');
    $assignedToMe = request()->boolean('assigned_to_me') || request()->query('assigned') === 'me';
@endphp

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[280px] -translate-x-full flex-col bg-[#142321] px-4 py-5 text-white transition-transform duration-300 overflow-y-auto lg:static lg:translate-x-0">
    <!-- Brand Header -->
    <div class="flex items-center justify-between px-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-[14px] bg-[#c8f3dc] text-[#142321] shadow-[0_0_0_5px_rgba(200,243,220,0.12)]">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="3" r="2"/><circle cx="6" cy="21" r="2"/><circle cx="18" cy="21" r="2"/></svg>
            </span>
            <span>
                <span class="block text-[17px] font-semibold tracking-[-0.03em]">Flowline</span>
                <span class="block text-[10px] uppercase tracking-[0.2em] text-[#8fa09b]">Operations OS</span>
            </span>
        </a>
        <button id="mobile-close" class="rounded-lg p-2 text-[#8fa09b] hover:bg-white/10 lg:hidden" title="Close navigation">
            <span class="text-xl leading-none">&times;</span>
        </button>
    </div>

    <!-- Workspace Info & Quick Notifications -->
    <div class="mt-6 rounded-2xl border border-white/10 bg-white/[0.055] p-3">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#f5c96b] text-sm font-bold text-[#473718]">AC</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">Acme Corporation</p>
                <p class="truncate text-xs text-[#8fa09b]">Operations workspace</p>
            </div>
            <a href="#notifications" id="sidebar-bell-btn" class="relative grid h-8 w-8 place-items-center rounded-lg text-[#8fa09b] hover:bg-white/10 hover:text-white transition" title="Notifications">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <span id="sidebar-unread-badge" class="{{ $unreadCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#f47c6b] px-1 text-[9px] font-bold text-white">{{ $unreadCount }}</span>
            </a>
        </div>
    </div>

    <!-- Navigation Groups -->
    <nav class="mt-6 flex-1 space-y-6" aria-label="Main navigation">
        <!-- 1. Workspace Core (Developer 1) -->
        <div>
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#687b75]">Workspace</p>
            <div class="space-y-1">
                @if($isDashboard)
                    <button class="sidebar-link is-active flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-view="overview">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                        Overview
                    </button>
                    <button class="sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-view="workflows">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="3" r="2"/><circle cx="6" cy="21" r="2"/><circle cx="18" cy="21" r="2"/></svg></span>
                        Workflows
                    </button>
                    <button class="sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-view="forms">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                        Dynamic forms
                    </button>
                    @canany(['workflow.approve', 'workflow.manage'])
                        <button class="sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-view="approvals">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg></span>
                            My approvals
                            <span id="approval-count" class="ml-auto rounded-full bg-[#f47c6b] px-2 py-0.5 text-[10px] font-bold text-[#351714]">3</span>
                        </button>
                    @endcanany
                    @can('audit.view')
                        <button class="sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-view="audit">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                            Audit trail
                        </button>
                    @endcan
                @else
                    <a href="{{ route('dashboard', ['view' => 'overview']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                        Overview
                    </a>
                    <a href="{{ route('dashboard', ['view' => 'workflows']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="3" r="2"/><circle cx="6" cy="21" r="2"/><circle cx="18" cy="21" r="2"/></svg></span>
                        Workflows
                    </a>
                    <a href="{{ route('dashboard', ['view' => 'forms']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
                        Dynamic forms
                    </a>
                    @canany(['workflow.approve', 'workflow.manage'])
                        <a href="{{ route('dashboard', ['view' => 'approvals']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg></span>
                            My approvals
                        </a>
                    @endcanany
                    @can('audit.view')
                        <a href="{{ route('dashboard', ['view' => 'audit']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                            Audit trail
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <!-- 2. Task Operations (Developer 2) -->
        <div>
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#687b75]">Task Operations</p>
            <div class="space-y-1">
                <a href="{{ route('tasks.index', ['assigned_to_me' => 1]) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ (request()->routeIs('tasks.*') && $assignedToMe) ? 'bg-white/15 text-white font-semibold' : 'text-[#c0d0cb] hover:bg-white/10 hover:text-white' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    My Tasks
                </a>

                <a href="{{ route('tasks.index', ['status' => 'pending']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ (request()->routeIs('tasks.*') && $currentStatus === 'pending') ? 'bg-white/15 text-white font-semibold' : 'text-[#c0d0cb] hover:bg-white/10 hover:text-white' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                    </span>
                    Pending Tasks
                </a>

                <a href="{{ route('tasks.index', ['status' => 'delegated']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ (request()->routeIs('tasks.*') && $currentStatus === 'delegated') ? 'bg-white/15 text-white font-semibold' : 'text-[#c0d0cb] hover:bg-white/10 hover:text-white' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                    </span>
                    Delegated Tasks
                </a>

                <a href="{{ route('tasks.index', ['status' => 'overdue']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ (request()->routeIs('tasks.*') && $currentStatus === 'overdue') ? 'bg-[#f47c6b]/20 text-[#f47c6b] font-semibold' : 'text-[#f47c6b] hover:bg-white/10' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#f47c6b]/15 text-[#f47c6b]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </span>
                    Overdue Tasks
                </a>
            </div>
        </div>

        <!-- 3. Monitoring & Analytics (Admin / Manager Only) -->
        @canany(['workflow.manage', 'workflow.approve', 'audit.view'])
        <div>
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#687b75]">Monitoring & Analytics</p>
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-white/15 text-white font-semibold' : 'text-[#c0d0cb] hover:bg-white/10 hover:text-white' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#c8f3dc]/15 text-[#c8f3dc]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    </span>
                    Admin Dashboard
                </a>

                <a href="{{ route('analytics.index') }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('analytics.index') ? 'bg-white/15 text-white font-semibold' : 'text-[#c0d0cb] hover:bg-white/10 hover:text-white' }}">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#b9e7ed]/15 text-[#b9e7ed]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                    </span>
                    Workflow Analytics
                </a>

                <a href="/horizon" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-[#c0d0cb] hover:bg-white/10 hover:text-white transition">
                    <span class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-purple-500/15 text-purple-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        </span>
                        Queue Monitor
                    </span>
                    <svg class="h-3.5 w-3.5 text-[#8fa09b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>

                <a href="/telescope" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-[#c0d0cb] hover:bg-white/10 hover:text-white transition">
                    <span class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-blue-500/15 text-blue-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        System Telescope
                    </span>
                    <svg class="h-3.5 w-3.5 text-[#8fa09b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>
        </div>
        @endcanany

        <!-- 4. Reports & Exports -->
        <div>
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#687b75]">Reports & Exports</p>
            <div class="space-y-1">
                <a href="/api/v1/reports/compliance/pdf" target="_blank" rel="noopener noreferrer" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-[#c0d0cb] hover:bg-white/10 hover:text-white transition">
                    <span class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#f47c6b]/15 text-[#f47c6b]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </span>
                        Compliance PDF
                    </span>
                    <span class="rounded bg-white/10 px-1.5 py-0.5 text-[9px] uppercase font-bold text-[#8fa09b]">PDF</span>
                </a>

                <a href="/api/v1/reports/tasks/excel" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-[#c0d0cb] hover:bg-white/10 hover:text-white transition">
                    <span class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#c8f3dc]/15 text-[#c8f3dc]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h8"/></svg>
                        </span>
                        Tasks Export
                    </span>
                    <span class="rounded bg-white/10 px-1.5 py-0.5 text-[9px] uppercase font-bold text-[#8fa09b]">XLSX</span>
                </a>

                <a href="/api/v1/reports/analytics/excel" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-medium text-[#c0d0cb] hover:bg-white/10 hover:text-white transition">
                    <span class="flex items-center gap-3">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-[#b9e7ed]/15 text-[#b9e7ed]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        </span>
                        Analytics Summary
                    </span>
                    <span class="rounded bg-white/10 px-1.5 py-0.5 text-[9px] uppercase font-bold text-[#8fa09b]">XLSX</span>
                </a>
            </div>
        </div>

        <!-- 5. Manage / Submit Action -->
        @can('form.submit')
        <div>
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#687b75]">Manage</p>
            <div class="space-y-1">
                @if($isDashboard)
                    <button class="sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium hover:bg-white/10 transition" data-submit-request>
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg></span>
                        Submit a request
                    </button>
                @else
                    <a href="{{ route('dashboard', ['action' => 'submit']) }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium hover:bg-white/10 transition">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/10"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg></span>
                        Submit a request
                    </a>
                @endif
            </div>
        </div>
        @endcan
    </nav>

    <!-- User Profile & Sign Out -->
    <div class="mt-4 border-t border-white/10 pt-4">
        <div class="flex items-center gap-3 rounded-xl px-2 py-2">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-[#b9e7ed] text-xs font-bold text-[#28565d]">
                {{ $user ? str($user->name)->substr(0, 2)->upper() : 'US' }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $user->name ?? 'User' }}</p>
                <p class="truncate text-xs text-[#8fa09b]">{{ $user->getRoleNames()->first() ?? 'Member' }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-[#8fa09b] hover:bg-white/10 hover:text-white transition" title="Sign out">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-5"/></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
