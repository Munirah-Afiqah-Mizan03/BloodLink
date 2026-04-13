<?php
// BloodLink — Module 2: Assign Volunteers to Event
session_start();
require_once 'db.php';

$activePage = 'events';
$pageTitle  = 'Assign Volunteers';
$errors     = [];
$success    = false;

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Fetch event
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) { header('Location: index.php'); exit; }

// ── HANDLE REMOVE VOLUNTEER ───────────────────────────────────
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    $rem = $conn->prepare("DELETE FROM event_volunteers WHERE id = ? AND event_id = ?");
    $rem->bind_param("ii", $remove_id, $id);
    $rem->execute();
    $_SESSION['flash_vol'] = ['type'=>'success', 'msg'=>'Volunteer removed successfully.'];
    header("Location: assign_volunteers.php?id=$id");
    exit;
}

// ── HANDLE ASSIGN ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vol_name    = trim($_POST['vol_name']    ?? '');
    $vol_role    = trim($_POST['vol_role']    ?? '');
    $assigned_by = 'Dr. Siti Aminah';

    if (!$vol_name) $errors[] = 'Volunteer name is required.';
    if (!$vol_role) $errors[] = 'Please select a role.';

    if (empty($errors)) {
        // Find volunteer by name (partial match)
        $vq = $conn->prepare("SELECT id, volunteer_id, full_name FROM volunteers WHERE full_name LIKE ? LIMIT 1");
        $like = "%$vol_name%";
        $vq->bind_param("s", $like);
        $vq->execute();
        $vol = $vq->get_result()->fetch_assoc();

        if (!$vol) {
            $errors[] = "Volunteer \"$vol_name\" not found. Please check the name and try again.";
        } else {
            // Check for duplicate assignment
            $dup = $conn->prepare("SELECT id FROM event_volunteers WHERE event_id=? AND volunteer_id=?");
            $dup->bind_param("ii", $id, $vol['id']);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $errors[] = "{$vol['full_name']} is already assigned to this event.";
            } else {
                $ins = $conn->prepare("INSERT INTO event_volunteers (event_id, volunteer_id, role, assigned_by) VALUES (?,?,?,?)");
                $ins->bind_param("iiss", $id, $vol['id'], $vol_role, $assigned_by);
                if ($ins->execute()) {
                    $success = true;
                    $_SESSION['flash_vol'] = ['type'=>'success', 'msg'=>"{$vol['full_name']} assigned as {$vol_role}."];
                    header("Location: assign_volunteers.php?id=$id");
                    exit;
                } else {
                    $errors[] = 'Database error: ' . $conn->error;
                }
            }
        }
    }
}

// Fetch currently assigned volunteers
$assigned_stmt = $conn->prepare("SELECT ev.id AS assign_id, v.full_name, v.volunteer_id AS vol_code,
    v.phone, ev.role, ev.assigned_by, ev.assigned_at
    FROM event_volunteers ev
    JOIN volunteers v ON ev.volunteer_id = v.id
    WHERE ev.event_id = ?
    ORDER BY ev.assigned_at DESC");
$assigned_stmt->bind_param("i", $id);
$assigned_stmt->execute();
$assigned = $assigned_stmt->get_result();

// All volunteers for autocomplete hint
$all_vols = $conn->query("SELECT full_name, volunteer_id FROM volunteers ORDER BY full_name");

// Flash from redirect
$flash_vol = $_SESSION['flash_vol'] ?? null;
unset($_SESSION['flash_vol']);

$st_class = [
  'Upcoming'  => 'bl-badge-upcoming',
  'Completed' => 'bl-badge-completed',
  'Cancelled' => 'bl-badge-cancelled',
][$event['status']] ?? 'bl-badge-pending';

$roles = ['Registration Desk','Medical Assistant','Blood Collection','Refreshment','Logistics','Coordinator'];
?>
<?php include 'sidebar.php'; ?>

  <div class="bl-main">

    <!-- Breadcrumb -->
    <div class="bl-breadcrumb">
      <a href="index.php">Event Management</a>
      <span class="sep">›</span>
      <span>#<?php echo htmlspecialchars($event['event_id']); ?></span>
      <span class="sep">›</span>
      <span>Assign volunteers</span>
    </div>

    <!-- Header -->
    <div class="bl-page-header">
      <div>
        <h1>Assign volunteers</h1>
        <p>Manage volunteer team for <strong style="color:#E57373"><?php echo htmlspecialchars($event['event_name']); ?></strong></p>
      </div>
      <div class="bl-status-badge">
        <span>Event status:</span>
        <span class="bl-badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($event['status']); ?></span>
      </div>
    </div>

    <!-- Flash -->
    <?php if ($flash_vol): ?>
    <div class="bl-notice bl-notice-<?php echo $flash_vol['type']; ?>" style="margin-bottom:1.25rem">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <circle cx="12" cy="12" r="9" stroke="#7ecf93" stroke-width="1.5"/>
        <path d="M9 12l2 2 4-4" stroke="#7ecf93" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <p><?php echo htmlspecialchars($flash_vol['msg']); ?></p>
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

    <div style="display:grid;grid-template-columns:1fr 380px;gap:1.25rem;align-items:start">

      <!-- LEFT: Assigned volunteers list -->
      <div class="bl-card">
        <div class="bl-section">
          <div class="bl-section-hd">
            <div class="bl-section-title">
              <div class="bl-bar"></div>
              <h3>Assigned volunteers</h3>
            </div>
            <span style="font-size:11px;color:#aaa"><?php echo $assigned->num_rows; ?> assigned</span>
          </div>

          <div style="margin-top:1rem">
            <?php if ($assigned->num_rows === 0): ?>
            <div class="bl-empty" style="padding:2rem">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                <circle cx="9" cy="7" r="4" stroke="#555" stroke-width="1.5"/>
                <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="#555" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M16 11l2 2 4-4" stroke="#555" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <p>No volunteers assigned yet</p>
              <small>Use the form on the right to add volunteers</small>
            </div>
            <?php else: ?>
            <div class="bl-volunteer-list">
              <?php while($v = $assigned->fetch_assoc()): ?>
              <?php
                $parts = explode(' ', $v['full_name']);
                $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[count($parts)-1])?substr($parts[count($parts)-1],0,1):''));
              ?>
              <div class="bl-vol-item">
                <div class="bl-vol-avatar"><?php echo $initials; ?></div>
                <div class="bl-vol-info">
                  <strong><?php echo htmlspecialchars($v['full_name']); ?></strong>
                  <small><?php echo htmlspecialchars($v['vol_code']); ?>
                    <?php if($v['phone']): ?>&nbsp;·&nbsp;<?php echo htmlspecialchars($v['phone']); ?><?php endif; ?>
                  </small>
                </div>
                <span class="bl-vol-role"><?php echo htmlspecialchars($v['role']); ?></span>
                <a href="assign_volunteers.php?id=<?php echo $id; ?>&remove=<?php echo $v['assign_id']; ?>"
                   class="bl-vol-remove bl-vol-remove-btn"
                   title="Remove volunteer"
                   onclick="return confirm('Remove <?php echo htmlspecialchars(addslashes($v['full_name'])); ?> from this event?')">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                </a>
              </div>
              <?php endwhile; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- RIGHT: Assign form -->
      <div>
        <div class="bl-card">
          <div class="bl-section">
            <div class="bl-section-title" style="margin-bottom:1.25rem">
              <div class="bl-bar"></div><h3>Add volunteer</h3>
            </div>

            <form method="POST" action="assign_volunteers.php?id=<?php echo $id; ?>">
              <div class="bl-field" style="margin-bottom:.875rem">
                <label>Volunteer name <span class="req">*</span></label>
                <input type="text" name="vol_name"
                       list="vol-list"
                       placeholder="Type or select volunteer name"
                       value="<?php echo htmlspecialchars($_POST['vol_name'] ?? ''); ?>"
                       autocomplete="off">
                <datalist id="vol-list">
                  <?php
                  $all_vols->data_seek(0);
                  while($av = $all_vols->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($av['full_name']); ?>">
                    <?php echo htmlspecialchars($av['volunteer_id']); ?>
                  </option>
                  <?php endwhile; ?>
                </datalist>
                <p class="bl-hint">Start typing to search registered volunteers</p>
              </div>

              <div class="bl-field" style="margin-bottom:1.25rem">
                <label>Role <span class="req">*</span></label>
                <select name="vol_role">
                  <option value="">Select a role</option>
                  <?php foreach($roles as $r): ?>
                  <option value="<?php echo $r; ?>"
                    <?php echo (($_POST['vol_role'] ?? '')===$r)?'selected':''; ?>><?php echo $r; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <button type="submit" class="bl-btn bl-btn-primary" style="width:100%;justify-content:center">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                  <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Assign volunteer
              </button>
            </form>
          </div>
        </div>

        <!-- Registered volunteers hint -->
        <div class="bl-card" style="margin-top:0">
          <div class="bl-section">
            <div class="bl-section-title" style="margin-bottom:1rem">
              <div class="bl-bar bl-bar-grey"></div>
              <h3 style="font-size:12px;color:var(--text-muted)">Registered volunteers</h3>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
              <?php
              $all_vols->data_seek(0);
              while($av = $all_vols->fetch_assoc()):
                $p = explode(' ', $av['full_name']);
                $ini = strtoupper(substr($p[0],0,1) . (isset($p[count($p)-1])?substr($p[count($p)-1],0,1):''));
              ?>
              <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)">
                <div class="bl-vol-avatar" style="width:24px;height:24px;font-size:9px"><?php echo $ini; ?></div>
                <div style="flex:1">
                  <div style="font-size:12px;font-weight:500"><?php echo htmlspecialchars($av['full_name']); ?></div>
                  <div style="font-size:10px;color:var(--text-muted)"><?php echo htmlspecialchars($av['volunteer_id']); ?></div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>

        <!-- Back button -->
        <a href="index.php" class="bl-btn bl-btn-ghost" style="width:100%;justify-content:center;margin-top:0">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
            <path d="M19 12H5M12 5l-7 7 7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Back to events
        </a>
      </div>

    </div><!-- /grid -->

  </div><!-- /bl-main -->

<?php include 'footer.php'; ?>
