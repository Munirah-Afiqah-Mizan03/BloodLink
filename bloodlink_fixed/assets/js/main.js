// BloodLink — Unified JS

// ── Toast ──────────────────────────────────────────────────────
function blToast(msg, type) {
  var t  = document.getElementById('bl-toast');
  var tm = document.getElementById('bl-toast-msg');
  if (!t || !tm) return;
  tm.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3500);
}

// ── Field Validation ────────────────────────────────────────
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

// ── Donor Lookup (AJAX to get_donor.php) ────────────────────
function blLookupDonor(val) {
  var key = val.trim().toUpperCase();
  if (!key.startsWith('D-')) key = 'D-' + key.replace(/^D-?/i, '');
  var preview = document.getElementById('bl-donor-preview');
  var grey = '<span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>';

  if (key.length < 3) {
    resetAutoField('field-donor-name');
    resetAutoField('field-ic');
    resetAutoField('field-phone');
    if (preview) preview.style.display = 'none';
    return;
  }

  fetch('get_donor.php?donor_id=' + encodeURIComponent(key))
    .then(function(r) { return r.json(); })
    .then(function(donor) {
      if (donor.found) {
        setAutoField('field-donor-name', donor.name);
        setAutoField('field-ic', donor.ic);
        setAutoField('field-phone', donor.phone);
        if (preview) {
          document.getElementById('preview-initials').textContent = donor.initials;
          document.getElementById('preview-name').textContent = donor.name;
          document.getElementById('preview-sub').textContent = key + ' · ' + donor.phone + ' · Last donated: ' + donor.last_donation;
          document.getElementById('preview-blood').textContent = donor.blood_type;
          preview.style.display = 'block';
        }
        var bt = document.getElementById('field-bloodtype');
        if (bt) {
          for (var i = 0; i < bt.options.length; i++) {
            if (bt.options[i].value === donor.blood_type) { bt.selectedIndex = i; break; }
          }
        }
      } else {
        resetAutoField('field-donor-name');
        resetAutoField('field-ic');
        resetAutoField('field-phone');
        if (preview) preview.style.display = 'none';
      }
    })
    .catch(function() {});
}

function setAutoField(id, value) {
  var el = document.getElementById(id);
  if (el) el.innerHTML = '<span style="color:var(--text)">' + value + '</span><span class="bl-auto-tag">Auto</span>';
}
function resetAutoField(id) {
  var el = document.getElementById(id);
  if (el) el.innerHTML = '<span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>';
}

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  var df = document.getElementById('field-date');
  if (df && !df.value) df.value = new Date().toISOString().split('T')[0];

  var flash = document.getElementById('bl-flash-data');
  if (flash) blToast(flash.dataset.msg, flash.dataset.type);

  document.querySelectorAll('.bl-vol-remove-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      if (!confirm('Remove this volunteer from the event?')) e.preventDefault();
    });
  });
});
