@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $recentNotifications = $user ? $user->notifications()->take(5)->get() : collect();
@endphp

<header class="flex h-[76px] items-center justify-between border-b border-[#dce6e1] bg-[#f8fbf9]/90 px-5 backdrop-blur md:px-8">
    <div class="flex items-center gap-3">
        <button id="mobile-menu" class="rounded-xl border border-[#dce6e1] bg-white p-2.5 text-[#526560] lg:hidden" title="Open navigation">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
            <p class="text-xs font-medium text-[#84938e]">{{ now()->format('l, F j, Y') }}</p>
            <h1 id="page-title" class="mt-0.5 text-lg font-semibold tracking-[-0.03em] text-[#142321]">{{ $title ?? 'Overview' }}</h1>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <!-- Search -->
        <label class="hidden items-center gap-2 rounded-xl border border-[#dce6e1] bg-white px-3 py-2 text-sm text-[#91a09b] md:flex">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input id="search" class="w-36 bg-transparent text-sm text-[#30443f] outline-none placeholder:text-[#91a09b]" placeholder="Search..." />
        </label>

        <!-- Theme Toggle -->
        <button id="theme-toggle" class="rounded-xl border border-[#dce6e1] bg-white p-2.5 text-[#526560] hover:bg-[#f1f6f3] transition" title="Toggle theme">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>
        </button>

        <!-- Notification Bell with Dropdown -->
        <div class="relative" id="notification-dropdown-container">
            <button id="notification-bell-btn" class="relative rounded-xl border border-[#dce6e1] bg-white p-2.5 text-[#526560] hover:bg-[#f1f6f3] transition" title="Notifications">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <span id="header-unread-badge" class="{{ $unreadCount > 0 ? '' : 'hidden' }} absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#f47c6b] px-1 text-[9px] font-bold text-white">{{ $unreadCount }}</span>
            </button>

            <!-- Notifications Dropdown -->
            <div id="notification-dropdown" class="absolute right-0 top-12 z-50 hidden w-80 rounded-2xl border border-[#dce6e1] bg-white p-4 shadow-2xl transition">
                <div class="flex items-center justify-between border-b border-[#edf2ef] pb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#142321]">Notifications</span>
                    <button id="mark-all-read-btn" class="text-xs font-semibold text-[#1d9a65] hover:underline">Mark all read</button>
                </div>
                <div class="mt-2 max-h-64 divide-y divide-[#edf2ef] overflow-y-auto" id="notification-items-list">
                    @forelse($recentNotifications as $notif)
                        <div class="py-2.5 text-left {{ $notif->read_at ? 'opacity-60' : '' }}">
                            <p class="text-xs font-semibold text-[#142321]">{{ $notif->data['title'] ?? 'Task update' }}</p>
                            <p class="text-[11px] text-[#71817c]">{{ $notif->data['message'] ?? 'Notification received' }}</p>
                            <span class="text-[10px] text-[#8fa09b]">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-xs text-[#8fa09b]">No notifications found.</p>
                    @endforelse
                </div>
                <div class="mt-3 border-t border-[#edf2ef] pt-2 text-center">
                    <a href="{{ route('tasks.index') }}" class="text-xs font-medium text-[#1d9a65] hover:underline">View open tasks &rarr;</a>
                </div>
            </div>
        </div>

        <!-- User Avatar -->
        <span class="grid h-9 w-9 place-items-center rounded-full bg-[#142321] text-xs font-bold text-[#c8f3dc]">
            {{ $user ? str($user->name)->substr(0, 2)->upper() : 'US' }}
        </span>
    </div>
</header>
