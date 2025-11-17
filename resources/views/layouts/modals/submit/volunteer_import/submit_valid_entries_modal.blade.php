<!-- Submit Verified Entries Modal -->
<div id="modalSubmit" class="custom-modal-overlay">
    <div class="custom-modal confirm-modal">
        <h3>
            <i class="fa-solid fa-database"></i> Submit to Database
        </h3>
        <p id="modalSubmitCount">Are you sure you want to submit verified entries to the database?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
                <i class="fa-solid fa-check"></i> Yes, Submit
            </button>
            <button type="button" class="btn btn-secondary" id="cancelSubmitBtn">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="custom-modal-overlay">
    <div class="custom-modal error-modal" style="border-top: 5px solid #d9534f;">
        <h3><i class="fa-solid fa-triangle-exclamation" style="color:#d9534f;"></i> Error</h3>
        <p id="errorModalMessage">Something went wrong.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="closeErrorModal">
                <i class="fa-solid fa-xmark"></i> Close
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalSubmit');
    const openModalBtn = document.getElementById('openSubmitModalBtn');
    const confirmBtn = document.getElementById('confirmSubmitBtn');
    const cancelBtn = document.getElementById('cancelSubmitBtn');
    const validForm = document.querySelector('#import-Section-valid form');

    const errorModal = document.getElementById('errorModal');
    const errorMessageBox = document.getElementById('errorModalMessage');
    const closeErrorBtn = document.getElementById('closeErrorModal');

    if (!openModalBtn || !validForm) return;

    // Utility: get all valid table checkboxes
    const getTableCheckboxes = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]');
    const getCheckedTableCheckboxes = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]:checked');

    // Backend duplicate check
    const checkDuplicatesBackend = async (ids) => {
        try {
            const response = await fetch("/check-duplicates", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ ids })
            });
            return await response.json(); // expects { duplicates: [...] }
        } catch (err) {
            console.error('Error checking duplicates:', err);
            return { duplicates: [] };
        }
    };

    const handleOpenModal = async () => {
        const checkboxes = getTableCheckboxes();

        if (checkboxes.length === 0) {
            errorMessageBox.textContent = "No verified entries to submit.";
            errorModal.classList.add('active');
            return;
        }

        // Auto-check all if none selected
        const checked = getCheckedTableCheckboxes();
        if (checked.length === 0) checkboxes.forEach(cb => cb.checked = true);

        // Step 1: Check invalid entries
        const invalidCheckboxes = document.querySelectorAll('#invalid-entries-table tbody input[type="checkbox"]');
        if (invalidCheckboxes.length > 0) {
            confirmBtn.disabled = true;
            modal.querySelector('#modalSubmitCount').innerHTML =
                `⚠️ <span style="color:#dc3545; font-weight:600;">${invalidCheckboxes.length} invalid entr${invalidCheckboxes.length > 1 ? 'ies' : 'y'}</span> still exist. Please fix them first.`;
            modal.classList.add('active');
            return;
        }

        // Step 2: Backend duplicate check
        const ids = Array.from(getCheckedTableCheckboxes())
            .map(cb => cb.dataset.idNumber?.trim())
            .filter(id => id);

        if (ids.length === 0) {
            errorMessageBox.textContent = "No valid IDs found for submission.";
            errorModal.classList.add('active');
            return;
        }

        confirmBtn.disabled = true;
        modal.querySelector('#modalSubmitCount').innerHTML = "Checking for duplicates...";

        const result = await checkDuplicatesBackend(ids);
        const duplicates = result.duplicates.map(id => id.toString().trim());

        // Automatically uncheck duplicates
        duplicates.forEach(id => {
            const cb = document.querySelector(`#valid-entries-table tbody input[data-id-number="${id}"]`);
            if (cb) cb.checked = false;
        });

        if (duplicates.length > 0) {
            errorMessageBox.innerHTML =
                `❌ The following ID number${duplicates.length > 1 ? 's' : ''} already exist in the database: <strong>${duplicates.join(', ')}</strong>`;
            errorModal.classList.add('active');
            confirmBtn.disabled = true;
            return;
        }

        // Step 3: All clear - show submit modal
        const nonDuplicateIds = Array.from(getCheckedTableCheckboxes())
            .map(cb => cb.dataset.idNumber?.trim())
            .filter(id => id);

        confirmBtn.disabled = false;
        modal.querySelector('#modalSubmitCount').innerHTML =
            `Are you sure you want to submit <span style="color:#28a745; font-weight:600;">${nonDuplicateIds.length} verified entr${nonDuplicateIds.length > 1 ? 'ies' : 'y'}</span> to the database?`;
        modal.classList.add('active');
    };

    // Event listeners
    openModalBtn.addEventListener('click', handleOpenModal);
    cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
    confirmBtn.addEventListener('click', () => {
        if (confirmBtn.disabled) return;

        const checked = getCheckedTableCheckboxes();
        if (checked.length === 0) {
            errorMessageBox.textContent = "No entries selected to submit.";
            errorModal.classList.add('active');
            return;
        }

        validForm.submit();
        modal.classList.remove('active');
    });

    if (closeErrorBtn) {
        closeErrorBtn.addEventListener('click', () => errorModal.classList.remove('active'));
    }
});
</script>

