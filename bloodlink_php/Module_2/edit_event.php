<?php
// BloodLink — Module 2: Edit Event
session_start();
require_once 'db.php';

$activePage = 'events';
$pageTitle  = 'Edit Event';
$errors     = [];

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Fetch existing event
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) { header('Location: index.php'); exit; }

// ── HANDLE SUBMIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name  = trim($_POST['event_name']  ?? '');
    $event_date  = trim($_POST['event_date']  ?? '');
    $location    = trim($_POST['location']    ?? '');
    $partner     = trim($_POST['partner']     ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status']      ?? '');

    if (!$event_name) $errors[] = 'Event name is required.';
    if (!$event_date) $errors[] = 'Event date is required.';
    if (!$location)   $errors[] = 'Location is required.';

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE events SET
            event_name=?, event_date=?, location=?, partner=?,
            description=?, status=?, updated_at=NOW()
            WHERE id=?");
        $upd->bind_param("ssssssi",
            $event_name, $event_date, $location, $partner,
            $description, $status, $id);

        if ($upd->execute()) {
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Event #{$event['event_id']} updated successfully."];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
    // Keep posted values
    $event = array_merge($event, $_POST);
}

// Volunteer count for this event
$vc = $conn->prepare("SELECT COUNT(*) AS c FROM event_volunteers WHERE event_id=?");
$vc->bind_param("i", $id);
$vc->execute();
$vol_count = $vc->get_result()->fetch_assoc()['c'];
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Event Management</a>
      <span class="sep">›</span>
      <span>#<?php echo htmlspecialchars($event['event_id']); ?></span>
      <span class="sep">›</span>
      <span>Edit event</span>
    </div>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Edit event</h1>
        <p>Update information for event <strong style="color:#E57373">#<?php echo htmlspecialchars($event['event_id']); ?></strong></p>
      </div>
      <div class="bl-status-badge">
        <span>Current status:</span>
        <?php
          $st_class = [
            'Upcoming'  => 'bl-badge-upcoming',
            'Completed' => 'bl-badge-completed',
            'Cancelled' => 'bl-badge-cancelled',
          ][$event['status']] ?? 'bl-badge-pending';
        ?>
        <span class="bl-badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($event['status']); ?></span>
      </div>
    </div>

    <!-- Warning notice -->
    <div class="bl-notice bl-notice-warn" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>Make sure all changes are accurate before saving. This event currently has
         <strong><?php echo $vol_count; ?></strong> volunteer<?php echo $vol_count!=1?'s':''; ?> assigned.</p>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
      <?php foreach($errors as $e): ?>
      <p>⚠ <?php echo htmlspecialchars($e); ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="edit_event.php?id=<?php echo $id; ?>">
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
                   value="<?php echo htmlspecialchars($event['event_name']); ?>">
          </div>

          <div class="bl-field">
            <label>Event date <span class="req">*</span></label>
            <input type="date" name="event_date"
                   value="<?php echo htmlspecialchars($event['event_date']); ?>">
          </div>

          <div class="bl-field">
            <label>Status <span class="req">*</span></label>
            <select name="status">
              <?php foreach(['Upcoming','Completed','Cancelled'] as $s): ?>
              <option value="<?php echo $s; ?>"
                <?php echo ($event['status']===$s)?'selected':''; ?>><?php echo $s; ?></option>
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
                   value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>">
          </div>

          <div class="bl-field">
            <label>Partner organisation <span class="bl-optional">(optional)</span></label>
            <input type="text" name="partner" placeholder="e.g. Hospital Putra Kota Bharu"
                   value="<?php echo htmlspecialchars($event['partner'] ?? ''); ?>">
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
          <textarea name="description" rows="4"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
        </div>
      </div>

      <!-- Section 4: System Info (read-only) -->
      <div class="bl-section">
        <div class="bl-section-hd">
          <div class="bl-section-title"><div class="bl-bar"></div><h3>System information</h3></div>
          <span class="bl-readonly-tag">Read-only</span>
        </div>
        <div class="bl-grid-2" style="margin-top:1rem">
          <div class="bl-field">
            <label>Created by</label>
            <div class="bl-auto-field">
              <span><?php echo htmlspecialchars($event['created_by'] ?? 'Dr. Siti Aminah'); ?></span>
              <span class="bl-auto-tag">Auto</span>
            </div>
          </div>
          <div class="bl-field">
            <label>Date created</label>
            <div class="bl-auto-field">
              <span><?php echo date('d M Y', strtotime($event['created_at'])); ?></span>
              <span class="bl-auto-tag">Auto</span>
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

<?php include 'footer.php'; ?>
