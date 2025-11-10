<!-- Styles (updated for dropdown + disabled district) -->
<style>
/* Modal Base */
.edit-volunteer-modal {
    position: fixed;
    inset: 0;
    display: none;
    z-index: 9999;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.edit-volunteer-modal.is-open {
    display: flex;
    justify-content: center;
    align-items: center;
}
.edit-volunteer-modal .modal-overlay {
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.55);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Modal Content */
.edit-volunteer-modal .modal-content {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 850px;
    max-height: 90vh;
    padding: 2rem;
    box-shadow: 0 12px 40px rgba(0,0,0,0.25);
    overflow-y: auto;
    animation: slideIn 0.3s ease forwards;
}
/* Autofill default (no class) */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px #fff inset !important; /* default white */
    -webkit-text-fill-color: #000 !important;
    transition: background-color 5000s ease-in-out 0s;
}

/* Autofill when valid */
input.valid:-webkit-autofill,
input.valid:-webkit-autofill:hover,
input.valid:-webkit-autofill:focus,
input.valid:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px #e6f9ea inset !important;
    -webkit-text-fill-color: #000 !important;
}

/* Autofill when invalid */
input.invalid:-webkit-autofill,
input.invalid:-webkit-autofill:hover,
input.invalid:-webkit-autofill:focus,
input.invalid:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px #ffe6e6 inset !important;
    -webkit-text-fill-color: #000 !important;
}


/* Small screens */
@media(max-height:600px){ .edit-volunteer-modal .modal-content { max-height: 95vh; padding: 1.5rem; } }
@media(max-width:500px){ .input-grid { grid-template-columns: 1fr; } }

/* Header */
.modal-header {
    display:flex;
    align-items:center;
    gap:0.5rem;
    margin-bottom:1.5rem;
}
.modal-header h2 { font-size:1.6rem; color:#B2000C; margin:0; }
.modal-icon { font-size:2rem; color:#B2000C; transition: transform 0.25s ease, color 0.25s ease; }
.modal-icon:hover { transform: rotate(-15deg); }

/* Input Grid */
.input-grid {
    display: grid;
    grid-template-columns: repeat(2,1fr);
    gap:1rem;
    margin-bottom:1.5rem;
}

/* Input Container */
.volunteer-info {
    position: relative;
    display: flex;
    flex-direction: column;
    margin-bottom: 1.2rem;
}
.volunteer-info label {
    font-size:0.9rem;
    color:#555;
    margin-top:1rem;
    font-weight:500;
}
.input-wrapper { position: relative; }

/* Inputs & Selects */
.volunteer-info input,
.volunteer-info select {
    width: 100%;
    padding: 0.6rem 0.75rem 0.6rem 2.5rem;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:1rem;
    transition: all 0.25s ease;
    background: #fff;
    color: #000;
}

/* Select specific styling */
.volunteer-info select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    cursor: pointer;
    background: #fff url("data:image/svg+xml,%3Csvg fill='%23942a2a' height='12' viewBox='0 0 24 24' width='12' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 0.75rem center;
    background-size: 12px;
    padding-right: 2rem;
}

/* Disabled District */
#district {
    cursor: not-allowed;
    background-color: #f7f7f7 !important;
    color: #555 !important;
}

/* Move input-icon */
.volunteer-info select + .input-icon {
    left: auto;
    right: 0.75rem;
    pointer-events: none;
}

/* Focus states */
.volunteer-info input:focus,
.volunteer-info select:focus {
    outline: none;
    border-color: #B2000C;
}

/* Valid / Invalid */
.volunteer-info input.invalid,
.volunteer-info select.invalid {
    border-color: #dc3545 !important;
    background: #ffe6e6 !important;
}
.volunteer-info input.valid,
.volunteer-info select.valid {
    border-color: #28a745 !important;
    background: #e6f9ea !important;
}

/* Input Icon */
.input-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #942a2a;
    font-size: 1.2rem;
    pointer-events: none;
    transition: transform 0.25s ease, color 0.25s ease;
}
.volunteer-info input:focus + .input-icon,
.volunteer-info select:focus + .input-icon,
.volunteer-info:hover .input-icon {
    transform: translateY(-50%) rotate(-15deg);
    color: #B2000C;
}

/* Error tooltip */
.error-tooltip {
    display: none;
    font-size: 0.75rem;
    color: #fff;
    background: #dc3545;
    padding: 0.35rem 0.5rem;
    border-radius: 5px;
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 0.5rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}
.volunteer-info input.invalid + .input-icon + .error-tooltip,
.volunteer-info select.invalid + .input-icon + .error-tooltip {
    display: block;
    opacity: 1;
    pointer-events: auto;
}

/* Footer Buttons */
.modal-footer { display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; margin-top:1rem; }
.modal-btn { display:flex; align-items:center; justify-content:center; gap:0.5rem; padding:0.65rem 1.8rem; font-size:1rem; font-weight:600; border-radius:8px; cursor:pointer; border:none; transition: all 0.25s ease; height:50px; }
.modal-btn.cancel { background:#f1f1f1; color:#333; }
.modal-btn.cancel:hover { background:#e0e0e0; transform:scale(1.05); }
.modal-btn.save { background:#B2000C; color:#fff; }
.modal-btn.save:hover { background:#7F0008; transform:scale(1.05); }
.modal-btn.save:disabled { background: #B2000C; opacity: 0.5; cursor: not-allowed; box-shadow: none; transform: none; }
.modal-btn.save.enabled { box-shadow: 0 8px 24px rgba(178,0,12,0.28); transform: translateY(-2px); background: linear-gradient(180deg,#c41a1a,#B2000C); opacity: 1; cursor: pointer; }

@keyframes slideIn {
    from { opacity:0; transform: translateY(-20px) scale(0.96); }
    to { opacity:1; transform: translateY(0) scale(1); }
}
</style>

@php
// Fetch locations from DB and group by barangay
$locations = DB::table('locations')->orderBy('barangay')->get()->groupBy('barangay');
@endphp

<script>
window.volunteersData = {
    invalid: @json(session('invalidEntries', [])),
    valid: @json(session('validEntries', []))
};

// Barangay -> District mapping
const locations = @json($locations->mapWithKeys(function($items, $key) {
    return [$key => $items->pluck('district')];
}));
</script>

<div class="edit-volunteer-modal" id="editVolunteerModal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="modal-header">
        <i class="fa-solid fa-user-edit modal-icon"></i>
        <h2>Edit Volunteer</h2>
      </div>
      <form id="editVolunteerForm" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body input-grid">
          @php
          $fields = [
              'full_name' => ['label'=>'Full Name','icon'=>'fa-user','type'=>'text','required'=>true],
              'id_number' => ['label'=>'School ID','icon'=>'fa-id-card','type'=>'text','required'=>true],
              'course' => ['label'=>'Course','icon'=>'fa-graduation-cap','type'=>'text','required'=>true],
              'year_level' => ['label'=>'Year Level','icon'=>'fa-calendar','type'=>'text','required'=>true],
              'contact_number' => ['label'=>'Contact Number','icon'=>'fa-phone','type'=>'text','required'=>true],
              'emergency_contact' => ['label'=>'Emergency Contact','icon'=>'fa-phone-volume','type'=>'text','required'=>true],
              'email' => ['label'=>'Email','icon'=>'fa-envelope','type'=>'text','required'=>true],
              'fb_messenger' => ['label'=>'FB Messenger','icon'=>'fa-comment','type'=>'text','required'=>false],
              'barangay' => ['label'=>'Barangay','icon'=>'fa-house','type'=>'select','required'=>true],
              'district' => ['label'=>'District','icon'=>'fa-map-location-dot','type'=>'text','required'=>true], // now a text input
          ];
          @endphp

          @foreach ($fields as $key => $info)
          <div class="volunteer-info">
            <label>{{ $info['label'] }} @if($info['required'])* @endif</label>
            <div class="input-wrapper">
              @if($info['type'] === 'select' && $key==='barangay')
              <select id="barangay" name="barangay">
                <option value="">-- Select Barangay --</option>
                @foreach($locations as $barangay => $items)
                <option value="{{ $barangay }}">{{ $barangay }}</option>
                @endforeach
              </select>
              @elseif($key==='district')
              <!-- District is now readonly text -->
              <input type="text" id="district" name="district" placeholder="District" readonly>
              @else
              <input type="text" id="{{ $key }}" name="{{ $key }}" placeholder="{{ $info['label'] }}">
              @endif
              <i class="fa-solid {{ $info['icon'] }} input-icon"></i>
              <span class="error-tooltip" id="{{ $key }}-error"></span>
            </div>
          </div>
          @endforeach
        </div>

        <div class="modal-footer">
           <button type="button" class="modal-btn cancel" onclick="closeEditVolunteerModal()">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="submit" class="modal-btn save">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('editVolunteerModal');
  const form = document.getElementById('editVolunteerForm');
  const saveBtn = form.querySelector('.modal-btn.save');

  const barangaySelect = document.getElementById('barangay');
  const districtInput = document.getElementById('district');

  // Auto-fill district based on selected barangay
  barangaySelect.addEventListener('change', function(){
      const selected = this.value;
      if(selected && locations[selected]){
          // pick first district (or handle multiple if needed)
          districtInput.value = locations[selected][0] || '';
      } else {
          districtInput.value = '';
      }
      validateAll();
  });

  const rules = {
    full_name: v => v.trim() !== '' && /^[A-Za-z\s\-.,]+$/.test(v) || 'Invalid full name',
    id_number: v => v.trim() !== '' && /^\d{6,7}$/.test(v) || 'ID must be 6-7 digits',
    course: v => v.trim() !== '' && /^[A-Za-z\s]+$/.test(v) || 'Invalid course',
    year_level: v => v.trim() !== '' && /^[1-6]$/.test(v) || 'Year must be 1-6',
    contact_number: v => v.trim() !== '' && /^(09|\+639)\d{9}$/.test(v) || 'Invalid PH number',
    emergency_contact: v => v.trim() !== '' && /^(09|\+639)\d{9}$/.test(v) || 'Invalid PH number',
    email: v => v.trim() !== '' && /^[a-zA-Z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/.test(v) || 'Must be a @gmail.com or @adzu.edu.ph',
    fb_messenger: v => { if(!v) return true; try { const url=new URL(v); if(!['http:','https:'].includes(url.protocol)) return 'URL must start with http:// or https://'; if(!url.hostname.includes('facebook.com')) return 'URL should be a Facebook link'; return true;} catch{return 'Must be a valid URL like https://www.facebook.com/username';} },
    barangay: v => v.trim()!=='' || 'Please select a barangay',
    district: v => v.trim()!=='' || 'District is required'
  };

  function validateField(input){
    const val = input.value.trim();
    const errorSpan = document.getElementById(input.id+'-error');
    const res = rules[input.id]?.(val);
    if(res!==true){
      input.classList.remove('valid');
      input.classList.add('invalid');
      errorSpan.textContent = res;
      return false;
    } else {
      input.classList.remove('invalid');
      input.classList.add('valid');
      errorSpan.textContent = '';
      return true;
    }
  }

  function validateAll(){
    let allValid = true;
    document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input=>{
      if(!validateField(input)) allValid=false;
    });
    saveBtn.disabled = !allValid;
    if(allValid) saveBtn.classList.add('enabled'); else saveBtn.classList.remove('enabled');
    return allValid;
  }

  document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input=>{
    input.addEventListener('input', validateAll);
    input.addEventListener('change', validateAll);
    input.addEventListener('blur', validateAll);
  });

  window.openEditVolunteerModal = function(type,index){
    const volunteer = (window.volunteersData[type]||[])[index]||{};
    Object.keys(rules).forEach(key=>{
      const input = document.getElementById(key);
      if(input){
        input.value = volunteer[key]||'';
        validateField(input);
      }
    });

    // Trigger barangay change to fill district
    barangaySelect.dispatchEvent(new Event('change'));

    const routeTemplate = "{{ route('volunteer.import.update-entry', ['index' => '__INDEX__', 'type' => '__TYPE__']) }}";
    form.action = routeTemplate.replace('__INDEX__',index).replace('__TYPE__',type);
    modal.classList.add('is-open');
    document.documentElement.style.overflow='hidden';
    document.body.style.overflow='hidden';
    document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeEditVolunteerModal(); });
    validateAll();
  };

  window.closeEditVolunteerModal = function(){
    modal.classList.remove('is-open');
    document.documentElement.style.overflow='';
    document.body.style.overflow='';
  };

  modal.querySelector('.modal-overlay').addEventListener('click', e=>{
    if(e.target===modal.querySelector('.modal-overlay')) closeEditVolunteerModal();
  });

  form.addEventListener('submit', e=>{ if(!validateAll()) e.preventDefault(); });

  saveBtn.disabled=true;
})();
</script>
