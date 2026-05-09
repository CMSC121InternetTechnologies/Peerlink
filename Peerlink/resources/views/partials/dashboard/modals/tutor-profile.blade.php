{{-- Phase 3.1: Tutor profile detail modal opened from a tutor card.
     Body is rendered client-side from /api/tutors/{id} JSON. --}}
<div class="modal-overlay" id="tutorProfileOverlay" onclick="closeTutorProfile()">
  <div class="modal modal-lg modal-tutor-profile" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeTutorProfile()">✕</button>
    <div id="tutorProfileContent">
      <div class="modal-loading">Loading…</div>
    </div>
  </div>
</div>
