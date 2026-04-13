<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Donor List — BloodLink</title>
  <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Lato', sans-serif; background: #fff5f5; }
    .app { display: flex; min-height: 100vh; }
    .sidebar { width: 210px; background: #fff; border-right: 0.5px solid #f5c4c4; padding: 24px 0; display: flex; flex-direction: column; flex-shrink: 0; position: fixed; height: 100vh; }
    .logo { display: flex; align-items: center; gap: 8px; padding: 0 20px 28px; }
    .logo-icon { width: 28px; height: 28px; background: #e85d75; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .logo-text { font-size: 17px; font-weight: 700; color: #c94060; }
    .nav a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; font-size: 13.5px; color: #888; text-decoration: none; transition: background 0.15s; }
    .nav a:hover { background: #fff0f2; color: #c94060; }
    .nav a.active { background: #fff0f2; color: #c94060; font-weight: 600; border-right: 3px solid #e85d75; }
    .nav-logout { margin-top: auto; border-top: 0.5px solid #f5c4c4; padding-top: 16px; }
    .main { flex: 1; padding: 32px 36px; margin-left: 210px; }
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
    .topbar h1 { font-size: 22px; font-weight: 700; color: #2a2a2a; }
    .topbar p { font-size: 13px; color: #aaa; margin-top: 2px; }
    .badge { background: #fce4e8; color: #c94060; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 600; }
    .card { background: white; border-radius: 14px; border: 0.5px solid #f5c4c4; padding: 20px 22px; margin-bottom: 16px; }
    .back-btn { display: inline-flex; align-items: center; gap: 6px; color: #c94060; font-size: 13px; font-weight: 600; text-decoration: none; margin-bottom: 16px; }
    .back-btn:hover { opacity: 0.75; }
    .stat-grid { display: flex; gap: 10px; flex-wrap: wrap; }
    .stat-box { text-align: center; border-radius: 10px; padding: 10px 18px; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table th { text-align: left; padding: 8px 12px; color: #aaa; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 0.5px solid #f5c4c4; }
    .table td { padding: 12px; border-bottom: 0.5px solid #fdf0f0; color: #2a2a2a; vertical-align: middle; }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover td { background: #fffafa; }
    .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .pill-pending { background: #fff8e6; color: #b07800; }
    .pill-approved { background: #e8f7ee; color: #1a7a47; }
    .pill-rejected { background: #fce4e8; color: #c94060; }
    .btn { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; font-family: 'Lato', sans-serif; text-decoration: none; display: inline-block; }
    .btn-approve { background: #e8f7ee; color: #1a7a47; border: 1px solid #9fe1cb; }
    .btn-reject-open { background: #fce4e8; color: #c94060; border: 1px solid #f5a0b0; }
    .btn-reset { background: #f5f5f5; color: #888; border: 1px solid #ddd; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .form-input { padding: 6px 10px; border: 1px solid #f5c4c4; border-radius: 7px; font-size: 12px; font-family: 'Lato', sans-serif; background: #fffafa; color: #2a2a2a; outline: none; }
    .form-input:focus { border-color: #e85d75; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 100; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 16px; padding: 28px; width: 360px; border: 0.5px solid #f5c4c4; }
    .modal h2 { font-size: 16px; font-weight: 700; margin-bottom: 10px; color: #2a2a2a; }
    .reject-option { display: flex; align-items: center; gap: 8px; font-size: 13px; margin: 8px 0; cursor: pointer; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-primary { background: #e85d75; color: white; padding: 8px 18px; border-radius: 8px; font-size: 13px; }
    .btn-outline { background: white; color: #c94060; border: 1px solid #f5a0b0; padding: 8px 18px; border-radius: 8px; font-size: 13px; }
    .empty { text-align: center; padding: 40px; color: #bbb; font-size: 14px; }
    .alert-success { background: #e8f7ee; color: #1a7a47; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
  </style>
</head>
<body>
<div class="app">
  <div class="sidebar">
    <div class="logo">
      <div class="logo-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/></svg></div>
      <span class="logo-text">BloodLink</span>
    </div>
    <nav class="nav">
      <a href="#">Dashboard</a>
      <a href="#">My Profile</a>
      <a href="#">Donation Events</a>
      <a href="dashboard.php" class="active">Attendance</a>
      <div class="nav-logout">
        <a href="../logout.php">Logout</a>
      </div>
    </nav>
  </div>

  <div class="main">
    <div class="topbar">
      <div>
        <h1>Good morning, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <p>Attendance Management — Donor List</p>
      </div>
      <span class="badge">Medical Officer</span>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <a href="dashboard.php?event=<?php echo urlencode($event_get); ?>" class="back-btn">&#8592; Back to slots</a>

    <div class="card" style="margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
        <div>
          <div style="font-size:18px;font-weight:700;color:#2a2a2a;"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
          <div style="font-size:13px;color:#aaa;margin-top:3px;"><?php echo htmlspecialchars($slot['event_name'] . ' · ' . $slot['event_date'] . ' · ' . $slot['event_location']); ?></div>
        </div>
        <div class="stat-grid">
          <div class="stat-box" style="background:#fce4e8;"><div style="font-size:18px;font-weight:700;color:#c94060;"><?php echo $counts['total'] ?? 0; ?></div><div style="font-size:10px;color:#c94060;">Booked</div></div>
          <div class="stat-box" style="background:#fff8e6;"><div style="font-size:18px;font-weight:700;color:#b07800;"><?php echo $counts['pending'] ?? 0; ?></div><div style="font-size:10px;color:#b07800;">Pending</div></div>
          <div class="stat-box" style="background:#e8f7ee;"><div style="font-size:18px;font-weight:700;color:#1a7a47;"><?php echo $counts['approved'] ?? 0; ?></div><div style="font-size:10px;color:#1a7a47;">Approved</div></div>
          <div class="stat-box" style="background:#fce4e8;"><div style="font-size:18px;font-weight:700;color:#c94060;"><?php echo $counts['rejected'] ?? 0; ?></div><div style="font-size:10px;color:#c94060;">Rejected</div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="section-header">
        <span style="font-size:15px;font-weight:600;color:#2a2a2a;">Donor list</span>
        <form method="GET" style="display:inline;">
          <input type="hidden" name="slot_id" value="<?php echo $slot_id; ?>">
          <input type="hidden" name="event" value="<?php echo htmlspecialchars($event_get); ?>">
          <select class="form-input" name="filter" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="pending" <?php echo $filter==='pending'?'selected':''; ?>>Pending</option>
            <option value="approved" <?php echo $filter==='approved'?'selected':''; ?>>Approved</option>
            <option value="rejected" <?php echo $filter==='rejected'?'selected':''; ?>>Rejected</option>
          </select>
        </form>
      </div>

      <?php if ($donors): ?>
      <table class="table">
        <thead>
          <tr>
            <th>Donor</th>
            <th>IC No.</th>
            <th>Blood Type</th>
            <th>Status</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donors as $donor):
            $pill = $donor['status'] === 'approved' ? 'pill-approved' : ($donor['status'] === 'rejected' ? 'pill-rejected' : 'pill-pending');
            $label = $donor['status'] === 'approved' ? 'Donated' : ($donor['status'] === 'rejected' ? ($donor['reject_reason'] === 'noshow' ? 'No Show' : 'Not Eligible') : 'Pending');
          ?>
          <tr>
            <td>
              <div style="font-weight:600;"><?php echo htmlspecialchars($donor['donor_name']); ?></div>
              <div style="font-size:11px;color:#bbb;"><?php echo htmlspecialchars($donor['ic_number']); ?></div>
            </td>
            <td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($donor['ic_number']); ?></td>
            <td><span class="pill" style="background:#fce4e8;color:#c94060;"><?php echo htmlspecialchars($donor['blood_type']); ?></span></td>
            <td><span class="pill <?php echo $pill; ?>"><?php echo $label; ?></span></td>
            <td style="text-align:right;">
              <?php if ($donor['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="booking_id" value="<?php echo $donor['id']; ?>">
                  <input type="hidden" name="action" value="approved">
                  <button type="submit" class="btn btn-approve">Approve</button>
                </form>
                <button class="btn btn-reject-open" style="margin-left:6px;" onclick="openReject(<?php echo $donor['id']; ?>)">Reject</button>
              <?php else: ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="booking_id" value="<?php echo $donor['id']; ?>">
                  <input type="hidden" name="action" value="pending">
                  <button type="submit" class="btn btn-reset">Reset</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty">No donors found<?php echo $filter ? ' with this status' : ' for this slot yet'; ?>.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <h2>Reject Donor</h2>
    <p style="font-size:13px;color:#888;margin-bottom:10px;">Select a reason for rejection:</p>
    <form method="POST" id="rejectForm">
      <input type="hidden" name="booking_id" id="rejectBookingId">
      <input type="hidden" name="action" value="rejected">
      <label class="reject-option"><input type="radio" name="reason" value="noshow" required> No Show (did not attend)</label>
      <label class="reject-option"><input type="radio" name="reason" value="health"> Did not pass health check</label>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeReject()">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background:#c94060;">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReject(bookingId) {
  document.getElementById('rejectBookingId').value = bookingId;
  document.getElementById('rejectModal').classList.add('open');
}
function closeReject() {
  document.getElementById('rejectModal').classList.remove('open');
}
window.onclick = function(e) {
  if (e.target === document.getElementById('rejectModal')) closeReject();
}
</script>
</body>
</html>