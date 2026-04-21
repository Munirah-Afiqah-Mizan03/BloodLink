<?php
// BloodLink — Module 1: Donor Registration
require_once __DIR__ . '/../config.php';

// Already logged in → redirect
if (!empty($_SESSION['user_id'])) {
    header('Location: donor_dashboard.php');
    exit;
}

$pageTitle = 'Register as Donor';
$errors    = [];
$success   = false;

// ── HANDLE SUBMIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name']   ?? '');
    $last_name    = trim($_POST['last_name']     ?? '');
    $dob          = trim($_POST['dob']           ?? '');
    $gender       = trim($_POST['gender']        ?? '');
    $ic_number    = trim($_POST['ic_number']     ?? '');
    $address      = trim($_POST['address']       ?? '');
    $city         = trim($_POST['city']          ?? '');
    $postcode     = trim($_POST['postcode']      ?? '');
    $email        = trim($_POST['email']         ?? '');
    $phone        = trim($_POST['phone']         ?? '');
    $password     = $_POST['password']           ?? '';
    $confirm_pass = $_POST['confirm_password']   ?? '';
    $blood_type   = trim($_POST['blood_type']    ?? '');
    $weight       = trim($_POST['weight']        ?? '');
    $health_status= trim($_POST['health_status'] ?? 'Healthy');
    $last_donation= trim($_POST['last_donation'] ?? '');
    $consent      = isset($_POST['consent']);

    // ── VALIDATION ─────────────────────────────────────────────
    if (!$first_name)  $errors[] = 'First name is required.';
    if (!$last_name)   $errors[] = 'Last name is required.';
    if (!$dob)         $errors[] = 'Date of birth is required.';
    elseif (strtotime($dob) >= strtotime('today')) $errors[] = 'Date of birth must be in the past.';
    if (!$ic_number)   $errors[] = 'IC / Passport number is required.';
    if (!$email)       $errors[] = 'Email address is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!$phone)       $errors[] = 'Phone number is required.';
    if (!$blood_type)  $errors[] = 'Blood type is required.';
    if (!$weight || !is_numeric($weight) || $weight < 45)
                       $errors[] = 'Weight must be a number and at least 45 kg to be eligible.';
    if (!$password)    $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    elseif ($password !== $confirm_pass) $errors[] = 'Passwords do not match.';
    if (!$consent)     $errors[] = 'You must consent to data storage to register.';

    // Check duplicate username (email used as username)
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM bl_users WHERE username = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email already exists. Please sign in.';
        }
    }

    // Check duplicate IC
    if (empty($errors)) {
        $chk2 = $conn->prepare("SELECT id FROM bl_donors WHERE ic_number = ? LIMIT 1");
        $chk2->bind_param("s", $ic_number);
        $chk2->execute();
        if ($chk2->get_result()->num_rows > 0) {
            $errors[] = 'A donor with this IC number is already registered.';
        }
    }

    // ── INSERT ─────────────────────────────────────────────────
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $full_name = "$first_name $last_name";

            // 1. bl_users
            $ins_user = $conn->prepare(
                "INSERT INTO bl_users (username, password, full_name, role, blood_type, ic_number)
                 VALUES (?, ?, ?, 'donor', ?, ?)"
            );
            $ins_user->bind_param("sssss", $email, $hashed, $full_name, $blood_type, $ic_number);
            $ins_user->execute();

            // 2. bl_donors — generate donor_id
            $last_row = $conn->query("SELECT donor_id FROM bl_donors ORDER BY id DESC LIMIT 1")->fetch_assoc();
            $next_num = $last_row ? intval(substr($last_row['donor_id'], 2)) + 1 : 1;
            $donor_id = 'D-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

            $ins_donor = $conn->prepare(
                "INSERT INTO bl_donors (donor_id, first_name, last_name, ic_number, phone, blood_type, health_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $ins_donor->bind_param("sssssss",
                $donor_id, $first_name, $last_name, $ic_number,
                $phone, $blood_type, $health_status
            );
            $ins_donor->execute();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$blood_types = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
$health_opts = ['Healthy' => 'Healthy — eligible to donate', 'Under Medication' => 'Under Medication', 'Not Eligible' => 'Currently not eligible'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — BloodLink</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { background:var(--bg); min-height:100vh; }
    .bl-reg-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:1rem 2rem; border-bottom:1px solid var(--border);
      background:var(--bg2);
    }
    .bl-reg-logo { display:flex; align-items:center; gap:10px; }
    .bl-reg-logo-icon { width:32px; height:32px; background:var(--red); border-radius:8px; display:grid; place-items:center; }
    .bl-reg-logo span { font-size:15px; font-weight:700; }
    .bl-reg-body { max-width:860px; margin:2.5rem auto; padding:0 1.5rem 3rem; }
    .bl-reg-body h1 { font-size:20px; font-weight:700; margin-bottom:4px; }
    .bl-reg-body > p { color:var(--text-muted); font-size:13px; margin-bottom:1.75rem; }
    .bl-success-box {
      background:rgba(100,180,120,.08); border:1px solid rgba(100,180,120,.2);
      border-radius:var(--radius); padding:2rem; text-align:center;
    }
    .bl-success-box h3 { color:#7ecf93; font-size:16px; margin-bottom:.5rem; }
    .bl-success-box p  { color:var(--text-muted); font-size:13px; margin-bottom:1.25rem; }
  </style>
</head>
<body>

<!-- Top bar -->
<div class="bl-reg-header">
  <div class="bl-reg-logo">
    <div class="bl-reg-logo-icon">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
        <path d="M12 2C12 2 5 9.5 5 14a7 7 0 0 0 14 0C19 9.5 12 2 12 2z" fill="#fff" opacity=".9"/>
        <path d="M9 14.5a3 3 0 0 0 6 0" stroke="#C0392B" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </div>
    <span>BloodLink</span>
  </div>
  <a href="../login.php" style="color:var(--red-light);font-size:12.5px">Already have an account? Sign in</a>
</div>

<div class="bl-reg-body">
  <h1>Create your donor account</h1>
  <p>Fill in your details below to register as a blood donor on BloodLink</p>

  <?php if ($success): ?>
  <div class="bl-success-box">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="margin-bottom:.75rem">
      <circle cx="12" cy="12" r="10" fill="rgba(100,180,120,.2)"/>
      <path d="M8 12l3 3 5-5" stroke="#7ecf93" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <h3>Registration Successful!</h3>
    <p>Your donor account has been created. You can now sign in with your email and password.</p>
    <a href="../login.php" class="bl-btn bl-btn-primary" style="display:inline-flex">Go to Sign In</a>
  </div>

  <?php else: ?>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
  <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
    <?php foreach($errors as $e): ?>
    <p>⚠ <?php echo htmlspecialchars($e); ?></p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="register.php">
  <div class="bl-card">

    <!-- PERSONAL DETAILS -->
    <div class="bl-section">
      <div class="bl-section-title" style="margin-bottom:1.25rem">
        <div class="bl-bar"></div><h3>Personal details</h3>
      </div>
      <div class="bl-grid-2">

        <div class="bl-field">
          <label>First name <span class="req">*</span></label>
          <input type="text" name="first_name" placeholder="Ahmad"
                 value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Last name <span class="req">*</span></label>
          <input type="text" name="last_name" placeholder="Razali"
                 value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Date of birth <span class="req">*</span></label>
          <input type="date" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Gender</label>
          <select name="gender">
            <option value="">Select</option>
            <?php foreach(['Male','Female','Prefer not to say'] as $g): ?>
            <option value="<?php echo $g; ?>"
              <?php echo (($_POST['gender'] ?? '') === $g) ? 'selected' : ''; ?>><?php echo $g; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="bl-field">
          <label>IC / Passport number <span class="req">*</span></label>
          <input type="text" name="ic_number" placeholder="e.g. 990101-01-1234"
                 value="<?php echo htmlspecialchars($_POST['ic_number'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Address</label>
          <input type="text" name="address" placeholder="No. 12, Jalan Bukit Bintang"
                 value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>City</label>
          <input type="text" name="city" placeholder="Kuala Lumpur"
                 value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Postcode</label>
          <input type="text" name="postcode" placeholder="50450"
                 value="<?php echo htmlspecialchars($_POST['postcode'] ?? ''); ?>">
        </div>

      </div>
    </div>

    <!-- CONTACT & ACCOUNT -->
    <div class="bl-section">
      <div class="bl-section-title" style="margin-bottom:1.25rem">
        <div class="bl-bar"></div><h3>Contact &amp; account</h3>
      </div>
      <div class="bl-grid-2">

        <div class="bl-field">
          <label>Email address <span class="req">*</span></label>
          <input type="email" name="email" placeholder="you@example.com"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          <p class="bl-hint">This will be your login username</p>
        </div>

        <div class="bl-field">
          <label>Phone number <span class="req">*</span></label>
          <input type="text" name="phone" placeholder="+60 12-345 6789"
                 value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>

        <div class="bl-field">
          <label>Password <span class="req">*</span></label>
          <input type="password" name="password" placeholder="Min. 6 characters">
        </div>

        <div class="bl-field">
          <label>Confirm password <span class="req">*</span></label>
          <input type="password" name="confirm_password" placeholder="Repeat password">
        </div>

      </div>
    </div>

    <!-- HEALTH INFORMATION -->
    <div class="bl-section bl-section-muted">
      <div class="bl-section-title" style="margin-bottom:1.25rem">
        <div class="bl-bar"></div><h3>Health information</h3>
      </div>
      <div class="bl-grid-2">

        <div class="bl-field">
          <label>Blood type <span class="req">*</span></label>
          <select name="blood_type">
            <option value="">Select</option>
            <?php foreach($blood_types as $bt): ?>
            <option value="<?php echo $bt; ?>"
              <?php echo (($_POST['blood_type'] ?? '') === $bt) ? 'selected' : ''; ?>><?php echo $bt; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="bl-field">
          <label>Weight (kg) <span class="req">*</span></label>
          <input type="number" name="weight" placeholder="e.g. 65" min="45" max="300"
                 value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
          <p class="bl-hint">Minimum 45 kg required to donate</p>
        </div>

        <div class="bl-field">
          <label>Current health status <span class="req">*</span></label>
          <select name="health_status">
            <?php foreach($health_opts as $val => $label): ?>
            <option value="<?php echo $val; ?>"
              <?php echo (($_POST['health_status'] ?? 'Healthy') === $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="bl-field">
          <label>Last donation date <span class="bl-optional">(if any)</span></label>
          <input type="date" name="last_donation"
                 value="<?php echo htmlspecialchars($_POST['last_donation'] ?? ''); ?>">
          <p class="bl-hint">Leave blank if this is your first donation</p>
        </div>

      </div>
    </div>

    <!-- CONSENT & SUBMIT -->
    <div class="bl-footer">
      <label style="display:flex;align-items:flex-start;gap:9px;cursor:pointer;font-size:12px;color:var(--text-muted);line-height:1.5;max-width:560px">
        <input type="checkbox" name="consent" style="margin-top:2px;accent-color:var(--red);flex-shrink:0"
               <?php echo isset($_POST['consent']) ? 'checked' : ''; ?>>
        I confirm that the information provided is accurate and I consent to BloodLink storing and processing my personal and health data for donor management purposes.
      </label>
      <div class="bl-footer-actions">
        <a href="../login.php" class="bl-btn bl-btn-ghost">Cancel</a>
        <button type="submit" class="bl-btn bl-btn-primary">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
            <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Register as donor
        </button>
      </div>
    </div>

  </div><!-- /bl-card -->
  </form>

  <?php endif; ?>
</div>

</body>
</html>
