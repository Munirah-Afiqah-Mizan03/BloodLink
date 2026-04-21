<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'donor') {
    header('Location: ../login.php'); exit;
}

$activePage = 'booking';
$pageTitle  = 'Book Donation';
$base_url   = '../';
$user_id = $_SESSION['user_id'];

// Fetch user info
$user = $pdo->prepare("SELECT * FROM bl_users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

// Fetch available events
$events = $pdo->query("SELECT DISTINCT event_name, event_date, event_location FROM bl_slots ORDER BY event_date ASC")->fetchAll();
$selected_event = $_GET['event'] ?? ($events[0]['event_name'] ?? '');

// Fetch slots for selected event
$slots = [];
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

// Stats
$total = $pdo->prepare("SELECT COUNT(*) FROM bl_bookings WHERE donor_id = ? AND status = 'approved'");
$total->execute([$user_id]);
$total_donations = $total->fetchColumn();

$last = $pdo->prepare("SELECT s.event_date FROM bl_bookings b JOIN bl_slots s ON b.slot_id = s.id WHERE b.donor_id = ? AND b.status = 'approved' ORDER BY s.event_date DESC LIMIT 1");
$last->execute([$user_id]);
$last_date = $last->fetchColumn();
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="bl-main">

    <!-- Top Hero (Upcoming Events style) -->
    <div class="bl-top-hero">
      <div>
        <h2>Book Donation Slot</h2>
        <p>Browse upcoming events and book your donation slot</p>
      </div>
      <div class="bl-top-hero-actions">
        <?php if ($user['blood_type']): ?>
          <span class="bl-badge bl-badge-blood">Blood Type: <?php echo htmlspecialchars($user['blood_type']); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="bl-notice bl-notice-success" style="margin-bottom:1.25rem"><p><?php echo htmlspecialchars($_GET['success']); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="bl-notice bl-notice-error" style="margin-bottom:1.25rem"><p><?php echo htmlspecialchars($_GET['error']); ?></p></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="bl-stats">
      <div class="bl-stat bl-stat-red">
        <p>Total Donations</p>
        <p><?php echo $total_donations; ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Last Donation</p>
        <p style="font-size:16px"><?php
          if ($last_date) {
              $diff = (new DateTime())->diff(new DateTime($last_date));
              echo $diff->days . ' days ago';
          } else { echo '—'; }
        ?></p>
      </div>
      <div class="bl-stat bl-stat-grey">
        <p>Next Eligible</p>
        <p style="font-size:16px"><?php
          if ($last_date) {
              $next = (new DateTime($last_date))->modify('+90 days');
              $today = new DateTime();
              echo ($next > $today) ? $today->diff($next)->days . ' days' : 'Now';
          } else { echo 'Now'; }
        ?></p>
      </div>
    </div>

    <!-- Event Selector -->
    <div class="bl-card">
      <div class="bl-section" style="padding:1rem 1.25rem;border-bottom:none">
        <p style="font-size:11px;color:var(--text-dim);font-weight:600;text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">Select Event</p>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <form method="GET">
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
            <span style="font-size:13px;color:var(--text-muted)">
              <strong style="color:var(--text)"><?php echo htmlspecialchars($event_info['event_date']); ?></strong> &nbsp;·&nbsp;
              <strong style="color:var(--text)"><?php echo htmlspecialchars($event_info['event_location']); ?></strong>
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Available Slots -->
    <p style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:1rem">Available Slots</p>
    <?php if (!empty($slots)): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:1.5rem">
      <?php foreach ($slots as $slot):
        $pct = $slot['capacity'] > 0 ? min(round(($slot['booked'] / $slot['capacity']) * 100), 100) : 0;
        $full = $slot['booked'] >= $slot['capacity'];
        $my_status = $slot['my_status'] ?? null;
        $is_booked = $slot['already_booked'];
      ?>
      <div class="bl-card" style="margin-bottom:0;<?php echo $is_booked ? 'border-color:rgba(22,163,74,.3);background:rgba(22,163,74,.02)' : ($full ? 'opacity:0.6' : ''); ?>">
        <div class="bl-section" style="padding:1rem 1.25rem;border-bottom:none">
          <div style="font-size:15px;font-weight:700;color:var(--text)"><?php echo htmlspecialchars($slot['start_time'] . ' – ' . $slot['end_time']); ?></div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:3px"><?php echo $slot['booked']; ?> booked · <?php echo $slot['capacity']; ?> capacity</div>
          <div style="margin-top:8px">
            <?php if ($is_booked): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#15803d;margin-right:5px"></span>
              <span style="font-size:11px;font-weight:600;color:#15803d">Your booking</span>
            <?php elseif ($full): ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--red);margin-right:5px"></span>
              <span style="font-size:11px;font-weight:600;color:var(--red)">Full</span>
            <?php else: ?>
              <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#15803d;margin-right:5px"></span>
              <span style="font-size:11px;font-weight:600;color:#15803d">Open</span>
            <?php endif; ?>
          </div>
          <div style="height:5px;background:var(--border);border-radius:3px;margin-top:6px"><div style="height:100%;background:var(--red);border-radius:3px;width:<?php echo $pct; ?>%"></div></div>
          <div style="margin-top:12px">
            <?php if ($is_booked): ?>
              <?php
                $pill = $my_status === 'approved' ? 'bl-badge-completed' : ($my_status === 'rejected' ? 'bl-badge-cancelled' : 'bl-badge-pending');
                $label = $my_status === 'approved' ? 'Donated' : ($my_status === 'rejected' ? 'Rejected' : 'Pending');
              ?>
              <span class="bl-badge <?php echo $pill; ?>" style="margin-right:8px"><?php echo $label; ?></span>
              <?php if ($my_status === 'pending'): ?>
                <a href="book_slot.php?action=cancel&booking_id=<?php echo $slot['my_booking_id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="bl-btn bl-btn-sm bl-btn-del" onclick="return confirm('Cancel your booking?')">Cancel</a>
              <?php endif; ?>
            <?php elseif ($full): ?>
              <button class="bl-btn bl-btn-sm bl-btn-ghost" disabled>Slot Full</button>
            <?php else: ?>
              <a href="book_slot.php?action=book&slot_id=<?php echo $slot['id']; ?>&event=<?php echo urlencode($selected_event); ?>" class="bl-btn bl-btn-sm bl-btn-primary">Book Slot</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="bl-card"><div class="bl-empty"><p>No slots available for this event yet.</p></div></div>
    <?php endif; ?>

    <!-- Recent Donations -->
    <p style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:1rem">My Recent Donations</p>
    <div class="bl-card">
      <?php if ($history): ?>
      <div class="bl-table-wrap">
        <table class="bl-table">
          <thead><tr><th>Event</th><th>Date</th><th>Time Slot</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($history as $h):
              $pill = $h['status'] === 'approved' ? 'bl-badge-completed' : ($h['status'] === 'rejected' ? 'bl-badge-cancelled' : 'bl-badge-pending');
              $label = $h['status'] === 'approved' ? 'Donated' : ($h['status'] === 'rejected' ? ($h['reject_reason'] === 'noshow' ? 'No Show' : 'Not Eligible') : 'Pending');
            ?>
            <tr>
              <td>
                <div style="font-weight:600"><?php echo htmlspecialchars($h['event_name']); ?></div>
                <div class="sub"><?php echo htmlspecialchars($h['event_location']); ?></div>
              </td>
              <td style="color:var(--text-muted);font-size:12px"><?php echo htmlspecialchars($h['event_date']); ?></td>
              <td style="color:var(--text-muted);font-size:12px"><?php echo htmlspecialchars($h['start_time'] . ' – ' . $h['end_time']); ?></td>
              <td><span class="bl-badge <?php echo $pill; ?>"><?php echo $label; ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="bl-empty"><p>No donation history yet.</p></div>
      <?php endif; ?>
    </div>

  </div><!-- /bl-main -->

<?php $base_url = '../'; include __DIR__ . '/../includes/footer.php'; ?>
