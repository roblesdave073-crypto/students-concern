import { createClient } from 'https://esm.sh/@supabase/supabase-js@2';
import { SUPABASE_URL, SUPABASE_PUBLISHABLE_KEY } from './config.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const configured = SUPABASE_URL.includes('.supabase.co') && !SUPABASE_URL.includes('YOUR_')
  && SUPABASE_PUBLISHABLE_KEY.length > 25 && !SUPABASE_PUBLISHABLE_KEY.includes('YOUR_');
const db = configured ? createClient(SUPABASE_URL, SUPABASE_PUBLISHABLE_KEY) : null;
const table = (name) => db.from(`v2_${name}`);
const state = { user: null, profile: null, page: 'dashboard', authMode: 'login', data: {} };
const labels = { dashboard: 'Dashboard', complaints: 'Complaints', submit: 'Submit a concern', users: 'Accounts', departments: 'Departments', categories: 'Categories', reports: 'Reports', activity: 'Activity log', settings: 'Settings', profile: 'My profile', notifications: 'Notifications' };
const statusList = ['Pending','Under Review','Assigned','In Progress','Resolved','Rejected','Reopened'];
const statusClass = (s) => s.replaceAll(' ', '');
const esc = (v = '') => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const dateText = (v) => v ? new Date(v).toLocaleString() : '—';
const shortDate = (v) => v ? new Date(v).toLocaleDateString() : '—';
const byId = (list, id) => list?.find((x) => String(x.id) === String(id));
const fullName = (id) => state.data.profiles?.find((p) => p.id === id)?.full_name || (id === state.user?.id ? state.profile?.full_name : 'User');

function toast(message, error = false) {
  const node = document.createElement('div');
  node.className = `toast${error ? ' error' : ''}`;
  node.textContent = message;
  document.body.append(node);
  setTimeout(() => node.remove(), 3800);
}

function notice(message, error = false) {
  const box = $('#global-message');
  box.textContent = message;
  box.classList.toggle('error', error);
  box.classList.remove('hidden');
  setTimeout(() => box.classList.add('hidden'), 5000);
}

function showAuth(message = '', error = false) {
  $('#auth-screen').classList.remove('hidden');
  $('#workspace').classList.add('hidden');
  $('#auth-message').textContent = message;
  $('#auth-message').classList.toggle('error', error);
  $('#auth-message').classList.toggle('hidden', !message);
}

function authMode(mode) {
  state.authMode = mode;
  const signup = mode === 'signup';
  $('#auth-title').textContent = signup ? 'Create an account' : 'Welcome back';
  $('#auth-copy').textContent = signup ? 'Register as a student or request staff access.' : 'Sign in to submit and follow concerns.';
  $('#name-wrap').classList.toggle('hidden', !signup);
  $('#signup-fields').classList.toggle('hidden', !signup);
  $('#auth-name').required = signup;
  $('#auth-role').value = 'student';
  $('#auth-student-number').closest('label').classList.remove('hidden');
  $('#auth-course').closest('label').classList.remove('hidden');
  $('#auth-year').closest('label').classList.remove('hidden');
  $('#auth-position').closest('label').classList.add('hidden');
  $('#auth-submit').textContent = signup ? 'Create account' : 'Sign in';
  $('#toggle-auth').textContent = signup ? 'Back to sign in' : 'Create an account';
  $('#reset-password').classList.toggle('hidden', signup);
  $('#auth-password').autocomplete = signup ? 'new-password' : 'current-password';
}

$('#auth-role').addEventListener('change', () => {
  const staff = $('#auth-role').value === 'staff';
  $('#auth-student-number').closest('label').classList.toggle('hidden', staff);
  $('#auth-course').closest('label').classList.toggle('hidden', staff);
  $('#auth-year').closest('label').classList.toggle('hidden', staff);
  $('#auth-position').closest('label').classList.toggle('hidden', !staff);
});

function setBusy(button, busy, text) {
  if (!button) return;
  if (busy) { button.dataset.label = button.textContent; button.disabled = true; button.textContent = text || 'Please wait…'; }
  else { button.disabled = false; button.textContent = button.dataset.label || button.textContent; }
}

async function signedIn(session) {
  state.user = session?.user || null;
  if (!state.user) { state.profile = null; showAuth(); return; }
  const { data, error } = await table('profiles').select('*').eq('id', state.user.id).maybeSingle();
  if (error || !data) {
    await db.auth.signOut();
    showAuth(error?.message || 'Your profile could not be loaded. Please contact an administrator.', true);
    return;
  }
  state.profile = data;
  if (!data.is_active) {
    await db.auth.signOut();
    showAuth(data.role === 'staff' ? 'Your staff account is waiting for administrator approval.' : 'This account is inactive. Contact an administrator.', true);
    return;
  }
  $('#auth-screen').classList.add('hidden');
  $('#workspace').classList.remove('hidden');
  $('#user-chip').innerHTML = `<strong>${esc(data.full_name || state.user.email)}</strong>${esc(data.role)}`;
  $('#role-label').textContent = `${data.role.toUpperCase()} WORKSPACE`;
  await loadData();
  buildNav();
  render();
}

async function loadData() {
  $('#page-content').innerHTML = '<div class="spinner">Loading your workspace…</div>';
  const names = ['departments','categories','complaints','complaint_assignments','complaint_updates','notifications','app_settings','feedback','activity_logs'];
  const jobs = names.map((name) => table(name).select('*').order(name === 'app_settings' ? 'setting_key' : 'created_at', { ascending: name === 'app_settings' }).limit(name === 'complaint_updates' ? 300 : 500));
  if (state.profile.role === 'admin') jobs.push(table('profiles').select('*').order('created_at', { ascending: false }));
  else jobs.push(Promise.resolve({ data: [], error: null }));
  const results = await Promise.all(jobs);
  const errors = results.filter((r) => r.error);
  if (errors.length) console.warn('Some workspace data could not be loaded:', errors.map((r) => r.error.message));
  names.forEach((name, i) => state.data[name] = results[i].data || []);
  state.data.profiles = results.at(-1).data || [];
  const unread = state.data.notifications.filter((n) => !n.is_read).length;
  $('#notification-count').textContent = unread || '';
  $('#notification-count').classList.toggle('hidden', unread === 0);
}

function buildNav() {
  const role = state.profile.role;
  const items = [['dashboard','⌂','Dashboard'],['complaints','▤',role === 'staff' ? 'Assigned concerns' : 'Complaints']];
  if (role === 'student') items.push(['submit','＋','Submit a concern']);
  if (role === 'admin') items.push(['users','♙','Accounts'],['departments','▦','Departments'],['categories','◇','Categories'],['reports','▥','Reports'],['activity','◷','Activity log'],['settings','⚙','Settings']);
  items.push(['profile','○','My profile'],['notifications','♧','Notifications']);
  $('#navigation').innerHTML = items.map(([key, icon, label]) => `<button class="nav-item${state.page === key ? ' active' : ''}" data-page="${key}">${icon}<span>${label}</span></button>`).join('');
}

function activeComplaints() { return (state.data.complaints || []).filter((c) => !c.archived_at); }
function statCard(label, value, hint = '') { return `<article class="stat"><span>${esc(label)}</span><strong>${esc(value)}</strong>${hint ? `<small class="muted">${esc(hint)}</small>` : ''}</article>`; }
function badge(status) { return `<span class="badge ${statusClass(status)}">${esc(status)}</span>`; }
function complaintTitle(c) { return c.is_anonymous && state.profile.role === 'student' && c.student_id !== state.user.id ? 'Anonymous concern' : esc(c.title); }
function complaintRow(c, actions = true) {
  const cat = byId(state.data.categories, c.category_id)?.name || '—';
  const dept = byId(state.data.departments, c.department_id)?.name || '—';
  const student = c.is_anonymous ? 'Anonymous' : fullName(c.student_id);
  return `<tr><td><button class="link-button" data-open="${c.id}">${complaintTitle(c)}</button><div class="meta">${esc(c.tracking_no || `#${c.id}`)} · ${esc(cat)}</div></td><td>${badge(c.status)}</td><td>${esc(c.priority)}</td><td>${esc(dept)}</td><td>${esc(student)}</td><td>${shortDate(c.created_at)}</td>${actions ? `<td><button class="button secondary small" data-open="${c.id}">View</button></td>` : ''}</tr>`;
}
function complaintTable(rows, actions = true) {
  if (!rows.length) return '<div class="empty">No concerns to show yet.</div>';
  return `<div class="table-wrap"><table><thead><tr><th>Concern</th><th>Status</th><th>Priority</th><th>Department</th><th>Submitted by</th><th>Date</th>${actions ? '<th></th>' : ''}</tr></thead><tbody>${rows.map((c) => complaintRow(c, actions)).join('')}</tbody></table></div>`;
}

function renderDashboard() {
  const role = state.profile.role;
  const complaints = activeComplaints();
  const mine = role === 'student' ? complaints.filter((c) => c.student_id === state.user.id) : complaints;
  const assignedIds = new Set(state.data.complaint_assignments.filter((a) => a.staff_id === state.user.id).map((a) => a.complaint_id));
  const visible = role === 'staff' ? complaints.filter((c) => assignedIds.has(c.id)) : mine;
  const stats = role === 'admin'
    ? [['All active', complaints.length],['Pending', complaints.filter((c) => c.status === 'Pending').length],['In progress', complaints.filter((c) => ['Assigned','In Progress','Under Review'].includes(c.status)).length],['Resolved', complaints.filter((c) => c.status === 'Resolved').length]]
    : role === 'staff'
      ? [['Assigned', visible.length],['Assigned', visible.filter((c) => c.status === 'Assigned').length],['In progress', visible.filter((c) => c.status === 'In Progress').length],['Resolved', visible.filter((c) => c.status === 'Resolved').length]]
      : [['My concerns', visible.length],['Pending', visible.filter((c) => ['Pending','Under Review'].includes(c.status)).length],['In progress', visible.filter((c) => ['Assigned','In Progress','Reopened'].includes(c.status)).length],['Resolved', visible.filter((c) => c.status === 'Resolved').length]];
  const recent = [...visible].sort((a,b) => new Date(b.created_at) - new Date(a.created_at)).slice(0,6);
  const extra = role === 'admin' ? `<article class="card"><h2>Attention needed</h2><div class="card-list"><div class="inline"><span class="pill">Urgent unassigned</span><strong>${complaints.filter((c) => c.priority === 'Urgent' && !state.data.complaint_assignments.some((a) => a.complaint_id === c.id)).length}</strong></div><div class="inline"><span class="pill">Pending staff approvals</span><strong>${state.data.profiles.filter((p) => p.role === 'staff' && !p.is_active).length}</strong></div></div></article>` : `<article class="card"><h2>Quick guide</h2><p class="muted">Submit a concern with its category and department. You can follow status updates from your dashboard and reopen a resolved concern if it still needs attention.</p>${role === 'student' ? '<button class="button primary small" data-page="submit">Submit a concern</button>' : ''}</article>`;
  return `<div class="grid stats">${stats.map((x) => statCard(...x)).join('')}</div><div class="grid two-col"><article class="card"><div class="complaint-head"><h2>Recent concerns</h2><button class="link-button" data-page="complaints">View all</button></div>${complaintTable(recent)}</article>${extra}</div>`;
}

function renderComplaints() {
  const role = state.profile.role;
  let rows = activeComplaints();
  if (role === 'student') rows = rows.filter((c) => c.student_id === state.user.id);
  if (role === 'staff') {
    const assigned = new Set(state.data.complaint_assignments.filter((a) => a.staff_id === state.user.id).map((a) => a.complaint_id));
    rows = rows.filter((c) => assigned.has(c.id));
  }
  return `<div class="toolbar"><input class="filter" id="complaint-search" placeholder="Search tracking number or title"><select class="filter" id="status-filter"><option value="">All statuses</option>${statusList.map((s) => `<option>${esc(s)}</option>`).join('')}</select>${role === 'student' ? '<button class="button primary" data-page="submit">＋ New concern</button>' : ''}</div><div id="complaint-list">${complaintTable(rows)}</div>`;
}

function renderSubmit() {
  const cats = state.data.categories.filter((x) => x.is_active);
  return `<article class="card"><h2>Tell us what happened</h2><p class="muted">Your concern is visible to you and the staff handling it. Choose anonymous if you prefer not to show your name in staff views.</p><form id="complaint-form" class="form-grid"><label class="field">Title<input name="title" maxlength="180" required></label><label class="field">Category<select name="category_id" required><option value="">Choose a category</option>${cats.map((x) => `<option value="${x.id}">${esc(x.name)}</option>`).join('')}</select></label><label class="field">Department<select name="department_id" required><option value="">Choose a department</option>${state.data.departments.map((x) => `<option value="${x.id}">${esc(x.name)}</option>`).join('')}</select></label><label class="field">Priority<select name="priority"><option>Medium</option><option>Low</option><option>High</option><option>Urgent</option></select></label><label class="field full">Description<textarea name="description" minlength="10" required></textarea></label><label class="inline"><input type="checkbox" name="is_anonymous"> Submit anonymously</label><div class="field full"><button class="button primary">Submit concern</button></div></form></article>`;
}

function renderUsers() {
  const rows = state.data.profiles;
  return `<div class="toolbar"><input class="filter" id="user-search" placeholder="Search name or email"><select class="filter" id="role-filter"><option value="">All roles</option><option>student</option><option>staff</option><option>admin</option></select><select class="filter" id="account-filter"><option value="">All account states</option><option value="pending">Pending approval</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div><div class="table-wrap"><table><thead><tr><th>Name / email</th><th>Role</th><th>Details</th><th>State</th><th>Actions</th></tr></thead><tbody>${rows.map((p) => `<tr><td><strong>${esc(p.full_name)}</strong><div class="meta">${esc(p.email)}</div></td><td>${esc(p.role)}</td><td>${esc(p.role === 'student' ? [p.student_number,p.course,p.year_level].filter(Boolean).join(' · ') : [p.position,byId(state.data.departments,p.department_id)?.name].filter(Boolean).join(' · ') || '—')}</td><td>${p.is_active ? '<span class="badge Resolved">Active</span>' : `<span class="badge Pending">${p.role === 'staff' ? 'Pending' : 'Inactive'}</span>`}</td><td><div class="actions">${p.id !== state.user.id ? `<button class="button secondary small" data-user-toggle="${p.id}" data-active="${p.is_active}">${p.is_active ? 'Deactivate' : p.role === 'staff' ? 'Approve' : 'Activate'}</button>${p.role === 'staff' ? `<button class="button secondary small" data-user-dept="${p.id}">Set department</button>` : ''}` : '<span class="muted">You</span>'}</div></td></tr>`).join('')}</tbody></table></div>`;
}

function renderReference(kind) {
  const isDept = kind === 'departments';
  const items = state.data[kind] || [];
  return `<article class="card"><h2>Manage ${isDept ? 'departments' : 'categories'}</h2><form id="reference-form" class="toolbar"><input class="filter" name="name" maxlength="120" placeholder="New ${isDept ? 'department' : 'category'} name" required><button class="button primary">Add</button></form><div class="card-list">${items.map((x) => `<div class="complaint-card complaint-head"><div><strong>${esc(x.name)}</strong>${!isDept ? `<div class="meta">${x.is_active ? 'Active' : 'Inactive'}</div>` : ''}</div><div class="actions">${!isDept ? `<button class="button secondary small" data-category-toggle="${x.id}" data-active="${x.is_active}">${x.is_active ? 'Deactivate' : 'Activate'}</button>` : ''}<button class="button danger small" data-reference-delete="${x.id}" data-kind="${kind}">Delete</button></div></div>`).join('') || '<div class="empty">No entries yet.</div>'}</div></article>`;
}

function renderReports() {
  const rows = activeComplaints();
  const counts = statusList.map((s) => [s, rows.filter((c) => c.status === s).length]);
  const categories = state.data.categories.map((cat) => [cat.name, rows.filter((c) => c.category_id === cat.id).length]).sort((a,b) => b[1]-a[1]);
  return `<div class="toolbar"><button class="button secondary" id="export-csv">Download CSV</button></div><div class="grid two-col"><article class="card"><h2>By status</h2>${counts.map(([s,n]) => `<div class="complaint-head meta-row"><span>${esc(s)}</span><strong>${n}</strong></div>`).join('')}</article><article class="card"><h2>By category</h2>${categories.map(([s,n]) => `<div class="complaint-head meta-row"><span>${esc(s)}</span><strong>${n}</strong></div>`).join('')}</article></div><article class="card" style="margin-top:1rem"><h2>Current concerns</h2>${complaintTable(rows,false)}</article>`;
}

function renderActivity() {
  const rows = state.data.activity_logs || [];
  return `<article class="card"><h2>Recent activity</h2>${rows.length ? `<div class="card-list">${rows.map((x) => `<div class="complaint-card"><strong>${esc(x.action)}</strong><div class="meta">${esc(fullName(x.user_id))} · ${dateText(x.created_at)}</div></div>`).join('')}</div>` : '<div class="empty">Activity entries will appear here.</div>'}</article>`;
}

function renderSettings() {
  const setting = (key, fallback) => state.data.app_settings.find((s) => s.setting_key === key)?.setting_value ?? fallback;
  return `<article class="card"><h2>System settings</h2><form id="settings-form" class="stack"><label class="inline"><input name="anonymous_reports_enabled" type="checkbox" ${setting('anonymous_reports_enabled','1') === '1' ? 'checked' : ''}> Allow anonymous reports</label><label class="inline"><input name="complaint_reopening_enabled" type="checkbox" ${setting('complaint_reopening_enabled','1') === '1' ? 'checked' : ''}> Allow students to reopen resolved concerns</label><label class="field">Default priority<select name="default_complaint_priority">${['Low','Medium','High','Urgent'].map((v) => `<option ${setting('default_complaint_priority','Medium') === v ? 'selected' : ''}>${v}</option>`).join('')}</select></label><button class="button primary">Save settings</button></form></article>`;
}

function renderProfile() {
  const p = state.profile;
  return `<article class="card"><h2>Profile details</h2><form id="profile-form" class="form-grid"><label class="field">Full name<input name="full_name" value="${esc(p.full_name)}" maxlength="150" required></label><label class="field">Email<input value="${esc(p.email)}" disabled></label>${p.role === 'student' ? `<label class="field">Student number<input name="student_number" value="${esc(p.student_number || '')}" maxlength="50"></label><label class="field">Course<input name="course" value="${esc(p.course || '')}" maxlength="120"></label><label class="field">Year level<input name="year_level" value="${esc(p.year_level || '')}" maxlength="30"></label>` : p.role === 'staff' ? `<label class="field">Position<input name="position" value="${esc(p.position || '')}" maxlength="120"></label>` : ''}<div class="field full"><button class="button primary">Save profile</button></div></form><p class="helper">Change your password using “Forgot password?” on the sign-in screen.</p></article>`;
}

function renderNotifications() {
  const rows = state.data.notifications;
  return `<article class="card"><div class="complaint-head"><h2>Your notifications</h2><button class="button secondary small" id="mark-all-read">Mark all read</button></div>${rows.length ? `<div class="card-list">${rows.map((n) => `<div class="complaint-card"><div class="complaint-head"><strong>${esc(n.title)}</strong>${n.is_read ? '' : '<span class="badge Pending">New</span>'}</div><p>${esc(n.message)}</p><div class="meta">${dateText(n.created_at)} ${n.complaint_id ? `· <button class="link-button" data-open="${n.complaint_id}">View concern</button>` : ''}</div>${!n.is_read ? `<button class="button secondary small" data-notification-read="${n.id}">Mark read</button>` : ''}</div>`).join('')}</div>` : '<div class="empty">You have no notifications.</div>'}</article>`;
}

function render() {
  buildNav();
  $('#page-title').textContent = labels[state.page] || 'Dashboard';
  const content = {
    dashboard: renderDashboard,
    complaints: renderComplaints,
    submit: renderSubmit,
    users: renderUsers,
    departments: () => renderReference('departments'),
    categories: () => renderReference('categories'),
    reports: renderReports,
    activity: renderActivity,
    settings: renderSettings,
    profile: renderProfile,
    notifications: renderNotifications,
  }[state.page];
  $('#page-content').innerHTML = content ? content() : renderDashboard();
}

async function logAction(action, complaintId = null) {
  const { error } = await table('activity_logs').insert({ user_id: state.user.id, complaint_id: complaintId, action });
  if (error) console.warn(error.message);
}

async function notifyUser(userId, title, message, complaintId = null) {
  const { error } = await table('notifications').insert({ user_id: userId, title, message, complaint_id: complaintId });
  if (error) console.warn('Notification was not created:', error.message);
}

async function refreshAndRender(message) {
  await loadData();
  render();
  if (message) toast(message);
}

async function openComplaint(id) {
  const c = state.data.complaints.find((x) => String(x.id) === String(id));
  if (!c) return toast('Concern is unavailable.', true);
  const isAdmin = state.profile.role === 'admin';
  const isStaff = state.profile.role === 'staff';
  const isStudent = state.profile.role === 'student';
  const assigned = state.data.complaint_assignments.some((a) => String(a.complaint_id) === String(c.id) && a.staff_id === state.user.id);
  const canHandle = isAdmin || (isStaff && assigned);
  const updates = state.data.complaint_updates.filter((u) => String(u.complaint_id) === String(c.id) && (!u.is_internal || canHandle));
  const assignments = state.data.complaint_assignments.filter((a) => String(a.complaint_id) === String(c.id));
  const feedback = state.data.feedback.find((f) => String(f.complaint_id) === String(c.id));
  const dept = byId(state.data.departments, c.department_id)?.name || '—';
  const cat = byId(state.data.categories, c.category_id)?.name || '—';
  const options = statusList.map((s) => `<option ${c.status === s ? 'selected' : ''}>${esc(s)}</option>`).join('');
  const handlers = canHandle ? `<form id="status-form" data-id="${c.id}" class="form-grid"><label class="field">Update status<select name="status">${options}</select></label><label class="field">Priority<select name="priority">${['Low','Medium','High','Urgent'].map((p) => `<option ${c.priority === p ? 'selected' : ''}>${p}</option>`).join('')}</select></label><label class="field full">Resolution / rejection notes<textarea name="resolution_details">${esc(c.resolution_details || c.rejection_reason || '')}</textarea></label><label class="inline"><input type="checkbox" name="is_internal"> Internal note (staff/admin only)</label><label class="field full">Update message<input name="message" maxlength="500"></label><div class="field full"><button class="button primary">Save update</button></div></form>` : '';
  const assign = isAdmin ? `<form id="assign-form" data-id="${c.id}" class="toolbar"><select class="filter" name="staff_id" required><option value="">Assign staff</option>${state.data.profiles.filter((p) => p.role === 'staff' && p.is_active).map((p) => `<option value="${p.id}">${esc(p.full_name)}</option>`).join('')}</select><button class="button secondary small">Assign</button></form><div class="meta">Assigned: ${assignments.map((a) => esc(fullName(a.staff_id))).join(', ') || 'No one yet'}</div>` : '';
  const reopen = isStudent && c.student_id === state.user.id && c.status === 'Resolved' ? `<button class="button secondary small" data-reopen="${c.id}">Request to reopen</button>` : '';
  const giveFeedback = isStudent && c.student_id === state.user.id && c.status === 'Resolved' ? `<form id="feedback-form" data-id="${c.id}" class="form-grid"><label class="field">Rating<select name="rating">${[5,4,3,2,1].map((n) => `<option ${feedback?.rating === n ? 'selected' : ''}>${n}</option>`).join('')}</select></label><label class="field">Comment<input name="comments" value="${esc(feedback?.comments || '')}" maxlength="1000"></label><div class="field full"><button class="button secondary small">${feedback ? 'Update feedback' : 'Send feedback'}</button></div></form>` : '';
  $('#dialog-content').innerHTML = `<div class="dialog-inner"><div class="dialog-top"><div><div class="eyebrow">${esc(c.tracking_no || `#${c.id}`)} · ${esc(cat)} · ${esc(dept)}</div><h2>${complaintTitle(c)}</h2><div class="meta">${badge(c.status)} · ${esc(c.priority)} · ${dateText(c.created_at)}</div></div><button class="button secondary small" id="close-dialog">Close</button></div><p>${esc(c.description)}</p>${c.resolution_details ? `<div class="notice"><strong>Resolution:</strong> ${esc(c.resolution_details)}</div>` : ''}${c.rejection_reason ? `<div class="notice error"><strong>Reason:</strong> ${esc(c.rejection_reason)}</div>` : ''}${assign}${handlers}${reopen}${giveFeedback}<hr class="divider"><h3>Updates</h3><div class="timeline">${updates.map((u) => `<div class="timeline-item">${esc(u.message)}${u.is_internal ? ' <span class="pill">Internal</span>' : ''}<small>${esc(fullName(u.user_id))} · ${dateText(u.created_at)}${u.new_status ? ` · ${esc(u.new_status)}` : ''}</small></div>`).join('') || '<p class="muted">No updates yet.</p>'}</div></div>`;
  $('#detail-dialog').showModal();
}

function csvDownload(rows) {
  const columns = ['tracking_no','title','status','priority','created_at','student_id'];
  const csv = [columns.join(','), ...rows.map((row) => columns.map((key) => `"${String(row[key] ?? '').replaceAll('"','""')}"`).join(','))].join('\r\n');
  const link = document.createElement('a');
  link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  link.download = 'student-concerns-report.csv';
  link.click();
  URL.revokeObjectURL(link.href);
}

document.addEventListener('click', async (event) => {
  const target = event.target.closest('[data-page],[data-open],[data-user-toggle],[data-user-dept],[data-category-toggle],[data-reference-delete],[data-notification-read],[data-reopen],#sign-out,#toggle-auth,#reset-password,#close-dialog,#mark-all-read,#export-csv');
  if (!target) return;
  if (target.dataset.page) { state.page = target.dataset.page; render(); return; }
  if (target.id === 'close-dialog') { $('#detail-dialog').close(); return; }
  if (target.id === 'sign-out') { await db.auth.signOut(); state.user = null; state.profile = null; state.data = {}; showAuth('You have signed out.'); return; }
  if (target.id === 'mark-all-read') {
    const { error } = await table('notifications').update({ is_read: true }).eq('user_id', state.user.id);
    if (error) return toast(error.message, true);
    return refreshAndRender('Notifications marked as read.');
  }
  if (target.id === 'export-csv') return csvDownload(activeComplaints());
  if (target.dataset.open) return openComplaint(target.dataset.open);
  if (target.dataset.notificationRead) {
    const { error } = await table('notifications').update({ is_read: true }).eq('id', target.dataset.notificationRead);
    if (error) return toast(error.message, true);
    return refreshAndRender('Notification marked as read.');
  }
  if (target.dataset.userToggle) {
    const active = target.dataset.active === 'true';
    const { error } = await table('profiles').update({ is_active: !active }).eq('id', target.dataset.userToggle);
    if (error) return toast(error.message, true);
    await logAction(`${active ? 'Deactivated' : 'Activated'} user account`);
    return refreshAndRender(active ? 'Account deactivated.' : 'Account activated.');
  }
  if (target.dataset.userDept) {
    const id = target.dataset.userDept;
    const person = state.data.profiles.find((p) => p.id === id);
    const choice = prompt(`Department for ${person?.full_name || 'staff'}:\n${state.data.departments.map((d) => `${d.id}: ${d.name}`).join('\n')}\n\nEnter a department ID, or leave blank to clear.`);
    if (choice === null) return;
    const { error } = await table('profiles').update({ department_id: choice ? Number(choice) : null }).eq('id', id);
    if (error) return toast(error.message, true);
    return refreshAndRender('Staff department updated.');
  }
  if (target.dataset.categoryToggle) {
    const active = target.dataset.active === 'true';
    const { error } = await table('categories').update({ is_active: !active }).eq('id', target.dataset.categoryToggle);
    if (error) return toast(error.message, true);
    return refreshAndRender('Category updated.');
  }
  if (target.dataset.referenceDelete) {
    if (!confirm('Delete this item? Items used by concerns cannot be deleted.')) return;
    const { error } = await table(target.dataset.kind).delete().eq('id', target.dataset.referenceDelete);
    if (error) return toast(error.message, true);
    return refreshAndRender('Item deleted.');
  }
  if (target.dataset.reopen) {
    const id = target.dataset.reopen;
    const complaint = state.data.complaints.find((c) => String(c.id) === String(id));
    const { error } = await table('complaints').update({ status: 'Reopened' }).eq('id', id);
    if (error) return toast(error.message, true);
    await table('complaint_updates').insert({ complaint_id: Number(id), user_id: state.user.id, previous_status: complaint.status, new_status: 'Reopened', message: 'Student requested that this concern be reopened.' });
    await logAction('Requested concern to be reopened', Number(id));
    $('#detail-dialog').close();
    return refreshAndRender('Reopen request submitted.');
  }
});

document.addEventListener('submit', async (event) => {
  const form = event.target;
  event.preventDefault();
  const submitter = event.submitter || $('button[type="submit"]', form);
  setBusy(submitter, true);
  try {
    if (form.id === 'auth-form') {
      const email = $('#auth-email').value.trim().toLowerCase();
      const password = $('#auth-password').value;
      if (state.authMode === 'signup') {
        const role = $('#auth-role').value;
        const metadata = { full_name: $('#auth-name').value.trim(), requested_role: role,
          student_number: $('#auth-student-number').value.trim(), course: $('#auth-course').value.trim(),
          year_level: $('#auth-year').value.trim(), position: $('#auth-position').value.trim() };
        const { data, error } = await db.auth.signUp({ email, password, options: { data: metadata, emailRedirectTo: location.href } });
        if (error) throw error;
        if (!data.session) showAuth(role === 'staff' ? 'Check your email to confirm the account. Staff access will begin after approval.' : 'Check your email to confirm your account, then sign in.');
        else await signedIn(data.session);
      } else {
        const { data, error } = await db.auth.signInWithPassword({ email, password });
        if (error) throw error;
        await signedIn(data.session);
      }
      return;
    }
    if (form.id === 'complaint-form') {
      const f = new FormData(form);
      const anonymousEnabled = state.data.app_settings.find((s) => s.setting_key === 'anonymous_reports_enabled')?.setting_value !== '0';
      const { data, error } = await table('complaints').insert({
        tracking_no: `SC-${new Date().getFullYear()}-${crypto.randomUUID().slice(0,8).toUpperCase()}`,
        student_id: state.user.id, category_id: Number(f.get('category_id')), department_id: Number(f.get('department_id')),
        title: String(f.get('title')).trim(), description: String(f.get('description')).trim(),
        priority: String(f.get('priority') || 'Medium'), is_anonymous: anonymousEnabled && f.has('is_anonymous'),
      }).select().single();
      if (error) throw error;
      await table('complaint_updates').insert({ complaint_id: data.id, user_id: state.user.id, new_status: 'Pending', message: 'Concern submitted.' });
      await logAction('Submitted a concern', data.id);
      state.page = 'complaints';
      await loadData(); render(); toast(`Concern submitted. Tracking ID: ${data.tracking_no}`);
      return;
    }
    if (form.id === 'reference-form') {
      const name = new FormData(form).get('name').trim();
      const { error } = await table(state.page).insert({ name });
      if (error) throw error;
      return refreshAndRender('Added successfully.');
    }
    if (form.id === 'profile-form') {
      const f = new FormData(form);
      const values = { full_name: String(f.get('full_name')).trim() };
      if (state.profile.role === 'student') Object.assign(values, { student_number: String(f.get('student_number') || '').trim() || null, course: String(f.get('course') || '').trim() || null, year_level: String(f.get('year_level') || '').trim() || null });
      if (state.profile.role === 'staff') values.position = String(f.get('position') || '').trim() || null;
      const { error } = await table('profiles').update(values).eq('id', state.user.id);
      if (error) throw error;
      state.profile = { ...state.profile, ...values };
      await logAction('Updated profile');
      return refreshAndRender('Profile saved.');
    }
    if (form.id === 'settings-form') {
      const f = new FormData(form);
      const values = [
        { setting_key: 'anonymous_reports_enabled', setting_value: f.has('anonymous_reports_enabled') ? '1' : '0' },
        { setting_key: 'complaint_reopening_enabled', setting_value: f.has('complaint_reopening_enabled') ? '1' : '0' },
        { setting_key: 'default_complaint_priority', setting_value: String(f.get('default_complaint_priority')) },
      ];
      const { error } = await table('app_settings').upsert(values, { onConflict: 'setting_key' });
      if (error) throw error;
      return refreshAndRender('Settings saved.');
    }
    if (form.id === 'assign-form') {
      const id = Number(form.dataset.id);
      const staffId = new FormData(form).get('staff_id');
      const { error } = await table('complaint_assignments').upsert({ complaint_id: id, staff_id: staffId, assigned_by: state.user.id }, { onConflict: 'complaint_id,staff_id' });
      if (error) throw error;
      await table('complaints').update({ status: 'Assigned' }).eq('id', id);
      const c = state.data.complaints.find((x) => x.id === id);
      await notifyUser(staffId, 'Concern assigned', `${c?.tracking_no || 'A concern'} has been assigned to you.`, id);
      await notifyUser(c.student_id, 'Concern assigned', `${c.tracking_no || 'Your concern'} has been assigned for handling.`, id);
      await logAction('Assigned a concern to staff', id);
      await loadData(); await openComplaint(id); toast('Concern assigned.');
      return;
    }
    if (form.id === 'status-form') {
      const id = Number(form.dataset.id);
      const f = new FormData(form);
      const old = state.data.complaints.find((c) => c.id === id);
      const status = String(f.get('status'));
      const resolution = String(f.get('resolution_details') || '').trim();
      const values = { status, priority: String(f.get('priority')) };
      if (state.profile.role === 'admin') {
        values.resolution_details = status === 'Resolved' ? resolution : old.resolution_details;
        values.rejection_reason = status === 'Rejected' ? resolution : old.rejection_reason;
      } else if (status === 'Resolved') values.resolution_details = resolution;
      const { error } = await table('complaints').update(values).eq('id', id);
      if (error) throw error;
      const message = String(f.get('message') || '').trim() || `Status updated to ${status}.`;
      const { error: updateError } = await table('complaint_updates').insert({ complaint_id: id, user_id: state.user.id, previous_status: old.status, new_status: status, message, is_internal: f.has('is_internal') });
      if (updateError) throw updateError;
      if (!f.has('is_internal')) await notifyUser(old.student_id, 'Concern updated', `${old.tracking_no || 'Your concern'}: ${message}`, id);
      await logAction(`Updated concern status to ${status}`, id);
      await loadData(); await openComplaint(id); toast('Concern updated.');
      return;
    }
    if (form.id === 'feedback-form') {
      const f = new FormData(form);
      const { error } = await table('feedback').upsert({ complaint_id: Number(form.dataset.id), student_id: state.user.id, rating: Number(f.get('rating')), comments: String(f.get('comments') || '').trim() || null }, { onConflict: 'complaint_id' });
      if (error) throw error;
      await logAction('Submitted feedback', Number(form.dataset.id));
      await loadData(); await openComplaint(form.dataset.id); toast('Feedback saved.');
    }
  } catch (error) {
    toast(error.message || 'Something went wrong.', true);
  } finally { setBusy(submitter, false); }
});

document.addEventListener('input', (event) => {
  if (event.target.id === 'complaint-search' || event.target.id === 'status-filter') {
    const text = ($('#complaint-search')?.value || '').toLowerCase();
    const status = $('#status-filter')?.value || '';
    let rows = activeComplaints();
    if (state.profile.role === 'student') rows = rows.filter((c) => c.student_id === state.user.id);
    if (state.profile.role === 'staff') {
      const ids = new Set(state.data.complaint_assignments.filter((a) => a.staff_id === state.user.id).map((a) => a.complaint_id));
      rows = rows.filter((c) => ids.has(c.id));
    }
    rows = rows.filter((c) => (!status || c.status === status) && (`${c.title} ${c.tracking_no}`.toLowerCase().includes(text)));
    $('#complaint-list').innerHTML = complaintTable(rows);
  }
  if (event.target.id === 'user-search' || event.target.id === 'role-filter' || event.target.id === 'account-filter') {
    const text = ($('#user-search')?.value || '').toLowerCase(), role = $('#role-filter')?.value || '', account = $('#account-filter')?.value || '';
    const rows = state.data.profiles.filter((p) => (!text || `${p.full_name} ${p.email}`.toLowerCase().includes(text)) && (!role || p.role === role) && (!account || (account === 'pending' ? p.role === 'staff' && !p.is_active : account === 'active' ? p.is_active : !p.is_active)));
    const tbody = $('#page-content tbody');
    if (tbody) tbody.innerHTML = rows.map((p) => `<tr><td><strong>${esc(p.full_name)}</strong><div class="meta">${esc(p.email)}</div></td><td>${esc(p.role)}</td><td>${esc(p.role === 'student' ? [p.student_number,p.course,p.year_level].filter(Boolean).join(' · ') : p.position || '—')}</td><td>${p.is_active ? '<span class="badge Resolved">Active</span>' : '<span class="badge Pending">Pending / inactive</span>'}</td><td>${p.id !== state.user.id ? `<button class="button secondary small" data-user-toggle="${p.id}" data-active="${p.is_active}">${p.is_active ? 'Deactivate' : 'Approve / activate'}</button>` : 'You'}</td></tr>`).join('');
  }
});

$('#toggle-auth').addEventListener('click', () => authMode(state.authMode === 'login' ? 'signup' : 'login'));
$('#reset-password').addEventListener('click', async () => {
  const email = $('#auth-email').value.trim();
  if (!email) return showAuth('Enter your email first, then choose Forgot password.', true);
  const { error } = await db.auth.resetPasswordForEmail(email, { redirectTo: location.href });
  showAuth(error ? error.message : 'If an account exists, a password reset email has been sent.');
});
$('#notifications-button').addEventListener('click', () => { state.page = 'notifications'; render(); });
$('#detail-dialog').addEventListener('click', (event) => { if (event.target === $('#detail-dialog')) $('#detail-dialog').close(); });

if (!configured) {
  authMode('login');
  showAuth('Add your Supabase project URL and publishable (anon) key to supabase-app/config.js to connect this app.', true);
} else {
  authMode('login');
  db.auth.onAuthStateChange((_event, session) => { queueMicrotask(() => signedIn(session)); });
  db.auth.getSession().then(({ data }) => signedIn(data.session));
}
