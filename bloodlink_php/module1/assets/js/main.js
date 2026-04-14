// BloodLink — Module 2 JS

// ── Toast ──────────────────────────────────────────────────────
function blToast(msg, type) {
  const t  = document.getElementById('bl-toast');
  const tm = document.getElementById('bl-toast-msg');
  if (!t || !tm) return;
  tm.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3500);
}

// ── Confirm delete inline ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  // Flash toast from PHP session (if element exists)
  const flash = document.getElementById('bl-flash-data');
  if (flash) {
    blToast(flash.dataset.msg, flash.dataset.type);
  }

  // Volunteer remove confirmation
  document.querySelectorAll('.bl-vol-remove-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      if (!confirm('Remove this volunteer from the event?')) e.preventDefault();
    });
  });
});
