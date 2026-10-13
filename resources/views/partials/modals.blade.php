{{-- Generic confirmation modal used by forms with data-confirm="..." --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h5" id="confirmModalTitle">Please confirm</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p class="confirm-message mb-0"></p></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger confirm-ok">Yes, continue</button>
            </div>
        </div>
    </div>
</div>

{{-- Reason modal: buttons with data-bs-target="#reasonModal" data-action="URL" --}}
<div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5" id="reasonModalTitle">Provide a reason</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="reasonText" class="form-label">Reason <span class="text-danger">*</span></label>
                <textarea name="reason" id="reasonText" rows="4" class="form-control" required minlength="5" maxlength="1000"></textarea>
                <div class="form-text reason-help">The user will see this reason.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
