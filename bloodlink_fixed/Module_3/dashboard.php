<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'medical_officer') {
    header('Location: ../login.php'); exit;
}

$activePage = 'attendance';
$pageTitle  = 'Attendance Management';
$base_url   = '../';

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
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Attendance Management</h2>
        <p>Manage booking slots and donor attendance</p>
      </div>
      <div class="bl-top-hero-actions">
        <span class="bl-badge bl-badge-upcoming">Medical Officer</span>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="bl-notice bl-notice-success" style="margin-bottom:1.25rem"><p><?php echo htmlspecialchars($_GET['success']); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem"><p><?php echo htmlspecialchars($_GET['error']); ?></p></div>
    <?php endif; ?>

    <!-- Event Selector -->
    <div class="bl-card">
      <div class="bl-section" style="padding:1rem 1.25rem">
        <p style="font-size:11px;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">Select Event</p>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <form method="GET" style="display:inline;">
            <select class="bl-select" name="event" onchange="this.form.submit()" style="min-width:280px">
              <option value="">All Events</option>
              <?php foreach ($events as $ev): ?>
                <option value="<?php echo htmlspecialchars($ev['event_name']); ?>" <?php echo $selected_event === $ev['event_name'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ev['event_name'] . ' — ' . $ev['event_date']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($event_info): ?>
            <span style="font-size:13px;color:var(--text-muted);">
              <strong style="color:var(--text)"><?php echo htmlspecialchars($event_info['event_date']); ?></strong> &nbsp;·&nbsp;
              <strong style="color:var(--text)"><?php echo htmlspecialchars($event_info['event_location']); ?></strong>
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Slots Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <p style="font-size:14px;font-weight:600;color:var(--text)">Booking Slots</p>
      <button class="bl-btn bl-btn-primary" onclick="openModal('addModal')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
        Add Slot
      </button>
    </div>

    <?php if ($slots): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:1.5rem">
      <?php foreach ($slots as $slot):
        $pct = $slot['capacity'] > 0 ? min(round(($slot['booked'] / $slot['capacity']) * 100), 100) : 0;
        $full = $slot['booked'] >= $slot['capacity'];
        $pending = $pdo->prepare("SELECT COUNT(*) FROM bl_bookings WHERE slot_id = ? AND status = 'pending'");
        $pending->execute([$slot['id']]);
        $pending = $pending->fetchColumn();
      ?>
      <div class="bl-card" style="margin-bottom:0;position:relative;transition:border-color .15s">
        <div class="bl-section" style="padding:1rem 1.25rem;border-bottom:none">
          <!-- Hover actions -->
          <div style="position:absolute;top:10px;right:10px;display:flex;gap:5px">
            <button class="bl-btn bl-btn-sm bl-btn-edit" onclick="openEditModal(
              <?php echo (int)$slot['id']; ?>,
              <?php echo (int)($slot['event_id'] ?? 0); ?>,
              '<?php echo addslashes($slot['start_time']); ?>',
              '<?php echo addslashes($slot['end_time']); ?>',
              <?php echo (int)$slot['capacity']; ?>
            )">Edit</button>
            <a href="delete_slots.php?id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="bl-btn bl-btn-sm bl-btn-del" onclick="return confirm('Delete this slot?')">Delete</a>
          </div>
          <a href="donors.php?slot_id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" style="text-decoration:none;display:block">
            <div style="font-size:15px;font-weight:700;color:var(--text)"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:3px"><?php echo $slot['booked']; ?> booked · <?php echo $slot['capacity']; ?> capacity</div>
            <div style="margin-top:8px">
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:<?php echo $full ? 'var(--red)' : '#15803d'; ?>;margin-right:5px"></span>
              <span style="font-size:11px;font-weight:600;color:<?php echo $full ? 'var(--red)' : '#15803d'; ?>"><?php echo $full ? 'Full' : 'Open'; ?></span>
            </div>
            <div style="height:5px;background:var(--border);border-radius:3px;margin-top:6px"><div style="height:100%;background:var(--red);border-radius:3px;width:<?php echo $pct; ?>%"></div></div>
            <div style="margin-top:8px;font-size:11px;color:var(--text-dim)"><?php echo $pending; ?> pending approval</div>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="bl-card"><div class="bl-empty"><p>No slots yet. Click + Add Slot to create one.</p></div></div>
    <?php endif; ?>
  </div><!-- /bl-main -->

<!-- Add Slot Modal -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:100;align-items:center;justify-content:center" id="addModal">
  <div style="background:#fff;border-radius:var(--radius);padding:28px;width:440px;max-width:95vw;border:1px solid var(--border)">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:18px;color:var(--text)">Add New Slot</h2>
    <form method="POST" action="save_slot.php">
      <input type="hidden" name="event_get" value="<?php echo htmlspecialchars($selected_event); ?>">
      <div class="bl-grid-2" style="margin-bottom:14px">
        <div class="bl-field">
          <label>Start Time</label>
          <input type="time" name="start_time" required>
        </div>
        <div class="bl-field">
          <label>End Time</label>
          <input type="time" name="end_time" required>
        </div>
      </div>
      <div class="bl-field" style="margin-bottom:14px">
        <label>Capacity</label>
        <input type="number" name="capacity" value="5" min="1" required>
      </div>
      <div class="bl-field" style="margin-bottom:14px">
        <label>Event</label>
        <select name="event_id" required>
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
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
        <button type="button" class="bl-btn bl-btn-ghost" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="bl-btn bl-btn-primary">Create Slot</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Slot Modal -->
<div style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);z-index:100;align-items:center;justify-content:center" id="editModal">
  <div style="background:#fff;border-radius:var(--radius);padding:28px;width:440px;max-width:95vw;border:1px solid var(--border)">
    <h2 style="font-size:16px;font-weight:700;margin-bottom:18px;color:var(--text)">Edit Slot</h2>
    <form method="POST" action="save_slot.php">
      <input type="hidden" name="slot_id" id="edit_slot_id">
      <input type="hidden" name="event_get" value="<?php echo htmlspecialchars($selected_event); ?>">
      <div class="bl-grid-2" style="margin-bottom:14px">
        <div class="bl-field">
          <label>Start Time</label>
          <input type="time" name="start_time" id="edit_start_time" required>
        </div>
        <div class="bl-field">
          <label>End Time</label>
          <input type="time" name="end_time" id="edit_end_time" required>
        </div>
      </div>
      <div class="bl-field" style="margin-bottom:14px">
        <label>Capacity</label>
        <input type="number" name="capacity" id="edit_capacity" min="1" required>
      </div>
      <div class="bl-field" style="margin-bottom:14px">
        <label>Event</label>
        <select name="event_id" id="edit_event_id" required>
          <option value="">— Select an event —</option>
          <?php foreach ($all_events as $ev): ?>
            <option value="<?php echo (int)$ev['id']; ?>">
              <?php echo htmlspecialchars($ev['event_name'] . ' — ' . $ev['event_date']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
        <button type="button" class="bl-btn bl-btn-ghost" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="bl-btn bl-btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openEditModal(id, event_id, stime, etime, cap) {
  document.getElementById('edit_slot_id').value = id;
  document.getElementById('edit_start_time').value = stime;
  document.getElementById('edit_end_time').value = etime;
  document.getElementById('edit_capacity').value = cap;
  var sel = document.getElementById('edit_event_id');
  for (var i = 0; i < sel.options.length; i++) {
    if (parseInt(sel.options[i].value) === parseInt(event_id)) { sel.selectedIndex = i; break; }
  }
  openModal('editModal');
}
window.onclick = function(e) {
  ['addModal','editModal'].forEach(function(id) {
    if (e.target === document.getElementById(id)) closeModal(id);
  });
}
</script>

<?php $base_url = '../'; include __DIR__ . '/../includes/footer.php'; ?>
