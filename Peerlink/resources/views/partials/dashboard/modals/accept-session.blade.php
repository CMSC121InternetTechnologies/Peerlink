{{-- Phase 1.1: tutor accepts a direct request OR claims a broadcast.
     The same modal handles both — the JS sets _acceptIsClaim and the
     submit handler sends the right URL (.../accept vs .../claim). --}}
<div class="modal-overlay" id="acceptModalOverlay" onclick="closeAcceptModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeAcceptModal()">✕</button>
    <h2 class="mb-xs">Accept Session</h2>
    <p id="acceptStudentName" class="modal-subtle modal-subtle--small mb-md"></p>
    <input type="hidden" id="acceptRequestId"/>
    <div class="modal-form">
      <label for="acceptTime">Scheduled Date &amp; Time</label>
      <input type="datetime-local" id="acceptTime"/>
      <label for="acceptModality">Modality</label>
      <select id="acceptModality" class="select-course mb-sm" onchange="toggleAcceptLink()">
        <option value="In-Person">In-Person</option>
        <option value="Online">Online</option>
      </select>
      <div id="acceptRoomWrap">
        <label for="acceptRoom">Room</label>
        <select id="acceptRoom" class="select-course mb-sm">
          <option value="">— auto-assign —</option>
        </select>
      </div>
      <div id="acceptLinkWrap" style="display:none;">
        <label for="acceptLink">Meeting Link</label>
        <input type="url" id="acceptLink" placeholder="https://meet.google.com/…"/>
      </div>
      <button class="btn-primary full-width mt-md" onclick="submitAccept()">Confirm &amp; Accept</button>
    </div>
  </div>
</div>
