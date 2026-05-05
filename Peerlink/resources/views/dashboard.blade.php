<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PeerLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('style.css') }}"/>
</head>
<body class="mode-tutee">

  <!-- Pass server data to JS -->
  <script>
    window.__onboarding = {{ $onboarding ? 'true' : 'false' }};
    window.__courses = {!! json_encode($courses->map(fn($c) => ['code' => $c->course_code, 'name' => $c->course_name])->values()) !!};
  </script>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <a class="logo" href="#">
      <div class="logo-icon"></div>
      <span class="logo-text">PEER<strong>LINK</strong></span>
    </a>

    <div class="nav-links">
      <a href="#" class="nav-link" id="navDashboard"   onclick="switchView('dashboard')">Explore Tutors</a>
      <a href="#" class="nav-link" id="navMyRequests"  onclick="switchView('myRequests')">My Requests</a>
      <a href="#" class="nav-link" id="navProfile"     onclick="switchView('profile')">Profile</a>
      <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <a href="{{ route('logout') }}" class="nav-link"
           onclick="event.preventDefault(); this.closest('form').submit();">Log out</a>
      </form>
    </div>

    <!-- Notification Bell -->
    <div class="notif-bell-wrap" id="notifBellWrap">
      <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifDropdown()" title="Notifications">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
          <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
        </svg>
        <span class="notif-badge" id="notifBadge" style="display:none">0</span>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
          <span>Notifications</span>
          <button class="notif-mark-read" onclick="markAllNotificationsRead()">Mark all read</button>
        </div>
        <div id="notifList"><p class="notif-empty">No notifications yet.</p></div>
      </div>
    </div>

    <!-- Mode Toggle -->
    <div class="mode-toggle" id="modeToggle">
      <button class="toggle-btn" id="tutorBtn" onclick="setMode('tutor')">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
          <path d="M16 3.128a4 4 0 0 1 0 7.744"/>
          <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
          <circle cx="9" cy="7" r="4"/>
        </svg>
        Tutor
      </button>
      <button class="toggle-btn" id="tuteeBtn" onclick="setMode('tutee')">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M13 21h8"/>
          <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
        </svg>
        Tutee
      </button>
    </div>
  </nav>

  <main class="page">

    <!-- ===== DASHBOARD VIEW ===== -->
    <div id="dashboardView" style="display:none;">

      <!-- Tutee: Explore Tutors -->
      <div class="tutee-only">
        <div class="page-header">
          <h1 class="page-title">Explore Tutors</h1>
          <div class="search-bar-wrapper">
            <div class="search-input-wrap">
              <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2.2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
              </svg>
              <input type="text" id="searchName" placeholder="Search name" oninput="filterTutors()"/>
            </div>
            <div class="filter-tags" id="filterTags"></div>
            <div class="course-adder">
              <button class="course-add-btn" id="courseAddBtn" onclick="toggleCourseDropdown()">
                Course code
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M12 5v14M5 12h14"/>
                </svg>
              </button>
              <div class="course-dropdown" id="courseDropdown">
                <input type="text" id="courseInput" placeholder="e.g. CMSC 11" onkeydown="handleCourseInput(event)"/>
                <p class="course-hint">Press Enter to add</p>
                <div class="course-suggestions" id="courseSuggestions">
                  @foreach($courses->take(6) as $course)
                    <button onclick="addFilterCourse('{{ $course->course_code }}')">{{ $course->course_code }}</button>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tutors-grid" id="tutorsGrid"></div>
        <div class="empty-state" id="emptyState" style="display:none">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <p>No tutors found for your filters.</p>
        </div>
      </div>

      <!-- Tutor: Dashboard -->
      <div class="tutor-only">
        <div class="tutor-dashboard">
          <div class="dashboard-welcome">
            <h1>Welcome back, <span class="accent">{{ auth()->user()->first_name }}</span> 👋</h1>
            <p>You're in Tutor mode. Manage your sessions and profile below.</p>
          </div>
          <div class="dashboard-cards">
            <div class="dash-card">
              <div class="dash-card-icon">📅</div>
              <div class="dash-card-label">Upcoming Sessions</div>
              <div class="dash-card-value" id="statSessions">—</div>
            </div>
            <div class="dash-card">
              <div class="dash-card-icon">⭐</div>
              <div class="dash-card-label">Your Rating</div>
              <div class="dash-card-value" id="statRating">—</div>
            </div>
            <div class="dash-card">
              <div class="dash-card-icon">📘</div>
              <div class="dash-card-label">Courses Offered</div>
              <div class="dash-card-value" id="statCourses">—</div>
            </div>
          </div>
          <div class="tutor-cta">
            <button class="btn-primary"  onclick="openRequestsModal()">View Session Requests</button>
            <button class="btn-outline"  onclick="openGroupSessionModal()">Post Group Session</button>
            <button class="btn-outline"  onclick="switchView('profile')">Edit My Profile</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MY REQUESTS VIEW ===== -->
    <div id="myRequestsView" style="display:none;">
      <div class="page-header">
        <h1 class="page-title">My Requests</h1>
        <div class="filter-row">
          <select id="myRequestsFilter" onchange="renderMyRequests()" class="select-course" style="width:auto;min-width:160px;">
            <option value="all">All</option>
            <option value="Pending">Pending</option>
            <option value="CounterProposed">Counter-Proposed</option>
            <option value="Approved">Approved</option>
            <option value="Declined">Declined</option>
          </select>
        </div>
      </div>
      <div id="myRequestsList" class="requests-list" style="max-width:720px;margin:0 auto;"></div>
    </div>

    <!-- ===== PROFILE VIEW ===== -->
    <div id="profileView" class="profile-container">
      <div class="profile-card">
        <div class="profile-header-main">
          <div class="tutor-avatar larger">{{ substr(auth()->user()->first_name,0,1) }}{{ substr(auth()->user()->last_name,0,1) }}</div>
          <div class="profile-meta">
            <h1 id="profileDisplayName">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h1>
            <div class="role-badge" id="roleBadge"></div>
          </div>
          <button class="btn-outline" onclick="toggleEditMode(true)">Edit Profile</button>
        </div>

        <!-- View mode -->
        <div id="profileViewMode">
          <section class="profile-section bio-box">
            <h3>Bio</h3>
            <p id="bioDisplay" class="bio-text">No bio added yet.</p>
          </section>
          <div class="profile-grid">
            <section class="profile-section">
              <h3>Courses I Can Tutor</h3>
              <div id="tutorCoursesDisplay" class="tutor-courses"></div>
            </section>
            <section class="profile-section">
              <h3>Courses I Need Help With</h3>
              <div id="tuteeCoursesDisplay" class="tutor-courses"></div>
            </section>
          </div>
          <div class="danger-zone">
            <h4>Danger Zone</h4>
            <p class="danger-text">This action is permanent and cannot be undone.</p>
            <button class="btn-danger-outline" onclick="openDeleteModal()">Delete Account</button>
          </div>
        </div>

        <!-- Edit mode -->
        <form id="profileEditMode" style="display:none;" onsubmit="saveProfile(event)">
          <div class="edit-layout">
            <div class="input-group">
              <label>Bio</label>
              <div class="bio-textarea-wrapper">
                <textarea id="bioInput" maxlength="250"></textarea>
                <div class="char-counter"><span id="charCount">0</span>/250</div>
              </div>
            </div>
            <div class="course-edit-grid">
              <div class="input-group">
                <label>Expertise (Tutor)</label>
                <select class="select-course" id="tutorSelect" onchange="addProfileCourse('tutor')">
                  <option value="" disabled selected>+ Add Course</option>
                  @foreach($courses as $course)
                    <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_name }}</option>
                  @endforeach
                </select>
                <div id="tutorTags" class="edit-tags-container"></div>
              </div>
              <div class="input-group">
                <label>Learning Goals (Tutee)</label>
                <select class="select-course" id="tuteeSelect" onchange="addProfileCourse('tutee')">
                  <option value="" disabled selected>+ Add Course</option>
                  @foreach($courses as $course)
                    <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_name }}</option>
                  @endforeach
                </select>
                <div id="tuteeTags" class="edit-tags-container"></div>
              </div>
            </div>
            <div class="form-actions-spacer">
              <button type="button" class="btn-outline" onclick="toggleEditMode(false)">Cancel</button>
              <button type="submit" class="btn-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </main>

  <!-- ===== SESSION REQUEST MODAL (US_09) ===== -->
  <div class="modal-overlay" id="sessionModalOverlay" onclick="closeSessionModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeSessionModal()">✕</button>
      <div class="modal-avatar" id="modalAvatar"></div>
      <h2 class="modal-name" id="modalName"></h2>
      <p class="modal-sub"  id="modalSub"></p>
      <div class="modal-form">
        <label>Course</label>
        <select id="sessionCourse" class="select-course" style="margin-bottom:.75rem;">
          <option value="" disabled selected>Select a course</option>
        </select>
        <label>Subject / Topic</label>
        <input type="text" placeholder="e.g. Pointers in C, Recursion…" id="sessionTopic"/>
        <label>Preferred Schedule</label>
        <input type="datetime-local" id="sessionDate"/>
        <label>Message (optional)</label>
        <textarea placeholder="Any notes for the tutor…" id="sessionMessage"></textarea>
        <button class="btn-primary full-width" onclick="submitRequest()" style="margin-top:1rem;">Send Request</button>
      </div>
    </div>
  </div>

  <!-- ===== DELETE ACCOUNT MODAL ===== -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal">
      <button class="modal-close" onclick="closeDeleteModal()">✕</button>
      <h2 style="margin-bottom:1rem;color:var(--coral);">Delete Account</h2>
      <p style="margin-bottom:1rem;color:var(--text-muted);">Confirm with your password to permanently delete your account.</p>
      <input type="password" id="deleteConfirmPassword" class="modal-input"
             placeholder="Password"
             style="width:100%;padding:.8rem;border-radius:var(--radius-sm);border:1px solid #e0d8c8;margin-bottom:1.5rem;font-family:inherit;"/>
      <p id="deleteError" style="color:var(--coral);font-size:.85rem;margin-bottom:.75rem;display:none;"></p>
      <!-- Hidden form that does the real DELETE /profile submit -->
      <form id="deleteAccountForm" method="POST" action="/profile">
        @csrf
        @method('DELETE')
        <input type="hidden" name="password" id="deletePasswordHidden"/>
        <button type="submit" class="btn-primary full-width" style="background:var(--coral);"
                onclick="return prepareDeleteSubmit()">Delete Permanently</button>
      </form>
    </div>
  </div>

  <!-- ===== TUTOR: REQUESTS MODAL (direct + broadcast tabs) ===== -->
  <div class="modal-overlay" id="requestsModalOverlay" onclick="closeRequestsModal()">
    <div class="modal modal-lg" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeRequestsModal()">✕</button>
      <h2 class="page-title" style="margin-bottom:1rem;font-size:1.5rem;">Session Requests</h2>
      <!-- Tabs -->
      <div class="req-tabs">
        <button class="req-tab active" id="tabDirect"    onclick="switchReqTab('direct')">Direct Requests</button>
        <button class="req-tab"        id="tabBroadcast" onclick="switchReqTab('broadcast')">Broadcasts</button>
      </div>
      <div id="requestsList"  class="requests-list"></div>
      <div id="broadcastList" class="requests-list" style="display:none;"></div>
    </div>
  </div>

  <!-- ===== COUNTER-PROPOSAL MODAL (US_11 tutor) ===== -->
  <div class="modal-overlay" id="counterModalOverlay" onclick="closeCounterModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeCounterModal()">✕</button>
      <h2 style="margin-bottom:1rem;">Propose New Schedule</h2>
      <input type="hidden" id="counterRequestId"/>
      <div class="modal-form">
        <label>Proposed Date &amp; Time</label>
        <input type="datetime-local" id="counterTime"/>
        <label>Modality</label>
        <select id="counterModality" class="select-course" style="margin-bottom:.75rem;">
          <option value="In-Person">In-Person</option>
          <option value="Online">Online</option>
        </select>
        <label>Message to Student (optional)</label>
        <textarea id="counterMessage" placeholder="Explain the change…"></textarea>
        <button class="btn-primary full-width" onclick="submitCounterProposal()" style="margin-top:1rem;">Send Proposal</button>
      </div>
    </div>
  </div>

  <!-- ===== GROUP SESSION MODAL (US_13 tutor) ===== -->
  <div class="modal-overlay" id="groupSessionModalOverlay" onclick="closeGroupSessionModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeGroupSessionModal()">✕</button>
      <h2 style="margin-bottom:1rem;">Post Group Study Session</h2>
      <div class="modal-form">
        <label>Course</label>
        <select id="groupCourse" class="select-course" style="margin-bottom:.75rem;">
          <option value="" disabled selected>Select a course</option>
          @foreach($courses as $course)
            <option value="{{ $course->course_code }}">{{ $course->course_code }} — {{ $course->course_name }}</option>
          @endforeach
        </select>
        <label>Date &amp; Time</label>
        <input type="datetime-local" id="groupTime"/>
        <label>Modality</label>
        <select id="groupModality" class="select-course" style="margin-bottom:.75rem;" onchange="toggleGroupLink()">
          <option value="In-Person">In-Person</option>
          <option value="Online">Online</option>
        </select>
        <div id="groupRoomWrap">
          <label>Room</label>
          <select id="groupRoom" class="select-course" style="margin-bottom:.75rem;">
            <option value="">— auto-assign —</option>
          </select>
        </div>
        <div id="groupLinkWrap" style="display:none;">
          <label>Meeting Link</label>
          <input type="url" id="groupLink" placeholder="https://meet.google.com/…"/>
        </div>
        <label>Message (optional)</label>
        <textarea id="groupMessage" placeholder="What topics will you cover?"></textarea>
        <button class="btn-primary full-width" onclick="submitGroupSession()" style="margin-top:1rem;">Post Session</button>
      </div>
    </div>
  </div>

  <!-- ===== REVIEW MODAL (US_16 student) ===== -->
  <div class="modal-overlay" id="reviewModalOverlay" onclick="closeReviewModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeReviewModal()">✕</button>
      <h2 style="margin-bottom:.5rem;">Leave a Review</h2>
      <p id="reviewTutorName" style="color:var(--text-muted);margin-bottom:1rem;"></p>
      <input type="hidden" id="reviewSessionId"/>
      <input type="hidden" id="reviewTutorId"/>
      <div class="modal-form">
        <label>Rating</label>
        <div class="star-rating" id="starRating">
          <span class="star-btn" data-val="1">★</span>
          <span class="star-btn" data-val="2">★</span>
          <span class="star-btn" data-val="3">★</span>
          <span class="star-btn" data-val="4">★</span>
          <span class="star-btn" data-val="5">★</span>
        </div>
        <input type="hidden" id="reviewRating" value="0"/>
        <label style="margin-top:.75rem;">Feedback (optional)</label>
        <textarea id="reviewFeedback" placeholder="Share your experience…"></textarea>
        <button class="btn-primary full-width" onclick="submitReview()" style="margin-top:1rem;">Submit Review</button>
      </div>
    </div>
  </div>

  <!-- ===== ONBOARDING MODAL (US_06 + US_07) ===== -->
  <div class="modal-overlay open" id="onboardingOverlay" style="display:none;">
    <div class="modal modal-lg" onclick="event.stopPropagation()" style="max-width:560px;">

      <!-- Step 1: Welcome -->
      <div class="ob-step" id="obStep1">
        <div style="font-size:2.5rem;margin-bottom:.75rem;">👋</div>
        <h2 style="margin-bottom:.5rem;">Welcome to PeerLink!</h2>
        <p style="color:var(--text-muted);margin-bottom:1.5rem;line-height:1.6;">
          PeerLink connects students who need help with peers who can tutor them.
          Let's take a quick tour of the core flow before you dive in.
        </p>
        <button class="btn-primary full-width" onclick="obNext()">Start Tutorial →</button>
      </div>

      <!-- Step 2: Find a Tutor (mock) -->
      <div class="ob-step" id="obStep2" style="display:none;">
        <h2 style="margin-bottom:.25rem;">Step 1 — Find a Tutor</h2>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.9rem;">
          In <strong>Explore Tutors</strong>, you can filter by course code or search by name.
          Each card shows the tutor's courses and rating.
        </p>
        <div class="tutors-grid" style="pointer-events:none;opacity:.85;margin-bottom:1.25rem;">
          <div class="tutor-card">
            <div class="tutor-card-header">
              <div class="tutor-avatar">AS</div>
              <div class="tutor-info">
                <div class="tutor-name">Alex Santos</div>
                <div class="tutor-degree">BSCS 3</div>
                <div class="tutor-rating"><span class="star">★</span> 4.8 <span>(12 reviews)</span></div>
              </div>
            </div>
            <div class="tutor-courses">
              <span class="course-badge">CMSC121</span>
              <span class="course-badge">CMSC122</span>
            </div>
            <button class="btn-primary full-width" style="background:var(--purple);">Request session</button>
          </div>
          <div class="tutor-card">
            <div class="tutor-card-header">
              <div class="tutor-avatar">MC</div>
              <div class="tutor-info">
                <div class="tutor-name">Maria Cruz</div>
                <div class="tutor-degree">BSCS 4</div>
                <div class="tutor-rating"><span class="star">★</span> 4.9 <span>(8 reviews)</span></div>
              </div>
            </div>
            <div class="tutor-courses">
              <span class="course-badge">MATH18</span>
              <span class="course-badge">STAT105</span>
            </div>
            <button class="btn-primary full-width" style="background:var(--purple);">Request session</button>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button class="btn-outline" onclick="obPrev()">← Back</button>
          <button class="btn-primary" style="flex:1;" onclick="obNext()">Next →</button>
        </div>
      </div>

      <!-- Step 3: Request a Session (mock) -->
      <div class="ob-step" id="obStep3" style="display:none;">
        <h2 style="margin-bottom:.25rem;">Step 2 — Request a Session</h2>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.9rem;">
          Click <strong>Request session</strong> on any tutor card. Fill in the course,
          topic, preferred schedule, and an optional message.
          The tutor can accept, decline, or propose a different time.
        </p>
        <div style="background:var(--cream-dark);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem;pointer-events:none;opacity:.85;">
          <div style="font-weight:600;margin-bottom:.75rem;">📋 Sample Request Form</div>
          <div style="display:flex;flex-direction:column;gap:.5rem;font-size:.875rem;">
            <div><span style="color:var(--text-muted);">Course:</span> <span class="course-badge">CMSC121</span></div>
            <div><span style="color:var(--text-muted);">Topic:</span> Laravel Routing &amp; Controllers</div>
            <div><span style="color:var(--text-muted);">Schedule:</span> May 10, 2026 · 2:00 PM</div>
            <div><span style="color:var(--text-muted);">Message:</span> Hi! I'm struggling with middleware. Can we go through it?</div>
          </div>
        </div>
        <div style="display:flex;gap:.75rem;">
          <button class="btn-outline" onclick="obPrev()">← Back</button>
          <button class="btn-primary" style="flex:1;" onclick="obNext()">Next →</button>
        </div>
      </div>

      <!-- Step 4: Add Courses to Tutor (US_07 — real save) -->
      <div class="ob-step" id="obStep4" style="display:none;">
        <h2 style="margin-bottom:.25rem;">Step 3 — List Your Tutor Courses</h2>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:.9rem;">
          Add <strong>at least one course</strong> you're comfortable tutoring.
          This makes you visible to learners in the Explore tab.
        </p>
        <select id="obCourseSelect" class="select-course" style="margin-bottom:.5rem;" onchange="obAddCourse()">
          <option value="" disabled selected>+ Add a course you can tutor</option>
        </select>
        <div id="obCourseTags" class="edit-tags-container" style="margin-bottom:1.25rem;"></div>
        <div style="display:flex;gap:.75rem;">
          <button class="btn-outline" onclick="obPrev()">← Back</button>
          <button class="btn-primary" style="flex:1;" id="obFinishBtn" onclick="obFinish()">Finish &amp; Go to Dashboard</button>
        </div>
      </div>

    </div>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast"></div>

  <script src="{{ asset('app.js') }}"></script>
</body>
</html>
