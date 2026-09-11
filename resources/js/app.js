const demoWorkflows = [
	{ id: 1, title: 'Purchase request', owner: 'Maya Chen', status: 'In progress', step: 'Manager approval', amount: '$12,480', age: '2h ago', tone: 'mint' },
	{ id: 2, title: 'New contractor access', owner: 'Owen Smith', status: 'Pending', step: 'IT security review', amount: '3 systems', age: '5h ago', tone: 'sky' },
	{ id: 3, title: 'Travel reimbursement', owner: 'Lena Ortiz', status: 'Approved', step: 'Complete', amount: '$860', age: 'Yesterday', tone: 'sun' },
	{ id: 4, title: 'Vendor onboarding', owner: 'Theo James', status: 'Needs attention', step: 'Compliance check', amount: '8 fields', age: 'Yesterday', tone: 'coral' },
];

const userContext = window.flowlineContext ?? { role: 'Employee', permissions: [] };
const can = (permission) => userContext.permissions?.includes(permission) ?? false;

const demoApprovals = can('workflow.approve') || can('workflow.manage') ? [
	{ id: 1, initials: 'MC', name: 'Maya Chen', title: 'Purchase request', detail: 'Marketing campaign · $12,480', time: '12 min ago', color: 'bg-[#c8f3dc]' },
	{ id: 2, initials: 'OS', name: 'Owen Smith', title: 'New contractor access', detail: 'Product engineering · 3 systems', time: '1 hr ago', color: 'bg-[#b9e7ed]' },
	{ id: 3, initials: 'TJ', name: 'Theo James', title: 'Vendor onboarding', detail: 'Brightline Studios · compliance', time: '3 hrs ago', color: 'bg-[#f5c96b]' },
] : [];

const state = {
	workflows: [...demoWorkflows],
	approvals: [...demoApprovals],
	activeView: 'overview',
};

const $ = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

function icon(name, className = 'h-4 w-4') {
	const paths = {
		grid: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
		workflow: '<path d="M6 3v5a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v7"/><path d="M18 3v5a3 3 0 0 1-3 3H9a3 3 0 0 0-3 3v7"/><circle cx="6" cy="3" r="2"/><circle cx="18" cy="3" r="2"/><circle cx="6" cy="21" r="2"/><circle cx="18" cy="21" r="2"/>',
		form: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
		check: '<path d="m5 12 4 4L19 6"/>',
		clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		audit: '<path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
		plus: '<path d="M12 5v14M5 12h14"/>',
		search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
		bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
		arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
		x: '<path d="M6 6l12 12M18 6 6 18"/>',
		menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
		filter: '<path d="M4 5h16M7 12h10M10 19h4"/>',
		spark: '<path d="m12 3 1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5zM19 16l.5 2.5L22 19l-2.5.5L19 22l-.5-2.5L16 19l2.5-.5z"/>',
	};
	return `<svg class="${className}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths[name] ?? ''}</svg>`;
}

function statusClass(status) {
	return {
		'In progress': 'bg-[#e1f7eb] text-[#197a51]',
		Pending: 'bg-[#e3f5f7] text-[#24737d]',
		Approved: 'bg-[#fff2c9] text-[#8a6512]',
		'Needs attention': 'bg-[#ffe4df] text-[#a64d40]',
	}[status] ?? 'bg-slate-100 text-slate-600';
}

function renderWorkflowCards(items = state.workflows) {
	const markup = items.map((workflow) => `
		<article class="group relative overflow-hidden rounded-2xl border border-[#dce6e1] bg-white p-5 shadow-[0_8px_30px_rgba(32,62,52,0.04)] transition hover:-translate-y-1 hover:shadow-[0_18px_38px_rgba(32,62,52,0.09)]">
			<div class="absolute inset-x-0 top-0 h-1 ${workflow.tone === 'coral' ? 'bg-[#f47c6b]' : workflow.tone === 'sky' ? 'bg-[#77cdd7]' : workflow.tone === 'sun' ? 'bg-[#efbf53]' : 'bg-[#55bb84]'}"></div>
			<div class="flex items-start justify-between gap-3">
				<div class="flex items-center gap-3">
					<span class="grid h-10 w-10 place-items-center rounded-xl ${workflow.tone === 'coral' ? 'bg-[#ffe4df] text-[#a64d40]' : workflow.tone === 'sky' ? 'bg-[#e3f5f7] text-[#24737d]' : workflow.tone === 'sun' ? 'bg-[#fff2c9] text-[#8a6512]' : 'bg-[#e1f7eb] text-[#197a51]'}">${icon('workflow', 'h-5 w-5')}</span>
					<div><h3 class="font-semibold tracking-[-0.02em] text-[#142321]">${workflow.title}</h3><p class="mt-0.5 text-xs text-[#7a8b86]">${workflow.owner} · ${workflow.age}</p></div>
				</div>
				<button class="rounded-lg p-1.5 text-[#91a09b] transition hover:bg-[#f1f6f3] hover:text-[#142321]" title="Open workflow details" data-open-workflow="${workflow.id}">${icon('arrow')}</button>
			</div>
			<div class="mt-6 flex items-end justify-between"><div><p class="text-xs font-medium uppercase tracking-[0.12em] text-[#91a09b]">Current step</p><p class="mt-1 text-sm font-medium text-[#30443f]">${workflow.step}</p></div><p class="text-right text-lg font-semibold text-[#142321]">${workflow.amount}</p></div>
			<div class="mt-5 flex items-center justify-between border-t border-[#edf2ef] pt-4"><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold ${statusClass(workflow.status)}">${workflow.status}</span><span class="text-xs text-[#8a9994]">Instance #${String(workflow.id).padStart(4, '0')}</span></div>
		</article>`).join('');
	['#workflow-grid', '#workflow-grid-page'].forEach((selector) => {
		const target = $(selector);
		if (target) target.innerHTML = markup;
	});
	$$('[data-open-workflow]').forEach((button) => button.addEventListener('click', () => openWorkflow(Number(button.dataset.openWorkflow))));
}

function renderApprovals() {
	const markup = state.approvals.length ? state.approvals.map((approval) => `
		<div class="flex items-center gap-3 border-b border-[#edf2ef] py-4 last:border-0">
			<span class="grid h-10 w-10 shrink-0 place-items-center rounded-full ${approval.color} text-xs font-bold text-[#315048]">${approval.initials}</span>
			<div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-2"><p class="truncate text-sm font-semibold text-[#1d302c]">${approval.title}</p><span class="shrink-0 text-[11px] text-[#91a09b]">${approval.time}</span></div><p class="mt-1 truncate text-xs text-[#758681]">${approval.name} · ${approval.detail}</p></div>
			<div class="flex shrink-0 gap-1.5"><button class="grid h-8 w-8 place-items-center rounded-lg bg-[#e1f7eb] text-[#197a51] transition hover:bg-[#c8f3dc]" title="Approve" data-approval-action="approve" data-approval-id="${approval.id}">${icon('check')}</button><button class="grid h-8 w-8 place-items-center rounded-lg bg-[#fff0ed] text-[#a64d40] transition hover:bg-[#ffe4df]" title="Reject" data-approval-action="reject" data-approval-id="${approval.id}">${icon('x')}</button></div>
		</div>`).join('') : '<div class="py-10 text-center text-sm text-[#7a8b86]">Your approval queue is clear.</div>';
	['#approval-list', '#approval-list-page'].forEach((selector) => {
		const target = $(selector);
		if (target) target.innerHTML = markup;
	});
	$$('[data-approval-action]').forEach((button) => button.addEventListener('click', () => decideApproval(Number(button.dataset.approvalId), button.dataset.approvalAction)));
	const count = $('#approval-count');
	if (count) count.textContent = state.approvals.length;
}

function toast(message, tone = 'success') {
	const node = document.createElement('div');
	node.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm font-medium shadow-xl ${tone === 'success' ? 'border-[#bfe8d0] bg-[#f1fff6] text-[#197a51]' : 'border-[#f4c2b9] bg-[#fff5f2] text-[#a64d40]'}`;
	node.innerHTML = `${icon(tone === 'success' ? 'check' : 'x')}<span>${message}</span>`;
	document.body.appendChild(node);
	setTimeout(() => node.remove(), 2800);
}

function showModal(content) {
	$('#modal-content').innerHTML = content;
	$('#modal').classList.remove('hidden');
	document.body.classList.add('overflow-hidden');
}

function closeModal() {
	$('#modal').classList.add('hidden');
	document.body.classList.remove('overflow-hidden');
}

async function apiRequest(url, options = {}) {
	const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
	const response = await fetch(url, {
		...options,
		headers: {
			Accept: 'application/json',
			'Content-Type': 'application/json',
			'X-CSRF-TOKEN': csrf,
			'X-Requested-With': 'XMLHttpRequest',
			...(options.headers ?? {}),
		},
	});
	const body = await response.json().catch(() => null);
	if (!response.ok) {
		throw new Error(body?.message ?? Object.values(body?.errors ?? {}).flat()[0] ?? `Request failed (${response.status})`);
	}
	return body;
}

function openWorkflow(id) {
	const workflow = state.workflows.find((item) => item.id === id);
	if (!workflow) return;
	showModal(`<div class="flex items-start justify-between gap-6"><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#1d9a65]">Workflow instance #${String(id).padStart(4, '0')}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">${workflow.title}</h2><p class="mt-1 text-sm text-[#71817c]">Started by ${workflow.owner} · ${workflow.age}</p></div><button class="rounded-lg p-2 text-[#80908b] hover:bg-[#f1f6f3]" data-close-modal title="Close">${icon('x')}</button></div><div class="mt-8 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-[#f3f7f4] p-4"><p class="text-xs uppercase tracking-[0.12em] text-[#8a9994]">Status</p><p class="mt-2 font-semibold ${statusClass(workflow.status).replace('bg-', 'text-').split(' ')[1] ?? 'text-[#142321]'}">${workflow.status}</p></div><div class="rounded-xl bg-[#f3f7f4] p-4"><p class="text-xs uppercase tracking-[0.12em] text-[#8a9994]">Current step</p><p class="mt-2 font-semibold text-[#142321]">${workflow.step}</p></div><div class="rounded-xl bg-[#f3f7f4] p-4"><p class="text-xs uppercase tracking-[0.12em] text-[#8a9994]">Value</p><p class="mt-2 font-semibold text-[#142321]">${workflow.amount}</p></div></div><div class="mt-8"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8a9994]">Activity</p><div class="mt-4 space-y-4"><div class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 rounded-full bg-[#1d9a65]"></span><div><p class="text-sm font-medium text-[#30443f]">${workflow.step} assigned</p><p class="text-xs text-[#899792]">${workflow.age}</p></div></div><div class="flex gap-3"><span class="mt-1 h-2.5 w-2.5 rounded-full bg-[#b9e7ed]"></span><div><p class="text-sm font-medium text-[#30443f]">Request submitted by ${workflow.owner}</p><p class="text-xs text-[#899792]">Yesterday at 4:18 PM</p></div></div></div></div><div class="mt-8 flex justify-end"><button class="rounded-lg bg-[#142321] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24403a]" data-close-modal>Close details</button></div>`);
	$$('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
}

function openNewWorkflow() {
	showModal(`<div class="flex items-start justify-between gap-6"><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#1d9a65]">Workflow builder</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">Create a workflow</h2><p class="mt-1 text-sm text-[#71817c]">Start with the process name and its first approval step.</p></div><button class="rounded-lg p-2 text-[#80908b] hover:bg-[#f1f6f3]" data-close-modal title="Close">${icon('x')}</button></div><form id="new-workflow-form" class="mt-8 space-y-5"><label class="block"><span class="text-sm font-semibold text-[#30443f]">Workflow name</span><input required name="title" placeholder="e.g. Equipment request" class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm outline-none transition focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]" /></label><label class="block"><span class="text-sm font-semibold text-[#30443f]">First step</span><input required name="step" placeholder="e.g. Department approval" class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm outline-none transition focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]" /></label><div class="flex justify-end gap-3 pt-3"><button type="button" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-[#62736e] hover:bg-[#f1f6f3]" data-close-modal>Cancel</button><button class="rounded-lg bg-[#142321] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#24403a]" type="submit">Create workflow</button></div></form>`);
	$$('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
	$('#new-workflow-form').addEventListener('submit', async (event) => {
		event.preventDefault();
		const data = new FormData(event.currentTarget);
		try {
			const workflow = await apiRequest('/workflows', { method: 'POST', body: JSON.stringify({ title: data.get('title'), status: 'draft', steps: [{ name: data.get('step'), type: 'sequential', step_order: 1, assignee_role: 'Manager' }] }) });
			state.workflows.unshift({ id: workflow.data?.id ?? workflow.id, title: workflow.data?.title ?? workflow.title, owner: 'You', status: 'Draft', step: data.get('step'), amount: '—', age: 'Just now', tone: 'mint' });
			closeModal();
			renderWorkflowCards();
			toast('Workflow draft created');
		} catch (error) {
			toast(error.message, 'error');
		}
	});
}

async function openSubmission() {
	let forms = [];
	try {
		const workflows = await apiRequest('/workflows');
		const entries = workflows.data ?? workflows;
		for (const workflow of entries) {
			const response = await apiRequest(`/workflows/${workflow.id}/forms`);
			forms.push(...(response.data ?? response));
		}
	} catch (error) {
		toast(error.message, 'error');
		return;
	}
	if (!forms.length) {
		toast('No workflow forms are available for submission yet.', 'error');
		return;
	}
	showModal(`<div class="flex items-start justify-between gap-6"><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#1d9a65]">New request</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-[#142321]">Submit for approval</h2><p class="mt-1 text-sm text-[#71817c]">Submit data through the selected workflow form.</p></div><button class="rounded-lg p-2 text-[#80908b] hover:bg-[#f1f6f3]" data-close-modal title="Close">${icon('x')}</button></div><form id="submission-form" class="mt-8 space-y-5"><label class="block"><span class="text-sm font-semibold text-[#30443f]">Form</span><select name="form_id" class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm outline-none focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]">${forms.map((form) => `<option value="${form.id}">${form.title}</option>`).join('')}</select></label><label class="block"><span class="text-sm font-semibold text-[#30443f]">Request summary</span><textarea required name="summary" rows="4" placeholder="What does the reviewer need to know?" class="mt-2 w-full resize-none rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm outline-none focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]"></textarea></label><div class="flex justify-end gap-3 pt-3"><button type="button" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-[#62736e] hover:bg-[#f1f6f3]" data-close-modal>Cancel</button><button class="rounded-lg bg-[#142321] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#24403a]" type="submit">Submit request</button></div></form>`);
	$$('[data-close-modal]').forEach((button) => button.addEventListener('click', closeModal));
	$('#submission-form').addEventListener('submit', async (event) => {
		event.preventDefault();
		const data = new FormData(event.currentTarget);
		try {
			await apiRequest(`/forms/${data.get('form_id')}/submissions`, { method: 'POST', body: JSON.stringify({ data: { summary: data.get('summary') } }) });
			closeModal();
			toast('Request submitted to the next reviewer');
		} catch (error) {
			toast(error.message, 'error');
		}
	});
}

async function decideApproval(id, decision) {
	const approval = state.approvals.find((item) => item.id === id);
	if (!approval?.assignmentId) {
		toast('This demo item is not linked to a live assignment.', 'error');
		return;
	}
	try {
		await apiRequest(`/workflow-assignments/${approval.assignmentId}/${decision}`, { method: 'POST', body: JSON.stringify({ comment: `${decision === 'approve' ? 'Approved' : 'Rejected'} from Flowline` }) });
		state.approvals = state.approvals.filter((item) => item.id !== id);
		renderApprovals();
		toast(`${approval.title} ${decision === 'approve' ? 'approved' : 'sent back'}`);
	} catch (error) {
		toast(error.message, 'error');
	}
}

function setView(view) {
	state.activeView = view;
	$$('.view-panel').forEach((panel) => panel.classList.toggle('hidden', panel.id !== `${view}-view`));
	$$('.sidebar-link').forEach((link) => link.classList.toggle('is-active', link.dataset.view === view));
	const title = $('#page-title');
	if (title) title.textContent = { overview: 'Overview', workflows: 'Workflows', forms: 'Dynamic forms', approvals: 'Approval queue', audit: 'Audit trail' }[view] ?? 'Overview';
	$('#sidebar')?.classList.add('-translate-x-full');
	if (view === 'workflows') renderWorkflowCards();
}

function applyAccessGates() {
	const gate = (selector, allowed) => $$(selector).forEach((element) => { element.hidden = !allowed; });

	gate('[data-role-gated="approvals"]', can('workflow.approve') || can('workflow.manage'));
	gate('[data-role-gated="audit"]', can('audit.view'));
	gate('[data-role-gated="workflow-create"]', can('workflow.create'));
	gate('[data-role-gated="form-submit"]', can('form.submit'));
	gate('[data-view="audit"]', can('audit.view'));
	if (!can('workflow.approve') && !can('workflow.manage')) {
		$('#approvals-view')?.classList.add('hidden');
		[...document.querySelectorAll('p')].find((element) => element.textContent.trim() === 'Awaiting approval')?.closest('.rounded-2xl')?.setAttribute('hidden', '');
	}
	if (!can('audit.view')) {
		[...document.querySelectorAll('h3')].find((element) => element.textContent.trim() === 'A clear trail of decisions')?.closest('.rounded-2xl')?.setAttribute('hidden', '');
	}
}

function setupTheme() {
	const storedTheme = window.localStorage.getItem('flowline-theme');
	const dark = storedTheme === 'dark';
	document.body.classList.toggle('theme-dark', dark);
	document.body.dataset.theme = dark ? 'dark' : 'light';
	$('#theme-toggle')?.addEventListener('click', () => {
		const nextDark = !document.body.classList.contains('theme-dark');
		document.body.classList.toggle('theme-dark', nextDark);
		document.body.dataset.theme = nextDark ? 'dark' : 'light';
		window.localStorage.setItem('flowline-theme', nextDark ? 'dark' : 'light');
	});
}

function setupRoleDetection() {
	const email = $('#login-email');
	const indicator = $('#detected-role');
	if (!email || !indicator) return;
	let timeout;
	email.addEventListener('input', () => {
		window.clearTimeout(timeout);
		indicator.textContent = 'Role is detected from your account.';
		if (!email.value.includes('@')) return;
		timeout = window.setTimeout(async () => {
			try {
				const response = await fetch(`/login/role?email=${encodeURIComponent(email.value)}`, { headers: { Accept: 'application/json' } });
				const result = await response.json();
				indicator.textContent = result.role ? `Detected role: ${result.role}` : 'No matching account found yet.';
			} catch {
				indicator.textContent = 'Role will be confirmed after sign in.';
			}
		}, 250);
	});
}

function setupRegistrationRole() {
	const form = document.querySelector('form[action$="/register"]');
	const password = form?.querySelector('input[name="password"]')?.closest('label');
	if (!form || !password || form.querySelector('[name="role"]')) return;
	const role = document.createElement('label');
	role.className = 'block';
	const roles = window.flowlineRegistrationRoles ?? ['Employee'];
	role.innerHTML = `<span class="text-sm font-semibold text-[#30443f]">Starting role</span><select name="role" required class="mt-2 w-full rounded-xl border border-[#dce6e1] bg-white px-3.5 py-3 text-sm text-[#142321] outline-none transition focus:border-[#55bb84] focus:ring-4 focus:ring-[#c8f3dc]">${roles.map((item) => `<option value="${item}"${item === 'Employee' ? ' selected' : ''}>${item}</option>`).join('')}</select><span class="mt-2 block text-xs text-[#91a09b]">Choose the workspace role for this account. Permissions are enforced by the server.</span>`;
	password.parentElement?.before(role);
}

function setupNotifications() {
	const bellBtn = $('#notification-bell-btn');
	const sidebarBellBtn = $('#sidebar-bell-btn');
	const dropdown = $('#notification-dropdown');
	const markAllBtn = $('#mark-all-read-btn');

	const toggleDropdown = (event) => {
		event.preventDefault();
		event.stopPropagation();
		dropdown?.classList.toggle('hidden');
	};

	bellBtn?.addEventListener('click', toggleDropdown);
	sidebarBellBtn?.addEventListener('click', toggleDropdown);

	document.addEventListener('click', (event) => {
		if (dropdown && !dropdown.contains(event.target) && !bellBtn?.contains(event.target) && !sidebarBellBtn?.contains(event.target)) {
			dropdown.classList.add('hidden');
		}
	});

	markAllBtn?.addEventListener('click', async () => {
		try {
			const csrf = $('meta[name="csrf-token"]')?.getAttribute('content');
			const res = await fetch('/api/v1/notifications/read-all', {
				method: 'POST',
				headers: {
					Accept: 'application/json',
					'X-CSRF-TOKEN': csrf,
					'X-Requested-With': 'XMLHttpRequest',
				},
			});
			if (res.ok) {
				$('#header-unread-badge')?.classList.add('hidden');
				$('#sidebar-unread-badge')?.classList.add('hidden');
				$$('#notification-items-list > div').forEach((el) => el.classList.add('opacity-60'));
				toast('All notifications marked as read', 'success');
			}
		} catch {}
	});

	// Live unread badge count refresh
	async function refreshUnreadCount() {
		try {
			const res = await fetch('/api/v1/notifications/unread-count', {
				headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			});
			if (res.ok) {
				const data = await res.json();
				const count = data.unread_count ?? 0;
				const headerBadge = $('#header-unread-badge');
				const sidebarBadge = $('#sidebar-unread-badge');
				if (headerBadge) {
					headerBadge.textContent = count;
					headerBadge.classList.toggle('hidden', count === 0);
				}
				if (sidebarBadge) {
					sidebarBadge.textContent = count;
					sidebarBadge.classList.toggle('hidden', count === 0);
				}
			}
		} catch {}
	}

	refreshUnreadCount();
	setInterval(refreshUnreadCount, 30000);
}

function bindApp() {
	setupTheme();
	setupRoleDetection();
	setupRegistrationRole();
	setupNotifications();
	applyAccessGates();
	$$('[data-view]').forEach((link) => link.addEventListener('click', () => setView(link.dataset.view)));
	$('[data-new-workflow]')?.addEventListener('click', openNewWorkflow);
	$('[data-submit-request]')?.addEventListener('click', openSubmission);
	$('[data-close-modal]')?.addEventListener('click', closeModal);
	$('#modal')?.addEventListener('click', (event) => { if (event.target.id === 'modal') closeModal(); });
	$('#mobile-menu')?.addEventListener('click', () => {
		$('#sidebar')?.classList.toggle('-translate-x-full');
		$('#sidebar-scrim')?.classList.toggle('hidden');
	});
	$('#mobile-close')?.addEventListener('click', () => { $('#sidebar')?.classList.add('-translate-x-full'); $('#sidebar-scrim')?.classList.add('hidden'); });
	$('#sidebar-scrim')?.addEventListener('click', () => { $('#sidebar')?.classList.add('-translate-x-full'); $('#sidebar-scrim')?.classList.add('hidden'); });
	$('#search')?.addEventListener('input', (event) => { const query = event.target.value.toLowerCase(); renderWorkflowCards(state.workflows.filter((item) => `${item.title} ${item.owner} ${item.status}`.toLowerCase().includes(query))); });
	renderWorkflowCards();
	renderApprovals();

	// Check URL query parameters for initial view or modal actions
	const urlParams = new URLSearchParams(window.location.search);
	const initialView = urlParams.get('view');
	if (initialView && ['overview', 'workflows', 'forms', 'approvals', 'audit'].includes(initialView)) {
		setView(initialView);
	}
	if (urlParams.get('action') === 'submit') {
		openSubmission();
	}
}

document.addEventListener('DOMContentLoaded', bindApp);

