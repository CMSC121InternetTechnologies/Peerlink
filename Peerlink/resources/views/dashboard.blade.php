<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>PeerLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
  {{-- @vite() injects <link> tags for every CSS file and a <script> for app.js.
       The old asset('style.css') hard-link was removed to avoid loading the
       stylesheet twice (once from public/ and once through the Vite pipeline). --}}
  @vite([
    'resources/css/style.css',
    'resources/css/dashboard.css',
    'resources/css/register.css',
    'resources/js/app.js',       //{{-- Alpine + CSS bootstrap --}}
    'resources/js/dashboard.js', //{{-- The dashboard SPA — was public/app.js --}}
  ])
</head>
<body class="mode-tutee">

  <!-- Pass server data to JS -->
  <script>
    window.__onboarding = {{ ($onboarding ?? false) ? 'true' : 'false' }};
    {{-- Bug: {!! json_encode() !!} does not escape <, >, or & by default, allowing a
         malicious value in the database to break out of the <script> block (XSS).
         Fix: @json uses JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
         making all angle brackets and ampersands safe inside <script> tags. --}}
    window.__courses  = @json($courses->map(fn($c) => ['code' => $c->course_code, 'name' => $c->course_name])->values());
    window.__programs = @json($programs->map(fn($p) => ['code' => $p->program_code])->values());
    {{-- Bug: auth()->user() was called without null-safety; a corrupted or expired
         session would cause a fatal "Call to a member function on null" error.
         Fix: resolve the user once with null-safe operator (?->), falling back to
         empty strings/zero so the JS app still receives a well-formed object. --}}
    @php $authUser = auth()->user(); @endphp
    window.__authUser = {
      // userId is used to namespace the localStorage cache so a different
      // user logging in on the same browser never sees the previous user's data.
      userId:      @json($authUser?->user_id ?? ''),
      firstName:   @json($authUser?->first_name ?? ''),
      lastName:    @json($authUser?->last_name ?? ''),
      programCode: @json($authUser?->program_code ?? ''),
      yearLevel:   @json($authUser?->current_year_level ?? 0),
      contact:     @json($authUser?->contact_number ?? ''),
    };
  </script>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <a class="logo" href="#">
      <div class="logo-icon"></div>
      <span class="logo-text">PEER<strong>LINK</strong></span>
    </a>

    <div class="nav-links">
      <a href="#" class="nav-link" id="navDashboard"   onclick="switchView('dashboard')">Explore Tutors</a>
      <a href="#" class="nav-link" id="navMyRequests"  onclick="switchView('myRequests')">My Requests<span class="nav-req-badge" id="reqBadge" style="display:none;"></span></a>
      <a href="#" class="nav-link" id="navMySessions"  onclick="switchView('mySessions')">My Sessions</a>
      <a href="#" class="nav-link" id="navProfile"     onclick="switchView('profile')">Profile</a>
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
          <button class="notif-mark-read" onclick="markAllNotificationsRead(true)">Mark all read</button>
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

    <!-- Logout Button — wipes the per-user localStorage cache before the form submits
         so a different user logging in on the same browser never sees prior data. -->
    <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: flex; align-items: center;"
          onsubmit="try { window.cache && window.cache.clearAll(); } catch(_){}">
      @csrf
      <button type="submit" class="btn-logout" title="Log out safely">Log out</button>
    </form>
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
            <!-- Broadcast Request button — sits next to the Course code filter.
                 Posts a request with tutor_id:null so any tutor can claim it. -->
            <button class="btn-primary" id="broadcastRequestBtn" title="Post a request that any tutor can claim">
              Broadcast a Request
            </button>
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
            <h1>Welcome back, <span class="accent">{{ auth()->user()?->first_name ?? '' }}</span> 👋</h1>
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
          <select id="myRequestsFilter" onchange="localStorage.setItem('pl_reqFilter',this.value);renderMyRequests()" class="select-course" style="width:auto;min-width:160px;">
            <option value="all">All</option>
            <option value="Pending">Pending</option>
            <option value="CounterProposed">Counter-Proposed</option>
            <option value="Approved">Approved</option>
            <option value="Declined">Declined</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div id="myRequestsList" class="requests-list" style="max-width:720px;margin:0 auto;"></div>
    </div>

    <!-- ===== MY SESSIONS VIEW ===== -->
    <div id="mySessionsView" style="display:none;">
      <div class="page-header">
        <h1 class="page-title">My Sessions</h1>
        <div class="filter-row">
          <select id="sessionsFilter" onchange="localStorage.setItem('pl_sessFilter',this.value);renderSessions()" class="select-course" style="width:auto;min-width:160px;">
            <option value="all">All</option>
            <option value="Scheduled">Upcoming</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div id="mySessionsList" class="requests-list" style="max-width:720px;margin:0 auto;"></div>

      <!-- Browse open group sessions (tutee only) -->
      <div class="tutee-only" id="openSessionsSection" style="margin-top:2.5rem;padding-top:1.5rem;border-top:2px solid #e0d8c8;">
        <div class="page-header" style="padding-top:0;margin-bottom:1rem;">
          <h2 style="font-size:1.3rem;font-weight:600;margin:0;">Browse Open Group Sessions</h2>
        </div>
        <div id="openSessionsList" class="requests-list" style="max-width:720px;margin:0 auto;"></div>
      </div>
    </div>

    <!-- ===== PROFILE VIEW ===== -->
    <div id="profileView" class="profile-container" style="display:none;">
      <div class="profile-card">
        <div class="profile-header-main">
          <div class="profile-avatar-wrap">
            <div class="tutor-avatar larger" id="profileAvatar">{{ substr(auth()->user()?->first_name ?? '?', 0, 1) }}{{ substr(auth()->user()?->last_name ?? '?', 0, 1) }}</div>
            <!-- Camera icon = upload/replace. Clicking it opens the file picker. -->
            <label class="avatar-upload-label" title="Change photo" id="photoChangeBtn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                <circle cx="12" cy="13" r="4"/>
              </svg>
              <input type="file" accept="image/*" id="photoInput" style="display:none;">
            </label>
            <!-- Trash icon = remove the existing photo. Hidden until a photo is set
                 (toggled by app.js via .has-photo on the wrap). -->
            <button type="button" class="avatar-remove-btn" id="photoRemoveBtn"
                    title="Remove photo" aria-label="Remove profile photo">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
              </svg>
            </button>
          </div>
          <div class="profile-meta">
            <h1 id="profileDisplayName">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h1>
            <div class="role-badge" id="roleBadge"></div>
          </div>
          <button class="btn-outline" id="editProfileBtn">Edit Profile</button>
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
          <section class="profile-section">
            <h3>Personal Info</h3>
            <div class="personal-info-list">
              <div class="personal-info-row"><span class="info-label">Program</span><span id="displayProgram">—</span></div>
              <div class="personal-info-row"><span class="info-label">Year Level</span><span id="displayYearLevel">—</span></div>
              <div class="personal-info-row"><span class="info-label">Contact</span><span id="displayContact">—</span></div>
            </div>
            <button class="btn-outline" id="editPersonalInfoBtn" style="margin-top:.75rem;font-size:.85rem;padding:.4rem .9rem;">Edit Info</button>
            <div id="personalEditForm" style="display:none;margin-top:1rem;">
              <div class="course-edit-grid" style="margin-bottom:.75rem;">
                <div class="input-group">
                  <label for="programSelect">Program</label>
                  <select id="programSelect" class="select-course">
                    <option value="">— select —</option>
                    @foreach($programs as $p)
                      <option value="{{ $p->program_code }}">{{ $p->program_code }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="input-group">
                  <label for="yearLevelInput">Year Level</label>
                  <input type="number" id="yearLevelInput" min="1" max="10" class="select-course" style="padding:.6rem .75rem;"/>
                </div>
              </div>
              <div class="input-group" style="margin-bottom:.75rem;">
                <label for="contactInput">Contact Number</label>
                <input type="text" id="contactInput" placeholder="e.g. 09171234567" style="width:100%;padding:.7rem .9rem;border-radius:var(--radius-sm);border:1px solid #e0d8c8;font-family:inherit;font-size:.9rem;"/>
              </div>
              <div style="display:flex;gap:.75rem;">
                <button class="btn-outline" id="cancelPersonalEditBtn">Cancel</button>
                <button class="btn-primary" id="savePersonalInfoBtn">Save</button>
              </div>
            </div>
          </section>

          <section class="profile-section" style="margin-top:2rem;">
            <h3>Password</h3>
            <button class="btn-outline" id="pwChangeTrigger" style="font-size:.85rem;padding:.4rem .9rem;">Change Password</button>
            <div id="passwordChangeForm" style="display:none;margin-top:1rem;">
              <div class="input-group" style="margin-bottom:.75rem;">
                <label for="currentPassword">Current Password</label>
                <input type="password" id="currentPassword" style="width:100%;padding:.7rem .9rem;border-radius:var(--radius-sm);border:1px solid #e0d8c8;font-family:inherit;font-size:.9rem;"/>
              </div>
              <div class="input-group" style="margin-bottom:.75rem;">
                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" style="width:100%;padding:.7rem .9rem;border-radius:var(--radius-sm);border:1px solid #e0d8c8;font-family:inherit;font-size:.9rem;"/>
              </div>
              <div class="input-group" style="margin-bottom:.75rem;">
                <label for="confirmPassword">Confirm New Password</label>
                <input type="password" id="confirmPassword" style="width:100%;padding:.7rem .9rem;border-radius:var(--radius-sm);border:1px solid #e0d8c8;font-family:inherit;font-size:.9rem;"/>
              </div>
              <div style="display:flex;gap:.75rem;">
                <button class="btn-outline" id="cancelPasswordBtn">Cancel</button>
                <button class="btn-primary" id="updatePasswordBtn">Update Password</button>
              </div>
            </div>
          </section>

          <div class="danger-zone">
            <h4>Danger Zone</h4>
            <p class="danger-text">This action is permanent and cannot be undone.</p>
            <button class="btn-danger-outline" id="deleteAccountBtn">Delete Account</button>
          </div>
        </div>

        <!-- Edit mode -->
        <form id="profileEditMode" style="display:none;">
          <div class="edit-layout">
            <div class="input-group">
              <label for="bioInput">Bio</label>
              <div class="bio-textarea-wrapper">
                <textarea id="bioInput" maxlength="250"></textarea>
                <div class="char-counter"><span id="charCount">0</span>/250</div>
              </div>
            </div>
            <div class="course-edit-grid">
              <div class="input-group">
                <label for="tutorSelect">Expertise (Tutor)</label>
                <select class="select-course" id="tutorSelect">
                  <option value="" disabled selected>+ Add Course</option>
                  @foreach($courses as $course)
                    <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_name }}</option>
                  @endforeach
                </select>
                <div id="tutorTags" class="edit-tags-container"></div>
              </div>
              <div class="input-group">
                <label for="tuteeSelect">Learning Goals (Tutee)</label>
                <select class="select-course" id="tuteeSelect">
                  <option value="" disabled selected>+ Add Course</option>
                  @foreach($courses as $course)
                    <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_name }}</option>
                  @endforeach
                </select>
                <div id="tuteeTags" class="edit-tags-container"></div>
              </div>
            </div>
            <div class="form-actions-spacer">
              <button type="button" class="btn-outline" id="cancelProfileEditBtn">Cancel</button>
              <button type="submit" class="btn-primary">Save Changes</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </main>

  {{-- All modals + onboarding extracted to partials/dashboard/modals/ for
       readability. They depend on shared state set by dashboard.js (the
       window.__authUser/__courses globals at the top of this file) and
       function names exposed via Object.assign(window, {...}) at the bottom
       of dashboard.js. --}}
  @include('partials.dashboard.modals.session')
  @include('partials.dashboard.modals.delete-account')
  @include('partials.dashboard.modals.tutor-requests')
  @include('partials.dashboard.modals.counter-proposal')
  @include('partials.dashboard.modals.group-session')
  @include('partials.dashboard.modals.review')
  @include('partials.dashboard.modals.onboarding')
  @include('partials.dashboard.modals.accept-session')
  @include('partials.dashboard.modals.tutor-profile')
  @include('partials.dashboard.modals.confirm')
  @include('partials.dashboard.modals.complete-session')

  <!-- Toast -->
  <div id="toast" class="toast"></div>
  {{-- Dashboard JS is bundled by Vite — see @vite() in <head>. The old
       <script defer src="/app.js?v=…"> manual include used to live here. --}}
</body>
</html>
