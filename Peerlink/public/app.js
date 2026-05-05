// ===== UTILITIES =====
function esc(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function showToast(message) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ===== STATE =====
let userProfile = { bio: '', tutorCourses: [], tuteeCourses: [] };
let currentMode = 'tutee';
let currentView = 'dashboard';
let activeDashboardCourses = [];
let allTutors = [];
let allUniqueCourses = new Set();
let selectedTutor = null;
let dropdownOpen = false;
let notifDropdownOpen = false;
let myRequestsData = [];
let broadcastRequests = [];
let pendingRequests = [];
let availableRooms = [];
let obCourses = [];       // onboarding: courses the user picked to tutor
let currentReqTab = 'direct';

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  setMode('tutee');
  switchView('dashboard');

  fetchProfile();   // loads bio, tutorCourses, stats, rooms
  fetchTutors();
  fetchNotifications();
  setInterval(fetchNotifications, 30000);

  // Bio char counter
  const bioInput = document.getElementById('bioInput');
  if (bioInput) {
    bioInput.addEventListener('input', () => {
      document.getElementById('charCount').textContent = bioInput.value.length;
    });
  }

  // Star rating
  document.querySelectorAll('.star-btn').forEach(star => {
    star.addEventListener('click', () => {
      const val = parseInt(star.dataset.val);
      document.getElementById('reviewRating').value = val;
      document.querySelectorAll('.star-btn').forEach((s, i) => s.classList.toggle('active', i < val));
    });
  });

  // Close dropdowns on outside click
  document.addEventListener('click', e => {
    const bell = document.getElementById('notifBellWrap');
    if (bell && !bell.contains(e.target) && notifDropdownOpen) {
      notifDropdownOpen = false;
      document.getElementById('notifDropdown').classList.remove('open');
    }
    const adder = document.querySelector('.course-adder');
    if (adder && !adder.contains(e.target)) {
      dropdownOpen = false;
      document.getElementById('courseDropdown').classList.remove('open');
    }
  });

  // Course input live filter
  document.getElementById('courseInput').addEventListener('input', e => {
    renderCourseSuggestions(e.target.value.replace(/\s+/g, '').toUpperCase());
  });

  // Onboarding
  if (window.__onboarding) {
    initOnboarding();
  }
});

// ===== NAVIGATION =====
function switchView(view) {
  currentView = view;
  ['dashboard', 'myRequests', 'profile'].forEach(v => {
    const el = document.getElementById(v + 'View') || document.getElementById(v.charAt(0).toUpperCase() + v.slice(1) + 'View');
    const key = { dashboard: 'dashboardView', myRequests: 'myRequestsView', profile: 'profileView' }[v];
    if (key) document.getElementById(key).style.display = (v === view) ? 'block' : 'none';
  });

  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const navMap = { dashboard: 'navDashboard', myRequests: 'navMyRequests', profile: 'navProfile' };
  if (navMap[view]) document.getElementById(navMap[view]).classList.add('active');

  if (view === 'myRequests') fetchMyRequests();
}

// ===== MODE TOGGLE =====
function setMode(mode) {
  currentMode = mode;
  document.body.className = `mode-${mode}`;
  const badge = document.getElementById('roleBadge');
  if (mode === 'tutor') {
    badge.textContent = 'TUTOR PROFILE';
    badge.style.background = 'var(--teal-dark)';
  } else {
    badge.textContent = 'TUTEE PROFILE';
    badge.style.background = 'var(--coral)';
  }
}

// ===== PROFILE =====
async function fetchProfile() {
  try {
    const res = await fetch('/api/profile');
    if (!res.ok) return;
    const data = await res.json();

    userProfile.bio          = data.bio || '';
    userProfile.tutorCourses = data.tutorCourses || [];
    availableRooms           = data.rooms || [];

    updateProfileDisplay();
    populateGroupRoomSelect();

    // Tutor dashboard stats
    document.getElementById('statSessions').textContent = data.upcomingSessions ?? '—';
    document.getElementById('statRating').textContent   = data.ratingAvg > 0 ? data.ratingAvg.toFixed(1) : '—';
    document.getElementById('statCourses').textContent  = data.coursesCount ?? '—';
  } catch {
    // silently fail; stats stay as '—'
  }
}

function toggleEditMode(isEdit) {
  document.getElementById('profileViewMode').style.display = isEdit ? 'none' : 'block';
  document.getElementById('profileEditMode').style.display = isEdit ? 'block' : 'none';
  if (isEdit) {
    document.getElementById('bioInput').value = userProfile.bio;
    document.getElementById('charCount').textContent = userProfile.bio.length;
    renderProfileTags('tutor');
    renderProfileTags('tutee');
  }
}

function addProfileCourse(type) {
  const sel = document.getElementById(`${type}Select`);
  const val = sel.value;
  const list = type === 'tutor' ? userProfile.tutorCourses : userProfile.tuteeCourses;
  if (val && !list.includes(val)) { list.push(val); renderProfileTags(type); }
  sel.selectedIndex = 0;
}

function renderProfileTags(type) {
  const container = document.getElementById(`${type}Tags`);
  const list = type === 'tutor' ? userProfile.tutorCourses : userProfile.tuteeCourses;
  container.innerHTML = list.map(c => `
    <span class="filter-tag">${esc(c)}<button type="button" onclick="removeProfileTag('${esc(c)}','${type}')">✕</button></span>
  `).join('');
}

function removeProfileTag(course, type) {
  if (type === 'tutor') userProfile.tutorCourses = userProfile.tutorCourses.filter(c => c !== course);
  else                  userProfile.tuteeCourses = userProfile.tuteeCourses.filter(c => c !== course);
  renderProfileTags(type);
}

async function saveProfile(event) {
  event.preventDefault();
  userProfile.bio = document.getElementById('bioInput').value;

  try {
    const res = await fetch('/api/profile', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ bio: userProfile.bio, tutorCourses: userProfile.tutorCourses }),
    });
    if (!res.ok) throw new Error();
    showToast('Profile saved!');
    fetchProfile(); // refresh stats
  } catch {
    showToast('Failed to save profile.');
    return;
  }

  updateProfileDisplay();
  toggleEditMode(false);
}

function updateProfileDisplay() {
  document.getElementById('bioDisplay').textContent = userProfile.bio || 'No bio added yet.';
  document.getElementById('tutorCoursesDisplay').innerHTML =
    userProfile.tutorCourses.map(c => `<span class="course-badge">${esc(c)}</span>`).join('');
  document.getElementById('tuteeCoursesDisplay').innerHTML =
    userProfile.tuteeCourses.map(c => `<span class="course-badge">${esc(c)}</span>`).join('');
}

// ===== DELETE ACCOUNT =====
function openDeleteModal() {
  document.getElementById('deleteConfirmPassword').value = '';
  document.getElementById('deleteError').style.display = 'none';
  document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
function prepareDeleteSubmit() {
  const pw = document.getElementById('deleteConfirmPassword').value.trim();
  if (!pw) {
    document.getElementById('deleteError').textContent = 'Please enter your password.';
    document.getElementById('deleteError').style.display = 'block';
    return false; // prevent form submit
  }
  document.getElementById('deletePasswordHidden').value = pw;
  return true; // allow form submit
}

// ===== EXPLORE TUTORS =====
function toggleCourseDropdown() {
  dropdownOpen = !dropdownOpen;
  document.getElementById('courseDropdown').classList.toggle('open', dropdownOpen);
  if (dropdownOpen) {
    setTimeout(() => document.getElementById('courseInput').focus(), 50);
    renderCourseSuggestions('');
  }
}

function handleCourseInput(e) {
  if (e.key === 'Enter') {
    const val = document.getElementById('courseInput').value.replace(/\s+/g, '').toUpperCase();
    if (val) { addFilterCourse(val); document.getElementById('courseInput').value = ''; }
  }
}

function renderCourseSuggestions(query) {
  const container = document.getElementById('courseSuggestions');
  let list = Array.from(allUniqueCourses);
  if (query) list = list.filter(c => c.includes(query));
  container.innerHTML = list.slice(0, 8)
    .map(c => `<button onclick="addFilterCourse('${esc(c)}')">${esc(c)}</button>`).join('');
}

function addFilterCourse(code) {
  code = code.replace(/\s+/g, '').toUpperCase();
  if (!code || activeDashboardCourses.includes(code)) return;
  activeDashboardCourses.push(code);
  renderFilterTags();
  filterTutors();
  dropdownOpen = false;
  document.getElementById('courseDropdown').classList.remove('open');
}

function removeFilterCourse(code) {
  activeDashboardCourses = activeDashboardCourses.filter(c => c !== code);
  renderFilterTags();
  filterTutors();
}

function renderFilterTags() {
  document.getElementById('filterTags').innerHTML = activeDashboardCourses.map(code => `
    <span class="filter-tag">${esc(code)}<button onclick="removeFilterCourse('${esc(code)}')" title="Remove">✕</button></span>
  `).join('');
}

async function fetchTutors() {
  try {
    const res = await fetch('/api/tutors');
    if (!res.ok) throw new Error();
    const data = await res.json();
    allTutors = data.tutors || [];
    allTutors.forEach(t => (t.courses || []).forEach(c => allUniqueCourses.add(c)));
    filterTutors();
  } catch {
    allTutors = [];
    filterTutors();
  }
}

function filterTutors() {
  const query = document.getElementById('searchName').value.trim().toLowerCase();
  const filtered = allTutors.filter(t => {
    const nameMatch   = !query || t.name.toLowerCase().includes(query);
    const courseMatch = activeDashboardCourses.length === 0 ||
      activeDashboardCourses.every(c => (t.courses || []).includes(c));
    return nameMatch && courseMatch;
  });
  renderTutors(filtered);
}

function renderTutors(tutors) {
  const grid  = document.getElementById('tutorsGrid');
  const empty = document.getElementById('emptyState');
  if (!tutors.length) { grid.innerHTML = ''; empty.style.display = 'flex'; return; }
  empty.style.display = 'none';
  grid.innerHTML = tutors.map((t, i) => `
    <div class="tutor-card" style="animation-delay:${i * 0.06}s">
      <div class="tutor-card-header">
        <div class="tutor-avatar">${esc(t.initials || t.name[0])}</div>
        <div class="tutor-info">
          <div class="tutor-name">${esc(t.name)}</div>
          <div class="tutor-degree">${esc(t.degree || '')}</div>
          <div class="tutor-rating"><span class="star">★</span> ${(t.rating || 0).toFixed(1)}
            <span>(${t.reviews || 0} reviews)</span></div>
        </div>
      </div>
      <div class="tutor-courses">
        ${(t.courses || []).map(c => `<span class="course-badge">${esc(c)}</span>`).join('')}
      </div>
      <button class="btn-primary full-width" style="background:var(--purple);"
              onclick="openSessionModal('${esc(t.id)}')">Request session</button>
    </div>
  `).join('');
}

// ===== SESSION REQUEST MODAL (US_09) =====
function openSessionModal(id) {
  const tutor = allTutors.find(t => t.id === id);
  if (!tutor) return;
  selectedTutor = tutor;

  document.getElementById('modalName').textContent = tutor.name;
  document.getElementById('modalSub').textContent  = tutor.degree || '';
  document.getElementById('modalAvatar').textContent = tutor.initials || tutor.name[0];

  const sel = document.getElementById('sessionCourse');
  sel.innerHTML = '<option value="" disabled selected>Select a course</option>' +
    (tutor.courses || []).map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('');

  document.getElementById('sessionTopic').value   = '';
  document.getElementById('sessionDate').value    = '';
  document.getElementById('sessionMessage').value = '';
  document.getElementById('sessionModalOverlay').classList.add('open');
}

function closeSessionModal() {
  document.getElementById('sessionModalOverlay').classList.remove('open');
  selectedTutor = null;
}

async function submitRequest() {
  const courseCode = document.getElementById('sessionCourse').value;
  const topic      = document.getElementById('sessionTopic').value.trim();
  const date       = document.getElementById('sessionDate').value;
  const message    = document.getElementById('sessionMessage').value.trim();

  if (!courseCode || !topic || !date) { showToast('Please fill out all required fields.'); return; }

  try {
    const res = await fetch('/api/requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({
        course_code:    courseCode,
        tutor_id:       selectedTutor?.id,
        message:        topic + (message ? '\n' + message : ''),
        preferred_date: date,
      }),
    });
    if (!res.ok) throw new Error();
    closeSessionModal();
    showToast(`Request sent to ${selectedTutor?.name}!`);
  } catch {
    showToast('Failed to send request. Please try again.');
  }
}

// ===== TUTOR: REQUESTS MODAL =====
function openRequestsModal() {
  document.getElementById('requestsModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  switchReqTab('direct');
}

function closeRequestsModal() {
  document.getElementById('requestsModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function switchReqTab(tab) {
  currentReqTab = tab;
  document.getElementById('tabDirect').classList.toggle('active', tab === 'direct');
  document.getElementById('tabBroadcast').classList.toggle('active', tab === 'broadcast');
  document.getElementById('requestsList').style.display   = tab === 'direct'    ? 'block' : 'none';
  document.getElementById('broadcastList').style.display  = tab === 'broadcast' ? 'block' : 'none';

  if (tab === 'direct')    fetchTutorRequests();
  if (tab === 'broadcast') fetchBroadcastRequests();
}

async function fetchTutorRequests() {
  try {
    const res = await fetch('/api/requests?role=tutor');
    if (!res.ok) throw new Error();
    const data = await res.json();
    pendingRequests = data.requests || [];
  } catch {
    pendingRequests = [];
  }
  renderTutorRequests();
}

function renderTutorRequests() {
  const list = document.getElementById('requestsList');
  if (!pendingRequests.length) {
    list.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No pending requests! 🎉</p>`;
    return;
  }
  list.innerHTML = pendingRequests.map(req => {
    const dateStr = new Date(req.date).toLocaleString([], { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    return `
    <div class="request-card" id="reqCard-${esc(req.id)}">
      <div class="request-header">
        <div class="request-tutee">${esc(req.tuteeName)}</div>
        <span class="course-badge" style="margin:0;background:var(--teal);color:white;">${esc(req.course || 'New')}</span>
      </div>
      <div class="request-topic">${esc(req.topic)}</div>
      <div class="request-date">🗓 ${dateStr}</div>
      ${req.message ? `<div class="request-msg">"${esc(req.message)}"</div>` : ''}
      <div class="request-actions">
        <button class="btn-accept"  onclick="respondTutorRequest('${esc(req.id)}','accept')">Accept</button>
        <button class="btn-outline" onclick="openCounterModal('${esc(req.id)}')">Propose Changes</button>
        <button class="btn-decline" onclick="respondTutorRequest('${esc(req.id)}','decline')">Decline</button>
      </div>
    </div>`;
  }).join('');
}

async function respondTutorRequest(id, action) {
  try {
    const res = await fetch(`/api/requests/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ action }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.error || 'Action failed.');
      return;
    }
    pendingRequests = pendingRequests.filter(r => r.id !== id);
    renderTutorRequests();
    showToast(action === 'accept' ? 'Session accepted!' : 'Request declined.');
    if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
  } catch {
    showToast('Action failed. Please try again.');
  }
}

// ===== BROADCAST POOL =====
async function fetchBroadcastRequests() {
  try {
    const res = await fetch('/api/requests?role=broadcast');
    if (!res.ok) throw new Error();
    const data = await res.json();
    broadcastRequests = data.requests || [];
  } catch {
    broadcastRequests = [];
  }
  renderBroadcastRequests();
}

function renderBroadcastRequests() {
  const list = document.getElementById('broadcastList');
  if (!broadcastRequests.length) {
    list.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No open broadcast requests right now.</p>`;
    return;
  }
  list.innerHTML = broadcastRequests.map(req => {
    const dateStr = new Date(req.date).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    return `
    <div class="request-card" id="bcCard-${esc(req.id)}">
      <div class="request-header">
        <div class="request-tutee">${esc(req.studentName)}</div>
        <span class="course-badge" style="margin:0;background:var(--purple);color:white;">${esc(req.course || '')}</span>
      </div>
      <div class="request-topic">${esc(req.topic)}</div>
      <div class="request-date">🗓 ${dateStr}</div>
      ${req.message ? `<div class="request-msg">"${esc(req.message)}"</div>` : ''}
      <div class="request-actions">
        <button class="btn-accept" onclick="claimBroadcast('${esc(req.id)}')">Claim &amp; Accept</button>
      </div>
    </div>`;
  }).join('');
}

async function claimBroadcast(id) {
  try {
    const res = await fetch(`/api/requests/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ action: 'claim' }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.error || 'Could not claim request.');
      return;
    }
    broadcastRequests = broadcastRequests.filter(r => r.id !== id);
    renderBroadcastRequests();
    showToast('Broadcast claimed and session scheduled!');
    fetchProfile(); // update upcoming sessions count
  } catch {
    showToast('Action failed. Please try again.');
  }
}

// ===== US_11: COUNTER-PROPOSAL MODAL =====
function openCounterModal(requestId) {
  document.getElementById('counterRequestId').value  = requestId;
  document.getElementById('counterTime').value       = '';
  document.getElementById('counterMessage').value    = '';
  document.getElementById('counterModality').value   = 'In-Person';
  document.getElementById('counterModalOverlay').classList.add('open');
}

function closeCounterModal() {
  document.getElementById('counterModalOverlay').classList.remove('open');
}

async function submitCounterProposal() {
  const requestId = document.getElementById('counterRequestId').value;
  const time      = document.getElementById('counterTime').value;
  const message   = document.getElementById('counterMessage').value.trim();
  const modality  = document.getElementById('counterModality').value;

  if (!time) { showToast('Please select a proposed date and time.'); return; }

  try {
    const res = await fetch(`/api/requests/${requestId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ action: 'counter_propose', counter_time: time, counter_message: message, counter_modality: modality }),
    });
    if (!res.ok) throw new Error();
    closeCounterModal();
    pendingRequests = pendingRequests.filter(r => r.id !== requestId);
    renderTutorRequests();
    showToast('Counter-proposal sent to student.');
    if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
  } catch {
    showToast('Failed to send counter-proposal.');
  }
}

// ===== US_13: GROUP SESSION MODAL =====
function populateGroupRoomSelect() {
  const sel = document.getElementById('groupRoom');
  if (!sel) return;
  sel.innerHTML = '<option value="">— auto-assign —</option>' +
    availableRooms.map(r => `<option value="${r.room_id}">${esc(r.room_name)} (${r.room_type})</option>`).join('');
}

function toggleGroupLink() {
  const modality = document.getElementById('groupModality').value;
  document.getElementById('groupRoomWrap').style.display = modality === 'In-Person' ? 'block' : 'none';
  document.getElementById('groupLinkWrap').style.display = modality === 'Online'    ? 'block' : 'none';
}

function openGroupSessionModal() {
  document.getElementById('groupCourse').selectedIndex  = 0;
  document.getElementById('groupTime').value            = '';
  document.getElementById('groupModality').value        = 'In-Person';
  document.getElementById('groupMessage').value         = '';
  document.getElementById('groupLink').value            = '';
  document.getElementById('groupRoomWrap').style.display = 'block';
  document.getElementById('groupLinkWrap').style.display = 'none';
  document.getElementById('groupSessionModalOverlay').classList.add('open');
}

function closeGroupSessionModal() {
  document.getElementById('groupSessionModalOverlay').classList.remove('open');
}

async function submitGroupSession() {
  const courseCode = document.getElementById('groupCourse').value;
  const time       = document.getElementById('groupTime').value;
  const modality   = document.getElementById('groupModality').value;
  const roomId     = document.getElementById('groupRoom').value || null;
  const link       = document.getElementById('groupLink').value.trim();
  const message    = document.getElementById('groupMessage').value.trim();

  if (!courseCode || !time) { showToast('Please fill in course and date/time.'); return; }
  if (modality === 'Online' && !link) { showToast('Please provide a meeting link for online sessions.'); return; }

  try {
    const res = await fetch('/api/sessions/broadcast', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({
        course_code:    courseCode,
        scheduled_time: time,
        modality,
        room_id:        roomId ? parseInt(roomId) : null,
        meeting_link:   link || null,
        message,
      }),
    });
    if (!res.ok) throw new Error();
    closeGroupSessionModal();
    showToast('Group session posted!');
    fetchProfile();
  } catch {
    showToast('Failed to post group session.');
  }
}

// ===== MY REQUESTS VIEW (US_10 + US_11 student + US_16) =====
async function fetchMyRequests() {
  try {
    const res = await fetch('/api/requests?role=student');
    if (!res.ok) throw new Error();
    const data = await res.json();
    myRequestsData = data.requests || [];
  } catch {
    myRequestsData = [];
  }
  renderMyRequests();
}

function renderMyRequests() {
  const filter = document.getElementById('myRequestsFilter').value;
  const list   = document.getElementById('myRequestsList');
  const shown  = filter === 'all' ? myRequestsData : myRequestsData.filter(r => r.status === filter);

  if (!shown.length) {
    list.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No requests found.</p>`;
    return;
  }

  const statusColor = {
    Pending:         'var(--teal)',
    Approved:        '#4caf50',
    Declined:        'var(--coral)',
    Expired:         '#aaa',
    CounterProposed: 'var(--purple)',
  };

  list.innerHTML = shown.map(req => {
    const color     = statusColor[req.status] || '#aaa';
    const createdAt = req.createdAt ? new Date(req.createdAt).toLocaleDateString() : '';

    let counterHtml = '';
    if (req.status === 'CounterProposed' && req.counterProposal) {
      const cp     = req.counterProposal;
      const cpDate = cp.proposedTime
        ? new Date(cp.proposedTime).toLocaleString([], { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
        : '—';
      counterHtml = `
        <div class="counter-proposal-box">
          <strong>Tutor proposes:</strong>
          <div>🗓 ${cpDate}${cp.modality ? ' &nbsp;|&nbsp; ' + esc(cp.modality) : ''}${cp.room ? ' @ ' + esc(cp.room) : ''}</div>
          ${cp.message ? `<div style="font-style:italic;margin-top:.25rem;">"${esc(cp.message)}"</div>` : ''}
          <div class="request-actions" style="margin-top:.75rem;">
            <button class="btn-accept"  onclick="respondToCounter('${esc(req.id)}','student_accept')">Accept</button>
            <button class="btn-decline" onclick="respondToCounter('${esc(req.id)}','student_decline')">Decline</button>
          </div>
        </div>`;
    }

    let sessionHtml = '';
    if (req.session) {
      const sDate = req.session.scheduledTime
        ? new Date(req.session.scheduledTime).toLocaleString([], { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
        : '—';
      sessionHtml = `
        <div class="session-details-box">
          <strong>Session:</strong> ${esc(req.session.modality || '')}
          ${req.session.room ? '@ ' + esc(req.session.room) : ''} &nbsp;|&nbsp; 🗓 ${sDate}
          ${!req.session.hasReview && req.tutorId
            ? `<button class="btn-outline"
                style="margin-left:.5rem;padding:.3rem .8rem;font-size:.8rem;"
                onclick="openReviewModal('${esc(req.session.session_id)}','${esc(req.tutorId)}','${esc(req.tutorName)}')">
                ★ Leave Review
              </button>`
            : ''}
          ${req.session.hasReview ? `<span style="color:#aaa;margin-left:.5rem;font-size:.85rem;">Reviewed ✓</span>` : ''}
        </div>`;
    }

    return `
      <div class="request-card">
        <div class="request-header">
          <div class="request-tutee">${esc(req.course || '—')} &mdash; ${esc(req.tutorName || 'Broadcast')}</div>
          <span class="course-badge" style="margin:0;background:${color};color:white;">${esc(req.status)}</span>
        </div>
        ${req.message ? `<div class="request-msg" style="margin-top:.5rem;">"${esc(req.message)}"</div>` : ''}
        <div class="request-date" style="margin-top:.25rem;">Sent: ${createdAt}</div>
        ${counterHtml}
        ${sessionHtml}
      </div>`;
  }).join('');
}

async function respondToCounter(requestId, action) {
  try {
    const res = await fetch(`/api/requests/${requestId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ action }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.error || 'Action failed.');
      return;
    }
    showToast(action === 'student_accept' ? 'Session confirmed!' : 'Counter-proposal declined.');
    fetchMyRequests();
  } catch {
    showToast('Action failed. Please try again.');
  }
}

// ===== US_16: REVIEW MODAL =====
function openReviewModal(sessionId, tutorId, tutorName) {
  document.getElementById('reviewSessionId').value         = sessionId;
  document.getElementById('reviewTutorId').value           = tutorId;
  document.getElementById('reviewTutorName').textContent   = `Rate your session with ${tutorName}`;
  document.getElementById('reviewRating').value            = '0';
  document.getElementById('reviewFeedback').value          = '';
  document.querySelectorAll('.star-btn').forEach(s => s.classList.remove('active'));
  document.getElementById('reviewModalOverlay').classList.add('open');
}

function closeReviewModal() {
  document.getElementById('reviewModalOverlay').classList.remove('open');
}

async function submitReview() {
  const sessionId = document.getElementById('reviewSessionId').value;
  const tutorId   = document.getElementById('reviewTutorId').value;
  const rating    = parseInt(document.getElementById('reviewRating').value);
  const feedback  = document.getElementById('reviewFeedback').value.trim();

  if (!rating) { showToast('Please select a star rating.'); return; }

  try {
    const res = await fetch('/api/reviews', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ session_id: sessionId, reviewee_id: tutorId, rating, feedback }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.error || 'Failed to submit review.');
      return;
    }
    closeReviewModal();
    showToast('Review submitted!');
    fetchMyRequests();
    fetchTutors(); // refresh ratings in explore
  } catch {
    showToast('Failed to submit review.');
  }
}

// ===== US_15: NOTIFICATIONS =====
async function fetchNotifications() {
  try {
    const res = await fetch('/api/notifications');
    if (!res.ok) return;
    const data = await res.json();
    renderNotifications(data.notifications || [], data.unread_count || 0);
  } catch { /* silently ignore poll failures */ }
}

function renderNotifications(notifications, unreadCount) {
  const badge = document.getElementById('notifBadge');
  if (unreadCount > 0) {
    badge.textContent   = unreadCount > 9 ? '9+' : unreadCount;
    badge.style.display = 'flex';
  } else {
    badge.style.display = 'none';
  }

  const list = document.getElementById('notifList');
  if (!notifications.length) {
    list.innerHTML = `<p class="notif-empty">No notifications yet.</p>`;
    return;
  }
  list.innerHTML = notifications.map(n => {
    const time = n.created_at
      ? new Date(n.created_at).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
      : '';
    return `
      <div class="notif-item${n.is_read ? '' : ' notif-unread'}">
        <div class="notif-msg">${esc(n.message)}</div>
        <div class="notif-time">${time}</div>
      </div>`;
  }).join('');
}

function toggleNotifDropdown() {
  notifDropdownOpen = !notifDropdownOpen;
  document.getElementById('notifDropdown').classList.toggle('open', notifDropdownOpen);
  if (notifDropdownOpen) markAllNotificationsRead();
}

async function markAllNotificationsRead() {
  try {
    await fetch('/api/notifications/read', {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': getCsrfToken() },
    });
    document.getElementById('notifBadge').style.display = 'none';
    document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
  } catch { /* ignore */ }
}

// ===== US_06 + US_07: ONBOARDING =====
let obStep = 1;

function initOnboarding() {
  const overlay = document.getElementById('onboardingOverlay');
  overlay.style.display = 'flex';

  // Populate course select from window.__courses
  const sel = document.getElementById('obCourseSelect');
  if (sel && window.__courses) {
    window.__courses.forEach(c => {
      const opt = document.createElement('option');
      opt.value       = c.code;
      opt.textContent = `${c.code} — ${c.name}`;
      sel.appendChild(opt);
    });
  }

  obStep = 1;
  showObStep(1);
}

function showObStep(n) {
  [1, 2, 3, 4].forEach(i => {
    const el = document.getElementById(`obStep${i}`);
    if (el) el.style.display = i === n ? 'block' : 'none';
  });
}

function obNext() { obStep++; showObStep(obStep); }
function obPrev() { obStep--; showObStep(obStep); }

function obAddCourse() {
  const sel = document.getElementById('obCourseSelect');
  const val = sel.value;
  if (val && !obCourses.includes(val)) {
    obCourses.push(val);
    renderObTags();
  }
  sel.selectedIndex = 0;
}

function obRemoveCourse(code) {
  obCourses = obCourses.filter(c => c !== code);
  renderObTags();
}

function renderObTags() {
  const container = document.getElementById('obCourseTags');
  container.innerHTML = obCourses.map(c => `
    <span class="filter-tag">${esc(c)}
      <button type="button" onclick="obRemoveCourse('${esc(c)}')">✕</button>
    </span>
  `).join('');
}

async function obFinish() {
  const btn = document.getElementById('obFinishBtn');
  btn.disabled   = true;
  btn.textContent = 'Saving…';

  // Save courses via real API (US_07)
  if (obCourses.length > 0) {
    try {
      await fetch('/api/profile', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({ tutorCourses: obCourses }),
      });
      userProfile.tutorCourses = [...obCourses];
      updateProfileDisplay();
      fetchProfile();
    } catch { /* non-blocking */ }
  }

  document.getElementById('onboardingOverlay').style.display = 'none';
  showToast('Welcome to PeerLink!');
}
