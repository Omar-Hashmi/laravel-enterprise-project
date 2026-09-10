@extends('layouts.app', ['title' => 'Task Operations'])

@section('content')
<div class="space-y-8">
    <!-- Header Title & Description -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full border border-[#bfe8d0] bg-[#effcf4] px-3 py-1 text-xs font-semibold text-[#197a51]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#2eb875]"></span>
                Developer 2 Operations Module
            </div>
            <h2 class="text-3xl font-semibold tracking-[-0.04em] text-[#142321] md:text-4xl">Task Operations</h2>
            <p class="mt-1 text-sm text-[#71817c]">Manage assignments, track SLA commitments, and execute workflow decisions.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/api/v1/reports/tasks/excel" class="inline-flex items-center gap-2 rounded-xl border border-[#cddbd5] bg-white px-4 py-2.5 text-xs font-semibold text-[#30443f] shadow-sm hover:bg-[#f5faf7] transition">
                <svg class="h-4 w-4 text-[#197a51]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h8M8 17h8"/></svg>
                Export Excel
            </a>
            <a href="/api/v1/reports/compliance/pdf" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-[#cddbd5] bg-white px-4 py-2.5 text-xs font-semibold text-[#30443f] shadow-sm hover:bg-[#f5faf7] transition">
                <svg class="h-4 w-4 text-[#a64d40]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Compliance PDF
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </span>
                <span class="text-xs font-semibold text-[#1d9a65]">Assigned to You</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">My Tasks</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $summary['assigned_count'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#fff2c9] text-[#8a6512]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span class="text-xs font-semibold text-[#8a6512]">Action Needed</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Pending</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $summary['pending_count'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#ffe4df] text-[#a64d40]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                <span class="text-xs font-semibold text-[#a64d40]">SLA Breached</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Overdue</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $summary['overdue_count'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e3f5f7] text-[#24737d]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </span>
                <span class="text-xs font-semibold text-[#24737d]">Reassigned</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Delegated</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $summary['delegated_count'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
            <div class="flex items-center justify-between">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#e1f7eb] text-[#197a51]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                <span class="text-xs font-semibold text-[#1d9a65]">Processed</span>
            </div>
            <p class="mt-4 text-xs font-medium uppercase tracking-[0.13em] text-[#8a9994]">Completed</p>
            <p class="mt-1 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">{{ $summary['completed_count'] }}</p>
        </div>
    </div>

    <!-- Filter Tabs & Tasks Table -->
    <div class="rounded-2xl border border-[#dce6e1] bg-white p-6 shadow-[0_8px_30px_rgba(32,62,52,0.04)]">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#edf2ef] pb-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tasks.index') }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ empty($status) && !request()->boolean('assigned_to_me') ? 'bg-[#142321] text-white' : 'bg-[#f3f7f4] text-[#526560] hover:bg-[#e6eee9]' }}">
                    All Tasks
                </a>
                <a href="{{ route('tasks.index', ['assigned_to_me' => 1]) }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ request()->boolean('assigned_to_me') ? 'bg-[#142321] text-white' : 'bg-[#f3f7f4] text-[#526560] hover:bg-[#e6eee9]' }}">
                    Assigned to Me ({{ $summary['assigned_count'] }})
                </a>
                <a href="{{ route('tasks.index', ['status' => 'pending']) }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $status === 'pending' ? 'bg-[#142321] text-white' : 'bg-[#f3f7f4] text-[#526560] hover:bg-[#e6eee9]' }}">
                    Pending ({{ $summary['pending_count'] }})
                </a>
                <a href="{{ route('tasks.index', ['status' => 'overdue']) }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $status === 'overdue' ? 'bg-[#f47c6b] text-white' : 'bg-[#ffe4df] text-[#a64d40] hover:bg-[#ffd7d0]' }}">
                    Overdue ({{ $summary['overdue_count'] }})
                </a>
                <a href="{{ route('tasks.index', ['status' => 'delegated']) }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $status === 'delegated' ? 'bg-[#142321] text-white' : 'bg-[#f3f7f4] text-[#526560] hover:bg-[#e6eee9]' }}">
                    Delegated ({{ $summary['delegated_count'] }})
                </a>
                <a href="{{ route('tasks.index', ['status' => 'completed']) }}" class="rounded-xl px-3 py-1.5 text-xs font-semibold transition {{ $status === 'completed' ? 'bg-[#142321] text-white' : 'bg-[#f3f7f4] text-[#526560] hover:bg-[#e6eee9]' }}">
                    Completed ({{ $summary['completed_count'] }})
                </a>
            </div>

            <p class="text-xs text-[#8a9994]">Showing {{ $tasks->count() }} of {{ $tasks->total() }} tasks</p>
        </div>

        <!-- Table -->
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[#edf2ef] text-[11px] font-semibold uppercase tracking-wider text-[#8a9994]">
                        <th class="py-3 px-4">Task / Process</th>
                        <th class="py-3 px-4">Assignee</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Due Date & SLA</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#edf2ef]">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-[#f8fbf9] transition">
                            <td class="py-4 px-4">
                                <p class="font-semibold text-[#142321]">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-[#71817c]">
                                    @if($task->workflowInstance)
                                        Workflow #{{ str_pad($task->workflow_instance_id, 4, '0', STR_PAD_LEFT) }} ·
                                    @endif
                                    {{ $task->description ?? 'No extra description' }}
                                </p>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-[#b9e7ed] text-[10px] font-bold text-[#28565d]">
                                        {{ $task->user ? str($task->user->name)->substr(0, 2)->upper() : 'UN' }}
                                    </span>
                                    <div>
                                        <p class="text-xs font-medium text-[#142321]">{{ $task->user->name ?? 'Unassigned' }}</p>
                                        @if($task->delegatedTo)
                                            <p class="text-[10px] text-[#24737d]">Delegated &rarr; {{ $task->delegatedTo->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $pClass = match(strtolower($task->priority)) {
                                        'urgent' => 'bg-[#ffe4df] text-[#a64d40]',
                                        'high' => 'bg-[#fff2c9] text-[#8a6512]',
                                        'low' => 'bg-[#e3f5f7] text-[#24737d]',
                                        default => 'bg-[#e1f7eb] text-[#197a51]',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $pClass }}">
                                    {{ $task->priority }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                @if($task->due_at)
                                    <p class="text-xs font-medium {{ $task->due_at->isPast() && $task->status !== 'completed' ? 'text-[#a64d40] font-semibold' : 'text-[#30443f]' }}">
                                        {{ $task->due_at->format('M j, Y g:i A') }}
                                    </p>
                                    <p class="text-[10px] text-[#8a9994]">{{ $task->due_at->diffForHumans() }}</p>
                                    @if($task->sla_breached)
                                        <span class="inline-block mt-0.5 rounded bg-[#ffe4df] px-1.5 py-0.2 text-[9px] font-bold text-[#a64d40]">BREACHED</span>
                                    @endif
                                @else
                                    <span class="text-xs text-[#8a9994]">No deadline</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $sClass = match(strtolower($task->status)) {
                                        'completed' => 'bg-[#e1f7eb] text-[#197a51]',
                                        'cancelled' => 'bg-slate-100 text-slate-500',
                                        'delegated' => 'bg-[#e3f5f7] text-[#24737d]',
                                        'in_progress' => 'bg-[#fff2c9] text-[#8a6512]',
                                        default => 'bg-[#e1f7eb] text-[#197a51]',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $sClass }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                @if($task->status !== 'completed' && $task->status !== 'cancelled')
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Complete Action Button -->
                                        <button onclick="openCompleteModal('{{ $task->uuid }}', '{{ addslashes($task->title) }}')" class="rounded-lg bg-[#142321] px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#24403a] transition">
                                            Complete
                                        </button>
                                        <!-- Delegate Action Button -->
                                        <button onclick="openDelegateModal('{{ $task->uuid }}', '{{ addslashes($task->title) }}')" class="rounded-lg border border-[#cddbd5] bg-white px-2.5 py-1.5 text-xs font-semibold text-[#526560] hover:bg-[#f5faf7] transition" title="Delegate">
                                            Delegate
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-[#8a9994]">Done</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-sm text-[#71817c]">
                                No tasks match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $tasks->links() }}
        </div>
    </div>
</div>

<!-- Modal: Complete Task -->
<div id="complete-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/40 backdrop-blur-sm p-4">
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl md:p-8">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#1d9a65]">Task Decision</p>
                    <h3 class="mt-1 text-xl font-semibold text-[#142321]">Complete Task</h3>
                    <p id="complete-modal-title" class="mt-1 text-xs text-[#71817c]"></p>
                </div>
                <button onclick="closeCompleteModal()" class="rounded-lg p-2 text-[#8fa09b] hover:bg-[#f1f6f3]">&times;</button>
            </div>
            <form id="complete-form" method="POST" class="mt-6 space-y-4">
                @csrf
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#30443f]">Action Notes / Approval Comments</span>
                    <textarea name="action_notes" rows="4" placeholder="Enter reason or notes for task completion..." class="mt-2 w-full resize-none rounded-xl border border-[#dce6e1] p-3 text-sm outline-none focus:border-[#1d9a65] focus:ring-4 focus:ring-[#c8f3dc]"></textarea>
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeCompleteModal()" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-[#526560] hover:bg-[#f1f6f3]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#142321] px-5 py-2.5 text-xs font-semibold text-white shadow hover:bg-[#24403a]">Confirm & Complete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Delegate Task -->
<div id="delegate-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/40 backdrop-blur-sm p-4">
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl md:p-8">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#24737d]">Task Reassignment</p>
                    <h3 class="mt-1 text-xl font-semibold text-[#142321]">Delegate Task</h3>
                    <p id="delegate-modal-title" class="mt-1 text-xs text-[#71817c]"></p>
                </div>
                <button onclick="closeDelegateModal()" class="rounded-lg p-2 text-[#8fa09b] hover:bg-[#f1f6f3]">&times;</button>
            </div>
            <form id="delegate-form" method="POST" class="mt-6 space-y-4">
                @csrf
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#30443f]">Select New Assignee</span>
                    <select name="target_user_id" required class="mt-2 w-full rounded-xl border border-[#dce6e1] p-3 text-sm outline-none focus:border-[#1d9a65] focus:ring-4 focus:ring-[#c8f3dc]">
                        <option value="">-- Choose User --</option>
                        @foreach($users as $userOption)
                            <option value="{{ $userOption->id }}">{{ $userOption->name }} ({{ $userOption->email }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider text-[#30443f]">Delegation Commentary</span>
                    <textarea name="action_notes" rows="3" placeholder="Explain why this task is being delegated..." class="mt-2 w-full resize-none rounded-xl border border-[#dce6e1] p-3 text-sm outline-none focus:border-[#1d9a65] focus:ring-4 focus:ring-[#c8f3dc]"></textarea>
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeDelegateModal()" class="rounded-xl px-4 py-2.5 text-xs font-semibold text-[#526560] hover:bg-[#f1f6f3]">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#24737d] px-5 py-2.5 text-xs font-semibold text-white shadow hover:bg-[#1a5b63]">Confirm Delegation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCompleteModal(uuid, title) {
        document.getElementById('complete-form').action = '/tasks/' + uuid + '/complete';
        document.getElementById('complete-modal-title').textContent = title;
        document.getElementById('complete-modal').classList.remove('hidden');
    }
    function closeCompleteModal() {
        document.getElementById('complete-modal').classList.add('hidden');
    }
    function openDelegateModal(uuid, title) {
        document.getElementById('delegate-form').action = '/tasks/' + uuid + '/delegate';
        document.getElementById('delegate-modal-title').textContent = title;
        document.getElementById('delegate-modal').classList.remove('hidden');
    }
    function closeDelegateModal() {
        document.getElementById('delegate-modal').classList.add('hidden');
    }
</script>
@endsection
