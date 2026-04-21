<?php
// BloodLink — Module 1: Manage Profile
require_once 'auth.php';
require_role('donor');
require_once 'db.php';

$activePage = 'profile';
$pageTitle  = 'Manage Profile';
$u = current_user();
$errors  = [];
$success = '';
$donor_db_id = $u['donor_id'] ?? null;

// ── Fetch full donor + user record ────────────────────────────
$donor = null;
if ($donor_db_id) {
    $stmt = $conn->prepare(
        "SELECT d.*, u.username AS email, u.id AS user_id
         FROM bl_donors d
         JOIN bl_users u ON u.ic_number = d.ic_number
         WHERE d.id = ?"
    );
    $stmt->bind_param("i", $donor_db_id);
    $stmt->execute();
    $donor = $stmt->get_result()->fetch_assoc();
}

// Fallback: look up by ic_number from session
if (!$donor && !empty($u['ic_number'])) {
    $stmt2 = $conn->prepare(
        "SELECT d.*, u.username AS email, u.id AS user_id
         FROM bl_donors d
         JOIN bl_users u ON u.ic_number = d.ic_number
         WHERE d.ic_number = ? LIMIT 1"
    );
    $stmt2->bind_param("s", $u['ic_number']);
    $stmt2->execute();
    $donor = $stmt2->get_result()->fetch_assoc();
    if ($donor) {
        $donor_db_id = $donor['id'];
        $_SESSION['donor_id'] = $donor_db_id;
    }
}

if (!$donor) {
    $_SESSION['flash'] = ['type'=>'warn', 'msg'=>'No donor profile found. Please contact an administrator.'];
    header('Location: donor_dashboard.php');
    exit;
}

// ── Last donation from records ────────────────────────────────
$ld = $conn->prepare(
    "SELECT donation_date FROM bl_donation_records
     WHERE donor_id = ? ORDER BY donation_date DESC LIMIT 1"
);
$ld->bind_param("i", $donor_db_id);
$ld->execute();
$last_don_row = $ld->get_result()->fetch_assoc();
$last_donation_date = $last_don_row['donation_date'] ?? null;

// Eligibility
$eligible = $donor['health_status'] === 'Healthy';
if ($last_donation_date) {
    $diff = (new DateTime())->diff(new DateTime($last_donation_date))->days;
    if ($diff < 56) $eligible = false;
}

// ── HANDLE: Update Contact ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_contact') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$email) $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!$phone)  $errors[] = 'Phone number is required.';

    // Check email uniqueness (exclude current user)
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM bl_users WHERE username = ? AND id != ? LIMIT 1");
        $chk->bind_param("si", $email, $donor['user_id']);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'This email is already used by another account.';
        }
    }

    if (empty($errors)) {
        // Update bl_users username (= email)
        $u1 = $conn->prepare("UPDATE bl_users SET username = ? WHERE id = ?");
        $u1->bind_param("si", $email, $donor['user_id']);
        $u1->execute();

        // Update bl_donors phone
        $u2 = $conn->prepare("UPDATE bl_donors SET phone = ? WHERE id = ?");
        $u2->bind_param("si", $phone, $donor_db_id);
        $u2->execute();

        $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Contact details updated successfully.'];
        header('Location: manage_profile.php');
        exit;
    }
    // Patch display values if validation failed
    $donor['email'] = $email;
    $donor['phone'] = $phone;
}

// ── HANDLE: Update Health Status ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_health') {
    $blood_type    = trim($_POST['blood_type']    ?? '');
    $weight        = trim($_POST['weight']        ?? '');
    $health_status = trim($_POST['health_status'] ?? '');

    $valid_blood = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
    $valid_health = ['Healthy','Under Medication','Not Eligible'];

    if (!in_array($blood_type, $valid_blood))   $errors[] = 'Please select a valid blood type.';
    if (!is_numeric($weight) || $weight < 1)    $errors[] = 'Please enter a valid weight.';
    if (!in_array($health_status, $valid_health)) $errors[] = 'Please select a valid health status.';

    if (empty($errors)) {
        $upd = $conn->prepare(
            "UPDATE bl_donors SET blood_type = ?, health_status = ? WHERE id = ?"
        );
        $upd->bind_param("ssi", $blood_type, $health_status, $donor_db_id);
        $upd->execute();

        // Sync blood_type in bl_users as well
        $upd2 = $conn->prepare("UPDATE bl_users SET blood_type = ? WHERE id = ?");
        $upd2->bind_param("si", $blood_type, $donor['user_id']);
        $upd2->execute();

        $_SESSION['flash'] = ['type'=>'success', 'msg'=>'Health information updated successfully.'];
        header('Location: manage_profile.php');
        exit;
    }
}

// Flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$blood_types  = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
$health_opts  = ['Healthy','Under Medication','Not Eligible'];
$health_labels = [
    'Healthy'          => 'Healthy — eligible to donate',
    'Under Medication' => 'Under Medication',
    'Not Eligible'     => 'Currently not eligible',
];
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <?php if ($flash): ?>
    <div class="bl-notice bl-notice-<?php echo $flash['type']; ?>" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <?php if($flash['type']==='success'): ?>
        <path d="M9 12l2 2 4-4" stroke="#7ecf93" stroke-width="2" stroke-linecap="round"/>
        <circle cx="12" cy="12" r="9" stroke="#7ecf93" stroke-width="1.5"/>
        <?php else: ?>
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
        <?php endif; ?>
      </svg>
      <p><?php echo htmlspecialchars($flash['msg']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
      <?php foreach($errors as $e): ?>
      <p>⚠ <?php echo htmlspecialchars($e); ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Manage Profile</h2>
        <p>View and update your personal information, contact details, and health status</p>
      </div>
    </div>

    <!-- Status Bar -->
    <div class="bl-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
      <div class="bl-stat bl-stat-red">
        <p>Blood Type</p>
        <p><?php echo htmlspecialchars($donor['blood_type']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Health Status</p>
        <p style="font-size:15px"><?php echo htmlspecialchars($donor['health_status']); ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Last Donation</p>
        <p style="font-size:15px">
          <?php echo $last_donation_date ? date('d M Y', strtotime($last_donation_date)) : '—'; ?>
        </p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Donor Status</p>
        <p style="font-size:15px">
          <?php if ($eligible): ?>
          <span class="bl-badge bl-badge-completed">Eligible</span>
          <?php else: ?>
          <span class="bl-badge bl-badge-cancelled">Not Eligible</span>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <!-- SECTION: Personal Info (read-only) -->
    <div class="bl-card" style="margin-bottom:1.25rem">
      <div class="bl-section">
        <div class="bl-section-hd">
          <div class="bl-section-title"><div class="bl-bar"></div><h3>Personal information</h3></div>
          <span class="bl-readonly-tag">Read-only</span>
        </div>
        <div class="bl-grid-2" style="margin-top:1.25rem">
          <div class="bl-field">
            <label>First name</label>
            <div class="bl-auto-field"><span><?php echo htmlspecialchars($donor['first_name']); ?></span><span class="bl-auto-tag">Fixed</span></div>
          </div>
          <div class="bl-field">
            <label>Last name</label>
            <div class="bl-auto-field"><span><?php echo htmlspecialchars($donor['last_name']); ?></span><span class="bl-auto-tag">Fixed</span></div>
          </div>
          <div class="bl-field">
            <label>IC / Passport number</label>
            <div class="bl-auto-field"><span><?php echo htmlspecialchars($donor['ic_number']); ?></span><span class="bl-auto-tag">Fixed</span></div>
          </div>
          <div class="bl-field">
            <label>Donor ID</label>
            <div class="bl-auto-field"><span><?php echo htmlspecialchars($donor['donor_id']); ?></span><span class="bl-auto-tag">Auto</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION: Contact Details (editable) -->
    <div class="bl-card" style="margin-bottom:1.25rem">
      <form method="POST" action="manage_profile.php">
        <input type="hidden" name="action" value="update_contact">

        <div class="bl-section">
          <div class="bl-section-title" style="margin-bottom:1.25rem">
            <div class="bl-bar"></div><h3>Contact details</h3>
          </div>
          <div class="bl-grid-2">
            <div class="bl-field">
              <label>Email address <span class="req">*</span></label>
              <input type="email" name="email" placeholder="you@example.com"
                     value="<?php echo htmlspecialchars($donor['email']); ?>">
            </div>
            <div class="bl-field">
              <label>Phone number <span class="req">*</span></label>
              <input type="text" name="phone" placeholder="+60 12-345 6789"
                     value="<?php echo htmlspecialchars($donor['phone'] ?? ''); ?>">
            </div>
          </div>
        </div>

        <div class="bl-footer">
          <div class="bl-footer-note">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="#ccc" stroke-width="1.5"/>
              <path d="M12 8v4M12 16h.01" stroke="#ccc" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            Fields marked <span class="req">&nbsp;*&nbsp;</span> are required
          </div>
          <div class="bl-footer-actions">
            <a href="donor_dashboard.php" class="bl-btn bl-btn-ghost">Discard</a>
            <button type="submit" class="bl-btn bl-btn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Update contact
            </button>
          </div>
        </div>

      </form>
    </div>

    <!-- SECTION: Health Information (editable) -->
    <div class="bl-card">
      <form method="POST" action="manage_profile.php">
        <input type="hidden" name="action" value="update_health">

        <div class="bl-section bl-section-muted">
          <div class="bl-section-title" style="margin-bottom:1.25rem">
            <div class="bl-bar"></div><h3>Health status</h3>
          </div>
          <div class="bl-grid-2">
            <div class="bl-field">
              <label>Blood type <span class="req">*</span></label>
              <select name="blood_type">
                <?php foreach($blood_types as $bt): ?>
                <option value="<?php echo $bt; ?>"
                  <?php echo ($donor['blood_type']===$bt)?'selected':''; ?>><?php echo $bt; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="bl-field">
              <label>Weight (kg)</label>
              <input type="number" name="weight" min="45" max="300"
                     value="<?php echo htmlspecialchars($donor['weight'] ?? ''); ?>"
                     placeholder="e.g. 65">
            </div>
            <div class="bl-field bl-col-2">
              <label>Current health status <span class="req">*</span></label>
              <select name="health_status">
                <?php foreach($health_opts as $opt): ?>
                <option value="<?php echo $opt; ?>"
                  <?php echo ($donor['health_status']===$opt)?'selected':''; ?>><?php echo $health_labels[$opt]; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="bl-footer">
          <div class="bl-footer-note" style="color:var(--text-dim);font-size:12px">
            Updating health status immediately affects your donation eligibility
          </div>
          <div class="bl-footer-actions">
            <a href="donor_dashboard.php" class="bl-btn bl-btn-ghost">Discard</a>
            <button type="submit" class="bl-btn bl-btn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
              </svg>
              Update health info
            </button>
          </div>
        </div>

      </form>
    </div>

  </div><!-- /bl-main -->

<?php include 'footer.php'; ?>
