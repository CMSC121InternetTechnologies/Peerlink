{{-- Delete Account confirmation. Posts to /profile via the standard Laravel
     ProfileController so server-side password validation happens through
     the framework's normal request lifecycle. --}}
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <button class="modal-close" onclick="closeDeleteModal()">✕</button>
    <h2 class="modal-title-danger">Delete Account</h2>
    <p class="modal-subtle mb-md">
      Confirm with your password to permanently delete your account.
    </p>
    <input type="password" id="deleteConfirmPassword" class="modal-input modal-input--password"
           placeholder="Password"/>
    <p id="deleteError" class="modal-error" style="display:none;"></p>
    {{-- Hidden form does the real DELETE /profile submit. --}}
    <form id="deleteAccountForm" method="POST" action="/profile">
      @csrf
      @method('DELETE')
      <input type="hidden" name="password" id="deletePasswordHidden"/>
      <button type="submit" class="btn-primary full-width btn-danger"
              onclick="return prepareDeleteSubmit()">
        Delete Permanently
      </button>
    </form>
  </div>
</div>
