{{-- Session Request modal — opened when a tutee clicks "Request session"
     on a tutor card, or when they click "Broadcast a Request". The same
     modal serves both flows; openBroadcastModal() in dashboard.js adjusts
     the avatar/title/course list when no tutor is preselected. --}}
<div class="modal-overlay" id="sessionModalOverlay" onclick="closeSessionModal()">
  <div class="modal modal-lg" id="sessionModalContainer" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeSessionModal()">✕</button>
    <div class="modal-avatar" id="modalAvatar"></div>
    <h2 class="modal-name" id="modalName"></h2>
    <p class="modal-sub" id="modalSub"></p>

    <div class="modal-form">
      <label for="sessionCourse">Course</label>
      <select id="sessionCourse" class="select-course mb-sm" onchange="loadSessionTopics()">
        <option value="" disabled selected>Select a course</option>
      </select>
      <div id="sessionTopicsWrap" class="topic-picker" style="display:none;">
        <div class="topic-picker-header">
          <span class="topic-picker-label">Topics</span>
          <span class="topic-picker-hint">Optional · pick anything you want help with</span>
        </div>
        <div id="sessionTopicsList" class="topic-picker-grid"></div>
      </div>
      <label for="sessionTopic">Additional Notes</label>
      <input type="text" placeholder="e.g. Pointers in C, Recursion…" id="sessionTopic"/>
      <label for="sessionDate">Preferred Schedule</label>
      <input type="datetime-local" id="sessionDate"/>
      <label for="sessionMessage">Message (optional)</label>
      <textarea placeholder="Any notes for the tutor…" id="sessionMessage"></textarea>
      <button class="btn-primary full-width mt-md" onclick="submitRequest()">Send Request</button>
    </div>
  </div>
</div>
