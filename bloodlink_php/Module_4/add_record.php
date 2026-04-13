<?php
// BloodLink — Module 4: Add New Donation Record
session_start();
require_once 'includes/db.php';

$activePage = 'records';
$pageTitle  = 'Add New Record';
$errors     = [];
$success    = false;

// ── HANDLE FORM SUBMIT ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id_code  = trim($_POST['donor_id_code'] ?? '');
    $event_id       = intval($_POST['event_id']     ?? 0);
    $donation_date  = trim($_POST['donation_date']  ?? '');
    $blood_type     = trim($_POST['blood_type']     ?? '');
    $volume_ml      = intval($_POST['volume_ml']    ?? 0);
    $status         = trim($_POST['status']         ?? 'Pending');
    $blood_pressure = trim($_POST['blood_pressure'] ?? '');
    $haemoglobin    = trim($_POST['haemoglobin']    ?? '');
    $remarks        = trim($_POST['remarks']        ?? '');
    $recorded_by    = 'Dr. Siti Aminah'; // Replace with session user

    // Validate
    if (!$donor_id_code) $errors[] = 'Donor ID is required.';
    if (!$event_id)      $errors[] = 'Please select a donation event.';
    if (!$donation_date) $errors[] = 'Donation date is required.';
    if (!$blood_type)    $errors[] = 'Blood type is required.';
    if ($volume_ml < 50) $errors[] = 'Volume collected must be at least 50ml.';

    if (empty($errors)) {
        // Get donor's DB id
        $d = $conn->prepare("SELECT id FROM donors WHERE donor_id = ?");
        $d->bind_param("s", $donor_id_code);
        $d->execute();
        $donor_row = $d->get_result()->fetch_assoc();

        if (!$donor_row) {
            $errors[] = 'Donor ID not found. Please check and try again.';
        } else {
            // Generate next record_id
            $last = $conn->query("SELECT record_id FROM donation_records ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $next_num = $last ? intval(substr($last['record_id'], 3)) + 1 : 1;
            $record_id = 'DR-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

            $hb = $haemoglobin !== '' ? floatval($haemoglobin) : null;

            $ins = $conn->prepare("INSERT INTO donation_records
                (record_id, donor_id, event_id, blood_type, volume_ml, donation_date,
                 blood_pressure, haemoglobin, remarks, status, recorded_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $ins->bind_param("siisississs",
                $record_id, $donor_row['id'], $event_id, $blood_type, $volume_ml,
                $donation_date, $blood_pressure, $hb, $remarks, $status, $recorded_by);

            if ($ins->execute()) {
                $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Record $record_id saved successfully."];
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        }
    }
}

// Events for dropdown
$events = $conn->query("SELECT id, event_name, event_date FROM events WHERE status != 'Cancelled' ORDER BY event_date DESC");
?>
<?php include 'includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Donation Records</a>
      <span class="sep">›</span>
      <span>Add new record</span>
    </div>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Add new donation record</h1>
        <p>Fill in the details below after a donor has successfully donated blood</p>
      </div>
      <div class="bl-new-badge">
        <div class="bl-new-dot">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none">
            <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
          </svg>
        </div>
        <span>New Record</span>
      </div>
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
    <form method="POST" action="add_record.php">
    <div class="bl-card">

      <!-- Section 1: Donor Information -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Donor information</h3>
        </div>
        <div class="bl-grid-2">

          <div class="bl-field">
            <label>Donor ID <span class="req">*</span></label>
            <div class="bl-id-wrap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                <circle cx="11" cy="11" r="7" stroke="#F4A7A7" stroke-width="1.5"/>
                <path d="M16.5 16.5L21 21" stroke="#F4A7A7" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <input type="text" name="donor_id_code" id="field-donor-id"
                     placeholder="e.g. D-0021"
                     value="<?php echo htmlspecialchars($_POST['donor_id_code'] ?? ''); ?>"
                     oninput="blLookupDonor(this.value)">
            </div>
            <p class="bl-hint">Enter donor ID to auto-fill details below</p>
          </div>

          <div class="bl-field">
            <label>Donor name</label>
            <div class="bl-auto-field" id="field-donor-name">
              <span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>
            </div>
          </div>

          <div class="bl-field">
            <label>IC number</label>
            <div class="bl-auto-field" id="field-ic">
              <span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>
            </div>
          </div>

          <div class="bl-field">
            <label>Phone number</label>
            <div class="bl-auto-field" id="field-phone">
              <span>Auto-filled from donor ID</span><span class="bl-auto-tag">Auto</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Donor preview -->
      <div class="bl-donor-preview" id="bl-donor-preview" style="display:none">
        <p class="bl-preview-label">Donor preview</p>
        <div class="bl-donor-card">
          <div class="bl-donor-avatar" id="preview-initials">--</div>
          <div class="bl-donor-info">
            <strong id="preview-name">—</strong>
            <small  id="preview-sub">—</small>
          </div>
          <div class="bl-donor-badges">
            <span class="bl-badge bl-badge-blood" id="preview-blood">—</span>
            <span class="bl-badge bl-badge-verify">Eligible</span>
          </div>
        </div>
      </div>

      <!-- Section 2: Donation Details -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Donation details</h3>
        </div>
        <div class="bl-grid-3">

          <div class="bl-field">
            <label>Donation event <span class="req">*</span></label>
            <select name="event_id" id="field-event">
              <option value="">Select event</option>
              <?php while($ev = $events->fetch_assoc()): ?>
              <option value="<?php echo $ev['id']; ?>"
                <?php echo (($_POST['event_id'] ?? '')==$ev['id'])?'selected':''; ?>>
                <?php echo htmlspecialchars($ev['event_name']); ?>
              </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Donation date <span class="req">*</span></label>
            <input type="date" name="donation_date" id="field-date"
                   value="<?php echo htmlspecialchars($_POST['donation_date'] ?? date('Y-m-d')); ?>">
          </div>

          <div class="bl-field">
            <label>Blood type <span class="req">*</span></label>
            <select name="blood_type" id="field-bloodtype">
              <option value="">Select blood type</option>
              <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bt): ?>
              <option value="<?php echo $bt; ?>"
                <?php echo (($_POST['blood_type'] ?? '')===$bt)?'selected':''; ?>><?php echo $bt; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Volume collected (ml) <span class="req">*</span></label>
            <input type="number" name="volume_ml" id="field-volume"
                   placeholder="e.g. 450" min="50" max="600"
                   value="<?php echo htmlspecialchars($_POST['volume_ml'] ?? ''); ?>">
          </div>

          <div class="bl-field">
            <label>Status <span class="req">*</span></label>
            <select name="status" id="field-status">
              <?php foreach(['Pending','Verified','Rejected'] as $s): ?>
              <option value="<?php echo $s; ?>"
                <?php echo (($_POST['status'] ?? 'Pending')===$s)?'selected':''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="bl-field">
            <label>Recorded by</label>
            <div class="bl-auto-field">
              <span>Dr. Siti Aminah</span><span class="bl-auto-tag">Auto</span>
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
                   value="<?php echo htmlspecialchars($_POST['blood_pressure'] ?? ''); ?>">
          </div>
          <div class="bl-field">
            <label>Haemoglobin level (g/dL)</label>
            <input type="text" name="haemoglobin" placeholder="e.g. 13.5"
                   value="<?php echo htmlspecialchars($_POST['haemoglobin'] ?? ''); ?>">
          </div>
          <div class="bl-field bl-col-2">
            <label>Additional remarks</label>
            <textarea name="remarks" rows="3"
                      placeholder="Any notes or observations..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
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
          <button type="submit" class="bl-btn bl-btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Save record
          </button>
        </div>
      </div>

    </div><!-- /bl-card -->
    </form>

  </div><!-- /bl-main -->

<div class="bl-toast" id="bl-toast">
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
    <circle cx="12" cy="12" r="9" fill="#E57373"/>
    <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
  </svg>
  <span id="bl-toast-msg"></span>
</div>

<?php include 'includes/footer.php'; ?>
