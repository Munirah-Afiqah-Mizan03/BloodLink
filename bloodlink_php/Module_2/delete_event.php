<?php
// BloodLink — Module 2: Delete Event Confirmation
session_start();
require_once 'db.php';

$activePage = 'events';
$pageTitle  = 'Delete Event';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Fetch event
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) { header('Location: index.php'); exit; }

// Volunteer count
$vc = $conn->prepare("SELECT COUNT(*) AS c FROM event_volunteers WHERE event_id=?");
$vc->bind_param("i", $id);
$vc->execute();
$vol_count = $vc->get_result()->fetch_assoc()['c'];

// Donation records count
$rc = $conn->prepare("SELECT COUNT(*) AS c FROM donation_records WHERE event_id=?");
$rc->bind_param("i", $id);
$rc->execute();
$rec_count = $rc->get_result()->fetch_assoc()['c'];

// ── HANDLE CONFIRM DELETE ─────────────────────────────────────
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($rec_count > 0) {
        $errors[] = "Cannot delete: this event has $rec_count linked donation record(s). Please remove or reassign them first.";
    } else {
        $del = $conn->prepare("DELETE FROM events WHERE id = ?");
        $del->bind_param("i", $id);
        if ($del->execute()) {
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Event #{$event['event_id']} has been deleted."];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}

$st_class = [
  'Upcoming'  => 'bl-badge-upcoming',
  'Completed' => 'bl-badge-completed',
  'Cancelled' => 'bl-badge-cancelled',
][$event['status']] ?? 'bl-badge-pending';
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Event Management</a>
      <span class="sep">›</span>
      <span>#<?php echo htmlspecialchars($event['event_id']); ?></span>
      <span class="sep">›</span>
      <span>Delete</span>
    </div>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Delete event</h1>
        <p>Please confirm before permanently removing this event</p>
      </div>
    </div>

    <!-- Errors -->
    <?php if (!empty($errors)): ?>
    <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem;flex-direction:column;align-items:flex-start;gap:4px">
      <?php foreach($errors as $e): ?>
      <p>⚠ <?php echo htmlspecialchars($e); ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Donation records warning -->
    <?php if ($rec_count > 0): ?>
    <div class="bl-notice bl-notice-warn" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#D4A86A" stroke-width="1.5"/>
        <path d="M12 8v4M12 16h.01" stroke="#D4A86A" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p>This event has <strong><?php echo $rec_count; ?></strong> linked donation record(s) and cannot be deleted. Please remove or reassign the records first.</p>
    </div>
    <?php endif; ?>

    <!-- Delete Confirmation Box -->
    <div class="bl-delete-box">

      <!-- Icon & Title -->
      <div class="bl-delete-header">
        <div class="bl-delete-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="#E57373" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M10 11v5M14 11v5" stroke="#E57373" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </div>
        <h2>Delete this event?</h2>
        <p>This action is permanent and cannot be undone. All volunteer assignments for this event will also be removed.</p>
      </div>

      <!-- Event Details -->
      <div class="bl-delete-detail">
        <div class="bl-delete-row">
          <span>Event ID</span>
          <span style="font-family:monospace">#<?php echo htmlspecialchars($event['event_id']); ?></span>
        </div>
        <div class="bl-delete-row">
          <span>Event name</span>
          <span><?php echo htmlspecialchars($event['event_name']); ?></span>
        </div>
        <div class="bl-delete-row">
          <span>Date</span>
          <span><?php echo date('d M Y', strtotime($event['event_date'])); ?></span>
        </div>
        <div class="bl-delete-row">
          <span>Location</span>
          <span><?php echo htmlspecialchars($event['location']); ?></span>
        </div>
        <div class="bl-delete-row">
          <span>Status</span>
          <span><span class="bl-badge <?php echo $st_class; ?>"><?php echo $event['status']; ?></span></span>
        </div>
        <div class="bl-delete-row">
          <span>Volunteers assigned</span>
          <span><?php echo $vol_count; ?> volunteer<?php echo $vol_count!=1?'s':''; ?></span>
        </div>
        <div class="bl-delete-row">
          <span>Donation records</span>
          <span><?php echo $rec_count; ?> record<?php echo $rec_count!=1?'s':''; ?>
            <?php if($rec_count>0): ?><span style="color:var(--red-light);font-size:10px;margin-left:4px">⚠ Blocked</span><?php endif; ?>
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="bl-delete-footer">
        <a href="index.php" class="bl-btn bl-btn-ghost">Cancel</a>
        <?php if ($rec_count === 0): ?>
        <form method="POST" action="delete_event.php?id=<?php echo $id; ?>" style="display:inline">
          <button type="submit" name="confirm_delete" value="1" class="bl-btn bl-btn-danger">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Yes, delete event
          </button>
        </form>
        <?php else: ?>
        <button class="bl-btn bl-btn-danger" disabled style="opacity:.4;cursor:not-allowed">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Delete blocked
        </button>
        <?php endif; ?>
      </div>

    </div><!-- /bl-delete-box -->

  </div><!-- /bl-main -->

<?php include 'footer.php'; ?>
