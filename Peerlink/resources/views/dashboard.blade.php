<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PeerLink</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('style.css') }}"/>
</head>
<body class="mode-tutee">

  <!-- Navigation Bar -->
  <nav class="navbar">
      <!-- Logo -->
      <a class="logo" href="#">
        <div class="logo-icon"></div>
        <span class="logo-text">PEER<strong>LINK</strong></span>
      </a>

      <!-- Navigation Links -->
      <div class="nav-links">
        <a href="#" class="nav-link" id="navDashboard" onclick="switchView('dashboard')">Explore Tutors</a>
        <a href="#" class="nav-link active" id="navProfile" onclick="switchView('profile')">Profile</a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <a href="{{ route('logout') }}" class="nav-link" onclick="event.preventDefault(); this.closest('form').submit();">Log out</a>
        </form>
      </div>

      <!-- Mode Toggle -->
      <div class="mode-toggle" id="modeToggle">
          <!-- Tutor Mode -->
          <button class="toggle-btn" id="tutorBtn" onclick="setMode('tutor')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-icon lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M16 3.128a4 4 0 0 1 0 7.744"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/></svg>
            Tutor
          </button>
          <!-- Tutee Mode -->
          <button class="toggle-btn" id="tuteeBtn" onclick="setMode('tutee')">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pen-line-icon lucide-pen-line"><path d="M13 21h8"/><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
            Tutee
          </button>
      </div>
  </nav>

  <!-- Main Content Area -->
  <main class="page">
    
    <!-- DASHBOARD VIEW -->
    <div id="dashboardView" style="display: none;">
      
      <!-- Tutee View (Explore Tutors) -->
      <div class="tutee-only">
        <div class="page-header">
          <h1 class="page-title">Explore Tutors</h1>
          <div class="search-bar-wrapper">
            
            <!-- Search Input -->
            <div class="search-input-wrap">
              <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input type="text" id="searchName" placeholder="Search name" oninput="filterTutors()"/>
            </div>

            <!-- Active Filters -->
            <div class="filter-tags" id="filterTags"></div>

            <!-- Course Adder Dropdown -->
            <div class="course-adder">
              <button class="course-add-btn" id="courseAddBtn" onclick="toggleCourseDropdown()">
                Course code
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
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

        <!-- Tutor Grid -->
        <div class="tutors-grid" id="tutorsGrid"></div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display:none">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <p>No tutors found for your filters.</p>
        </div>
      </div>

      <!-- Tutor View (Dashboard) -->
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
              <div class="dash-card-value">3</div>
            </div>
            <div class="dash-card">
              <div class="dash-card-icon">⭐</div>
              <div class="dash-card-label">Your Rating</div>
              <div class="dash-card-value">4.9</div>
            </div>
            <div class="dash-card">
              <div class="dash-card-icon">📘</div>
              <div class="dash-card-label">Courses Offered</div>
              <div class="dash-card-value">2</div>
            </div>
          </div>
          <div class="tutor-cta">
            <button class="btn-primary" onclick="openRequestsModal()">View Session Requests</button>
            <button class="btn-outline" onclick="switchView('profile')">Edit My Profile</button>
          </div>
        </div>
      </div>
    </div>

    <!-- PROFILE VIEW -->
    <div id="profileView" class="profile-container">
      <div class="profile-card">
      
        <!-- Profile Header -->
        <div class="profile-header-main">
            <div class="tutor-avatar larger">{{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}</div>
            <div class="profile-meta">
                <h1 id="profileDisplayName">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</h1>
                <div class="role-badge" id="roleBadge"></div>
            </div>
            <button class="btn-outline" onclick="toggleEditMode(true)">Edit Profile</button>
        </div>

        <!-- Profile Details -->
        <div id="profileViewMode">
            <!-- Bio Section -->
            <section class="profile-section bio-box">
                <h3>Bio</h3>
                <p id="bioDisplay" class="bio-text">No bio added yet.</p>
            </section>

            <!-- Courses Section -->
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
              
            <!-- Danger Zone -->
            <div class="danger-zone">
                <h4>Danger Zone</h4>
                <p class="danger-text">This action is permanent and cannot be undone.</p>
                <button class="btn-danger-outline" onclick="openDeleteModal()">Delete Account</button>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <form id="profileEditMode" style="display: none;" onsubmit="saveProfile(event)">
            <div class="edit-layout">
            
            <!-- Bio Input -->
            <div class="input-group">
                <label>Bio</label>
                <div class="bio-textarea-wrapper">
                    <textarea id="bioInput" maxlength="250"></textarea>
                    <div class="char-counter"><span id="charCount">0</span>/250</div>
                </div>
            </div>
                
            <!-- Courses Input -->
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

            <!-- Form Actions -->
            <div class="form-actions-spacer">
                <button type="button" class="btn-outline" onclick="toggleEditMode(false)">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
            </div>       
        </form>
      </div>
    </div>

  </main>

  <!-- Session Request Modal -->
  <div class="modal-overlay" id="sessionModalOverlay" onclick="closeSessionModal()">
    <div class="modal" onclick="event.stopPropagation()">
      <button class="modal-close" onclick="closeSessionModal()">✕</button>
      <div class="modal-avatar" id="modalAvatar"></div>
      <h2 class="modal-name" id="modalName"></h2>
      <p class="modal-sub" id="modalSub"></p>
      <div class="modal-courses" id="modalCourses"></div>
      <div class="modal-form">
        <label>Subject / Topic</label>
        <input type="text" placeholder="e.g. Pointers in C, Recursion..." id="sessionTopic"/>
        <label>Preferred Schedule</label>
        <input type="datetime-local" id="sessionDate"/>
        <label>Message (optional)</label>
        <textarea placeholder="Any notes for the tutor..." id="sessionMessage"></textarea>
        <button class="btn-primary full-width" onclick="submitRequest()" style="margin-top: 1rem;">Send Request</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal-overlay" id="deleteModal">
      <div class="modal">
          <button class="modal-close" onclick="closeDeleteModal()">✕</button>
          <h2 class="modal-title-danger" style="margin-bottom: 1rem; color: var(--coral);">Delete Account</h2>
          <p class="modal-warning-text" style="margin-bottom: 1rem; color: var(--text-muted);">Confirm with your password to permanently delete your account.</p>
          <input type="password" id="deleteConfirmPassword" class="modal-input" placeholder="Password" style="width: 100%; padding: 0.8rem; border-radius: var(--radius-sm); border: 1px solid #e0d8c8; margin-bottom: 1.5rem;" />
          <button class="btn-primary full-width" style="background: var(--coral);" onclick="closeDeleteModal()">Delete Permanently</button>
      </div>
  </div>

    <!-- Pending Requests Modal (Tutor View) -->
    <div class="modal-overlay" id="requestsModalOverlay" onclick="closeRequestsModal()">
    <div class="modal modal-lg" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeRequestsModal()">✕</button>
        <h2 class="page-title" style="margin-bottom: 1rem; font-size: 1.5rem;">Session Requests</h2>
        
        <div id="requestsList" class="requests-list">
        <!-- Request Cards injected via JS -->
        </div>
    </div>
    </div>

  <!-- Toast Notification -->
  <div id="toast" class="toast"></div>

  <!-- JavaScript -->
  <script src="{{ asset('app.js') }}"></script>
</body>
</html>