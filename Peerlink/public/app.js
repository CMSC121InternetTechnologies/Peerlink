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

async function apiPatch(url, body) {
  const token = getCsrfToken();
  if (!token) {
    showToast('Session error — please refresh the page (F5).');
    return null;
  }
  let res;
  try {
    res = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(body),
    });
  } catch (e) {
    console.error('PATCH network error', url, e);
    showToast('Could not reach the server — make sure it is running.');
    return null;
  }
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    const msg = err.error || err.message || `Error ${res.status}`;
    if (res.status === 419 || res.status === 401) showToast('Session expired — please refresh the page (F5).');
    else showToast(msg);
    console.error('PATCH', url, res.status, err);
    return null;
  }
  // Return {} instead of null on JSON parse failure so callers don't mistake
  // a successful 200 response for an error just because the body was empty.
  return res.json().catch(() => ({}));
}

async function apiPost(url, body) {
  const token = getCsrfToken();
  if (!token) {
    showToast('Session error — please refresh the page (F5).');
    return null;
  }
  let res;
  try {
    res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
      body: JSON.stringify(body),
    });
  } catch (e) {
    console.error('POST network error', url, e);
    showToast('Could not reach the server — make sure it is running.');
    return null;
  }
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    const msg = err.error || err.message || `Error ${res.status}`;
    if (res.status === 419 || res.status === 401) showToast('Session expired — please refresh the page (F5).');
    else showToast(msg);
    console.error('POST', url, res.status, err);
    return null;
  }
  return res.json().catch(() => ({}));
}

function showToast(message) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ============================================================================
// LOCALSTORAGE CACHE — instant reloads with stale-while-revalidate
// ----------------------------------------------------------------------------
// Each entry is { data, expiresAt }. The key is namespaced by user_id so a
// different user logging in on the same browser never sees prior data.
// Quota errors (e.g. when a profile photo data-URL is too big) are silently
// swallowed — caching is a best-effort optimisation, never a correctness path.
// ============================================================================
const CACHE_PREFIX = 'pl_cache_';
function _cacheKey(key) {
  const uid = (window.__authUser && window.__authUser.userId) || 'anon';
  return `${CACHE_PREFIX}${uid}:${key}`;
}
const cache = {
  set(key, data, ttlSeconds = 300) {
    try {
      localStorage.setItem(_cacheKey(key), JSON.stringify({
        data, expiresAt: Date.now() + ttlSeconds * 1000,
      }));
    } catch (e) { /* quota exceeded — silent */ }
  },
  // Returns { data, fresh } or null. `fresh` is true if not yet expired.
  get(key) {
    try {
      const raw = localStorage.getItem(_cacheKey(key));
      if (!raw) return null;
      const item = JSON.parse(raw);
      return { data: item.data, fresh: Date.now() <= item.expiresAt };
    } catch { return null; }
  },
  invalidate(...keys) {
    keys.forEach(k => { try { localStorage.removeItem(_cacheKey(k)); } catch {} });
  },
  // Wipe every cache key for this user — call on logout.
  clearAll() {
    try {
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const k = localStorage.key(i);
        if (k && k.startsWith(CACHE_PREFIX)) {
          localStorage.removeItem(k);
        }
      }
    } catch {}
  },
};

window.addEventListener('pageshow', function (event) {
    // If the page was restored from the browser's in-memory cache
    if (event.persisted) {
        // Force a hard reload from the server. 
        // The server will see they are logged out and redirect to /login.
        window.location.reload(); 
    }
});

document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Check if our cache prefix exists anywhere in localStorage
        let isLoggedOut = true;
        for (let i = 0; i < localStorage.length; i++) {
            if (localStorage.key(i).startsWith('pl_cache_')) {
                isLoggedOut = false;
                break;
            }
        }
        
        // If the cache is empty but we are on a protected page, force a reload
        if (isLoggedOut) {
            window.location.reload();
        }
    }
});

// Stale-while-revalidate JSON fetcher.
//   onData(data) is called twice when there is stale cache:
//     1) immediately with the cached data (fresh=false → instant UI)
//     2) again with fresh server data once it arrives (only if it actually changed)
//   When there's no cache at all it's called once with the server data.
async function cachedJson(key, url, ttlSeconds, onData) {
  const cached = cache.get(key);
  let cachedSerialised = null;

  // 1) Render from cache first if we have one (even if stale).
  if (cached) {
    cachedSerialised = JSON.stringify(cached.data);
    onData(cached.data, /* fromCache */ true);
    if (cached.fresh) return cached.data; // still fresh — skip network
  }

  // 2) Fetch fresh in the background (or as the only fetch if no cache).
  try {
    const res = await fetch(url, {
      cache: 'no-store',
      headers: { 'Accept': 'application/json' },
    });
    if (!res.ok) return cached?.data ?? null;
    const data = await res.json();
    cache.set(key, data, ttlSeconds);
    // Only re-render if the data actually changed — avoids needless flicker.
    if (cachedSerialised !== JSON.stringify(data)) onData(data, /* fromCache */ false);
    return data;
  } catch {
    return cached?.data ?? null;
  }
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
let dismissedNotifIds = new Set(
  JSON.parse(localStorage.getItem('pl_seenNotifs') || '[]')
); // persisted across reloads so seen notifications don't reappear
let lastFetchedNotifs = [];        // most recent poll result, used to populate dismissedNotifIds
let myRequestsData = [];
let myIncomingRequests = [];
let broadcastRequests = [];
let pendingRequests = [];
let availableRooms = [];
let obCourses = [];       // onboarding: courses the user picked to tutor
let obTuteeCourses = []; // onboarding: courses the user needs help with
let currentReqTab = 'direct';

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
  const savedMode = localStorage.getItem('pl_mode') || 'tutee';
  const savedView = window.__onboarding ? 'dashboard' : (localStorage.getItem('pl_view') || 'dashboard');
  setMode(savedMode);
  switchView(savedView);

  fetchProfile();   // loads bio, tutorCourses, stats, rooms
  fetchTutors();
  fetchNotifications();

  let notifInterval = setInterval(fetchNotifications, 60000);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      clearInterval(notifInterval);
    } else {
      fetchNotifications();
      notifInterval = setInterval(fetchNotifications, 60000);
    }
  });

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

  // ── Tutee: Broadcast Request button ──
  document.getElementById('broadcastRequestBtn')?.addEventListener('click', () => openBroadcastModal());

  // ── Profile section: explicit listeners replacing onclick attributes ──
  document.getElementById('editProfileBtn')?.addEventListener('click', () => toggleEditMode(true));
  document.getElementById('cancelProfileEditBtn')?.addEventListener('click', () => toggleEditMode(false));
  document.getElementById('profileEditMode')?.addEventListener('submit', e => saveProfile(e));
  document.getElementById('tutorSelect')?.addEventListener('change', () => addProfileCourse('tutor'));
  document.getElementById('tuteeSelect')?.addEventListener('change', () => addProfileCourse('tutee'));
  document.getElementById('photoInput')?.addEventListener('change', function () { handlePhotoSelect(this); });
  document.getElementById('editPersonalInfoBtn')?.addEventListener('click', () => togglePersonalEdit(true));
  document.getElementById('cancelPersonalEditBtn')?.addEventListener('click', () => togglePersonalEdit(false));
  document.getElementById('savePersonalInfoBtn')?.addEventListener('click', () => savePersonalInfo());
  document.getElementById('pwChangeTrigger')?.addEventListener('click', () => togglePasswordChange(true));
  document.getElementById('cancelPasswordBtn')?.addEventListener('click', () => togglePasswordChange(false));
  document.getElementById('updatePasswordBtn')?.addEventListener('click', () => changePassword());
  document.getElementById('deleteAccountBtn')?.addEventListener('click', () => openDeleteModal());

  // ── Complete Session modal ──
  document.getElementById('completeSessionOverlay')?.addEventListener('click', closeCompleteSessionModal);
  document.getElementById('completeSessionModal')?.addEventListener('click', e => e.stopPropagation());
  document.getElementById('closeCompleteBtn')?.addEventListener('click', closeCompleteSessionModal);
  document.getElementById('confirmCompleteBtn')?.addEventListener('click', submitCompleteSession);

  // ── Profile course tag removal ──
  document.getElementById('profileEditMode')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-action="remove-profile-tag"]');
    if (!btn) return;
    removeProfileTag(btn.dataset.course, btn.dataset.type);
  });

  // ── Tutor: Requests Modal (direct requests list) ──
  document.getElementById('requestsList')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const { action, id } = btn.dataset;
    if (action === 'req-accept')  respondTutorRequest(id, 'accept');
    else if (action === 'req-counter') openCounterModal(id);
    else if (action === 'req-decline') respondTutorRequest(id, 'decline');
  });

  // ── Tutor: Broadcast pool list ──
  document.getElementById('broadcastList')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-action="claim-broadcast"]');
    if (!btn) return;
    claimBroadcast(btn.dataset.id);
  });

  // ── My Sessions list: delegated listener ──
  document.getElementById('mySessionsList')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const { action, id, sessionId, tutorId, tutorName } = btn.dataset;
    if (action === 'complete')            completeSession(id);
    else if (action === 'cancel-session') cancelSession(id);
    else if (action === 'review')         openReviewModal(sessionId, tutorId, tutorName);
  });

  // ── My Requests list: delegated listener ──
  document.getElementById('myRequestsList')?.addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const { action, id, sessionId, tutorId, tutorName } = btn.dataset;
    if (action === 'accept-incoming')       openAcceptFromMyRequests(id);
    else if (action === 'counter-propose')  openCounterModal(id);
    else if (action === 'decline-incoming') declineIncomingRequest(id);
    else if (action === 'counter-accept')   respondToCounter(id, 'student_accept');
    else if (action === 'counter-decline')  respondToCounter(id, 'student_decline');
    else if (action === 'cancel-request')   cancelRequest(id);
    else if (action === 'review')           openReviewModal(sessionId, tutorId, tutorName);
  });
});

// ===== NAVIGATION =====
function switchView(view) {
  currentView = view;
  localStorage.setItem('pl_view', view);

  const viewMap = {
    dashboard:  'dashboardView',
    myRequests: 'myRequestsView',
    mySessions: 'mySessionsView',
    profile:    'profileView',
  };
  Object.entries(viewMap).forEach(([v, id]) => {
    const el = document.getElementById(id);
    if (el) el.style.display = (v === view) ? 'block' : 'none';
  });

  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  const navMap = {
    dashboard:  'navDashboard',
    myRequests: 'navMyRequests',
    mySessions: 'navMySessions',
    profile:    'navProfile',
  };
  if (navMap[view]) document.getElementById(navMap[view])?.classList.add('active');

  if (view === 'myRequests') {
    const f = document.getElementById('myRequestsFilter');
    if (f) f.value = localStorage.getItem('pl_reqFilter') || 'all';
    fetchMyRequests();
  }
  if (view === 'mySessions') {
    const f = document.getElementById('sessionsFilter');
    if (f) f.value = localStorage.getItem('pl_sessFilter') || 'all';
    fetchSessions();
    fetchOpenSessions();
  }
  if (view === 'profile') {
    loadPersonalInfo();
  }
}

// ===== MODE TOGGLE =====
function setMode(mode) {
  currentMode = mode;
  localStorage.setItem('pl_mode', mode);
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
  else updateReqBadge();
}

// ===== PROFILE =====
// Uses cachedJson so a reload paints from localStorage instantly, then
// updates the UI when fresh data arrives (only if it actually changed).
async function fetchProfile() {
  await cachedJson('profile', '/api/profile', 300, (data) => {
    userProfile.bio          = data.bio || '';
    userProfile.tutorCourses = data.tutorCourses || [];
    userProfile.tuteeCourses = data.tuteeCourses || [];
    availableRooms           = data.rooms || [];

    updateProfileDisplay();
    populateGroupRoomSelect();
    populateAcceptRoomSelect();

    const sSess = document.getElementById('statSessions');
    if (sSess) sSess.textContent = data.upcomingSessions ?? '—';
    const sRate = document.getElementById('statRating');
    if (sRate) sRate.textContent = data.ratingAvg > 0 ? data.ratingAvg.toFixed(1) : '—';
    const sCour = document.getElementById('statCourses');
    if (sCour) sCour.textContent = data.coursesCount ?? '—';

    if (data.photoUrl) displayAvatar(data.photoUrl);
    if (data.programCode) { const el = document.getElementById('displayProgram'); if (el) el.textContent = data.programCode; }
    if (data.yearLevel)   { const el = document.getElementById('displayYearLevel'); if (el) el.textContent = data.yearLevel; }
    const contactEl = document.getElementById('displayContact');
    if (contactEl) contactEl.textContent = data.contactNumber || '—';
  });
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
    <span class="filter-tag">${esc(c)}<button type="button" data-action="remove-profile-tag" data-course="${esc(c)}" data-type="${type}">✕</button></span>
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

  const res = await apiPatch('/api/profile', {
    bio: userProfile.bio,
    tutorCourses: userProfile.tutorCourses,
    tuteeCourses: userProfile.tuteeCourses,
  });
  if (!res) return;

  // Tutor course list changed → tutor directory may have changed too
  cache.invalidate('profile', 'tutors');
  showToast('Profile saved!');
  fetchProfile();
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
  await cachedJson('tutors', '/api/tutors', 300, (data) => {
    allTutors = data.tutors || [];
    allUniqueCourses = new Set();
    allTutors.forEach(t => (t.courses || []).forEach(c => allUniqueCourses.add(c)));
    filterTutors();
  });
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
  if (!tutors.length) { if (grid) grid.innerHTML = ''; if (empty) empty.style.display = 'flex'; return; }
  if (empty) empty.style.display = 'none';
  if (!grid) return;
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
  const wrap = document.getElementById('sessionTopicsWrap');
  if (wrap) wrap.style.display = 'none';
  const list = document.getElementById('sessionTopicsList');
  if (list) list.innerHTML = '';
}

// ===== TUTEE BROADCAST REQUEST =====
// Reuses the existing session modal but with no preselected tutor and the
// FULL course catalog populated. submitRequest() already handles
// `selectedTutor === null` by sending tutor_id: null to the API → the
// request lands in the broadcast pool where any tutor can claim it.
function openBroadcastModal() {
  selectedTutor = null;

  document.getElementById('modalName').textContent = 'Broadcast a Request';
  document.getElementById('modalSub').textContent  = 'Any available tutor can claim this';
  // Broadcast has no specific tutor — leave the avatar empty rather than using
  // a placeholder emoji. The styled circle still renders, just blank.
  document.getElementById('modalAvatar').textContent = '';

  // Populate course dropdown with ALL courses (not just one tutor's expertise).
  const allCourses = window.__courses || [];
  const sel = document.getElementById('sessionCourse');
  sel.innerHTML = '<option value="" disabled selected>Select a course</option>' +
    allCourses.map(c => `<option value="${esc(c.code)}">${esc(c.code)} — ${esc(c.name)}</option>`).join('');

  // Reset other fields
  document.getElementById('sessionTopic').innerHTML = '<option value="" disabled selected>Select a course first</option>';
  document.getElementById('sessionDate').value = '';
  document.getElementById('sessionMessage').value = '';
  const wrap = document.getElementById('sessionTopicsWrap');
  if (wrap) wrap.style.display = 'none';
  const tlist = document.getElementById('sessionTopicsList');
  if (tlist) tlist.innerHTML = '';

  document.getElementById('sessionModalOverlay').classList.add('open');
}

function updateSessionTopics() {
    const courseCode = document.getElementById('sessionCourse').value;
    const topicSelect = document.getElementById('sessionTopic');

    const courseObj = window.__courses?.find(c => c.code === courseCode);

    if (courseObj && courseObj.topics && courseObj.topics.length > 0) {
      topicSelect.innerHTML = '<option value="" disabled selected>Select a topic</option>' +
      courseObj.topics.map(t => `<option value="${esc(t)}">${esc(t)}</option>`).join('');
    } else {
      topicSelect.innerHTML = '<option value="General Tutoring">General Tutoring</option>';
    }
}

async function submitRequest() {
  const courseCode = document.getElementById('sessionCourse').value;
  const date       = document.getElementById('sessionDate').value;
  const message    = document.getElementById('sessionMessage').value.trim();

  // Collect checked topics from the topic picker
  const checkedTopics = Array.from(
    document.querySelectorAll('#sessionTopicsList input[type=checkbox]:checked')
  ).map(cb => cb.value);
  let topicField = document.getElementById('sessionTopic').value.trim();
  if (checkedTopics.length) {
    topicField = checkedTopics.join(', ') + (topicField ? '; ' + topicField : '');
  }

  if (!courseCode || !date) { showToast('Please select a course and preferred schedule.'); return; }

  const tutorName = selectedTutor?.name || 'tutor';
  const tutorId   = selectedTutor?.id || null;

  const res = await apiPost('/api/requests', {
    course_code:    courseCode,
    tutor_id:       tutorId,
    message:        (topicField ? topicField + (message ? '\n' + message : '') : message) || null,
    preferred_date: date,
  });
  if (!res) return;
  // Invalidate every list that could now contain this new request.
  // - student's "My Requests" view (always)
  // - broadcast pool       (if no tutor was selected → tutor_id null)
  // - target tutor's pending list (if direct request)
  cache.invalidate('myRequests:student', 'myRequests:tutor', 'notifications');
  if (!tutorId) cache.invalidate('broadcastPool');
  closeSessionModal();
  showToast(tutorId ? `Request sent to ${tutorName}!` : 'Broadcast posted — any tutor can claim it.');
  fetchMyRequests();
  fetchNotifications();
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
  // Short TTL (30s) — tutor needs to see new student requests quickly.
  await cachedJson('tutorRequests', '/api/requests?role=tutor', 30, (data) => {
    pendingRequests = data.requests || [];
    renderTutorRequests();
  });
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
        <button class="btn-accept"  data-action="req-accept"  data-id="${esc(req.id)}">Accept</button>
        <button class="btn-outline" data-action="req-counter" data-id="${esc(req.id)}">Propose Changes</button>
        <button class="btn-decline" data-action="req-decline" data-id="${esc(req.id)}">Decline</button>
      </div>
    </div>`;
  }).join('');
}

async function respondTutorRequest(id, action) {
  // For accept, open the scheduling modal instead of sending directly
  if (action === 'accept') {
    const req = pendingRequests.find(r => r.id === id) || myIncomingRequests.find(r => r.id === id);
    if (req) { openAcceptModal(req); return; }
  }

  const doRequest = async () => {
    const res = await apiPatch(`/api/requests/${id}`, { action });
    if (!res) return;
    cache.invalidate('tutorRequests', 'myRequests:student', 'myRequests:tutor', 'notifications');
    pendingRequests      = pendingRequests.filter(r => r.id !== id);
    myIncomingRequests   = myIncomingRequests.filter(r => r.id !== id);
    renderTutorRequests();
    showToast('Request declined.');
    fetchMyRequests();
    fetchNotifications();
    if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
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
  await cachedJson('broadcastPool', '/api/requests?role=broadcast', 30, (data) => {
    broadcastRequests = data.requests || [];
    renderBroadcastRequests();
  });
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
        <button class="btn-accept" data-action="claim-broadcast" data-id="${esc(req.id)}">Claim &amp; Accept</button>
      </div>
    </div>`;
  }).join('');
}

// C2 fix: open accept modal so tutor specifies time/modality before claiming (US_14)
function claimBroadcast(id) {
  const req = broadcastRequests.find(r => r.id === id);
  if (!req) return;
  // Synthesise a shape compatible with openAcceptModal
  openAcceptModal({
    id:        req.id,
    tuteeName: req.studentName || 'Student',
    course:    req.course,
    message:   req.message || '',
    _isClaim:  true,  // flag so submitAccept uses 'claim' action
  });
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
  const rawTime   = document.getElementById('counterTime').value;
  const time      = rawTime ? rawTime.replace('T', ' ') + ':00' : '';
  const message   = document.getElementById('counterMessage').value.trim();
  const modality  = document.getElementById('counterModality').value;

  if (!time) { showToast('Please select a proposed date and time.'); return; }

  const res = await apiPatch(`/api/requests/${requestId}`, { action: 'counter_propose', counter_time: time, counter_message: message, counter_modality: modality });
  if (!res) return;
  cache.invalidate('tutorRequests', 'myRequests:student', 'myRequests:tutor', 'notifications');
  closeCounterModal();
  pendingRequests = pendingRequests.filter(r => r.id !== requestId);
  renderTutorRequests();
  showToast('Counter-proposal sent to student.');
  fetchMyRequests();
  fetchNotifications();
  if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
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

  const res = await apiPost('/api/sessions/broadcast', {
    course_code:    courseCode,
    scheduled_time: time,
    modality,
    room_id:        roomId ? parseInt(roomId) : null,
    meeting_link:   link || null,
    message,
  });
  if (!res) return;
  cache.invalidate('profile', 'sessions', 'openSessions', 'notifications');
  closeGroupSessionModal();
  showToast('Group session posted!');
  fetchProfile();
  fetchSessions();
  fetchNotifications();
}

// ===== MY REQUESTS VIEW (US_10 + US_11 student + US_16) =====
async function fetchMyRequests() {
  const list = document.getElementById('myRequestsList');

  // 1) Hydrate from cache instantly so the user sees something within ~16ms.
  const cachedStudent = cache.get('myRequests:student');
  const cachedTutor   = cache.get('myRequests:tutor');
  if (cachedStudent) myRequestsData     = cachedStudent.data.requests || [];
  if (cachedTutor)   myIncomingRequests = cachedTutor.data.requests   || [];
  if (cachedStudent || cachedTutor) {
    try { renderMyRequests(); } catch {}
  } else if (list) {
    list.innerHTML = '<p style="text-align:center;color:var(--text-muted);padding:2rem 0;">Loading…</p>';
  }

  // 2) Always refetch in the background so writes from another tab show up
  //    quickly. Skip the network only when both caches are still fresh.
  if (cachedStudent?.fresh && cachedTutor?.fresh) return;

  const jsonHeaders = { 'Accept': 'application/json' };
  try {
    const [sRes, tRes] = await Promise.all([
      fetch('/api/requests?role=student', { cache: 'no-store', headers: jsonHeaders }),
      fetch('/api/requests?role=tutor',   { cache: 'no-store', headers: jsonHeaders }),
    ]);
    if (sRes.ok) {
      const sData = await sRes.json();
      myRequestsData = sData.requests || [];
      cache.set('myRequests:student', sData, 60);
    }
    if (tRes.ok) {
      const tData = await tRes.json();
      myIncomingRequests = tData.requests || [];
      cache.set('myRequests:tutor', tData, 60);
    }
    if (!sRes.ok || !tRes.ok) throw new Error(`HTTP ${!sRes.ok ? sRes.status : tRes.status}`);
  } catch (err) {
    console.error('fetchMyRequests failed:', err);
    if (!cachedStudent && !cachedTutor && list) {
      list.innerHTML = `<p style="text-align:center;color:var(--coral);padding:2rem 0;">Failed to load requests (${err.message}). Try refreshing.</p>`;
    }
    return;
  }
  try {
    renderMyRequests();
  } catch (err) {
    console.error('renderMyRequests failed:', err);
    if (list) list.innerHTML = '<p style="text-align:center;color:var(--coral);padding:2rem 0;">Failed to display requests.</p>';
  }
}

async function respondToCounter(requestId, action) {
  const doRequest = async () => {
    const res = await apiPatch(`/api/requests/${requestId}`, { action });
    if (!res) return;
    cache.invalidate('myRequests:student', 'myRequests:tutor', 'sessions', 'notifications');
    showToast(action === 'student_accept' ? 'Session confirmed!' : 'Counter-proposal declined.');
    fetchMyRequests();
    fetchNotifications();
    if (action === 'student_accept') fetchSessions();
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

  const res = await apiPost('/api/reviews', { session_id: sessionId, reviewee_id: tutorId, rating, feedback });
  if (!res) return;
  // Review changes the tutor's rating_avg → tutor directory + my requests both stale.
  cache.invalidate('myRequests:student', 'myRequests:tutor', 'sessions', 'tutors');
  closeReviewModal();
  showToast('Review submitted!');
  fetchMyRequests();
  fetchTutors();
}

// ===== US_15: NOTIFICATIONS =====
async function fetchNotifications() {
  // Notifications are polled every 60s anyway, so a 30s cache just smooths
  // out reloads — the next poll will replace the data either way.
  await cachedJson('notifications', '/api/notifications', 30, (data) => {
    lastFetchedNotifs = data.notifications || [];
    renderNotifications(lastFetchedNotifs, data.unread_count || 0);
  });
}

function renderNotifications(notifications, unreadCount) {
  const visible = notifications.filter(n => !dismissedNotifIds.has(n.id));

  const badge = document.getElementById('notifBadge');
  if (visible.length > 0) {
    badge.textContent   = visible.length > 9 ? '9+' : visible.length;
    badge.style.display = 'flex';
  } else {
    badge.style.display = 'none';
  }

  const list = document.getElementById('notifList');
  if (!visible.length) {
    list.innerHTML = `<p class="notif-empty">No new notifications.</p>`;
    return;
  }
  const SESSION_TYPES = new Set(['session_completed', 'session_cancelled', 'student_joined']);
  list.innerHTML = visible.map(n => {
    const time = n.created_at
      ? new Date(n.created_at).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
      : '';
    const targetView = SESSION_TYPES.has(n.type) ? 'mySessions' : 'myRequests';
    return `
      <div class="notif-item notif-unread" style="cursor:pointer" onclick="notifNavigate('${esc(targetView)}')">
        <div class="notif-msg">${esc(n.message)}</div>
        <div class="notif-time">${time}</div>
      </div>`;
  }).join('');
}

function toggleNotifDropdown() {
  notifDropdownOpen = !notifDropdownOpen;
  document.getElementById('notifDropdown').classList.toggle('open', notifDropdownOpen);
  if (notifDropdownOpen) {
    // Fetch first so the user sees the notifications, then mark as read
    fetchNotifications().then(async () => {
      if (lastFetchedNotifs.length > 0) {
        lastFetchedNotifs.forEach(n => dismissedNotifIds.add(n.id));
        // Persist dismissed IDs so they survive page reloads
        try {
          const arr = [...dismissedNotifIds].slice(-300);
          dismissedNotifIds = new Set(arr);
          localStorage.setItem('pl_seenNotifs', JSON.stringify(arr));
        } catch {}
        document.getElementById('notifBadge').style.display = 'none';
        await apiPatch('/api/notifications/read', {});
      }
    });
  }
}

async function markAllNotificationsRead() {
  lastFetchedNotifs.forEach(n => dismissedNotifIds.add(n.id));
  try {
    const arr = [...dismissedNotifIds].slice(-300);
    dismissedNotifIds = new Set(arr);
    localStorage.setItem('pl_seenNotifs', JSON.stringify(arr));
  } catch {}
  document.getElementById('notifBadge').style.display = 'none';
  const list = document.getElementById('notifList');
  if (list) list.innerHTML = '<p class="notif-empty">No new notifications.</p>';
  await apiPatch('/api/notifications/read', {});
  cache.invalidate('notifications');
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

  obCourses      = [];
  obTuteeCourses = [];

  // Populate both course selects from window.__courses
  function populateSel(id) {
    const sel = document.getElementById(id);
    if (!sel || !window.__courses) return;
    window.__courses.forEach(c => {
      const opt = document.createElement('option');
      opt.value       = c.code;
      opt.textContent = `${c.code} — ${c.name}`;
      sel.appendChild(opt);
    });
  }
  populateSel('obCourseSelect');
  populateSel('obTuteeCourseSelect');

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

function obAddTuteeCourse() {
  const sel = document.getElementById('obTuteeCourseSelect');
  const val = sel.value;
  if (val && !obTuteeCourses.includes(val)) {
    obTuteeCourses.push(val);
    renderObTuteeTags();
  }
  sel.selectedIndex = 0;
}

function obRemoveTuteeCourse(code) {
  obTuteeCourses = obTuteeCourses.filter(c => c !== code);
  renderObTuteeTags();
}

function renderObTuteeTags() {
  const container = document.getElementById('obTuteeTags');
  if (!container) return;
  container.innerHTML = obTuteeCourses.map(c => `
    <span class="filter-tag" style="background:var(--coral);">${esc(c)}
      <button type="button" onclick="obRemoveTuteeCourse('${esc(c)}')">✕</button>
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

  // Save courses via real API (US_07) — obCourses is guaranteed non-empty from guard above
  const saveRes = await apiPatch('/api/profile', { tutorCourses: obCourses, tuteeCourses: obTuteeCourses });
  if (saveRes) {
    userProfile.tutorCourses = [...obCourses];
    userProfile.tuteeCourses = [...obTuteeCourses];
    updateProfileDisplay();
    fetchProfile();
  }

  document.getElementById('onboardingOverlay').style.display = 'none';
  switchView('dashboard');
  showToast('Welcome to PeerLink! Your courses have been saved.');
}


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
    const res = await apiPost('/api/user/photo', { photo: dataUrl });
    if (res) {
      cache.invalidate('profile');
      showToast('Photo updated!');
    }
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
  const trigger = document.getElementById('editPersonalInfoBtn');
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

  const res = await apiPatch('/api/user/profile', {
    program_code: programCode,
    current_year_level: yearLevel,
    contact_number: contactNumber || null,
  });
  if (!res) return;

  cache.invalidate('profile');
  window.__authUser = { ...(window.__authUser || {}), programCode, yearLevel, contact: contactNumber };
  document.getElementById('displayProgram').textContent   = programCode;
  document.getElementById('displayYearLevel').textContent = yearLevel;
  document.getElementById('displayContact').textContent   = contactNumber || '—';
  togglePersonalEdit(false);
  showToast('Personal info updated!');
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

  const res = await apiPost('/api/user/password', {
    current_password: current,
    password: newPw,
    password_confirmation: confirm,
  });
  if (!res) return;

  togglePasswordChange(false);
  showToast('Password changed successfully!');
}

// ===== PHASE 1.1: ACCEPT MODAL =====
let _acceptIsClaim = false;  // true when opened from claimBroadcast

function populateAcceptRoomSelect() {
  const sel = document.getElementById('acceptRoom');
  if (!sel) return;
  sel.innerHTML = '<option value="">— auto-assign —</option>' +
    availableRooms.map(r => `<option value="${r.room_id}">${esc(r.room_name)} (${r.room_type})</option>`).join('');
}

function openAcceptModal(req) {
  _acceptIsClaim = !!req._isClaim;
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
    if (m) {
      // Normalize "YYYY-MM-DD HH:MM:SS" or "YYYY-MM-DD HH:MM" → datetime-local "YYYY-MM-DDTHH:MM"
      prefTime = m[1].trim().replace(' ', 'T').substring(0, 16);
    }
  }
  document.getElementById('acceptTime').value = prefTime;
  populateAcceptRoomSelect();
  document.getElementById('acceptModalOverlay').classList.add('open');
}

function closeAcceptModal() {
  document.getElementById('acceptModalOverlay').classList.remove('open');
  _acceptIsClaim = false;
}

function toggleAcceptLink() {
  const modality = document.getElementById('acceptModality').value;
  document.getElementById('acceptRoomWrap').style.display = modality === 'In-Person' ? 'block' : 'none';
  document.getElementById('acceptLinkWrap').style.display = modality === 'Online'    ? 'block' : 'none';
}

async function submitAccept() {
  const requestId    = document.getElementById('acceptRequestId').value;
  const rawTime      = document.getElementById('acceptTime').value;
  const scheduledTime = rawTime ? rawTime.replace('T', ' ') + ':00' : '';
  const modality     = document.getElementById('acceptModality').value;
  const roomId       = document.getElementById('acceptRoom').value || null;
  const meetingLink  = document.getElementById('acceptLink').value.trim() || null;
  const isClaim      = _acceptIsClaim;

  if (!scheduledTime) { showToast('Please select a date and time.'); return; }
  if (modality === 'Online' && !meetingLink) { showToast('Please provide a meeting link for online sessions.'); return; }

  const res = await apiPatch(`/api/requests/${requestId}`, {
    action:         isClaim ? 'claim' : 'accept',
    scheduled_time: scheduledTime,
    modality,
    room_id:        roomId ? parseInt(roomId) : null,
    meeting_link:   meetingLink,
  });
  if (!res) return;
  // Accepting/claiming creates a new session row + flips request status →
  // every list above can change. Invalidate them all so the next paint is fresh.
  cache.invalidate(
    'profile', 'sessions', 'openSessions',
    'tutorRequests', 'broadcastPool',
    'myRequests:student', 'myRequests:tutor', 'notifications'
  );
  closeAcceptModal();
  if (isClaim) {
    broadcastRequests = broadcastRequests.filter(r => r.id !== requestId);
    renderBroadcastRequests();
    showToast('Broadcast claimed and session scheduled!');
  } else {
    pendingRequests = pendingRequests.filter(r => r.id !== requestId);
    renderTutorRequests();
    showToast('Session accepted and scheduled!');
    if (!pendingRequests.length) setTimeout(closeRequestsModal, 1500);
  }
  fetchProfile();
  fetchMyRequests();
  fetchSessions();
  fetchNotifications();
}


// ===== PHASE 2.1: MY SESSIONS =====
let mySessions = [];

async function fetchSessions() {
  await cachedJson('sessions', '/api/sessions', 60, (data) => {
    mySessions = data.sessions || [];
    renderSessions();
  });
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
          <button class="btn-accept"  data-action="complete"      data-id="${esc(s.session_id)}">Mark Complete</button>
          <button class="btn-decline" data-action="cancel-session" data-id="${esc(s.session_id)}">Cancel Session</button>
        </div>`;
    }
    if (s.canReview) {
      actionsHtml += `
        <div style="margin-top:.5rem;">
          <button class="btn-outline" style="font-size:.85rem;padding:.4rem .9rem;"
            data-action="review" data-session-id="${esc(s.session_id)}" data-tutor-id="${esc(s.tutorId)}" data-tutor-name="${esc(s.partnerName)}">★ Leave Review</button>
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

function completeSession(id) {
  const session = mySessions.find(s => s.session_id === id);
  if (session && session.scheduledTime && new Date(session.scheduledTime) > new Date()) {
    showToast('Sessions can only be marked complete after their scheduled start time.');
    return;
  }
  document.getElementById('completeSessionId').value = id;
  document.getElementById('completeSummary').value   = '';
  document.getElementById('completeSessionOverlay').classList.add('open');
}

function closeCompleteSessionModal() {
  document.getElementById('completeSessionOverlay').classList.remove('open');
}

async function submitCompleteSession() {
  const id      = document.getElementById('completeSessionId').value;
  const summary = document.getElementById('completeSummary').value.trim();
  const res = await apiPatch(`/api/sessions/${id}`, { action: 'complete', summary: summary || null });
  if (!res) return;
  cache.invalidate('sessions', 'profile', 'notifications', 'myRequests:student', 'myRequests:tutor');
  closeCompleteSessionModal();
  showToast('Session marked as completed!');
  const filterEl = document.getElementById('sessionsFilter');
  if (filterEl) filterEl.value = 'all';
  fetchSessions();
  fetchProfile();
  fetchNotifications();
}

async function cancelSession(id) {
  showConfirmModal(
    'Cancel Session',
    'This session will be cancelled and all participants will be notified.',
    async () => {
      const res = await apiPatch(`/api/sessions/${id}`, { action: 'cancel' });
      if (!res) return;
      cache.invalidate('sessions', 'openSessions', 'profile', 'notifications', 'myRequests:student', 'myRequests:tutor');
      showToast('Session cancelled.');
      const filterElCancel = document.getElementById('sessionsFilter');
      if (filterElCancel) filterElCancel.value = 'all';
      fetchSessions();
      fetchProfile();
      fetchNotifications();
    },
    { icon: '🚫', confirmLabel: 'Yes, Cancel Session', cancelLabel: 'Keep it', destructive: true }
  );
}

// ===== PHASE 2.2: BROWSE OPEN GROUP SESSIONS =====
let openSessions = [];

async function fetchOpenSessions() {
  await cachedJson('openSessions', '/api/sessions/open', 60, (data) => {
    openSessions = data.sessions || [];
    renderOpenSessions();
  });
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
  const res = await apiPost(`/api/sessions/${id}/join`, {});
  if (!res) return;
  cache.invalidate('openSessions', 'sessions', 'notifications');
  showToast('You have joined the session!');
  fetchOpenSessions();
  fetchSessions();
}


async function declineIncomingRequest(id) {
  showConfirmModal(
    'Decline Request',
    'Are you sure you want to decline this tutoring request? The student will be notified.',
    async () => {
      const res = await apiPatch(`/api/requests/${id}`, { action: 'decline' });
      if (!res) return;
      cache.invalidate('tutorRequests', 'myRequests:student', 'myRequests:tutor', 'notifications');
      myIncomingRequests = myIncomingRequests.filter(r => r.id !== id);
      renderMyRequests();
      showToast('Request declined.');
      fetchNotifications();
    },
    { icon: '✖️', confirmLabel: 'Decline', cancelLabel: 'Go Back', destructive: true }
  );
}

async function cancelRequest(id) {
  showConfirmModal(
    'Cancel Request',
    'Cancel this request? It will be marked as Cancelled in your history.',
    async () => {
      const res = await apiPatch(`/api/requests/${id}`, { action: 'cancel' });
      if (!res) return;
      cache.invalidate('myRequests:student', 'myRequests:tutor', 'broadcastPool', 'tutorRequests', 'notifications');
      showToast('Request cancelled.');
      // Optimistically update the badge right away so the user sees "Cancelled"
      const req = myRequestsData.find(r => r.id === id);
      if (req) req.status = 'Cancelled';
      const filterEl = document.getElementById('myRequestsFilter');
      if (filterEl) filterEl.value = 'all';   // switch to All so the cancelled card stays visible
      renderMyRequests();                       // instant re-render with grey badge
      fetchMyRequests();                        // then sync fresh data from server
      fetchNotifications();
    },
    { confirmLabel: 'Yes, Cancel', cancelLabel: 'Keep it', destructive: true }
  );
}

function openAcceptFromMyRequests(id) {
  const req = myIncomingRequests.find(r => r.id === id);
  if (!req) return;
  openAcceptModal(req);
}

function updateReqBadge() {
  const badge = document.getElementById('reqBadge');
  if (!badge) return;
  const count = currentMode === 'tutor'
    ? myIncomingRequests.length
    : myRequestsData.filter(r => r.status === 'Pending' || r.status === 'CounterProposed').length;
  if (count > 0) {
    badge.textContent   = count > 9 ? '9+' : count;
    badge.style.display = 'inline-flex';
  } else {
    badge.style.display = 'none';
  }
}

// Mode-aware: tutor toggle → incoming requests, tutee toggle → sent requests
function renderMyRequests() {
  updateReqBadge();
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
              <button class="btn-accept"  data-action="accept-incoming"  data-id="${esc(req.id)}">Accept</button>
              <button class="btn-outline" data-action="counter-propose"  data-id="${esc(req.id)}">Propose Changes</button>
              <button class="btn-decline" data-action="decline-incoming" data-id="${esc(req.id)}">Decline</button>
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
              <button class="btn-accept"  data-action="counter-accept"  data-id="${esc(req.id)}">Accept</button>
              <button class="btn-decline" data-action="counter-decline" data-id="${esc(req.id)}">Decline</button>
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
                  data-action="review" data-session-id="${esc(req.session.session_id)}" data-tutor-id="${esc(req.tutorId)}" data-tutor-name="${esc(req.tutorName)}">
                  ★ Leave Review
                </button>`
              : ''}
            ${req.session.hasReview ? `<span style="color:#aaa;margin-left:.5rem;font-size:.85rem;">Reviewed ✓</span>` : ''}
          </div>`;
      }

      const cancelBtn = (req.status === 'Pending' || req.status === 'CounterProposed')
        ? `<div style="margin-top:.5rem;">
             <button class="btn-outline" style="font-size:.8rem;padding:.3rem .7rem;color:var(--coral);border-color:var(--coral);"
               data-action="cancel-request" data-id="${esc(req.id)}">Cancel Request</button>
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
    // Use semantic classes (.topic-chip) — styling lives in dashboard.css.
    // The .checked class is toggled below so the chip turns purple when picked,
    // giving the user clear visual feedback at a glance.
    listEl.innerHTML = topics.map(t => `
      <label class="topic-chip">
        <input type="checkbox" value="${esc(t.topic_name)}">
        <span>${esc(t.topic_name)}</span>
      </label>`).join('');
    // Single delegated change-listener flips the .checked class on whichever
    // chip the user clicks. Cleaner than wiring N individual onchange handlers.
    listEl.onchange = (e) => {
      if (e.target.matches('input[type="checkbox"]')) {
        e.target.closest('.topic-chip')?.classList.toggle('checked', e.target.checked);
      }
    };
    wrap.style.display = 'block';
  } catch {
    wrap.style.display = 'none';
  }
}


// ===== ESC KEY: close any open modal =====
document.addEventListener('keydown', function (e) {
  if (e.key !== 'Escape') return;
  const modalMap = [
    ['confirmModalOverlay',    closeConfirmModal],
    ['sessionModalOverlay',    closeSessionModal],
    ['requestsModalOverlay',   closeRequestsModal],
    ['counterModalOverlay',    closeCounterModal],
    ['groupSessionModalOverlay', closeGroupSessionModal],
    ['reviewModalOverlay',     closeReviewModal],
    ['acceptModalOverlay',     closeAcceptModal],
    ['tutorProfileOverlay',    closeTutorProfile],
    ['deleteModal',            closeDeleteModal],
    ['completeSessionOverlay', closeCompleteSessionModal],
  ];
  for (const [id, fn] of modalMap) {
    const el = document.getElementById(id);
    if (el && el.classList.contains('open')) { fn(); return; }
  }
  if (notifDropdownOpen) {
    notifDropdownOpen = false;
    document.getElementById('notifDropdown').classList.remove('open');
  }
});

// ===== GLOBAL EXPORTS =====
// Explicitly assigns every function called by an onclick="" attribute in any
// Blade template to window, so they work whether this file is loaded as a
// classic <script> or compiled as a Vite ES module in the future.
Object.assign(window, {
  // Navigation & layout
  switchView, setMode,

  // Explore Tutors
  filterTutors, toggleCourseDropdown, addFilterCourse, removeFilterCourse, handleCourseInput,

  // Notifications
  toggleNotifDropdown, markAllNotificationsRead, notifNavigate,

  // Session request modal
  closeSessionModal, loadSessionTopics, submitRequest,

  // Tutor: Requests modal
  openRequestsModal, closeRequestsModal, switchReqTab,
  respondTutorRequest,
  openCounterModal, closeCounterModal, submitCounterProposal,
  openAcceptFromMyRequests, closeAcceptModal, toggleAcceptLink, submitAccept,
  claimBroadcast,

  // Tutor: Group session
  openGroupSessionModal, closeGroupSessionModal, toggleGroupLink, submitGroupSession,

  // Session lifecycle (Task 5)
  completeSession, closeCompleteSessionModal, submitCompleteSession, cancelSession,

  // Open group sessions (tutee)
  joinSession,

  // Reviews
  openReviewModal, closeReviewModal, submitReview,

  // My Requests
  cancelRequest, respondToCounter, declineIncomingRequest,

  // Tutor profile detail
  openTutorProfile, closeTutorProfile,

  // Onboarding
  obNext, obPrev, obFinish,
  obAddCourse, obRemoveCourse,
  obAddTuteeCourse, obRemoveTuteeCourse,

  // Profile edit
  toggleEditMode, saveProfile, addProfileCourse, removeProfileTag, handlePhotoSelect,
  togglePersonalEdit, savePersonalInfo,
  togglePasswordChange, changePassword,

  // Delete account
  openDeleteModal, closeDeleteModal, prepareDeleteSubmit,

  // Confirm modal
  closeConfirmModal, _executeConfirm,

  // Cache (exposed for the logout form's onsubmit hook in dashboard.blade.php
  // and as a debugging aid: open DevTools and run `cache.clearAll()` to reset)
  cache,

  // Tutee broadcast modal opener
  openBroadcastModal,
});
