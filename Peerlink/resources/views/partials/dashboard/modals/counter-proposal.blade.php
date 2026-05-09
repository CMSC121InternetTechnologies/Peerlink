{{-- US_11: tutor offers a different schedule for an incoming request.
     The student then sees this in their My Requests view and can accept
     or decline it (PATCH /api/requests/{id}/student-accept or /student-decline). --}}
<div class="modal-overlay" id="counterModalOverlay" onclick="closeCounterModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeCounterModal()">✕</button>
    <h2 class="mb-md">Propose New Schedule</h2>
    <input type="hidden" id="counterRequestId"/>
    <div class="modal-form">
      <label for="counterTime">Proposed Date &amp; Time</label>
      <input type="datetime-local" id="counterTime"/>
      <label for="counterModality">Modality</label>
      <select id="counterModality" class="select-course mb-sm">
        <option value="In-Person">In-Person</option>
        <option value="Online">Online</option>
      </select>
      <label for="counterMessage">Message to Student (optional)</label>
      <textarea id="counterMessage" placeholder="Explain the change…"></textarea>
      <button class="btn-primary full-width mt-md" onclick="submitCounterProposal()">Send Proposal</button>
    </div>
  </div>
</div>
