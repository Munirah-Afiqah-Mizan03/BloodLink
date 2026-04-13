<?php
require_once '../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

$events = $pdo->query("SELECT DISTINCT event_name, event_date, event_location FROM bl_slots ORDER BY event_date ASC")->fetchAll();
$selected_event = $_GET['event'] ?? ($events[0]['event_name'] ?? '');

if ($selected_event) {
    $stmt = $pdo->prepare("SELECT s.*, COUNT(b.id) as booked FROM bl_slots s LEFT JOIN bl_bookings b ON s.id = b.slot_id WHERE s.event_name = ? GROUP BY s.id ORDER BY s.start_time ASC");
    $stmt->execute([$selected_event]);
} else {
    $stmt = $pdo->query("SELECT s.*, COUNT(b.id) as booked FROM bl_slots s LEFT JOIN bl_bookings b ON s.id = b.slot_id GROUP BY s.id ORDER BY s.start_time ASC");
}
$slots = $stmt->fetchAll();

$event_info = $pdo->prepare("SELECT * FROM bl_slots WHERE event_name = ? LIMIT 1");
$event_info->execute([$selected_event]);
$event_info = $event_info->fetch();

try {
    $all_events = $pdo->query("SELECT id, event_id, event_name, event_date, location FROM bl_events WHERE status = 'Upcoming' ORDER BY event_date ASC")->fetchAll();
} catch (Exception $e) {
    $all_events = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medical Officer Dashboard — BloodLink</title>
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
    .event-wrap { position: relative; display: inline-block; }
    .event-select { appearance: none; background: white; border: 1.5px solid #f5a0b0; border-radius: 10px; padding: 9px 40px 9px 14px; font-size: 13.5px; font-weight: 600; color: #c94060; min-width: 280px; cursor: pointer; font-family: 'Lato', sans-serif; }
    .event-select:focus { outline: none; border-color: #e85d75; }
    .event-arrow { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; color: #e85d75; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .section-title { font-size: 15px; font-weight: 600; color: #2a2a2a; }
    .slots-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; margin-bottom: 24px; }
    .slot-card { background: white; border-radius: 12px; border: 1px solid #f5c4c4; padding: 16px 18px; position: relative; transition: border-color 0.15s, box-shadow 0.15s; }
    .slot-card:hover { border-color: #e85d75; box-shadow: 0 2px 12px rgba(232,93,117,0.1); }
    .slot-card a.slot-link { text-decoration: none; display: block; }
    .slot-actions { position: absolute; top: 12px; right: 12px; display: flex; gap: 6px; opacity: 0; transition: opacity 0.15s; }
    .slot-card:hover .slot-actions { opacity: 1; }
    .cap-bar { height: 5px; background: #f5e0e0; border-radius: 3px; margin-top: 6px; }
    .cap-fill { height: 100%; background: #e85d75; border-radius: 3px; }
    .btn { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: 'Lato', sans-serif; text-decoration: none; display: inline-block; transition: opacity 0.15s; }
    .btn:hover { opacity: 0.88; }
    .btn-primary { background: #e85d75; color: white; }
    .btn-edit { background: #fff8e6; color: #b07800; border: 1px solid #fac775; padding: 5px 12px; font-size: 12px; border-radius: 6px; }
    .btn-delete { background: #fce4e8; color: #c94060; border: 1px solid #f5a0b0; padding: 5px 12px; font-size: 12px; border-radius: 6px; }
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 100; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal { background: white; border-radius: 16px; padding: 28px; width: 440px; max-width: 95vw; border: 0.5px solid #f5c4c4; }
    .modal h2 { font-size: 16px; font-weight: 700; margin-bottom: 18px; color: #2a2a2a; }
    .form-group { margin-bottom: 14px; }
    .form-label { font-size: 12px; font-weight: 600; color: #888; margin-bottom: 5px; display: block; }
    .form-input { width: 100%; padding: 9px 12px; border: 1px solid #f5c4c4; border-radius: 8px; font-size: 13px; background: #fffafa; font-family: 'Lato', sans-serif; color: #2a2a2a; outline: none; }
    .form-input:focus { border-color: #e85d75; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-outline { background: white; color: #c94060; border: 1px solid #f5a0b0; }
    .empty { text-align: center; padding: 40px; color: #bbb; font-size: 14px; }
    .alert-success { background: #e8f7ee; color: #1a7a47; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .alert-error { background: #fce4e8; color: #c94060; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
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
        <p>Attendance Management — Booking Slots</p>
      </div>
      <span class="badge">Medical Officer</span>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="card">
      <p style="font-size:11px;color:#aaa;font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">Select Event</p>
      <form method="GET" style="display:inline;">
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

    <div class="section-header">
      <span class="section-title">Booking Slots</span>
      <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Slot</button>
    </div>

    <?php if ($slots): ?>
    <div class="slots-grid">
      <?php foreach ($slots as $slot):
        $pct = $slot['capacity'] > 0 ? min(round(($slot['booked'] / $slot['capacity']) * 100), 100) : 0;
        $full = $slot['booked'] >= $slot['capacity'];
        $pending = $pdo->prepare("SELECT COUNT(*) FROM bl_bookings WHERE slot_id = ? AND status = 'pending'");
        $pending->execute([$slot['id']]);
        $pending = $pending->fetchColumn();
      ?>
      <div class="slot-card">
        <div class="slot-actions">
          <button class="btn btn-edit" onclick="openEditModal(
            <?php echo (int)$slot['id']; ?>,
            <?php echo (int)($slot['event_id'] ?? 0); ?>,
            '<?php echo addslashes($slot['start_time']); ?>',
            '<?php echo addslashes($slot['end_time']); ?>',
            <?php echo (int)$slot['capacity']; ?>
          )">Edit</button>
          <a href="delete_slot.php?id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="btn btn-delete" onclick="return confirm('Delete this slot?')">Delete</a>
        </div>
        <a href="donors.php?slot_id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="slot-link">
          <div style="font-size:15px;font-weight:700;color:#2a2a2a;"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
          <div style="font-size:12px;color:#aaa;margin-top:3px;"><?php echo $slot['booked']; ?> booked · <?php echo $slot['capacity']; ?> capacity</div>
          <div style="margin-top:8px;">
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?php echo $full ? '#e85d75' : '#5dcaa5'; ?>;margin-right:5px;"></span>
            <span style="font-size:11px;font-weight:600;color:<?php echo $full ? '#c94060' : '#1a7a47'; ?>"><?php echo $full ? 'Full' : 'Open'; ?></span>
          </div>
          <div class="cap-bar"><div class="cap-fill" style="width:<?php echo $pct; ?>%"></div></div>
          <div style="margin-top:8px;font-size:11px;color:#ccc;"><?php echo $pending; ?> pending approval</div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty">No slots yet. Click + Add Slot to create one.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Slot Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <h2>Add New Slot</h2>
    <form method="POST" action="save_slot.php">
      <input type="hidden" name="event_get" value="<?php echo htmlspecialchars($selected_event); ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Time</label>
          <input class="form-input" type="time" name="start_time" required>
        </div>
        <div class="form-group">
          <label class="form-label">End Time</label>
          <input class="form-input" type="time" name="end_time" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Capacity</label>
        <input class="form-input" type="number" name="capacity" value="5" min="1" required>
      </div>
      <div class="form-group">
        <label class="form-label">Event</label>
        <select class="form-input" name="event_id" required>
          <option value="">— Select an event —</option>
          <?php foreach ($all_events as $ev): ?>
            <option value="<?php echo (int)$ev['id']; ?>">
              <?php echo htmlspecialchars($ev['event_name'] . ' — ' . $ev['event_date']); ?>
            </option>
          <?php endforeach; ?>
          <?php if (empty($all_events)): ?>
            <option value="" disabled>No upcoming events found</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Slot</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Slot Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h2>Edit Slot</h2>
    <form method="POST" action="save_slot.php">
      <input type="hidden" name="slot_id" id="edit_slot_id">
      <input type="hidden" name="event_get" value="<?php echo htmlspecialchars($selected_event); ?>">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Time</label>
          <input class="form-input" type="time" name="start_time" id="edit_start_time" required>
        </div>
        <div class="form-group">
          <label class="form-label">End Time</label>
          <input class="form-input" type="time" name="end_time" id="edit_end_time" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Capacity</label>
        <input class="form-input" type="number" name="capacity" id="edit_capacity" min="1" required>
      </div>
      <div class="form-group">
        <label class="form-label">Event</label>
        <select class="form-input" name="event_id" id="edit_event_id" required>
          <option value="">— Select an event —</option>
          <?php foreach ($all_events as $ev): ?>
            <option value="<?php echo (int)$ev['id']; ?>">
              <?php echo htmlspecialchars($ev['event_name'] . ' — ' . $ev['event_date']); ?>
            </option>
          <?php endforeach; ?>
          <?php if (empty($all_events)): ?>
            <option value="" disabled>No upcoming events found</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
function openEditModal(id, event_id, stime, etime, cap) {
  document.getElementById('edit_slot_id').value    = id;
  document.getElementById('edit_start_time').value = stime;
  document.getElementById('edit_end_time').value   = etime;
  document.getElementById('edit_capacity').value   = cap;
  var sel = document.getElementById('edit_event_id');
  for (var i = 0; i < sel.options.length; i++) {
    if (parseInt(sel.options[i].value) === parseInt(event_id)) {
      sel.selectedIndex = i;
      break;
    }
  }
  openModal('editModal');
}
window.onclick = function(e) {
  ['addModal','editModal'].forEach(function(id) {
    if (e.target === document.getElementById(id)) closeModal(id);
  });
}
</script>
</body>
</html>