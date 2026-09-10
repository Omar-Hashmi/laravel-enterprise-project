@extends('layouts.app', ['title' => 'Workflow Analytics'])

@section('content')
<div class="space-y-8">
    <!-- Header Title & Description -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-[#bfe8d0] bg-[#effcf4] px-3 py-1 text-xs font-semibold text-[#197a51]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#2eb875]"></span>
                Business Process Intelligence
            </div>
            <h2 class="text-3xl font-semibold tracking-[-0.04em] text-[#142321] md:text-4xl">Workflow Analytics</h2>
            <p class="mt-1 text-sm text-[#71817c]">Deep insights into cycle times, SLA bottlenecks, and organizational throughput.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/api/v1/reports/analytics/excel" class="inline-flex items-center gap-2 rounded-xl border border-[#cddbd5] bg-white px-4 py-2.5 text-xs font-semibold text-[#30443f] shadow-sm hover:bg-[#f5faf7] transition">
                <svg class="h-4 w-4 text-[#197a51]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><rect x="8" y="12" width="8" height="6" rx="1"/></svg>
                Export Analytics Excel
            </a>
            <a href="/api/v1/reports/compliance/pdf" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-[#142321] px-4 py-2.5 text-xs font-semibold text-white shadow hover:bg-[#24403a] transition">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Compliance Audit PDF
            </a>
        </div>
    </div>

    <!-- Cycle Time & SLA Cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <p class="text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Average Cycle Time</p>
            <p class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#142321]">
                {{ $completionStats['avg_duration_minutes'] }} <span class="text-sm font-normal text-[#71817c]">min</span>
            </p>
            <p class="mt-2 text-xs text-[#8a9994]">Across {{ $completionStats['completed_count'] }} completed tasks</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <p class="text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Median Duration</p>
            <p class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#142321]">
                {{ $completionStats['median_duration_minutes'] }} <span class="text-sm font-normal text-[#71817c]">min</span>
            </p>
            <p class="mt-2 text-xs text-[#8a9994]">Min: {{ $completionStats['min_duration_minutes'] }}m · Max: {{ $completionStats['max_duration_minutes'] }}m</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <p class="text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">System SLA Compliance</p>
            <p class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#197a51]">
                {{ $slaCompliance['compliance_rate'] }}%
            </p>
            <p class="mt-2 text-xs text-[#8a9994]">{{ $slaCompliance['on_time_tasks'] }} of {{ $slaCompliance['total_tasks'] }} on-time</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <p class="text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">7-Day Process Efficiency</p>
            <p class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-[#24737d]">
                {{ $efficiency['efficiency_ratio'] }}%
            </p>
            <p class="mt-2 text-xs text-[#8a9994]">{{ $efficiency['tasks_completed'] }} completed / {{ $efficiency['tasks_created'] }} created</p>
        </div>
    </div>

    <!-- Department Performance Table -->
    <div class="rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
        <div class="border-b border-[#edf2ef] pb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#1d9a65]">Organizational Units</p>
            <h3 class="mt-1 text-xl font-semibold text-[#142321]">Department & Role Performance</h3>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[#edf2ef] text-[11px] font-semibold uppercase tracking-wider text-[#8a9994]">
                        <th class="py-3 px-4">Department / Role</th>
                        <th class="py-3 px-4">Members</th>
                        <th class="py-3 px-4">Total Tasks</th>
                        <th class="py-3 px-4">Completed</th>
                        <th class="py-3 px-4">Overdue</th>
                        <th class="py-3 px-4">Completion Rate</th>
                        <th class="py-3 px-4">SLA Compliance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    @forelse($departmentPerformance as $dept)
                        <tr class="hover:bg-[#f8fbf9] transition">
                            <td class="py-3.5 px-4 font-semibold text-[#142321]">{{ $dept['department'] }}</td>
                            <td class="py-3.5 px-4 text-xs text-[#526560]">{{ $dept['member_count'] }}</td>
                            <td class="py-3.5 px-4 text-xs font-medium">{{ $dept['total_tasks'] }}</td>
                            <td class="py-3.5 px-4 text-xs text-[#197a51] font-semibold">{{ $dept['completed_tasks'] }}</td>
                            <td class="py-3.5 px-4 text-xs text-[#a64d40] font-semibold">{{ $dept['overdue_tasks'] }}</td>
                            <td class="py-3.5 px-4">
                                <span class="rounded-full bg-[#e1f7eb] px-2.5 py-0.5 text-xs font-bold text-[#197a51]">
                                    {{ $dept['completion_rate'] }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="rounded-full {{ $dept['sla_compliance_rate'] >= 90 ? 'bg-[#e1f7eb] text-[#197a51]' : 'bg-[#ffe4df] text-[#a64d40]' }} px-2.5 py-0.5 text-xs font-bold">
                                    {{ $dept['sla_compliance_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-[#8a9994]">No department metrics available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottlenecks Detection Table -->
    <div class="rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
        <div class="border-b border-[#edf2ef] pb-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-[#a64d40]">Root Cause Identification</p>
            <h3 class="mt-1 text-xl font-semibold text-[#142321]">Operational Bottlenecks</h3>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[#edf2ef] text-[11px] font-semibold uppercase tracking-wider text-[#8a9994]">
                        <th class="py-3 px-4">Assignee</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Pending Tasks</th>
                        <th class="py-3 px-4">Overdue Tasks</th>
                        <th class="py-3 px-4">Overdue Ratio</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    @forelse($bottlenecks as $bn)
                        <tr class="hover:bg-[#f8fbf9] transition">
                            <td class="py-3.5 px-4">
                                <p class="font-semibold text-[#142321]">{{ $bn['name'] }}</p>
                                <p class="text-xs text-[#8a9994]">{{ $bn['email'] }}</p>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-[#526560]">{{ $bn['role'] }}</td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-[#8a6512]">{{ $bn['pending_tasks_count'] }}</td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-[#a64d40]">{{ $bn['overdue_tasks_count'] }}</td>
                            <td class="py-3.5 px-4">
                                <span class="rounded-full bg-[#ffe4df] px-2.5 py-0.5 text-xs font-bold text-[#a64d40]">
                                    {{ $bn['overdue_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-xs text-[#8a9994]">
                                No bottlenecks detected! Tasks are progressing normally.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
