@extends('layouts.app', ['title' => 'Executive Admin Dashboard'])

@section('content')
<div class="space-y-8">
    <!-- Header Title & Description -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-[#bfe8d0] bg-[#effcf4] px-3 py-1 text-xs font-semibold text-[#197a51]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#2eb875]"></span>
                Operations Command Center
            </div>
            <h2 class="text-3xl font-semibold tracking-[-0.04em] text-[#142321] md:text-4xl">Admin Dashboard</h2>
            <p class="mt-1 text-sm text-[#71817c]">Real-time operational health, SLA monitoring, and cross-department throughput.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/horizon" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-[#cddbd5] bg-white px-4 py-2.5 text-xs font-semibold text-[#30443f] shadow-sm hover:bg-[#f5faf7] transition">
                <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                Horizon Queue Monitor &rarr;
            </a>
            <a href="/telescope" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-[#cddbd5] bg-white px-4 py-2.5 text-xs font-semibold text-[#30443f] shadow-sm hover:bg-[#f5faf7] transition">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                Telescope Debugger &rarr;
            </a>
            <a href="/api/v1/reports/compliance/pdf" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-[#142321] px-4 py-2.5 text-xs font-semibold text-white shadow hover:bg-[#24403a] transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Audit PDF Report
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#1d9a65]">Active</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Active Tasks</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['active_tasks'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#ffe4df] text-[#a64d40]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#a64d40]">Breached</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Overdue</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['overdue_tasks'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e3f5f7] text-[#24737d]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#24737d]">Running</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Workflows</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['running_workflows'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#fff2c9] text-[#8a6512]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#8a6512]">Pending</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Approvals</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['pending_approvals'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#1d9a65]">Velocity</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Completed 24h</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['completed_24h'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                <span class="text-[11px] font-bold text-[#1d9a65]">% Ratio</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">24h Velocity</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $kpis['completion_rate_24h'] }}%</p>
        </div>
    </div>

    <!-- Two Column Layout: Delayed Processes & Approval Statistics -->
    <div class="grid gap-6 xl:grid-cols-3">
        <!-- Delayed Processes (2 cols) -->
        <div class="xl:col-span-2 rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between border-b border-[#edf2ef] pb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#a64d40]">Attention Required</p>
                    <h3 class="mt-1 text-xl font-semibold text-[#142321]">Delayed Processes (SLA Breaches)</h3>
                </div>
                <a href="{{ route('tasks.index', ['status' => 'overdue']) }}" class="text-xs font-semibold text-[#1d9a65] hover:underline">
                    View all overdue &rarr;
                </a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#edf2ef] text-[11px] font-semibold uppercase tracking-wider text-[#8a9994]">
                            <th class="py-2.5 px-3">Process</th>
                            <th class="py-2.5 px-3">Assignee</th>
                            <th class="py-2.5 px-3">Priority</th>
                            <th class="py-2.5 px-3">Delay</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf2ef]">
                        @forelse($delayedProcesses as $proc)
                            <tr class="hover:bg-[#f8fbf9] transition">
                                <td class="py-3 px-3">
                                    <p class="font-medium text-[#142321]">{{ $proc['title'] }}</p>
                                    <p class="text-[10px] text-[#8a9994]">UUID: {{ substr($proc['uuid'], 0, 8) }}...</p>
                                </td>
                                <td class="py-3 px-3 text-xs text-[#526560]">{{ $proc['assignee'] }}</td>
                                <td class="py-3 px-3">
                                    <span class="rounded bg-[#ffe4df] px-2 py-0.5 text-[10px] font-bold text-[#a64d40]">
                                        {{ $proc['priority'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-xs font-semibold text-[#a64d40]">
                                    {{ $proc['delayed_hours'] > 0 ? "+{$proc['delayed_hours']}h overdue" : 'Due soon' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-xs text-[#8a9994]">
                                    No overdue processes! All SLAs are healthy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Approval Statistics (1 col) -->
        <div class="rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="border-b border-[#edf2ef] pb-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-[#1d9a65]">Governance</p>
                <h3 class="mt-1 text-xl font-semibold text-[#142321]">Approval Statistics</h3>
            </div>
            <div class="mt-6 space-y-4">
                <div class="flex items-center justify-between rounded-xl bg-[#f8fbf9] p-3 border border-[#edf2ef]">
                    <span class="text-xs font-medium text-[#526560]">Approved Assignments</span>
                    <span class="text-base font-bold text-[#197a51]">{{ $approvalStats['approved'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-[#f8fbf9] p-3 border border-[#edf2ef]">
                    <span class="text-xs font-medium text-[#526560]">Rejected Assignments</span>
                    <span class="text-base font-bold text-[#a64d40]">{{ $approvalStats['rejected'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-[#f8fbf9] p-3 border border-[#edf2ef]">
                    <span class="text-xs font-medium text-[#526560]">Pending Approvals</span>
                    <span class="text-base font-bold text-[#8a6512]">{{ $approvalStats['pending'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-[#f8fbf9] p-3 border border-[#edf2ef]">
                    <span class="text-xs font-medium text-[#526560]">Escalated Assignments</span>
                    <span class="text-base font-bold text-[#712b2b]">{{ $approvalStats['escalated'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-[#f8fbf9] p-3 border border-[#edf2ef]">
                    <span class="text-xs font-medium text-[#526560]">Delegated Tasks</span>
                    <span class="text-base font-bold text-[#24737d]">{{ $approvalStats['delegated'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent User Activity Stream -->
    <div class="rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
        <div class="flex items-center justify-between border-b border-[#edf2ef] pb-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#8a9994]">Audit Stream</p>
                <h3 class="mt-1 text-xl font-semibold text-[#142321]">Recent User Activity</h3>
            </div>
            <a href="/api/v1/reports/tasks/excel" class="text-xs font-semibold text-[#1d9a65] hover:underline">
                Export Activity Log &rarr;
            </a>
        </div>
        <div class="mt-4 divide-y divide-[#edf2ef]">
            @forelse($userActivity as $act)
                <div class="flex items-center justify-between py-3.5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[#142321]">{{ $act['title'] }}</p>
                            <p class="text-xs text-[#71817c]">
                                <span class="font-medium text-[#30443f]">{{ $act['actor'] }}</span> ·
                                <span class="capitalize">{{ $act['status'] }}</span>
                                @if(!empty($act['action_notes']))
                                    · "{{ Str::limit($act['action_notes'], 50) }}"
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="text-xs text-[#8a9994] shrink-0">
                        {{ \Illuminate\Support\Carbon::parse($act['timestamp'])->diffForHumans() }}
                    </span>
                </div>
            @empty
                <div class="py-8 text-center text-xs text-[#8a9994]">No user activity recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
