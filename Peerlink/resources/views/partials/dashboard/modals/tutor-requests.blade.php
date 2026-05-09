{{-- Tutor's "Session Requests" modal. Two tabs:
       - Direct Requests   → requests sent specifically to this tutor
       - Broadcasts        → unassigned requests any tutor can claim
     The lists themselves are populated by dashboard.js. --}}
<div class="modal-overlay" id="requestsModalOverlay" onclick="closeRequestsModal()">
  <div class="modal modal-lg" onclick="event.stopPropagation()">
    <button class="modal-close" onclick="closeRequestsModal()">✕</button>
    <h2 class="modal-section-title">Session Requests</h2>
    <div class="req-tabs">
      <button class="req-tab active" id="tabDirect"    onclick="switchReqTab('direct')">Direct Requests</button>
      <button class="req-tab"        id="tabBroadcast" onclick="switchReqTab('broadcast')">Broadcasts</button>
    </div>
    <div id="requestsList"  class="requests-list"></div>
    <div id="broadcastList" class="requests-list" style="display:none;"></div>
  </div>
</div>
