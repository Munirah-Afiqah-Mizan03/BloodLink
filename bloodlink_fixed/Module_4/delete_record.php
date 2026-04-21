<?php
// BloodLink — Module 4: Delete Donation Record
require_once 'includes/db.php';

$activePage = 'records';
$pageTitle  = 'Delete Record';
$errors     = [];

$current_user_display = trim($_SESSION['full_name'] ?? '');
if (($_SESSION['role'] ?? '') === 'medical_officer' && $current_user_display && !preg_match('/^dr\b/i', $current_user_display)) {
    $current_user_display = 'Dr. ' . $current_user_display;
}
if (!$current_user_display) $current_user_display = 'System';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// ── FETCH RECORD ──────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT dr.*, d.donor_id AS donor_code,
        CONCAT(d.first_name,' ',d.last_name) AS donor_name,
        CONCAT(LEFT(d.first_name,1), LEFT(d.last_name,1)) AS initials,
        d.ic_number, d.phone, d.blood_type AS donor_blood,
        e.event_name
     FROM bl_donation_records dr
     JOIN bl_donors d ON dr.donor_id = d.id
     JOIN bl_events e ON dr.event_id = e.id
     WHERE dr.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
if (!$rec) { header('Location: index.php'); exit; }

// ── HANDLE CONFIRM DELETE ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_reason = trim($_POST['delete_reason'] ?? '');
    $confirm_id    = trim($_POST['confirm_id']    ?? '');

    if (!$delete_reason) $errors[] = 'Please select a reason for deletion.';
    if ($confirm_id !== $rec['record_id']) $errors[] = 'Record ID confirmation does not match.';

    if (empty($errors)) {
        // ── LOG TO HISTORY BEFORE DELETE ─────────────────────
        $log = $conn->prepare(
            "INSERT INTO bl_record_edit_history
                (record_id, edited_by, edit_category, edit_note)
             VALUES (?, ?, 'Deletion', ?)"
        );
        $deleted_by  = $current_user_display;
        $log_note    = 'Record deleted. Reason: ' . $delete_reason;
        $log->bind_param("iss", $id, $deleted_by, $log_note);
        $log->execute();
        $log->close();

        // ── DELETE RECORD ─────────────────────────────────────
        $del = $conn->prepare("DELETE FROM bl_donation_records WHERE id = ?");
        $del->bind_param("i", $id);

        if ($del->execute()) {
            $del->close();
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => "Record {$rec['record_id']} has been deleted successfully."
            ];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
            $del->close();
        }
    }
}
?>
<?php include 'includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Donation Records</a>
      <span class="sep">›</span>
      <span>#<?php echo htmlspecialchars($rec['record_id']); ?></span>
      <span class="sep">›</span>
      <span>Delete record</span>
    </div>

    <!-- Top Hero -->
    <div class="bl-top-hero bl-top-hero-danger">
      <div>
        <h2>Delete donation record</h2>
        <p>You are about to permanently delete record <strong style="color:rgba(255,255,255,.92)">#<?php echo htmlspecialchars($rec['record_id']); ?></strong></p>
      </div>
      <div class="bl-top-hero-actions">
        <div class="bl-status-badge">
          <span style="color:rgba(255,255,255,.9)">Current status:</span>
          <span class="bl-badge bl-badge-verify"><?php echo htmlspecialchars($rec['status']); ?></span>
        </div>
      </div>
    </div>

    <!-- Danger warning notice -->
    <div class="bl-notice bl-notice-danger">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#E57373" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#E57373" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>This action <strong>cannot be undone</strong>. The record will be permanently removed from the database. An audit log entry will be created before deletion.</p>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bl-notice bl-notice-warn"
         style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
      <?php foreach ($errors as $e): ?>
      <p>⚠ <?php echo htmlspecialchars($e); ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="delete_record.php?id=<?php echo $id; ?>">
    <div class="bl-card">

      <!-- Section 1: Record Summary (read-only) -->
      <div class="bl-section">
        <div class="bl-section-hd">
          <div class="bl-section-title"><div class="bl-bar"></div><h3>Record to be deleted</h3></div>
          <span class="bl-readonly-tag">Read-only</span>
        </div>

        <!-- Donor card -->
        <div class="bl-donor-card" style="margin-bottom:1.25rem">
          <div class="bl-donor-avatar"><?php echo htmlspecialchars($rec['initials']); ?></div>
          <div class="bl-donor-info">
            <strong><?php echo htmlspecialchars($rec['donor_name']); ?></strong>
            <small><?php echo htmlspecialchars($rec['donor_code']); ?> &nbsp;·&nbsp;
                  <?php echo htmlspecialchars($rec['phone']); ?> &nbsp;·&nbsp;
                  IC: <?php echo htmlspecialchars($rec['ic_number']); ?></small>
          </div>
          <span class="bl-badge bl-badge-blood"><?php echo htmlspecialchars($rec['donor_blood']); ?></span>
        </div>

        <!-- Record detail grid -->
        <div class="bl-grid-3">
          <div class="bl-field">
            <label>Record ID</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($rec['record_id']); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Donation date</label>
            <div class="bl-auto-field">
              <span><?php echo date('d M Y', strtotime($rec['donation_date'])); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Donation event</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($rec['event_name']); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Blood type</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($rec['blood_type']); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Volume collected (ml)</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($rec['volume_ml']); ?> ml</span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Recorded by</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($rec['recorded_by']); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Reason for Deletion -->
      <div class="bl-section bl-section-alt">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Reason for deletion <span class="req">*</span></h3>
        </div>
        <div class="bl-grid-2">
          <div class="bl-field">
            <label>Deletion reason <span class="req">*</span></label>
            <select name="delete_reason" id="field-delete-reason">
              <option value="">Select a reason</option>
              <option value="Duplicate record">Duplicate record</option>
              <option value="Data entry error">Data entry error</option>
              <option value="Donor request">Donor request</option>
              <option value="Test / demo data">Test / demo data</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <!-- Confirmation input -->
          <div class="bl-field">
            <label>
              Type <strong style="color:#E57373"><?php echo htmlspecialchars($rec['record_id']); ?></strong> to confirm <span class="req">*</span>
            </label>
            <input type="text" name="confirm_id" id="field-confirm-id"
                   placeholder="e.g. <?php echo htmlspecialchars($rec['record_id']); ?>"
                   autocomplete="off">
            <p class="bl-hint">Enter the record ID exactly as shown to enable deletion</p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="bl-footer">
        <div class="bl-footer-note">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="#ccc" stroke-width="1.5"/>
            <path d="M12 8v4M12 16h.01" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          Fields marked with <span class="req">&nbsp;*&nbsp;</span> are required
        </div>
        <div class="bl-footer-actions">
          <a href="index.php" class="bl-btn bl-btn-ghost">Cancel</a>
          <button type="submit" class="bl-btn bl-btn-danger" id="btn-delete" disabled>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Delete record
          </button>
        </div>
      </div>

    </div><!-- /bl-card -->
    </form>

  </div><!-- /bl-main -->

<script>
(function () {
  const confirmInput  = document.getElementById('field-confirm-id');
  const reasonSelect  = document.getElementById('field-delete-reason');
  const deleteBtn     = document.getElementById('btn-delete');
  const expectedId    = <?php echo json_encode($rec['record_id']); ?>;

  function checkReady() {
    const idMatch     = confirmInput.value.trim() === expectedId;
    const hasReason   = reasonSelect.value !== '';
    deleteBtn.disabled = !(idMatch && hasReason);
  }

  confirmInput.addEventListener('input',  checkReady);
  reasonSelect.addEventListener('change', checkReady);
})();
</script>

<?php include 'includes/footer.php'; ?>
