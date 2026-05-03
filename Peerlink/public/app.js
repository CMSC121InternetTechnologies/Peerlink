// ===== STATE =====
let userProfile = {
  bio: "",
  tutorCourses: [],
  tuteeCourses: []
};

let currentMode = 'tutee';
let currentView = 'dashboard'; // Default landing view
let activeDashboardCourses = [];
let allTutors = []; // Will be populated strictly by MariaDB API
let allUniqueCourses = new Set(); // Holds unique course codes for the search dropdown
let selectedTutor = null;
let dropdownOpen = false;

// ===== INITIALIZATION & NAVIGATION =====
document.addEventListener('DOMContentLoaded', () => {
  setMode('tutee');
  switchView('dashboard');
  updateProfileDisplay();
  
  // 1. Fetch real tutors from Laravel API on load
  fetchTutors();

  // 2. Character counter for bio input
  const bioInput = document.getElementById('bioInput');
  if (bioInput) {
    bioInput.addEventListener('input', () => {
      document.getElementById('charCount').textContent = bioInput.value.length;
    });
  }
});

// Switch between Profile and Dashboard views
function switchView(view) {
  currentView = view;
  
  // Toggle Visibility
  document.getElementById('dashboardView').style.display = view === 'dashboard' ? 'block' : 'none';
  document.getElementById('profileView').style.display = view === 'profile' ? 'block' : 'none';

  // Update Nav Active States
  document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
  document.getElementById(`nav${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.add('active');
}

// ===== MODE TOGGLE (Tutor / Tutee) =====
function setMode(mode) {
  currentMode = mode;
  document.body.className = `mode-${mode}`;
  const roleBadge = document.getElementById('roleBadge');

  if (mode === 'tutor') {
      roleBadge.textContent = "TUTOR PROFILE";
      roleBadge.style.background = "var(--teal-dark)";
  } else {
      roleBadge.textContent = "TUTEE PROFILE";
      roleBadge.style.background = "var(--coral)";
  }
}

// ===== PROFILE VIEW LOGIC =====
function toggleEditMode(isEdit) {
  const viewMode = document.getElementById('profileViewMode');
  const editMode = document.getElementById('profileEditMode');

  viewMode.style.display = isEdit ? 'none' : 'block';
  editMode.style.display = isEdit ? 'block' : 'none';

  if (isEdit) {
      document.getElementById('bioInput').value = userProfile.bio;
      renderProfileTags('tutor');
      renderProfileTags('tutee');
  }
}

function addProfileCourse(type) {
  const select = document.getElementById(`${type}Select`);
  const selectedValue = select.value;
  const courseList = type === 'tutor' ? userProfile.tutorCourses : userProfile.tuteeCourses;

  if (selectedValue && !courseList.includes(selectedValue)) {
      courseList.push(selectedValue);
      renderProfileTags(type);
  }
  select.selectedIndex = 0;
}

function renderProfileTags(type) {
  const container = document.getElementById(`${type}Tags`);
  const courseList = type === 'tutor' ? userProfile.tutorCourses : userProfile.tuteeCourses;

  container.innerHTML = courseList.map(course => `
  <span class="filter-tag">
      ${course}
      <button type="button" onclick="removeProfileTag('${course}', '${type}')">✕</button>
  </span>
  `).join('');
}

function removeProfileTag(course, type) {
  if (type === 'tutor') {
      userProfile.tutorCourses = userProfile.tutorCourses.filter(c => c !== course);
  } else {
      userProfile.tuteeCourses = userProfile.tuteeCourses.filter(c => c !== course);
  }
  renderProfileTags(type);
}

function saveProfile(event) {
  event.preventDefault();
  userProfile.bio = document.getElementById('bioInput').value;
  updateProfileDisplay();
  toggleEditMode(false);
  showToast("Profile updated!");
}

function updateProfileDisplay() {
  document.getElementById('bioDisplay').textContent = userProfile.bio || "No bio added yet.";
  document.getElementById('tutorCoursesDisplay').innerHTML = userProfile.tutorCourses.map(course => `<span class="course-badge">${course}</span>`).join('');
  document.getElementById('tuteeCoursesDisplay').innerHTML = userProfile.tuteeCourses.map(course => `<span class="course-badge">${course}</span>`).join('');
}


// ===== DASHBOARD (EXPLORE) LOGIC =====

function toggleCourseDropdown() {
  dropdownOpen = !dropdownOpen;
  const dropdown = document.getElementById('courseDropdown');
  dropdown.classList.toggle('open', dropdownOpen);
  
  if (dropdownOpen) {
    setTimeout(() => document.getElementById('courseInput').focus(), 50);
    renderCourseSuggestions(''); // Show all initial suggestions
  }
}

document.addEventListener('click', (e) => {
  const adder = document.querySelector('.course-adder');
  if (adder && !adder.contains(e.target)) {
    dropdownOpen = false;
    document.getElementById('courseDropdown').classList.remove('open');
  }
});

// Dynamic filtering as the user types a course
document.getElementById('courseInput').addEventListener('input', (e) => {
  const query = e.target.value.replace(/\s+/g, '').toUpperCase();
  renderCourseSuggestions(query);
});

// Handle "Enter" key
function handleCourseInput(e) {
  if (e.key === 'Enter') {
    const val = document.getElementById('courseInput').value.replace(/\s+/g, '').toUpperCase();
    if (val) {
      addFilterCourse(val);
      document.getElementById('courseInput').value = '';
    }
  }
}

// Render dynamic dropdown suggestions
function renderCourseSuggestions(query) {
  const container = document.getElementById('courseSuggestions');
  let filtered = Array.from(allUniqueCourses);
  
  if (query) {
    filtered = filtered.filter(c => c.includes(query));
  }
  
  // Show up to 8 suggestions
  container.innerHTML = filtered.slice(0, 8).map(c => `
    <button onclick="addFilterCourse('${c}')">${c}</button>
  `).join('');
}

function addFilterCourse(code) {
  code = code.replace(/\s+/g, '').toUpperCase();
  if (!code || activeDashboardCourses.includes(code)) return;
  activeDashboardCourses.push(code);
  renderFilterTags();
  filterTutors(); // Instant client-side filter
  dropdownOpen = false;
  document.getElementById('courseDropdown').classList.remove('open');
}

function removeFilterCourse(code) {
  activeDashboardCourses = activeDashboardCourses.filter(c => c !== code);
  renderFilterTags();
  filterTutors();
}

function renderFilterTags() {
  const container = document.getElementById('filterTags');
  container.innerHTML = activeDashboardCourses.map(code => `
    <span class="filter-tag">
      ${code}
      <button onclick="removeFilterCourse('${code}')" title="Remove">✕</button>
    </span>
  `).join('');
}


// ===== FETCH & RENDER TUTORS API =====

// Fetch all tutors ONE TIME from Laravel API on page load
async function fetchTutors() {
  try {
    const res = await fetch('/api/tutors');
    if (!res.ok) throw new Error('API error');
    
    const data = await res.json();
    allTutors = data.tutors || [];
    
    // Gather all unique course codes into our Set for the dropdown
    allTutors.forEach(tutor => {
      tutor.courses.forEach(c => allUniqueCourses.add(c));
    });

    filterTutors();
  } catch (err) {
    console.error('Failed to load tutors:', err);
    allTutors = [];
    filterTutors();
  }
}

// Perform the client-side search instantly
function filterTutors() {
  const query = document.getElementById('searchName').value.trim().toLowerCase();
  
  const filtered = allTutors.filter(t => {
    // 1. Search by full name
    const nameMatch = !query || t.name.toLowerCase().includes(query);
    
    // 2. Search by course code (Tutor must have ALL active filters to show)
    const courseMatch = activeDashboardCourses.length === 0 || activeDashboardCourses.every(c => t.courses.includes(c));
    
    return nameMatch && courseMatch;
  });
  
  renderTutors(filtered);
}

function renderTutors(tutors) {
  const grid = document.getElementById('tutorsGrid');
  const empty = document.getElementById('emptyState');

  if (!tutors.length) {
    grid.innerHTML = '';
    empty.style.display = 'flex';
    return;
  }

  empty.style.display = 'none';
  grid.innerHTML = tutors.map((t, i) => `
    <div class="tutor-card" style="animation-delay: ${i * 0.06}s">
      <div class="tutor-card-header">
        <div class="tutor-avatar">${t.initials || t.name[0]}</div>
        <div class="tutor-info">
          <div class="tutor-name">${t.name}</div>
          <div class="tutor-degree">${t.degree || ''}</div>
          <div class="tutor-rating"><span class="star">★</span> ${(t.rating || 0).toFixed(1)} <span>(${t.reviews || 0} reviews)</span></div>
        </div>
      </div>
      <div class="tutor-courses">
        ${(t.courses || []).map(c => `<span class="course-badge">${c}</span>`).join('')}
      </div>
      <button class="btn-primary full-width" style="background: var(--purple);" onclick="openSessionModal('${t.id}')">Request session</button>
    </div>
  `).join('');
}


// ===== MODALS & TOAST =====
function openSessionModal(id) {
  const tutor = allTutors.find(t => t.id === id); // id is now a UUID string from Laravel
  if (!tutor) return;
  selectedTutor = tutor;

  document.getElementById('modalName').textContent = tutor.name;
  document.getElementById('modalSub').textContent = tutor.degree || '';
  document.getElementById('modalCourses').innerHTML = (tutor.courses || []).map(c => `<span class="course-badge">${c}</span>`).join('');
  document.getElementById('modalAvatar').innerHTML = tutor.initials || tutor.name[0];
  document.getElementById('sessionModalOverlay').classList.add('open');
}

function closeSessionModal() {
  document.getElementById('sessionModalOverlay').classList.remove('open');
  selectedTutor = null;
}

function submitRequest() {
  if (!document.getElementById('sessionTopic').value.trim() || !document.getElementById('sessionDate').value) {
    showToast('Please fill out all required fields.');
    return;
  }
  closeSessionModal();
  showToast(`✅ Request sent to ${selectedTutor?.name}!`);
  document.getElementById('sessionTopic').value = '';
  document.getElementById('sessionDate').value = '';
  document.getElementById('sessionMessage').value = '';
}

function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('open');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}

function showToast(message) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}


// ===== VIEW PENDING REQUESTS (TUTOR MODE) =====
let pendingRequests = [];

async function fetchRequests() {
  try {
    // Attempt to fetch from API
    const res = await fetch('/api/requests'); // Updated to reflect standard Laravel API route naming
    if (!res.ok) throw new Error('API error');
    const data = await res.json();
    pendingRequests = data.requests || [];
  } catch (err) {
    console.warn('API unavailable, using frontend fallback mock data.');
    // Fallback if PHP isn't running
    pendingRequests = [
      { id: 101, tuteeName: "Juan Dela Cruz", topic: "Pointers and Memory Allocation in C", date: "2026-05-05T14:00", message: "Hi! I am struggling with our CMSC 21 lab about pointers. Can you help me debug my code?" }
    ];
  }
  renderRequests();
}

function openRequestsModal() {
  fetchRequests(); // Fetch fresh data every time the modal opens
  document.getElementById('requestsModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden'; // prevent background scrolling
}

function closeRequestsModal() {
  document.getElementById('requestsModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

function renderRequests() {
  const list = document.getElementById('requestsList');
  
  if (pendingRequests.length === 0) {
    list.innerHTML = `<p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No pending requests! 🎉</p>`;
    return;
  }

  list.innerHTML = pendingRequests.map(req => {
    const dateObj = new Date(req.date);
    const dateStr = dateObj.toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });

    return `
    <div class="request-card" id="reqCard-${req.id}">
      <div class="request-header">
        <div class="request-tutee">${req.tuteeName}</div>
        <div class="course-badge" style="margin:0; background: var(--teal); color: white;">New</div>
      </div>
      <div class="request-topic">${req.topic}</div>
      <div class="request-date">🗓 ${dateStr}</div>
      
      ${req.message ? `<div class="request-msg">"${req.message}"</div>` : ''}
      
      <div class="request-actions">
        <input type="text" class="reply-input" id="reply-${req.id}" placeholder="Type a reply note..." />
        <button class="btn-accept" onclick="respondRequest('${req.id}', 'accepted')">Accept</button>
        <button class="btn-decline" onclick="respondRequest('${req.id}', 'declined')">Decline</button>
      </div>
    </div>
    `;
  }).join('');
}

function respondRequest(id, status) {
  const replyInput = document.getElementById(`reply-${id}`).value.trim();
  
  // In production, you would POST this to another PHP file
  console.log(`Request ${id} marked as ${status}. Tutor reply:`, replyInput || "[No reply]");

  // Remove the card from the UI
  pendingRequests = pendingRequests.filter(r => r.id !== id);
  renderRequests();
  
  // Update dashboard counter mockup
  const countBadge = document.querySelector('.dash-card-value');
  if (countBadge && status === 'accepted') {
     countBadge.textContent = parseInt(countBadge.textContent) + 1;
  }

  showToast(`Session ${status}!`);
  
  // Auto-close if list is empty
  if (pendingRequests.length === 0) {
    setTimeout(closeRequestsModal, 1500);
  }
}