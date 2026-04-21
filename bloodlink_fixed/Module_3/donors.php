<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

$activePage = 'attendance';
$pageTitle  = 'Donor List';
$base_url   = '../';

$slot_id   = intval($_GET['slot_id'] ?? 0);
$event_get = $_GET['event'] ?? '';
$filter    = $_GET['filter'] ?? '';

if (!$slot_id) { header('Location: dashboard.php'); exit; }

$slot = $pdo->prepare("SELECT * FROM bl_slots WHERE id = ?");
$slot->execute([$slot_id]);
$slot = $slot->fetch();
if (!$slot) { header('Location: dashboard.php'); exit; }

// Handle approve/reject/reset actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $action     = trim($_POST['action'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');

    if ($booking_id && in_array($action, ['approved', 'rejected', 'pending'])) {
        $stmt = $pdo->prepare("UPDATE bl_bookings SET status=?, reject_reason=? WHERE id=?");
        $stmt->execute([$action, $action === 'rejected' ? $reason : null, $booking_id]);

        if ($action === 'approved') {
            $log = $pdo->prepare("INSERT INTO bl_attendance_log (booking_id, officer_id, action) VALUES (?,?,?)");
            $log->execute([$booking_id, $_SESSION['user_id'], 'approved']);
        } elseif ($action === 'rejected') {
            $log = $pdo->prepare("INSERT INTO bl_attendance_log (booking_id, officer_id, action, reject_reason) VALUES (?,?,?,?)");
            $log->execute([$booking_id, $_SESSION['user_id'], 'rejected', $reason]);
        }
    }
    header("Location: donors.php?slot_id=$slot_id&event=" . urlencode($event_get) . "&filter=$filter");
    exit;
}

// Fetch donors
if ($filter) {
    $stmt = $pdo->prepare("SELECT * FROM bl_bookings WHERE slot_id = ? AND status = ? ORDER BY created_at ASC");
    $stmt->execute([$slot_id, $filter]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM bl_bookings WHERE slot_id = ? ORDER BY created_at ASC");
    $stmt->execute([$slot_id]);
}
$donors = $stmt->fetchAll();

$counts = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(status='pending') as pending,
    SUM(status='approved') as approved,
    SUM(status='rejected') as rejected
    FROM bl_bookings WHERE slot_id = ?");
$counts->execute([$slot_id]);
$counts = $counts->fetch();
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Donor List</h2>
        <p>Attendance Management — Manage donor bookings</p>
      </div>
      <div class="bl-top-hero-actions">
        <span class="bl-badge bl-badge-upcoming">Medical Officer</span>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="bl-notice bl-notice-success" style="margin-bottom:1.25rem"><p><?php echo htmlspecialchars($_GET['success']); ?></p></div>
    <?php endif; ?>

    <!-- Back link -->
    <div class="bl-breadcrumb">
      <a href="dashboard.php?event=<?php echo urlencode($event_get); ?>">← Back to slots</a>
    </div>

    <!-- Slot info card -->
    <div class="bl-card" style="margin-bottom:1.25rem">
      <div class="bl-section" style="padding:1rem 1.25rem;border-bottom:none">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
          <div>
            <div style="font-size:18px;font-weight:700;color:var(--text)"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
            <div style="font-size:13px;color:var(--text-muted);margin-top:3px"><?php echo htmlspecialchars($slot['event_name'] . ' · ' . $slot['event_date'] . ' · ' . $slot['event_location']); ?></div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <div style="text-align:center;border-radius:var(--radius-sm);padding:8px 14px;background:var(--red-soft)"><div style="font-size:18px;font-weight:700;color:var(--red)"><?php echo $counts['total'] ?? 0; ?></div><div style="font-size:10px;color:var(--red)">Booked</div></div>
            <div style="text-align:center;border-radius:var(--radius-sm);padding:8px 14px;background:rgba(217,119,6,.08)"><div style="font-size:18px;font-weight:700;color:#b45309"><?php echo $counts['pending'] ?? 0; ?></div><div style="font-size:10px;color:#b45309">Pending</div></div>
            <div style="text-align:center;border-radius:var(--radius-sm);padding:8px 14px;background:rgba(22,163,74,.08)"><div style="font-size:18px;font-weight:700;color:#15803d"><?php echo $counts['approved'] ?? 0; ?></div><div style="font-size:10px;color:#15803d">Approved</div></div>
            <div style="text-align:center;border-radius:var(--radius-sm);padding:8px 14px;background:var(--red-soft)"><div style="font-size:18px;font-weight:700;color:var(--red)"><?php echo $counts['rejected'] ?? 0; ?></div><div style="font-size:10px;color:var(--red)">Rejected</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Donor table -->
    <div class="bl-card">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
        <span style="font-size:14px;font-weight:600;color:var(--text)">Donor list</span>
        <form method="GET" style="display:inline;">
          <input type="hidden" name="slot_id" value="<?php echo $slot_id; ?>">
          <input type="hidden" name="event" value="<?php echo htmlspecialchars($event_get); ?>">
          <select class="bl-select" name="filter" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="pending" <?php echo $filter==='pending'?'selected':''; ?>>Pending</option>
            <option value="approved" <?php echo $filter==='approved'?'selected':''; ?>>Approved</option>
            <option value="rejected" <?php echo $filter==='rejected'?'selected':''; ?>>Rejected</option>
          </select>
        </form>
      </div>

      <?php if ($donors): ?>
      <div class="bl-table-wrap">
        <table class="bl-table">
          <thead>
            <tr>
              <th>Donor</th>
              <th>IC No.</th>
              <th>Blood Type</th>
              <th>Status</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($donors as $donor):
              $pill = $donor['status'] === 'approved' ? 'bl-badge-completed' : ($donor['status'] === 'rejected' ? 'bl-badge-cancelled' : 'bl-badge-pending');
              $label = $donor['status'] === 'approved' ? 'Donated' : ($donor['status'] === 'rejected' ? ($donor['reject_reason'] === 'noshow' ? 'No Show' : 'Not Eligible') : 'Pending');
            ?>
            <tr>
              <td>
                <div style="font-weight:600"><?php echo htmlspecialchars($donor['donor_name']); ?></div>
              </td>
              <td style="color:var(--text-muted);font-size:12px;font-family:monospace"><?php echo htmlspecialchars($donor['ic_number']); ?></td>
              <td><span class="bl-badge bl-badge-blood"><?php echo htmlspecialchars($donor['blood_type']); ?></span></td>
              <td><span class="bl-badge <?php echo $pill; ?>"><?php echo $label; ?></span></td>
              <td style="text-align:right">
                <?php if ($donor['status'] === 'pending'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="booking_id" value="<?php echo $donor['id']; ?>">
                    <input type="hidden" name="action" value="approved">
                    <button type="submit" class="bl-btn bl-btn-sm bl-btn-vol">Approve</button>
                  </form>
                  <button class="bl-btn bl-btn-sm bl-btn-del" style="margin-left:4px" onclick="openReject(<?php echo $donor['id']; ?>)">Reject</button>
                <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="booking_id" value="<?php echo $donor['id']; ?>">
                    <input type="hidden" name="action" value="pending">
                    <button type="submit" class="bl-btn bl-btn-sm bl-btn-ghost">Reset</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="bl-empty"><p>No donors found<?php echo $filter ? ' with this status' : ' for this slot yet'; ?>.</p></div>
      <?php endif; ?>
    </div>
  </div><!-- /bl-main -->

<!-- Reject Modal -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:100;align-items:center;justify-content:center" id="rejectModal">
  <div style="background:#fff;border-radius:var(--radius);padding:28px;width:360px;border:1px solid var(--border)">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:10px;color:var(--text)">Reject Donor</h2>
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px">Select a reason for rejection:</p>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="booking_id" id="rejectBookingId">
      <input type="hidden" name="action" value="rejected">
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:8px 0;cursor:pointer"><input type="radio" name="reason" value="noshow" required style="accent-color:var(--red)"> No Show (did not attend)</label>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:8px 0;cursor:pointer"><input type="radio" name="reason" value="health" style="accent-color:var(--red)"> Did not pass health check</label>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
        <button type="button" class="bl-btn bl-btn-ghost" onclick="closeReject()">Cancel</button>
        <button type="submit" class="bl-btn bl-btn-danger">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReject(bookingId) {
  document.getElementById('rejectBookingId').value = bookingId;
  document.getElementById('rejectModal').style.display = 'flex';
}
function closeReject() { document.getElementById('rejectModal').style.display = 'none'; }
window.onclick = function(e) { if (e.target === document.getElementById('rejectModal')) closeReject(); }
</script>

<?php $base_url = '../'; include __DIR__ . '/../includes/footer.php'; ?>
