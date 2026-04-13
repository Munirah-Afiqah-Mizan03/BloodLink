// BloodLink — Module 4: Donation Record Management
// main.js

// ── TOAST NOTIFICATION ──────────────────────────────────────
function blToast(msg, color) {
  var t = document.getElementById('bl-toast');
  if (!t) return;
  document.getElementById('bl-toast-msg').textContent = msg;
  t.style.borderLeftColor = color || '#E57373';
  t.classList.add('show');
  setTimeout(function () { t.classList.remove('show'); }, 3500);
}

// ── FIELD VALIDATION ────────────────────────────────────────
function blValidate(ids) {
  var ok = true;
  ids.forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    if (!el.value.trim()) {
      el.classList.add('error');
      ok = false;
      el.addEventListener('input', function () { el.classList.remove('error'); }, { once: true });
    }
  });
  return ok;
}

// ── DONOR LOOKUP (demo data — replace with AJAX to get_donor.php) ──
var BL_DONORS = {
  'D-0021': { name: 'Ahmad Haziq bin Roslan',       ic: '980214-03-5671', phone: '019-3821044', blood: 'A+',  initials: 'AH', last: '1 Feb 2026'  },
  'D-0019': { name: 'Nurul Fatihah binti Hassan',   ic: '001203-03-4521', phone: '011-2345678', blood: 'O+',  initials: 'NF', last: '15 Jan 2026' },
  'D-0015': { name: 'Muhammad Razif bin Zain',      ic: '990506-08-1234', phone: '017-8765432', blood: 'B+',  initials: 'MR', last: '20 Dec 2025' },
  'D-0012': { name: 'Siti Khadijah binti Ali',      ic: '970312-05-9876', phone: '013-5556789', blood: 'AB+', initials: 'SK', last: '10 Nov 2025' },
  'D-0009': { name: 'Farid Khairul bin Ismail',     ic: '950820-07-1122', phone: '016-9876543', blood: 'O-',  initials: 'FK', last: '5 Oct 2025'  },
};

function blLookupDonor(val) {
  var key     = val.trim().toUpperCase();
  if (!key.startsWith('D-')) key = 'D-' + key.replace(/^D-?/i, '');
  var donor   = BL_DONORS[key];
  var preview = document.getElementById('bl-donor-preview');
  var grey    = '<span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>';

  if (donor) {
    setAutoField('field-donor-name', donor.name);
    setAutoField('field-ic',         donor.ic);
    setAutoField('field-phone',      donor.phone);

    if (preview) {
      document.getElementById('preview-initials').textContent = donor.initials;
      document.getElementById('preview-name').textContent     = donor.name;
      document.getElementById('preview-sub').textContent      = key + ' · ' + donor.phone + ' · Last donated: ' + donor.last;
      document.getElementById('preview-blood').textContent    = donor.blood;
      preview.style.display = 'block';
    }

    // auto-select blood type
    var bt = document.getElementById('field-bloodtype');
    if (bt) {
      for (var i = 0; i < bt.options.length; i++) {
        if (bt.options[i].value === donor.blood) { bt.selectedIndex = i; break; }
      }
    }
  } else {
    resetAutoField('field-donor-name');
    resetAutoField('field-ic');
    resetAutoField('field-phone');
    if (preview) preview.style.display = 'none';
  }
}

function setAutoField(id, value) {
  var el = document.getElementById(id);
  if (el) el.innerHTML = '<span style="color:#555">' + value + '</span><span class="bl-auto-tag">Auto</span>';
}
function resetAutoField(id) {
  var el = document.getElementById(id);
  if (el) el.innerHTML = '<span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>';
}

// ── SET TODAY DATE ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  var df = document.getElementById('field-date');
  if (df && !df.value) df.value = new Date().toISOString().split('T')[0];
});

// ── CONFIRM DELETE ───────────────────────────────────────────
function blConfirmDelete(id, event_name) {
  return confirm('Delete record #' + id + ' from "' + event_name + '"?\nThis action cannot be undone.');
}
