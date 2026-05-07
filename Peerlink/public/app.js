// ===== CONFIRM MODAL =====
let _confirmCallback = null;

function showConfirmModal(title, message, onConfirm, opts = {}) {
  const {
    icon         = '⚠️',
    confirmLabel = 'Confirm',
    cancelLabel  = 'Cancel',
    destructive  = true,
  } = opts;

  document.getElementById('confirmIcon').textContent        = icon;
  document.getElementById('confirmTitle').textContent       = title;
  document.getElementById('confirmMessage').textContent     = message;
  document.getElementById('confirmCancelBtn').textContent   = cancelLabel;

  const okBtn = document.getElementById('confirmOkBtn');
  okBtn.textContent       = confirmLabel;
  okBtn.style.background  = destructive ? 'var(--coral)' : 'var(--teal)';

  _confirmCallback = onConfirm;
  document.getElementById('confirmModalOverlay').classList.add('open');
}

function closeConfirmModal() {
  document.getElementById('confirmModalOverlay').classList.remove('open');
  _confirmCallback = null;
}

function _executeConfirm() {
  const cb = _confirmCallback;
  closeConfirmModal();
  if (cb) cb();
}

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
let myIncomingRequests = [];
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

  if (view === 'myRequests') {
    const f = document.getElementById('myRequestsFilter');
    if (f) f.value = 'all';
    fetchMyRequests();
  }
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
  if (currentView === 'myRequests') renderMyRequests();
}

// ===== PROFILE =====
async function fetchProfile() {
  try {
    const res = await fetch('/api/profile');
    if (!res.ok) return;
    const data = await res.json();

    console.log("PROFILE DATA FROM DATABASE:", data);

    userProfile.bio          = data.bio || '';
    userProfile.tutorCourses = data.tutorCourses || [];
    userProfile.tuteeCourses = data.tuteeCourses || []; 
    availableRooms           = data.rooms || [];

    updateProfileDisplay();
    populateGroupRoomSelect();

    document.getElementById('statSessions').textContent = data.upcomingSessions ?? '—';
    document.getElementById('statRating').textContent   = data.ratingAvg > 0 ? data.ratingAvg.toFixed(1) : '—';
    document.getElementById('statCourses').textContent  = data.coursesCount ?? '—';
  } catch {}
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
      body: JSON.stringify({ 
          bio: userProfile.bio, 
          tutorCourses: userProfile.tutorCourses,
          tuteeCourses: userProfile.tuteeCourses
      }),
    });
    if (!res.ok) throw new Error();

    showToast('Profile saved!');
    fetchProfile();
  } catch {
    showToast('Failed to save profile.');
    return;
  }

  toggleEditMode(false);
}

function updateProfileDisplay() {
  document.getElementById('bioDisplay').textContent = userProfile.bio || 'No bio added yet.';

  const tutorDisplay = document.getElementById('tutorCoursesDisplay');
  if (tutorDisplay) tutorDisplay.innerHTML = userProfile.tutorCourses.length > 0 ? userProfile.tutorCourses.map(c => `<span class="course-badge">${esc(c)}</span>`).join(''): '<p style="color: var(--text-muted); font-size: 0.9rem;">No courses added yet.</p>';

  const tuteeDisplay = document.getElementById('tuteeCoursesDisplay');
  if (tuteeDisplay) tuteeDisplay.innerHTML = userProfile.tuteeCourses.length > 0 ? userProfile.tuteeCourses.map(c => `<span class="course-badge">${esc(c)}</span>`).join('') : '<p style="color: var(--text-muted); font-size: 0.9rem;">No courses added yet.</p>';
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
  let list = window.__courses ? window.__courses.map(c => c.code) : [];
  if (query) list = list.filter(c => c.includes(query)); 
  container.innerHTML = list.slice(0, 6).map(c => `<button onclick="addFilterCourse('${esc(c)}')">${esc(c)}</button>`).join('');
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

  document.getElementById('sessionTopic').innerHTML = '<option value="" disabled selected>Select a course first</option>';
  document.getElementById('sessionDate').value = '';
  document.getElementById('sessionMessage').value = '';
  document.getElementById('sessionModalOverlay').classList.add('open');
}

function closeSessionModal() {
  document.getElementById('sessionModalOverlay').classList.remove('open');
  selectedTutor = null;
}

function updateSessionTopics() {
    const courseCode = document.getElementById('sessionCourse').value;
    const topicSelect = document.getElementById('sessionTopic');

    const courseObj = window.__courses.find(c => c.code === courseCode);

    if (courseObj && courseObj.topics && courseObj.topics.length > 0) {
      topicSelect.innerHTML = '<option value="" disabled selected>Select a topic</option>' +
      courseObj.topics.map(t => `<option value="${esc(t)}">${esc(t)}</option>`).join('');
    } else {
      topicSelect.innerHTML = '<option value="General Tutoring">General Tutoring</option>';
    }
}

async function submitRequest() {
  const courseCode = document.getElementById('sessionCourse').value;
  const topic      = document.getElementById('sessionTopic').value.trim();
  const date       = document.getElementById('sessionDate').value;
  const message    = document.getElementById('sessionMessage').value.trim();

  if (!courseCode || !date) { showToast('Please select a course and preferred schedule.'); return; }

  const tutorName = selectedTutor?.name || 'tutor';
  const tutorId   = selectedTutor?.id || null;

  try {
    const res = await fetch('/api/requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({
        course_code:    courseCode,
        tutor_id:       tutorId,
        message:        (topic ? topic + (message ? '\n' + message : '') : message) || null,
        preferred_date: date,
      }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      showToast(err.message || 'Failed to send request.');
      return;
    }
    closeSessionModal();
    showToast(`Request sent to ${tutorName}!`);
    fetchMyRequests();
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
  const doRequest = async () => {
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
      fetchNotifications();
      if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
    } catch {
      showToast('Action failed. Please try again.');
    }
  };

  if (action === 'decline') {
    showConfirmModal(
      'Decline Request',
      'Decline this tutoring request? The student will be notified.',
      doRequest,
      { icon: '✖️', confirmLabel: 'Decline', cancelLabel: 'Go Back', destructive: true }
    );
  } else {
    doRequest();
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
    fetchProfile();
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
  const list = document.getElementById('myRequestsList');
  if (list) list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">Loading…</p>';
  try {
    const [sRes, tRes] = await Promise.all([
      fetch('/api/requests?role=student'),
      fetch('/api/requests?role=tutor'),
    ]);
    myRequestsData     = sRes.ok ? ((await sRes.json()).requests || []) : [];
    myIncomingRequests = tRes.ok ? ((await tRes.json()).requests || []) : [];
  } catch (err) {
    myRequestsData = [];
    myIncomingRequests = [];
    console.error('fetchMyRequests failed:', err);
    if (list) list.innerHTML = `<p style="text-align:center;color:var(--coral);padding:2rem 0;">Failed to load requests (${err.message}). Try refreshing.</p>`;
    return;
  }
  try {
    renderMyRequests();
  } catch (err) {
    console.error('renderMyRequests failed:', err);
    if (list) list.innerHTML = '<p style="text-align:center;color:var(--coral);padding:2rem 0;">Failed to display requests.</p>';
  }
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
    Cancelled:       '#888',
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
  const doRequest = async () => {
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
      fetchNotifications();
    } catch {
      showToast('Action failed. Please try again.');
    }
  };

  if (action === 'student_decline') {
    showConfirmModal(
      'Decline Proposal',
      'Decline the tutor\'s proposed schedule? This request will be marked as declined.',
      doRequest,
      { icon: '✖️', confirmLabel: 'Decline Proposal', cancelLabel: 'Go Back', destructive: true }
    );
  } else {
    doRequest();
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
  const SESSION_TYPES = new Set(['session_completed', 'session_cancelled']);
  list.innerHTML = notifications.map(n => {
    const time = n.created_at
      ? new Date(n.created_at).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
      : '';
    const targetView = SESSION_TYPES.has(n.type) ? 'sessions' : 'requests';
    return `
      <div class="notif-item${n.is_read ? '' : ' notif-unread'}" style="cursor:pointer" onclick="notifNavigate('${esc(targetView)}')">
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
  document.getElementById('notifBadge').style.display = 'none';
  const list = document.getElementById('notifList');
  if (list) list.innerHTML = '<p class="notif-empty">No new notifications.</p>';
  try {
    await fetch('/api/notifications/read', {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': getCsrfToken() },
    });
    fetchNotifications();
  } catch { /* ignore */ }
}

function notifNavigate(view) {
  notifDropdownOpen = false;
  document.getElementById('notifDropdown').classList.remove('open');
  switchView(view);
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
  // Check if the user selected at least one course
  if (obCourses.length === 0) {
    showToast('Please add at least one course before finishing.');
    return;
  }

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

// ===== UPDATED: switchView handles mySessions =====
const _origSwitchView = switchView;
switchView = function(view) {
  currentView = view;
  const views = { dashboardView: 'dashboard', myRequestsView: 'myRequests', mySessionsView: 'mySessions', profileView: 'profile' };
  Object.keys(views).forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.display = (views[id] === view) ? 'block' : 'none';
  });
  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const navMap = { dashboard: 'navDashboard', myRequests: 'navMyRequests', mySessions: 'navMySessions', profile: 'navProfile' };
  if (navMap[view]) { const el = document.getElementById(navMap[view]); if (el) el.classList.add('active'); }

  if (view === 'myRequests') {
    const f = document.getElementById('myRequestsFilter');
    if (f) f.value = 'all';
    fetchMyRequests();
  }
  if (view === 'mySessions') { fetchSessions(); fetchOpenSessions(); }
  if (view === 'profile')    loadPersonalInfo();
};

// ===== UPDATED: fetchProfile loads photo and personal info =====
const _origFetchProfile = fetchProfile;
fetchProfile = async function() {
  try {
    const res = await fetch('/api/profile');
    if (!res.ok) return;
    const data = await res.json();

    userProfile.bio          = data.bio || '';
    userProfile.tutorCourses = data.tutorCourses || [];
    availableRooms           = data.rooms || [];

    updateProfileDisplay();
    populateGroupRoomSelect();
    populateAcceptRoomSelect();

    document.getElementById('statSessions').textContent = data.upcomingSessions ?? '—';
    document.getElementById('statRating').textContent   = data.ratingAvg > 0 ? data.ratingAvg.toFixed(1) : '—';
    document.getElementById('statCourses').textContent  = data.coursesCount ?? '—';

    if (data.photoUrl) displayAvatar(data.photoUrl);
    if (data.programCode) document.getElementById('displayProgram').textContent = data.programCode;
    if (data.yearLevel)   document.getElementById('displayYearLevel').textContent = data.yearLevel;
    if (data.contactNumber) document.getElementById('displayContact').textContent = data.contactNumber;
    else document.getElementById('displayContact').textContent = '—';
  } catch { /* silently fail */ }
};

// ===== PROFILE PHOTO =====
function displayAvatar(url) {
  const el = document.getElementById('profileAvatar');
  if (!el || !url) return;
  el.style.backgroundImage = `url(${url})`;
  el.style.backgroundSize  = 'cover';
  el.style.backgroundPosition = 'center';
  el.style.color = 'transparent';
  el.style.fontSize = '0';
}

async function handlePhotoSelect(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { showToast('Photo must be under 2MB.'); input.value = ''; return; }

  const reader = new FileReader();
  reader.onload = async e => {
    const dataUrl = e.target.result;
    displayAvatar(dataUrl);
    try {
      const res = await fetch('/api/user/photo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({ photo: dataUrl }),
      });
      if (!res.ok) throw new Error();
      showToast('Photo updated!');
    } catch { showToast('Failed to upload photo.'); }
  };
  reader.readAsDataURL(file);
}

// ===== PERSONAL INFO =====
function loadPersonalInfo() {
  const u = window.__authUser || {};
  const programEl = document.getElementById('displayProgram');
  const yearEl    = document.getElementById('displayYearLevel');
  const contactEl = document.getElementById('displayContact');
  if (programEl) programEl.textContent = u.programCode || '—';
  if (yearEl)    yearEl.textContent    = u.yearLevel   || '—';
  if (contactEl) contactEl.textContent = u.contact     || '—';
}

function togglePersonalEdit(show) {
  document.getElementById('personalEditForm').style.display = show ? 'block' : 'none';
  const trigger = document.querySelector('[onclick="togglePersonalEdit(true)"]');
  if (trigger) trigger.style.display = show ? 'none' : 'inline-flex';
  if (show) {
    const u = window.__authUser || {};
    const ps = document.getElementById('programSelect');
    if (ps) ps.value = u.programCode || '';
    const yl = document.getElementById('yearLevelInput');
    if (yl) yl.value = u.yearLevel || '';
    const ci = document.getElementById('contactInput');
    if (ci) ci.value = u.contact || '';
  }
}

async function savePersonalInfo() {
  const programCode  = document.getElementById('programSelect').value;
  const yearLevel    = parseInt(document.getElementById('yearLevelInput').value);
  const contactNumber = document.getElementById('contactInput').value.trim();

  if (!programCode || !yearLevel) { showToast('Please fill in all required fields.'); return; }

  try {
    const res = await fetch('/api/user/profile', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ program_code: programCode, current_year_level: yearLevel, contact_number: contactNumber || null }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.message || 'Update failed.');
      return;
    }
    window.__authUser = { ...(window.__authUser || {}), programCode, yearLevel, contact: contactNumber };
    document.getElementById('displayProgram').textContent   = programCode;
    document.getElementById('displayYearLevel').textContent = yearLevel;
    document.getElementById('displayContact').textContent   = contactNumber || '—';
    togglePersonalEdit(false);
    showToast('Personal info updated!');
  } catch { showToast('Failed to update personal info.'); }
}

// ===== PASSWORD CHANGE =====
function togglePasswordChange(show) {
  document.getElementById('passwordChangeForm').style.display = show ? 'block' : 'none';
  const trigger = document.getElementById('pwChangeTrigger');
  if (trigger) trigger.style.display = show ? 'none' : 'inline-flex';
  if (!show) {
    ['currentPassword','newPassword','confirmPassword'].forEach(id => {
      const el = document.getElementById(id); if (el) el.value = '';
    });
  }
}

async function changePassword() {
  const current  = document.getElementById('currentPassword').value;
  const newPw    = document.getElementById('newPassword').value;
  const confirm  = document.getElementById('confirmPassword').value;

  if (!current || !newPw || !confirm) { showToast('Please fill in all password fields.'); return; }
  if (newPw !== confirm) { showToast('New passwords do not match.'); return; }
  if (newPw.length < 8)  { showToast('Password must be at least 8 characters.'); return; }

  try {
    const res = await fetch('/api/user/password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({ current_password: current, password: newPw, password_confirmation: confirm }),
    });
    if (!res.ok) {
      const err = await res.json();
      showToast(err.error || 'Failed to change password.');
      return;
    }
    togglePasswordChange(false);
    showToast('Password changed successfully!');
  } catch { showToast('Failed to change password.'); }
}

// ===== PHASE 1.1: ACCEPT MODAL =====
function populateAcceptRoomSelect() {
  const sel = document.getElementById('acceptRoom');
  if (!sel) return;
  sel.innerHTML = '<option value="">— auto-assign —</option>' +
    availableRooms.map(r => `<option value="${r.room_id}">${esc(r.room_name)} (${r.room_type})</option>`).join('');
}

function openAcceptModal(req) {
  document.getElementById('acceptRequestId').value  = req.id;
  document.getElementById('acceptStudentName').textContent = `Student: ${req.tuteeName}${req.course ? ' — ' + req.course : ''}`;
  document.getElementById('acceptModality').value   = 'In-Person';
  document.getElementById('acceptLink').value       = '';
  document.getElementById('acceptRoomWrap').style.display  = 'block';
  document.getElementById('acceptLinkWrap').style.display  = 'none';

  // Pre-fill datetime from preferred date embedded in message
  let prefTime = '';
  if (req.message) {
    const m = req.message.match(/\[Preferred:\s*([^\]]+)\]/);
    if (m) prefTime = m[1].trim();
  }
  document.getElementById('acceptTime').value = prefTime;
  populateAcceptRoomSelect();
  document.getElementById('acceptModalOverlay').classList.add('open');
}

function closeAcceptModal() {
  document.getElementById('acceptModalOverlay').classList.remove('open');
}

function toggleAcceptLink() {
  const modality = document.getElementById('acceptModality').value;
  document.getElementById('acceptRoomWrap').style.display = modality === 'In-Person' ? 'block' : 'none';
  document.getElementById('acceptLinkWrap').style.display = modality === 'Online'    ? 'block' : 'none';
}

async function submitAccept() {
  const requestId    = document.getElementById('acceptRequestId').value;
  const scheduledTime = document.getElementById('acceptTime').value;
  const modality     = document.getElementById('acceptModality').value;
  const roomId       = document.getElementById('acceptRoom').value || null;
  const meetingLink  = document.getElementById('acceptLink').value.trim() || null;

  if (!scheduledTime) { showToast('Please select a date and time.'); return; }
  if (modality === 'Online' && !meetingLink) { showToast('Please provide a meeting link for online sessions.'); return; }

  try {
    const res = await fetch(`/api/requests/${requestId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({
        action:         'accept',
        scheduled_time: scheduledTime,
        modality,
        room_id:        roomId ? parseInt(roomId) : null,
        meeting_link:   meetingLink,
      }),
    });
    if (!res.ok) { const err = await res.json(); showToast(err.error || 'Accept failed.'); return; }
    closeAcceptModal();
    pendingRequests = pendingRequests.filter(r => r.id !== requestId);
    renderTutorRequests();
    showToast('Session accepted and scheduled!');
    fetchProfile();
    fetchMyRequests();
    if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
  } catch { showToast('Failed to accept request.'); }
}

// Patch respondTutorRequest to open accept modal instead of sending directly
const _origRespond = respondTutorRequest;
respondTutorRequest = async function(id, action) {
  if (action === 'accept') {
    const req = pendingRequests.find(r => r.id === id);
    if (req) { openAcceptModal(req); return; }
  }
  return _origRespond(id, action);
};

// ===== PHASE 2.1: MY SESSIONS =====
let mySessions = [];

async function fetchSessions() {
  try {
    const res = await fetch('/api/sessions');
    if (!res.ok) throw new Error();
    const data = await res.json();
    mySessions = data.sessions || [];
  } catch { mySessions = []; }
  renderSessions();
}

function renderSessions() {
  const filter = document.getElementById('sessionsFilter')?.value || 'all';
  const list   = document.getElementById('mySessionsList');
  if (!list) return;
  const shown  = filter === 'all' ? mySessions : mySessions.filter(s => s.status === filter);

  if (!shown.length) {
    list.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No sessions found.</p>`;
    return;
  }

  list.innerHTML = shown.map(s => {
    const dateStr = s.scheduledTime
      ? new Date(s.scheduledTime).toLocaleString([], { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
      : '—';
    const statusColor = { Scheduled:'var(--teal)', Completed:'#4caf50', Cancelled:'var(--coral)' };
    const color = statusColor[s.status] || '#aaa';

    const partnerLabel = s.isGroup
      ? (s.myRole === 'Tutor' ? `Group (${s.tuteeCount} joined)` : `Group session`)
      : (s.partnerName || '—');

    let actionsHtml = '';
    if (s.myRole === 'Tutor' && s.status === 'Scheduled') {
      actionsHtml = `
        <div class="request-actions" style="margin-top:.75rem;">
          <button class="btn-accept"  onclick="completeSession('${esc(s.session_id)}')">Mark Complete</button>
          <button class="btn-decline" onclick="cancelSession('${esc(s.session_id)}')">Cancel Session</button>
        </div>`;
    }
    if (s.canReview) {
      actionsHtml += `
        <div style="margin-top:.5rem;">
          <button class="btn-outline" style="font-size:.85rem;padding:.4rem .9rem;"
            onclick="openReviewModal('${esc(s.session_id)}','${esc(s.tutorId)}','${esc(s.partnerName)}')">★ Leave Review</button>
        </div>`;
    }

    // Attendee list for tutor group sessions
    let attendeesHtml = '';
    if (s.myRole === 'Tutor' && s.isGroup && s.tuteeCount > 0) {
      attendeesHtml = `<div style="font-size:.82rem;color:var(--text-muted);margin-top:.25rem;">${s.tuteeCount} student${s.tuteeCount !== 1 ? 's' : ''} joined</div>`;
    }

    return `
      <div class="request-card">
        <div class="request-header">
          <div class="request-tutee">${esc(s.course || '—')} &mdash; ${esc(partnerLabel)}</div>
          <span class="course-badge" style="margin:0;background:${color};color:white;">${esc(s.status)}</span>
        </div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-top:.25rem;">
          ${s.myRole === 'Tutor' ? '📋 Tutor' : '📖 Student'}
          &nbsp;|&nbsp; ${esc(s.modality || '')}
          ${s.room ? '@ ' + esc(s.room) : ''}
          ${s.meetingLink ? `<a href="${esc(s.meetingLink)}" target="_blank" rel="noopener" style="color:var(--purple);">🔗 Join</a>` : ''}
        </div>
        <div class="request-date">🗓 ${dateStr}</div>
        ${attendeesHtml}
        ${actionsHtml}
      </div>`;
  }).join('');
}

async function completeSession(id) {
  showConfirmModal(
    'Mark as Completed',
    'Confirm this session is done? Students will be prompted to leave a review.',
    async () => {
      try {
        const res = await fetch(`/api/sessions/${id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
          body: JSON.stringify({ action: 'complete' }),
        });
        if (!res.ok) { const err = await res.json(); showToast(err.error || 'Failed.'); return; }
        showToast('Session marked as completed!');
        fetchSessions();
        fetchProfile();
        fetchNotifications();
      } catch { showToast('Action failed.'); }
    },
    { icon: '✅', confirmLabel: 'Mark Complete', cancelLabel: 'Not Yet', destructive: false }
  );
}

async function cancelSession(id) {
  showConfirmModal(
    'Cancel Session',
    'This session will be cancelled and all participants will be notified.',
    async () => {
      try {
        const res = await fetch(`/api/sessions/${id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
          body: JSON.stringify({ action: 'cancel' }),
        });
        if (!res.ok) { const err = await res.json(); showToast(err.error || 'Failed.'); return; }
        showToast('Session cancelled.');
        fetchSessions();
        fetchNotifications();
      } catch { showToast('Action failed.'); }
    },
    { icon: '🚫', confirmLabel: 'Yes, Cancel Session', cancelLabel: 'Keep it', destructive: true }
  );
}

// ===== PHASE 2.2: BROWSE OPEN GROUP SESSIONS =====
let openSessions = [];

async function fetchOpenSessions() {
  try {
    const res = await fetch('/api/sessions/open');
    if (!res.ok) throw new Error();
    const data = await res.json();
    openSessions = data.sessions || [];
  } catch { openSessions = []; }
  renderOpenSessions();
}

function renderOpenSessions() {
  const list = document.getElementById('openSessionsList');
  if (!list) return;

  if (!openSessions.length) {
    list.innerHTML = `<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No open group sessions right now.</p>`;
    return;
  }

  list.innerHTML = openSessions.map(s => {
    const dateStr = new Date(s.scheduledTime).toLocaleString([], { weekday:'short', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
    const spots   = s.capacity - s.tuteeCount;
    return `
      <div class="request-card">
        <div class="request-header">
          <div class="request-tutee">${esc(s.course || '—')} &mdash; ${esc(s.tutorName)}</div>
          <span class="course-badge" style="margin:0;background:var(--purple);color:white;">${s.tuteeCount}/${s.capacity} joined</span>
        </div>
        ${s.message ? `<div class="request-msg" style="margin-top:.35rem;">${esc(s.message)}</div>` : ''}
        <div class="request-date">🗓 ${dateStr} &nbsp;|&nbsp; ${esc(s.modality)}${s.room ? ' @ ' + esc(s.room) : ''}</div>
        <div class="request-actions" style="margin-top:.75rem;">
          ${s.alreadyJoined
            ? `<span style="color:var(--teal);font-size:.875rem;font-weight:500;">✓ Already joined</span>`
            : s.full
              ? `<span style="color:#aaa;font-size:.875rem;">Session full</span>`
              : `<button class="btn-accept" onclick="joinSession('${esc(s.session_id)}')">Join Session</button>`
          }
        </div>
      </div>`;
  }).join('');
}

async function joinSession(id) {
  try {
    const res = await fetch(`/api/sessions/${id}/join`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
    });
    if (!res.ok) { const err = await res.json(); showToast(err.error || 'Failed to join.'); return; }
    showToast('You have joined the session!');
    fetchOpenSessions();
    fetchSessions();
  } catch { showToast('Failed to join session.'); }
}

// ===== PHASE 2.3: CANCEL REQUEST =====
async function cancelRequest(id) {
  showConfirmModal(
    'Cancel Request',
    'Cancel this request? This cannot be undone.',
    async () => {
      try {
        const res = await fetch(`/api/requests/${id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
          body: JSON.stringify({ action: 'cancel' }),
        });
        if (!res.ok) { const err = await res.json(); showToast(err.error || 'Failed.'); return; }
        showToast('Request cancelled.');
        fetchMyRequests();
        fetchNotifications();
      } catch { showToast('Failed to cancel request.'); }
    },
    { icon: '🗑️', confirmLabel: 'Yes, Cancel', cancelLabel: 'Keep it', destructive: true }
  );
}

async function declineIncomingRequest(id) {
  showConfirmModal(
    'Decline Request',
    'Are you sure you want to decline this tutoring request? The student will be notified.',
    async () => {
      try {
        const res = await fetch(`/api/requests/${id}`, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
          body: JSON.stringify({ action: 'decline' }),
        });
        if (!res.ok) { const e = await res.json(); showToast(e.error || 'Failed.'); return; }
        showToast('Request declined.');
        fetchMyRequests();
        fetchNotifications();
      } catch { showToast('Failed to decline request.'); }
    },
    { icon: '✖️', confirmLabel: 'Decline', cancelLabel: 'Go Back', destructive: true }
  );
}

function openAcceptFromMyRequests(id) {
  const req = myIncomingRequests.find(r => r.id === id);
  if (!req) return;
  openAcceptModal(req);
}

// Override renderMyRequests — mode-aware: tutor toggle → incoming, tutee toggle → sent
renderMyRequests = function() {
  const list      = document.getElementById('myRequestsList');
  const filterRow = document.querySelector('#myRequestsView .filter-row');

  if (currentMode === 'tutor') {
    if (filterRow) filterRow.style.display = 'none';

    if (!myIncomingRequests.length) {
      list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No pending requests from students.</p>';
      return;
    }

    list.innerHTML = `
      <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.75rem;">Incoming from Students</h3>
      ${myIncomingRequests.map(req => {
        const dateStr = req.date ? new Date(req.date).toLocaleDateString() : '';
        const msg = req.message ? req.message.replace(/^\[Preferred:[^\]]*\]\s*/, '') : '';
        return `
          <div class="request-card" style="border-left:3px solid var(--teal);">
            <div class="request-header">
              <div class="request-tutee">${esc(req.course || '—')} &mdash; ${esc(req.tuteeName || 'Student')}</div>
              <span class="course-badge" style="margin:0;background:var(--teal);color:white;">Pending</span>
            </div>
            ${msg ? `<div class="request-msg" style="margin-top:.5rem;">"${esc(msg)}"</div>` : ''}
            <div class="request-date" style="margin-top:.25rem;">Received: ${dateStr}</div>
            <div class="request-actions" style="margin-top:.75rem;">
              <button class="btn-accept"  onclick="openAcceptFromMyRequests('${esc(req.id)}')">Accept</button>
              <button class="btn-decline" onclick="declineIncomingRequest('${esc(req.id)}')">Decline</button>
            </div>
          </div>`;
      }).join('')}`;
    return;
  }

  // ── Tutee mode: sent requests ──────────────────────────────────────────
  if (filterRow) filterRow.style.display = '';

  const filter = document.getElementById('myRequestsFilter').value;
  const shown  = filter === 'all' ? myRequestsData : myRequestsData.filter(r => r.status === filter);

  if (!shown.length) {
    list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">No requests found.</p>';
    return;
  }

  const statusColor = {
    Pending:         'var(--teal)',
    Approved:        '#4caf50',
    Declined:        'var(--coral)',
    Expired:         '#aaa',
    CounterProposed: 'var(--purple)',
    Cancelled:       '#888',
  };

  list.innerHTML = `
    <h3 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:.75rem;">Requests I Sent</h3>
    ${shown.map(req => {
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

      const canCancel = req.status === 'Pending' || req.status === 'CounterProposed';
      const cancelBtn = canCancel
        ? `<div style="margin-top:.5rem;">
             <button class="btn-outline" style="font-size:.8rem;padding:.3rem .7rem;color:var(--coral);border-color:var(--coral);"
               onclick="cancelRequest('${esc(req.id)}')">Cancel Request</button>
           </div>`
        : '';

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
          ${cancelBtn}
        </div>`;
    }).join('')}`;
};

// ===== PHASE 3.1: TUTOR PROFILE DETAIL MODAL =====
async function openTutorProfile(id) {
  document.getElementById('tutorProfileOverlay').classList.add('open');
  document.getElementById('tutorProfileContent').innerHTML =
    '<div style="text-align:center;padding:2rem;color:var(--text-muted);">Loading…</div>';

  try {
    const res = await fetch(`/api/tutors/${id}`);
    if (!res.ok) throw new Error();
    const t = await res.json();

    const stars = n => '★'.repeat(Math.round(n)) + '☆'.repeat(5 - Math.round(n));
    const courseBadges = (t.courses || []).map(c => `<span class="course-badge">${esc(c)}</span>`).join('');
    const reviewsHtml = (t.reviews || []).length
      ? (t.reviews || []).map(r => `
          <div style="border-top:1px solid #e0d8c8;padding:.75rem 0;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="color:#f5a623;font-size:1rem;">${stars(r.rating)}</span>
              <span style="font-size:.8rem;color:var(--text-muted);">${r.reviewerName}</span>
            </div>
            ${r.feedback ? `<p style="margin:.35rem 0 0;font-size:.875rem;">${esc(r.feedback)}</p>` : ''}
          </div>`).join('')
      : '<p style="color:var(--text-muted);font-size:.875rem;">No reviews yet.</p>';

    document.getElementById('tutorProfileContent').innerHTML = `
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
        <div class="tutor-avatar" style="width:64px;height:64px;font-size:1.4rem;">${esc(t.initials)}</div>
        <div>
          <h2 style="margin:0 0 .2rem;">${esc(t.name)}</h2>
          <div style="color:var(--text-muted);font-size:.875rem;">${esc(t.degree)}</div>
          <div style="margin-top:.2rem;">
            <span style="color:#f5a623;">${stars(t.rating)}</span>
            <span style="font-size:.8rem;color:var(--text-muted);margin-left:.3rem;">${(t.rating || 0).toFixed(1)} (${t.reviewCount} reviews)</span>
          </div>
        </div>
      </div>
      ${t.bio ? `<p style="margin-bottom:1rem;color:var(--text-muted);line-height:1.6;">${esc(t.bio)}</p>` : ''}
      <div style="margin-bottom:1rem;">
        <strong style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);">Courses</strong>
        <div style="margin-top:.4rem;">${courseBadges || '<span style="color:var(--text-muted);">None listed</span>'}</div>
      </div>
      <div style="margin-bottom:1rem;">
        <strong style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);">Reviews</strong>
        ${reviewsHtml}
      </div>
      <button class="btn-primary full-width" onclick="closeTutorProfile(); openSessionModal('${esc(t.id)}')">Request Session</button>
    `;
  } catch {
    document.getElementById('tutorProfileContent').innerHTML =
      '<p style="text-align:center;color:var(--coral);padding:2rem;">Failed to load profile.</p>';
  }
}

function closeTutorProfile() {
  document.getElementById('tutorProfileOverlay').classList.remove('open');
}

// ===== PHASE 3.2: COURSE TOPIC PICKER IN REQUEST MODAL =====
async function loadSessionTopics() {
  const code = document.getElementById('sessionCourse').value;
  const wrap = document.getElementById('sessionTopicsWrap');
  const listEl = document.getElementById('sessionTopicsList');
  if (!code) { wrap.style.display = 'none'; return; }

  try {
    const res = await fetch(`/api/courses/${encodeURIComponent(code)}/topics`);
    if (!res.ok) throw new Error();
    const data = await res.json();
    const topics = data.topics || [];
    if (!topics.length) { wrap.style.display = 'none'; return; }
    listEl.innerHTML = topics.map(t => `
      <label style="display:flex;align-items:center;gap:.3rem;cursor:pointer;font-size:.82rem;background:white;padding:.25rem .6rem;border-radius:20px;border:1px solid #d0c8b8;">
        <input type="checkbox" value="${esc(t.topic_name)}" style="accent-color:var(--purple);">
        ${esc(t.topic_name)}
      </label>`).join('');
    wrap.style.display = 'block';
  } catch {
    wrap.style.display = 'none';
  }
}

// Patch submitRequest to include selected topics (topics are optional)
const _origSubmitRequest = submitRequest;
submitRequest = async function() {
  const courseCode = document.getElementById('sessionCourse').value;
  const date       = document.getElementById('sessionDate').value;
  const message    = document.getElementById('sessionMessage').value.trim();

  const checkedTopics = Array.from(
    document.querySelectorAll('#sessionTopicsList input[type=checkbox]:checked')
  ).map(cb => cb.value);

  let topicField = document.getElementById('sessionTopic').value.trim();
  if (checkedTopics.length) {
    topicField = checkedTopics.join(', ') + (topicField ? '; ' + topicField : '');
    document.getElementById('sessionTopic').value = topicField;
  }

  if (!courseCode || !date) { showToast('Please select a course and preferred schedule.'); return; }

  const tutorName = selectedTutor?.name || 'tutor';
  const tutorId   = selectedTutor?.id || null;

  try {
    const res = await fetch('/api/requests', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
      body: JSON.stringify({
        course_code:    courseCode,
        tutor_id:       tutorId,
        message:        (topicField ? topicField + (message ? '\n' + message : '') : message) || null,
        preferred_date: date,
      }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      showToast(err.message || 'Failed to send request.');
      return;
    }
    closeSessionModal();
    showToast(`Request sent to ${tutorName}!`);
    fetchMyRequests();
  } catch {
    showToast('Failed to send request. Please try again.');
  }
};

// Patch closeSessionModal to reset topics
const _origCloseSessionModal = closeSessionModal;
closeSessionModal = function() {
  _origCloseSessionModal();
  const wrap = document.getElementById('sessionTopicsWrap');
  if (wrap) wrap.style.display = 'none';
  const list = document.getElementById('sessionTopicsList');
  if (list) list.innerHTML = '';
};

// ===== PATCH renderTutors to add "View Profile" button
const _origRenderTutors = renderTutors;
renderTutors = function(tutors) {
  _origRenderTutors(tutors);
  // Replace the grid content with updated cards that include a profile button
  const grid = document.getElementById('tutorsGrid');
  if (!grid || !tutors.length) return;
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
      <div style="display:flex;gap:.5rem;margin-top:.75rem;">
        <button class="btn-outline" style="flex:1;font-size:.82rem;padding:.5rem;"
                onclick="openTutorProfile('${esc(t.id)}')">View Profile</button>
        <button class="btn-primary"  style="flex:2;background:var(--purple);"
                onclick="openSessionModal('${esc(t.id)}')">Request session</button>
      </div>
    </div>
  `).join('');
};
