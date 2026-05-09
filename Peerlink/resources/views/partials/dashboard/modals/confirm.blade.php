{{-- Generic yes/no confirmation modal used by every "are you sure?" flow.
     showConfirmModal() in dashboard.js sets the icon, title, message,
     button labels, and callback dynamically. --}}
<div class="modal-overlay" id="confirmModalOverlay" onclick="closeConfirmModal()">
  <div class="confirm-modal" onclick="event.stopPropagation()">
    <div class="confirm-modal-icon" id="confirmIcon"></div>
    <h2 class="confirm-modal-title" id="confirmTitle"></h2>
    <p class="confirm-modal-message" id="confirmMessage"></p>
    <div class="confirm-modal-actions">
      <button class="btn-outline confirm-btn-cancel" id="confirmCancelBtn" onclick="closeConfirmModal()">Cancel</button>
      <button class="confirm-btn-ok" id="confirmOkBtn" onclick="_executeConfirm()">Confirm</button>
    </div>
  </div>
</div>
