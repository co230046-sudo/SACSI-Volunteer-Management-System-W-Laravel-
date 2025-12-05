/**
 * admin_profile.js
 * Final robust version:
 * - dynamic inputs for editable fields
 * - protected fields remain read-only but are submitted as hidden inputs
 * - profile picture preview + upload
 * - updates top display name before submit so full_name saves correctly
 */

document.addEventListener('DOMContentLoaded', function() {

  const editBtn = document.getElementById('editSaveBtn');
  const profileSection = document.getElementById('profileSection');
  if (!profileSection || !editBtn) return;

  const form = profileSection.querySelector('form');
  const detailCards = document.querySelectorAll('.volunteer-details .detail-card');
  const profileImage = document.getElementById('profileImage');
  const fileInput = document.getElementById('profileUpload');

  // Map visible label => form field name
  const fieldMapping = {
    "Admin ID": "admin_id",
    "Username": "username",
    "Email": "email",
    "Password": "password",
    "Full Name": "full_name",
    "Role": "role",
    "Contact #": "contact_number",
    "Created": "created_at" // kept for mapping only
  };

  // Protected fields (not editable in UI) BUT must still be submitted
  const protectedFields = [
    "Admin ID",
    "Username",
    "Role",
    "Created"
  ];

  let editing = false;

  // Utility: get text value inside detail-card (span text if any)
  function getCardValue(card) {
    const span = card.querySelector('span');
    if (span) return span.innerText.trim();
    // fallback: any direct text inside <p>
    const p = card.querySelector('p');
    if (p) return p.innerText.trim();
    return '';
  }

  // Utility: create input element for a card title (titleText must match keys in fieldMapping)
  function createInputForCard(titleText, currentValue) {
    const input = document.createElement('input');
    input.classList.add('form-control', 'form-control-sm', 'mt-1');

    if (titleText === 'Password') {
      input.type = 'password';
      input.placeholder = 'Enter new password...';
      input.value = ''; // don't prefill password
    } else if (titleText === 'Email') {
      input.type = 'email';
      input.value = currentValue || '';
    } else {
      input.type = 'text';
      input.value = currentValue || '';
    }

    // safe name lookup (if mapping missing, leave no name)
    const name = fieldMapping[titleText];
    if (name) input.name = name;

    return input;
  }

  // Called when user clicks Edit (enter edit mode)
  function enterEditMode() {
    editing = true;
    editBtn.innerHTML = '<i class="fas fa-save"></i> Save';
    profileSection.classList.add('edit-mode');

    detailCards.forEach(card => {
      const titleEl = card.querySelector('h6');
      if (!titleEl) return;
      const titleText = titleEl.innerText.trim();

      // If protected, leave as-is (no input)
      if (protectedFields.includes(titleText)) {
        return;
      }

      // Replace current <p> (value) with input
      const p = card.querySelector('p');
      if (!p) return;

      const currentValue = getCardValue(card);
      const input = createInputForCard(titleText, currentValue);
      p.replaceWith(input);
    });

    // Also allow top profile photo click to open file
    if (profileImage && fileInput) {
      profileImage.style.cursor = 'pointer';
    }
  }

  // Remove any old helper hidden inputs (cleanup)
  function removeOldHiddenHelpers() {
    const oldHelpers = form.querySelectorAll('input._hidden_protected, input._hidden_displayName, input._hidden_displayRole');
    oldHelpers.forEach(n => n.remove());
  }

  // Called before submitting to ensure protected values are included in request
  function insertHiddenForProtectedFields() {
    // Cleanup previous hidden inputs first
    removeOldHiddenHelpers();

    // Add protected fields (username, role, admin_id, created)
    detailCards.forEach(card => {
      const titleEl = card.querySelector('h6');
      if (!titleEl) return;
      const titleText = titleEl.innerText.trim();

      if (!protectedFields.includes(titleText)) return;
      const name = fieldMapping[titleText];
      if (!name) return;

      const value = getCardValue(card);

      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = name;
      hidden.value = value ?? '';
      hidden.classList.add('_hidden_protected');
      form.appendChild(hidden);
    });

    // Also add top-level displayName (full_name) and displayRole (role) as hidden inputs
    const headerName = document.getElementById('displayName');
    const headerRole = document.getElementById('displayRole');

    if (headerName) {
      const hiddenName = document.createElement('input');
      hiddenName.type = 'hidden';
      hiddenName.name = 'full_name';
      hiddenName.value = headerName.innerText.trim() || '';
      hiddenName.classList.add('_hidden_displayName');
      form.appendChild(hiddenName);
    }

    if (headerRole) {
      const hiddenRole = document.createElement('input');
      hiddenRole.type = 'hidden';
      hiddenRole.name = 'role';
      hiddenRole.value = headerRole.innerText.trim() || '';
      hiddenRole.classList.add('_hidden_displayRole');
      form.appendChild(hiddenRole);
    }
  }

  // Before submit: if any editable inputs exist we should also update top header values
  function syncHeaderFromEditedFields() {
    // Look for a Full Name input inside detail-cards (the dynamic input)
    detailCards.forEach(card => {
      const titleEl = card.querySelector('h6');
      if (!titleEl) return;
      const titleText = titleEl.innerText.trim();

      if (titleText === 'Full Name') {
        const input = card.querySelector('input[name="full_name"]');
        const span = card.querySelector('span');
        const headerName = document.getElementById('displayName');
        if (input && headerName) {
          headerName.innerText = input.value.trim() || headerName.innerText;
        } else if (span && headerName) {
          headerName.innerText = span.innerText.trim() || headerName.innerText;
        }
      }

      // sync role top display only if role is editable in your layout (you set role protected earlier)
      if (titleText === 'Role') {
        const input = card.querySelector('input[name="role"]');
        const span = card.querySelector('span');
        const headerRole = document.getElementById('displayRole');
        if (input && headerRole) {
          headerRole.innerText = input.value.trim() || headerRole.innerText;
        } else if (span && headerRole) {
          headerRole.innerText = span.innerText.trim() || headerRole.innerText;
        }
      }
    });
  }

  // Called when user clicks Save (exit edit mode, submit)
  function exitEditModeAndSubmit() {
    // Sync header values (so top displayName reflects changes)
    syncHeaderFromEditedFields();

    // Before submit: ensure protected values are added as hidden inputs
    insertHiddenForProtectedFields();

    // Submit the form
    form.submit();
  }

  // Toggle handler
  editBtn.addEventListener('click', function(e) {
    if (!editing) {
      enterEditMode();
      return;
    }
    exitEditModeAndSubmit();
  });

  // Profile image preview behavior (only clickable in edit mode)
  if (profileImage && fileInput) {
    profileImage.addEventListener('click', function() {
      if (!editing) return;
      fileInput.click();
    });

    fileInput.addEventListener('change', function(evt) {
      const file = evt.target.files && evt.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(ev) {
        profileImage.src = ev.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

});
document.addEventListener("DOMContentLoaded", function () {

    const btn = document.getElementById("toggleButton");
    const logSection = document.getElementById("activityLogSection");
    const adminSection = document.getElementById("adminAccountsSection");

    if (btn) {
        btn.addEventListener("click", function () {

            if (adminSection.style.display === "none" || adminSection.style.display === "") {
                
                // Show admin accounts
                adminSection.style.display = "block";
                logSection.style.display = "none";

                // Change button style + text
                btn.innerHTML = `<i class="fas fa-list"></i> View Activity Log`;
                btn.classList.remove("btn-danger");
                btn.classList.add("btn-primary");

            } else {

                // Show activity logs
                adminSection.style.display = "none";
                logSection.style.display = "block";

                // Change button back
                btn.innerHTML = `<i class="fas fa-id-card"></i> View Admin Profiles`;
                btn.classList.remove("btn-primary");
                btn.classList.add("btn-danger");
            }
        });
    }

});
function loadAdminLogs(adminId) {
    fetch(`/admin/profile/logs/${adminId}`)
        .then(res => res.json())
        .then(data => {
            const logContainer = document.getElementById('logContainer');
            if (!logContainer) return;

            logContainer.innerHTML = '';

            if (!data.logs || data.logs.length === 0) {
                logContainer.innerHTML = `<p class="text-muted">No logs available.</p>`;
                return;
            }

            data.logs.forEach(log => {
                const item = document.createElement('div');
                item.classList.add('log-item');

                item.innerHTML = `
                    <div><strong>${log.created_at}</strong></div>
                    <div>${log.description || log.action || 'Log entry'}</div>
                `;

                logContainer.appendChild(item);
            });
        })
        .catch(err => console.error('Error loading logs:', err));
}
