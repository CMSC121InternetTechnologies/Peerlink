{{-- Tutor marks a session as complete. The optional summary is stored on
     Sessions.summary and shown in the request history. Students get a
     follow-up notification prompting them to leave a review. --}}
<div class="modal-overlay" id="completeSessionOverlay">
  <div class="modal" id="completeSessionModal">
    <button class="modal-close" id="closeCompleteBtn">✕</button>
    <div class="complete-emoji">✅</div>
    <h2 class="mb-xs">Mark Session as Complete</h2>
    <p class="modal-subtle complete-subtitle">
      Confirm that this session took place. Students will be prompted to leave a review.
    </p>
    <input type="hidden" id="completeSessionId"/>
    <div class="modal-form">
      <label for="completeSummary">Post-Session Summary <span class="label-optional">(optional)</span></label>
      <textarea id="completeSummary" class="textarea-tall"
                placeholder="Brief notes on topics covered, progress made, or next steps…"></textarea>
      <button class="btn-primary full-width mt-md btn-success" id="confirmCompleteBtn">
        Confirm &amp; Complete
      </button>
    </div>
  </div>
</div>
