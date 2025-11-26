/**
 * admin_profile.js
 * Final corrected and consolidated JavaScript file.
 */

// --- Copy Admin Details ---
function copyVolunteerData(button) {
  const volunteerDetails = document.querySelector('.volunteer-details');
  if (!volunteerDetails) return;
  let textToCopy = '';
  let counter = 1;
  volunteerDetails.querySelectorAll('.detail-card').forEach(card => {
    const title = card.querySelector('h6')?.innerText || '';
    const value = card.querySelector('p')?.innerText || '';
    if (title && value) {
      textToCopy += `${counter}. ${title}: ${value}\n`;
      counter++;
    }
  });
  navigator.clipboard.writeText(textToCopy.trim()).then(() => {
    const original = button.innerHTML;
    button.innerHTML = 'Copied <i class="fas fa-check"></i>';
    setTimeout(() => button.innerHTML = original, 2000);
  });
}

// --- Print Profile ---
function printLeftColumn() {
  const leftColumn = document.querySelector('.left-col');
  if (!leftColumn) return;
  const clone = leftColumn.cloneNode(true);
  
  // Remove all interactive elements before printing
  clone.querySelectorAll('button, .info-card a, .copy-volunteer-btn, .profile-upload-overlay').forEach(el => el.remove()); 
  
  const printWindow = window.open('', '', 'width=900,height=700');
  printWindow.document.write('<html><head><title>Admin Profile</title>');
  
  // Include styles
  document.querySelectorAll('link[rel="stylesheet"], style').forEach(node => {
    printWindow.document.write(node.outerHTML);
  });
  
  // Add print-specific styles
  printWindow.document.write(`
    <style>
        .info-card:hover, .copy-volunteer-btn {
            transform: none !important;
            box-shadow: none !important;
        }
        .profile-photo:hover {
            box-shadow: none !important;
            transform: none !important;
        }
    </style>
  `);
  
  printWindow.document.write('</head><body>');
  printWindow.document.write(clone.outerHTML);
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
  printWindow.close();
}

// --- Edit Button Functionality (Combined Logic) ---
document.addEventListener('DOMContentLoaded', function() {
  const editBtn = document.querySelector('.info-card[title="Edit"]');
  const detailCards = document.querySelectorAll('.volunteer-details .detail-card');
  const profileSection = document.getElementById('profileSection');
  const profileImage = document.getElementById('profileImage');
  const fileInput = document.getElementById('profileUpload');

  // Fields that should NOT be editable
  const protectedFields = [
    'Admin ID', 
    'Role', 
    'Account Created At'
  ];

  if (editBtn) {
    // 1. EDIT BUTTON CLICK HANDLER
    editBtn.addEventListener('click', function() {
      const isEditing = this.classList.toggle('editing');
      
      // TOGGLE EDIT MODE CLASS for Profile Section (controls photo upload overlay visibility)
      if (profileSection) {
          profileSection.classList.toggle('edit-mode', isEditing);
      }

      if (isEditing) {
        // Enter edit mode
        this.innerHTML = '<i class="fas fa-save"></i> Save';
        detailCards.forEach(card => {
          const titleElement = card.querySelector('h6');
          const fieldName = titleElement ? titleElement.innerText.trim() : '';

          // Check if the current field is protected
          if (protectedFields.includes(fieldName)) {
            // Skip this card, leave it as static <p> element
            return; 
          }

          // Proceed with editable fields
          const isPasswordCard = fieldName.includes('Password');
          
          const valueElement = card.querySelector('p');
          const currentValue = valueElement.innerText;
          const input = document.createElement('input');
          
          input.type = 'text'; // Start as text for all editable fields

          // Handle Password field specifically
          if (isPasswordCard) {
              // Set to empty string for security on reveal
              input.value = ''; 
              input.setAttribute('data-password-field', 'true');
              input.placeholder = 'Enter new password...';
          } else {
              // For all other editable fields
              input.value = currentValue;
          }
          
          input.classList.add('form-control', 'form-control-sm', 'mt-1');
          valueElement.replaceWith(input);
        });
      } else {
        // Save changes
        this.innerHTML = '<i class="fas fa-edit"></i> Edit';
        detailCards.forEach(card => {
          // Check if an input field exists (meaning it was an editable field)
          const input = card.querySelector('input');
          if (input) {
            const newValue = input.value.trim();
            const newP = document.createElement('p');
            
            const isPasswordInput = input.hasAttribute('data-password-field');
            
            if (isPasswordInput) {
                // Password logic: mask on save
                if (newValue.length > 0) {
                    newP.innerText = '********'; 
                } else {
                    // If empty, retrieve and display the old masked value ('********')
                    newP.innerText = '********'; 
                }
            } else {
                // For all other editable fields
                newP.innerText = newValue || '—';
            }
            
            input.replaceWith(newP);
          }
          // If the card did not contain an <input>, it was a protected field 
          // and its original <p> tag is left untouched.
        });
        alert('Admin profile details updated successfully!');
      }
    });
  }
  
  // 2. PHOTO UPLOAD EVENT LISTENERS (Single listener for the image to prevent duplication)
  if (profileImage && fileInput && profileSection) {
    // Listener 1: Clicking the image triggers file input
    profileImage.addEventListener('click', () => {
        if (profileSection.classList.contains('edit-mode')) {
            fileInput.click();
        }
    });

    // Listener 2: File change handler (shows preview)
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
            alert(`Photo selected: ${file.name}. Will be saved when you click "Save".`);
        }
    });
  }
});