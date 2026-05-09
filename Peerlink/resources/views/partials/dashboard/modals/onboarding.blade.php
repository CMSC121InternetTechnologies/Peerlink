{{-- 4-step onboarding modal shown the first time a user logs in (US_06 + US_07).
     Steps 2 and 3 are mock UI for explanation; step 4 is the real save where
     the user picks their tutor expertise + tutee learning goals. --}}
<div class="modal-overlay open ob-overlay" id="onboardingOverlay" style="display:none;">
  <div class="modal modal-lg ob-modal" onclick="event.stopPropagation()">

    {{-- Step 1: welcome --}}
    <div class="ob-step" id="obStep1">
      <div class="ob-emoji">👋</div>
      <h2 class="ob-title">Welcome to PeerLink!</h2>
      <p class="ob-body">
        PeerLink connects students who need help with peers who can tutor them.
        Let's take a quick tour of the core flow before you dive in.
      </p>
      <button class="btn-primary full-width" onclick="obNext()">Start Tutorial →</button>
    </div>

    {{-- Step 2: find a tutor (mock) --}}
    <div class="ob-step" id="obStep2" style="display:none;">
      <h2 class="ob-step-title">Step 1 — Find a Tutor</h2>
      <p class="ob-step-body">
        In <strong>Explore Tutors</strong>, you can filter by course code or search by name.
        Each card shows the tutor's courses and rating.
      </p>
      <div class="tutors-grid ob-mock">
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
          <button class="btn-primary full-width btn-purple">Request session</button>
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
          <button class="btn-primary full-width btn-purple">Request session</button>
        </div>
      </div>
      <div class="ob-actions">
        <button class="btn-outline" onclick="obPrev()">← Back</button>
        <button class="btn-primary ob-action-grow" onclick="obNext()">Next →</button>
      </div>
    </div>

    {{-- Step 3: request a session (mock) --}}
    <div class="ob-step" id="obStep3" style="display:none;">
      <h2 class="ob-step-title">Step 2 — Request a Session</h2>
      <p class="ob-step-body">
        Click <strong>Request session</strong> on any tutor card. Fill in the course,
        topic, preferred schedule, and an optional message. The tutor can accept,
        decline, or propose a different time.
      </p>
      <div class="ob-sample-form">
        <div class="ob-sample-form-title">📋 Sample Request Form</div>
        <div class="ob-sample-form-list">
          <div><span class="muted">Course:</span> <span class="course-badge">CMSC121</span></div>
          <div><span class="muted">Topic:</span> Laravel Routing &amp; Controllers</div>
          <div><span class="muted">Schedule:</span> May 10, 2026 · 2:00 PM</div>
          <div><span class="muted">Message:</span> Hi! I'm struggling with middleware. Can we go through it?</div>
        </div>
      </div>
      <div class="ob-actions">
        <button class="btn-outline" onclick="obPrev()">← Back</button>
        <button class="btn-primary ob-action-grow" onclick="obNext()">Next →</button>
      </div>
    </div>

    {{-- Step 4: actually save courses (US_07) --}}
    <div class="ob-step" id="obStep4" style="display:none;">
      <h2 class="ob-step-title">Step 3 — Set Up Your Courses</h2>
      <p class="ob-step-body">
        Tell us which courses you can <strong>tutor</strong> and which you <strong>need help with</strong>.
      </p>
      <div class="course-edit-grid mb-md">
        <div class="input-group">
          <label for="obCourseSelect" class="ob-course-label tutor-color">Courses I Can Tutor</label>
          <select id="obCourseSelect" class="select-course" onchange="obAddCourse()">
            <option value="" disabled selected>+ Add a course</option>
          </select>
          <div id="obCourseTags" class="edit-tags-container ob-tags"></div>
        </div>
        <div class="input-group">
          <label for="obTuteeCourseSelect" class="ob-course-label tutee-color">Courses I Need Help With</label>
          <select id="obTuteeCourseSelect" class="select-course" onchange="obAddTuteeCourse()">
            <option value="" disabled selected>+ Add a course</option>
          </select>
          <div id="obTuteeTags" class="edit-tags-container ob-tags"></div>
        </div>
      </div>
      <p class="ob-hint">ℹ️ At least one tutor course is required to appear in Explore Tutors.</p>
      <div class="ob-actions">
        <button class="btn-outline" onclick="obPrev()">← Back</button>
        <button class="btn-primary ob-action-grow" id="obFinishBtn" onclick="obFinish()">Finish &amp; Go to Dashboard</button>
      </div>
    </div>

  </div>
</div>
