<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: ../login.php'); exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user = $pdo->prepare("SELECT * FROM bl_users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

// Fetch available events
$events = $pdo->query("SELECT DISTINCT event_name, event_date, event_location FROM bl_slots ORDER BY event_date ASC")->fetchAll();
$selected_event = $_GET['event'] ?? ($events[0]['event_name'] ?? '');

// Fetch slots for selected event
if ($selected_event) {
    $stmt = $pdo->prepare("
        SELECT s.*,
               COUNT(b.id) as booked,
               MAX(CASE WHEN b.donor_id = ? THEN 1 ELSE 0 END) as already_booked,
               MAX(CASE WHEN b.donor_id = ? THEN b.id ELSE NULL END) as my_booking_id,
               MAX(CASE WHEN b.donor_id = ? THEN b.status ELSE NULL END) as my_status
        FROM bl_slots s
        LEFT JOIN bl_bookings b ON s.id = b.slot_id
        WHERE s.event_name = ?
        GROUP BY s.id
        ORDER BY s.start_time ASC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $selected_event]);
    $slots = $stmt->fetchAll();
}

// Fetch donor's booking history
$history = $pdo->prepare("
    SELECT b.*, s.event_name, s.event_date, s.event_location, s.start_time, s.end_time
    FROM bl_bookings b
    JOIN bl_slots s ON b.slot_id = s.id
    WHERE b.donor_id = ?
    ORDER BY b.created_at DESC
");
$history->execute([$user_id]);
$history = $history->fetchAll();

$event_info = $pdo->prepare("SELECT * FROM bl_slots WHERE event_name = ? LIMIT 1");
$event_info->execute([$selected_event]);
$event_info = $event_info->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Donor Dashboard — BloodLink</title>
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
    .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
    .stat-box { background: white; border-radius: 12px; border: 0.5px solid #f5c4c4; padding: 16px 20px; }
    .stat-label { font-size: 12px; color: #aaa; margin-bottom: 4px; }
    .stat-value { font-size: 26px; font-weight: 700; }
    .event-wrap { position: relative; display: inline-block; }
    .event-select { appearance: none; background: white; border: 1.5px solid #f5a0b0; border-radius: 10px; padding: 9px 40px 9px 14px; font-size: 13.5px; font-weight: 600; color: #c94060; min-width: 280px; cursor: pointer; font-family: 'Lato', sans-serif; }
    .event-select:focus { outline: none; border-color: #e85d75; }
    .event-arrow { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #e85d75; }
    .slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .slot-card { background: white; border-radius: 12px; border: 1px solid #f5c4c4; padding: 16px 18px; transition: border-color 0.15s, box-shadow 0.15s; }
    .slot-card.available:hover { border-color: #e85d75; box-shadow: 0 2px 12px rgba(232,93,117,0.1); }
    .slot-card.booked-by-me { border-color: #9fe1cb; background: #f6fdf9; }
    .slot-card.full { opacity: 0.6; }
    .cap-bar { height: 5px; background: #f5e0e0; border-radius: 3px; margin-top: 6px; }
    .cap-fill { height: 100%; background: #e85d75; border-radius: 3px; }
    .btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: 'Lato', sans-serif; text-decoration: none; display: inline-block; transition: opacity 0.15s; }
    .btn:hover { opacity: 0.88; }
    .btn-primary { background: #e85d75; color: white; }
    .btn-booked { background: #e8f7ee; color: #1a7a47; border: 1px solid #9fe1cb; cursor: default; }
    .btn-full { background: #f5f5f5; color: #bbb; cursor: not-allowed; }
    .btn-cancel { background: #fce4e8; color: #c94060; border: 1px solid #f5a0b0; padding: 5px 12px; font-size: 12px; border-radius: 6px; }
    .section-title { font-size: 15px; font-weight: 600; color: #2a2a2a; margin-bottom: 14px; }
    .table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .table th { text-align: left; padding: 8px 12px; color: #aaa; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 0.5px solid #f5c4c4; }
    .table td { padding: 12px; border-bottom: 0.5px solid #fdf0f0; color: #2a2a2a; vertical-align: middle; }
    .table tr:last-child td { border-bottom: none; }
    .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .pill-pending { background: #fff8e6; color: #b07800; }
    .pill-approved { background: #e8f7ee; color: #1a7a47; }
    .pill-rejected { background: #fce4e8; color: #c94060; }
    .alert-success { background: #e8f7ee; color: #1a7a47; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .alert-error { background: #fce4e8; color: #c94060; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .empty { text-align: center; padding: 30px; color: #bbb; font-size: 14px; }
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
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="#">My Profile</a>
      <a href="dashboard.php">Donation Events</a>
      <div class="nav-logout">
        <a href="../logout.php">Logout</a>
      </div>
    </nav>
  </div>

  <div class="main">
    <div class="topbar">
      <div>
        <h1>Good morning, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
        <p>Welcome back to BloodLink</p>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <?php if ($user['blood_type']): ?>
          <span class="badge">Blood Type: <?php echo htmlspecialchars($user['blood_type']); ?></span>
        <?php endif; ?>
        <span class="badge">Donor</span>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stat-grid">
      <div class="stat-box">
        <div class="stat-label">Total Donations</div>
        <div class="stat-value" style="color:#e85d75;">
          <?php
            $total = $pdo->prepare("SELECT COUNT(*) FROM bl_bookings WHERE donor_id = ? AND status = 'approved'");
            $total->execute([$user_id]);
            echo $total->fetchColumn();
          ?>
        </div>
        <div style="font-size:11px;color:#aaa;margin-top:2px;">Lifetime donations</div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Last Donation</div>
        <div class="stat-value" style="color:#e8a030;">
          <?php
            $last = $pdo->prepare("SELECT s.event_date FROM bl_bookings b JOIN bl_slots s ON b.slot_id = s.id WHERE b.donor_id = ? AND b.status = 'approved' ORDER BY s.event_date DESC LIMIT 1");
            $last->execute([$user_id]);
            $last_date = $last->fetchColumn();
            if ($last_date) {
                $diff = (new DateTime())->diff(new DateTime($last_date));
                echo $diff->days . 'd';
            } else {
                echo '—';
            }
          ?>
        </div>
        <div style="font-size:11px;color:#aaa;margin-top:2px;">Days ago</div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Next Eligible</div>
        <div class="stat-value" style="color:#aaa;">
          <?php
            if ($last_date) {
                $next = (new DateTime($last_date))->modify('+90 days');
                $today = new DateTime();
                if ($next > $today) {
                    $days_left = $today->diff($next)->days;
                    echo $days_left . 'd';
                } else {
                    echo 'Now';
                }
            } else {
                echo 'Now';
            }
          ?>
        </div>
        <div style="font-size:11px;color:#aaa;margin-top:2px;">
          <?php echo isset($days_left) && $days_left > 0 ? 'Days remaining' : 'Ready to donate'; ?>
        </div>
      </div>
    </div>

    <!-- Event Selector -->
    <div class="card">
      <p style="font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">Select Event</p>
      <form method="GET">
        <div class="event-wrap">
          <select class="event-select" name="event" onchange="this.form.submit()">
            <option value="">All Events</option>
            <?php foreach ($events as $ev): ?>
              <option value="<?php echo htmlspecialchars($ev['event_name']); ?>" <?php echo $selected_event === $ev['event_name'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($ev['event_name'] . ' — ' . $ev['event_date']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="event-arrow">▾</span>
        </div>
      </form>
      <?php if ($event_info): ?>
        <span style="margin-left:20px;font-size:13px;color:#aaa;">
          <strong style="color:#2a2a2a;"><?php echo htmlspecialchars($event_info['event_date']); ?></strong> &nbsp;·&nbsp;
          <strong style="color:#2a2a2a;"><?php echo htmlspecialchars($event_info['event_location']); ?></strong>
        </span>
      <?php endif; ?>
    </div>

    <!-- Available Slots -->
    <p class="section-title">Upcoming donation events</p>
    <?php if (!empty($slots)): ?>
    <div class="slots-grid">
      <?php foreach ($slots as $slot):
        $pct = $slot['capacity'] > 0 ? min(round(($slot['booked'] / $slot['capacity']) * 100), 100) : 0;
        $full = $slot['booked'] >= $slot['capacity'];
        $my_status = $slot['my_status'] ?? null;
        $card_class = $slot['already_booked'] ? 'booked-by-me' : ($full ? 'full' : 'available');
      ?>
      <div class="slot-card <?php echo $card_class; ?>">
        <div style="font-size:15px;font-weight:700;color:#2a2a2a;"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
        <div style="font-size:12px;color:#aaa;margin-top:3px;"><?php echo $slot['booked']; ?> booked · <?php echo $slot['capacity']; ?> capacity</div>
        <div style="margin-top:8px;">
          <?php if ($slot['already_booked']): ?>
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#5dcaa5;margin-right:5px;"></span>
            <span style="font-size:11px;font-weight:600;color:#1a7a47;">Your booking</span>
          <?php elseif ($full): ?>
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#e85d75;margin-right:5px;"></span>
            <span style="font-size:11px;font-weight:600;color:#c94060;">Full</span>
          <?php else: ?>
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#5dcaa5;margin-right:5px;"></span>
            <span style="font-size:11px;font-weight:600;color:#1a7a47;">Open</span>
          <?php endif; ?>
        </div>
        <div class="cap-bar"><div class="cap-fill" style="width:<?php echo $pct; ?>%"></div></div>
        <div style="margin-top:12px;">
          <?php if ($slot['already_booked']): ?>
            <?php
              $pill = $my_status === 'approved' ? 'pill-approved' : ($my_status === 'rejected' ? 'pill-rejected' : 'pill-pending');
              $label = $my_status === 'approved' ? 'Donated' : ($my_status === 'rejected' ? 'Rejected' : 'Pending');
            ?>
            <span class="pill <?php echo $pill; ?>" style="margin-right:8px;"><?php echo $label; ?></span>
            <?php if ($my_status === 'pending'): ?>
              <a href="book_slot.php?action=cancel&booking_id=<?php echo $slot['my_booking_id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="btn btn-cancel" onclick="return confirm('Cancel your booking?')">Cancel</a>
            <?php endif; ?>
          <?php elseif ($full): ?>
            <button class="btn btn-full" disabled>Slot Full</button>
          <?php else: ?>
            <a href="book_slot.php?action=book&slot_id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="btn btn-primary">Book Slot</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty">No slots available for this event yet.</div>
    <?php endif; ?>

    <!-- My recent donations -->
    <p class="section-title">My recent donations</p>
    <div class="card">
      <?php if ($history): ?>
      <table class="table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Time Slot</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h):
            $pill = $h['status'] === 'approved' ? 'pill-approved' : ($h['status'] === 'rejected' ? 'pill-rejected' : 'pill-pending');
            $label = $h['status'] === 'approved' ? 'Donated' : ($h['status'] === 'rejected' ? ($h['reject_reason'] === 'noshow' ? 'No Show' : 'Not Eligible') : 'Pending');
          ?>
          <tr>
            <td>
              <div style="font-weight:600;"><?php echo htmlspecialchars($h['event_name']); ?></div>
              <div style="font-size:11px;color:#aaa;"><?php echo htmlspecialchars($h['event_location']); ?></div>
            </td>
            <td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($h['event_date']); ?></td>
            <td style="color:#888;font-size:12px;"><?php echo htmlspecialchars($h['start_time'] . ' – ' . $h['end_time']); ?></td>
            <td><span class="pill <?php echo $pill; ?>"><?php echo $label; ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="empty">No donation history yet.</div>
      <?php endif; ?>
    </div>

  </div>
</div>
</body>
</html>