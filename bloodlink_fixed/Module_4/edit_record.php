<?php
// BloodLink — Module 4: Edit Donation Record
require_once 'includes/db.php';

$activePage = 'records';
$pageTitle  = 'Edit Record';
$errors     = [];

$current_user_display = trim($_SESSION['full_name'] ?? '');
if (($_SESSION['role'] ?? '') === 'medical_officer' && $current_user_display && !preg_match('/^dr\b/i', $current_user_display)) {
    $current_user_display = 'Dr. ' . $current_user_display;
}
if (!$current_user_display) $current_user_display = 'System';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Fetch existing record
$stmt = $conn->prepare("SELECT dr.*, d.donor_id AS donor_code,
    CONCAT(d.first_name,' ',d.last_name) AS donor_name,
    CONCAT(LEFT(d.first_name,1), LEFT(d.last_name,1)) AS initials,
    d.ic_number, d.phone, d.blood_type AS donor_blood,
    e.event_name
    FROM bl_donation_records dr
    JOIN bl_donors d ON dr.donor_id = d.id
    JOIN bl_events e ON dr.event_id = e.id
    WHERE dr.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
if (!$rec) { header('Location: index.php'); exit; }

// Fetch edit history
$hist_stmt = $conn->prepare("SELECT * FROM bl_record_edit_history WHERE record_id=? ORDER BY edited_at DESC");
$hist_stmt->bind_param("i", $id);
$hist_stmt->execute();
$history = $hist_stmt->get_result();

// Events dropdown
$events = $conn->query("SELECT id, event_name FROM bl_events ORDER BY event_date DESC");

// ── HANDLE SUBMIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id       = intval($_POST['event_id']       ?? 0);
    $donation_date  = trim($_POST['donation_date']    ?? '');
    $blood_type     = trim($_POST['blood_type']       ?? '');
    $volume_ml      = intval($_POST['volume_ml']      ?? 0);
    $status         = trim($_POST['status']           ?? '');
    $blood_pressure = trim($_POST['blood_pressure']   ?? '');
    $haemoglobin    = trim($_POST['haemoglobin']      ?? '');
    $remarks        = trim($_POST['remarks']          ?? '');
    $edit_category  = trim($_POST['edit_category']    ?? '');
    $edit_note      = trim($_POST['edit_note']        ?? '');
    $edited_by      = $current_user_display;

    if (!$event_id)     $errors[] = 'Please select a donation event.';
    if (!$donation_date)$errors[] = 'Donation date is required.';
    if (!$blood_type)   $errors[] = 'Blood type is required.';
    if ($volume_ml < 50)$errors[] = 'Volume must be at least 50ml.';
    if (!$edit_category)$errors[] = 'Please select a reason for editing.';
    if (!$edit_note)    $errors[] = 'Please provide an edit note.';

    if (empty($errors)) {
        $hb = $haemoglobin !== '' ? floatval($haemoglobin) : null;

        $upd = $conn->prepare("UPDATE bl_donation_records SET
            event_id=?, blood_type=?, volume_ml=?, donation_date=?,
            blood_pressure=?, haemoglobin=?, remarks=?, status=?,
            recorded_by=?, updated_at=NOW()
            WHERE id=?");
        $upd->bind_param("isiisssssi",
            $event_id, $blood_type, $volume_ml, $donation_date,
            $blood_pressure, $hb, $remarks, $status, $edited_by, $id);

        if ($upd->execute()) {
            // Log to history
            $log = $conn->prepare("INSERT INTO bl_record_edit_history
                (record_id, edited_by, edit_category, edit_note) VALUES (?,?,?,?)");
            $log->bind_param("isss", $id, $edited_by, $edit_category, $edit_note);
            $log->execute();

            $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Record #{$rec['record_id']} updated successfully."];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
    // Keep posted values on error
    $rec = array_merge($rec, $_POST);
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
      <span>Edit record</span>
    </div>

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Edit donation record</h2>
        <p>Update information for record <strong style="color:rgba(255,255,255,.92)">#<?php echo htmlspecialchars($rec['record_id']); ?></strong></p>
      </div>
      <div class="bl-top-hero-actions">
        <div class="bl-status-badge">
          <span style="color:rgba(255,255,255,.9)">Current status:</span>
          <span class="bl-badge bl-badge-verify"><?php echo htmlspecialchars($rec['status']); ?></span>
        </div>
      </div>
    </div>

    <!-- Warning notice -->
    <div class="bl-notice bl-notice-warn">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>All edits are logged automatically. Make sure all changes are accurate before saving.</p>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bl-notice bl-notice-warn" style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
      <?php foreach($errors as $e): ?>
      <p>⚠ <?php echo htmlspecialchars($e); ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="edit_record.php?id=<?php echo $id; ?>">
    <div class="bl-card">

      <!-- Section 1: Donor Info (read-only) -->
      <div class="bl-section">
        <div class="bl-section-hd">
          <div class="bl-section-title"><div class="bl-bar"></div><h3>Donor information</h3></div>
          <span class="bl-readonly-tag">Read-only</span>
        </div>
        <div class="bl-donor-card">
          <div class="bl-donor-avatar"><?php echo htmlspecialchars($rec['initials']); ?></div>
          <div class="bl-donor-info">
            <strong><?php echo htmlspecialchars($rec['donor_name']); ?></strong>
            <small><?php echo htmlspecialchars($rec['donor_code']); ?> &nbsp;·&nbsp;
                  <?php echo htmlspecialchars($rec['phone']); ?> &nbsp;·&nbsp;
                  IC: <?php echo htmlspecialchars($rec['ic_number']); ?></small>
          </div>
          <span class="bl-badge bl-badge-blood"><?php echo htmlspecialchars($rec['donor_blood']); ?></span>
        </div>
      </div>

      <!-- Section 2: Donation Details (editable) -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Donation details</h3>
        </div>
        <div class="bl-grid-3">

          <div class="bl-field">
            <label>Donation event <span class="req">*</span></label>
            <select name="event_id">
              <?php $events->data_seek(0); while($ev=$events->fetch_assoc()): ?>
              <option value="<?php echo $ev['id']; ?>"
                <?php echo ($rec['event_id']==$ev['id'])?'selected':''; ?>>
                <?php echo htmlspecialchars($ev['event_name']); ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Donation date <span class="req">*</span></label>
            <input type="date" name="donation_date"
                   value="<?php echo htmlspecialchars($rec['donation_date']); ?>">
          </div>

          <div class="bl-field">
            <label>Blood type <span class="req">*</span></label>
            <select name="blood_type">
              <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
              <option value="<?php echo $bt; ?>"
                <?php echo ($rec['blood_type']===$bt)?'selected':''; ?>><?php echo $bt; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Volume collected (ml) <span class="req">*</span></label>
            <input type="number" name="volume_ml" min="50" max="600"
                   value="<?php echo htmlspecialchars($rec['volume_ml']); ?>">
          </div>

          <div class="bl-field">
            <label>Status <span class="req">*</span></label>
            <select name="status">
              <?php foreach(['Pending','Verified','Rejected'] as $s): ?>
              <option value="<?php echo $s; ?>"
                <?php echo ($rec['status']===$s)?'selected':''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Last edited by</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($current_user_display); ?></span><span class="bl-auto-tag">Auto</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Section 3: Health Notes -->
      <div class="bl-section bl-section-muted">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div>
          <h3>Health screening notes <span class="bl-optional">(optional)</span></h3>
        </div>
        <div class="bl-grid-2">
          <div class="bl-field">
            <label>Blood pressure</label>
            <input type="text" name="blood_pressure" placeholder="e.g. 120/80 mmHg"
                   value="<?php echo htmlspecialchars($rec['blood_pressure'] ?? ''); ?>">
          </div>
          <div class="bl-field">
            <label>Haemoglobin level (g/dL)</label>
            <input type="text" name="haemoglobin" placeholder="e.g. 13.5"
                   value="<?php echo htmlspecialchars($rec['haemoglobin'] ?? ''); ?>">
          </div>
          <div class="bl-field bl-col-2">
            <label>Additional remarks</label>
            <textarea name="remarks" rows="3"><?php echo htmlspecialchars($rec['remarks'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Section 4: Reason for Editing -->
      <div class="bl-section bl-section-alt">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Reason for editing <span class="req">*</span></h3>
        </div>
        <div class="bl-grid-2">
          <div class="bl-field">
            <label>Edit category <span class="req">*</span></label>
            <select name="edit_category">
              <option value="">Select a reason</option>
              <option value="Data entry correction">Data entry correction</option>
              <option value="Volume update">Volume update</option>
              <option value="Status update">Status update</option>
              <option value="Event reassignment">Event reassignment</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="bl-field">
            <label>Edit note <span class="req">*</span></label>
            <input type="text" name="edit_note" placeholder="Brief description of the change...">
          </div>
        </div>
      </div>

      <!-- Section 5: Edit History -->
      <div class="bl-section">
        <div class="bl-section-hd" style="margin-bottom:1rem">
          <div class="bl-section-title">
            <div class="bl-bar bl-bar-grey"></div><h3>Edit history</h3>
          </div>
          <span style="font-size:11px;color:#aaa"><?php echo $history->num_rows; ?> previous edits</span>
        </div>
        <div class="bl-history-list">
          <?php if ($history->num_rows === 0): ?>
          <p style="font-size:12px;color:#ccc;padding:8px 0">No edits recorded yet.</p>
          <?php else: ?>
          <?php while($h = $history->fetch_assoc()): ?>
          <div class="bl-history-item">
            <div class="bl-history-avatar"><?php echo strtoupper(substr($h['edited_by'],3,2)); ?></div>
            <div class="bl-history-body">
              <div class="bl-history-meta">
                <strong><?php echo htmlspecialchars($h['edited_by']); ?></strong>
                <time><?php echo date('d M Y · h:i A', strtotime($h['edited_at'])); ?></time>
              </div>
              <p><?php echo htmlspecialchars($h['edit_category']); ?></p>
              <small>"<?php echo htmlspecialchars($h['edit_note']); ?>"</small>
            </div>
          </div>
          <?php endwhile; ?>
          <?php endif; ?>
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
          <a href="index.php" class="bl-btn bl-btn-ghost">Discard changes</a>
          <button type="submit" class="bl-btn bl-btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Save changes
          </button>
        </div>
      </div>

    </div><!-- /bl-card -->
    </form>
  </div><!-- /bl-main -->

<?php include 'includes/footer.php'; ?>
