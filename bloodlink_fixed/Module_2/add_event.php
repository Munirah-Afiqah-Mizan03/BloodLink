<?php
// BloodLink — Module 2: Add New Event

require_once 'db.php';

$activePage = 'events';
$pageTitle  = 'Add New Event';
$errors     = [];

$current_user_display = trim($_SESSION['full_name'] ?? '');
if (($_SESSION['role'] ?? '') === 'medical_officer' && $current_user_display && !preg_match('/^dr\b/i', $current_user_display)) {
    $current_user_display = 'Dr. ' . $current_user_display;
}
if (!$current_user_display) $current_user_display = 'System';

// ── HANDLE FORM SUBMIT ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name  = trim($_POST['event_name']  ?? '');
    $event_date  = trim($_POST['event_date']  ?? '');
    $location    = trim($_POST['location']    ?? '');
    $partner     = trim($_POST['partner']     ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status']      ?? 'Upcoming');
    $created_by  = $current_user_display;

    // Validate
    if (!$event_name) $errors[] = 'Event name is required.';
    if (!$event_date) $errors[] = 'Event date is required.';
    if (!$location)   $errors[] = 'Location is required.';

    if (empty($errors)) {
        // Generate next event_id
        $last = $conn->query("SELECT event_id FROM bl_events ORDER BY id DESC LIMIT 1")->fetch_assoc();
        $next_num = $last ? intval(substr($last['event_id'], 3)) + 1 : 1;
        $event_id = 'EV-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);

        $ins = $conn->prepare("INSERT INTO bl_events
            (event_id, event_name, event_date, location, partner, description, status, created_by)
            VALUES (?,?,?,?,?,?,?,?)");
        $ins->bind_param("ssssssss",
            $event_id, $event_name, $event_date, $location,
            $partner, $description, $status, $created_by);

        if ($ins->execute()) {
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Event $event_id created successfully."];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Event Management</a>
      <span class="sep">›</span>
      <span>Add new event</span>
    </div>

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Add new event</h2>
        <p>Fill in the details below to create a new blood donation event</p>
      </div>
      <div class="bl-top-hero-actions">
        <div class="bl-new-badge">
          <div class="bl-new-dot">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none">
              <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
          </div>
          <span>New Event</span>
        </div>
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
    <form method="POST" action="add_event.php">
    <div class="bl-card">

      <!-- Section 1: Event Details -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Event details</h3>
        </div>
        <div class="bl-grid-2">

          <div class="bl-field bl-col-2">
            <label>Event name <span class="req">*</span></label>
            <input type="text" name="event_name" placeholder="e.g. Hospital Putra Blood Drive"
                   value="<?php echo htmlspecialchars($_POST['event_name'] ?? ''); ?>">
          </div>

          <div class="bl-field">
            <label>Event date <span class="req">*</span></label>
            <input type="date" name="event_date"
                   value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
          </div>

          <div class="bl-field">
            <label>Status <span class="req">*</span></label>
            <select name="status">
              <?php foreach(['Upcoming','Completed','Cancelled'] as $s): ?>
              <option value="<?php echo $s; ?>"
                <?php echo (($_POST['status'] ?? 'Upcoming')===$s)?'selected':''; ?>><?php echo $s; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

        </div>
      </div>

      <!-- Section 2: Location & Partner -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>Location &amp; partner</h3>
        </div>
        <div class="bl-grid-2">

          <div class="bl-field">
            <label>Location <span class="req">*</span></label>
            <input type="text" name="location" placeholder="e.g. Hospital Putra, Kota Bharu"
                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
          </div>

          <div class="bl-field">
            <label>Partner organisation <span class="bl-optional">(optional)</span></label>
            <input type="text" name="partner" placeholder="e.g. Hospital Putra Kota Bharu"
                   value="<?php echo htmlspecialchars($_POST['partner'] ?? ''); ?>">
            <p class="bl-hint">Enter the name of the organising partner or sponsor</p>
          </div>

        </div>
      </div>

      <!-- Section 3: Description -->
      <div class="bl-section bl-section-muted">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div>
          <h3>Event description <span class="bl-optional">(optional)</span></h3>
        </div>
        <div class="bl-field">
          <label>Description</label>
          <textarea name="description" rows="4"
                    placeholder="Provide a brief description of this event, target donors, and any special instructions..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
      </div>

      <!-- Section 4: System Info -->
      <div class="bl-section">
        <div class="bl-section-title" style="margin-bottom:1.25rem">
          <div class="bl-bar"></div><h3>System information</h3>
        </div>
        <div class="bl-grid-2">
          <div class="bl-field">
            <label>Created by</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($current_user_display); ?></span><span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Date created</label>
            <div class="bl-auto-field">
              <span><?php echo date('d M Y'); ?></span><span class="bl-auto-tag">Auto</span>
            </div>
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
            Create event
          </button>
        </div>
      </div>

    </div><!-- /bl-card -->
    </form>

  </div><!-- /bl-main -->

<?php include 'footer.php'; ?>
