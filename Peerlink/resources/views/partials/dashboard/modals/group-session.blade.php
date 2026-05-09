{{-- US_13: tutor posts a group/open session that students can browse and
     join from the My Sessions tab's "Open group sessions" list. --}}
<div class="modal-overlay" id="groupSessionModalOverlay" onclick="closeGroupSessionModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeGroupSessionModal()">✕</button>
    <h2 class="mb-md">Post Group Study Session</h2>
    <div class="modal-form">
      <label for="groupCourse">Course</label>
      <select id="groupCourse" class="select-course mb-sm">
        <option value="" disabled selected>Select a course</option>
        @foreach($courses as $course)
          <option value="{{ $course->course_code }}">{{ $course->course_code }} — {{ $course->course_name }}</option>
        @endforeach
      </select>
      <label for="groupTime">Date &amp; Time</label>
      <input type="datetime-local" id="groupTime"/>
      <label for="groupModality">Modality</label>
      <select id="groupModality" class="select-course mb-sm" onchange="toggleGroupLink()">
        <option value="In-Person">In-Person</option>
        <option value="Online">Online</option>
      </select>
      <div id="groupRoomWrap">
        <label for="groupRoom">Room</label>
        <select id="groupRoom" class="select-course mb-sm">
          <option value="">— auto-assign —</option>
        </select>
      </div>
      <div id="groupLinkWrap" style="display:none;">
        <label for="groupLink">Meeting Link</label>
        <input type="url" id="groupLink" placeholder="https://meet.google.com/…"/>
      </div>
      <label for="groupMessage">Message (optional)</label>
      <textarea id="groupMessage" placeholder="What topics will you cover?"></textarea>
      <button class="btn-primary full-width mt-md" onclick="submitGroupSession()">Post Session</button>
    </div>
  </div>
</div>
