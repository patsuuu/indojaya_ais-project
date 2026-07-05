<?php
session_start();
require_once 'db_connect.php';
require_once 'role_helpers.php';

// HR only
if (!isset($_SESSION['user_id']) || !(hasRole('Hr') || hasRole('HR'))) {
  header('Location: login.php');
  exit;
}

// Ensure POST adds/updates
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $date = $_POST['holiday_date'] ?? '';
  $name = trim($_POST['holiday_name'] ?? '');

  // basic validation
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $message = 'Invalid date format.';
  } elseif ($name === '') {
    $message = 'Holiday name is required.';
  } else {
    // Upsert into holiday table (create if needed)
    $sql = "INSERT INTO holidays (holiday_date, holiday_name)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name)";

    // Create table if missing
    $conn->query("CREATE TABLE IF NOT EXISTS holidays (
      holiday_date DATE NOT NULL PRIMARY KEY,
      holiday_name VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      $message = 'Database prepare failed.';
    } else {
      $stmt->bind_param('ss', $date, $name);
      if ($stmt->execute()) {
        $success = true;
        $message = '✅ Holiday saved: ' . htmlspecialchars($date) . ' (' . htmlspecialchars($name) . ')';
      } else {
        $message = '❌ Failed to save holiday.';
      }
      $stmt->close();
    }
  }
}

// Load holidays for the calendar UI
$holidaysByDate = [];
$result = $conn->query('SELECT holiday_date, holiday_name FROM holidays ORDER BY holiday_date ASC');
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $holidaysByDate[$row['holiday_date']] = $row['holiday_name'];
  }
}

$conn->close();

// Build month view
$month = isset($_GET['month']) ? $_GET['month'] : '';
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month === '' && (!isset($_GET['month']) || !isset($_GET['year']))) {
  $month = (string)((int)date('m'));
} elseif ($month !== '' && !preg_match('/^\d{1,2}$/', $month)) {
  $month = (string)((int)date('m'));
}

if (!preg_match('/^\d{1,2}$/', (string)$month)) {
  $month = (string)((int)date('m'));
}
$month = (int)$month;

$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$daysInMonth = (int)$firstDay->format('t');
$startWeekday = (int)$firstDay->format('w'); // 0 (Sun) - 6 (Sat)

$today = (new DateTime('now'))->format('Y-m-d');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Holiday Calendar (HR)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .holiday-cell { cursor: default; }
    .holiday-badge { font-size: 11px; }
    .cal-wrap { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
    .day-name { font-size:12px; color:#666; font-weight:700; }
    .cell-today { border:2px solid #0d6efd !important; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <span class="navbar-brand mb-0">🗓️ Holiday Calendar (HR)</span>
    <a href="logout.php" class="btn btn-danger btn-sm">🔓 Logout</a>
  </div>
</nav>

<div class="container py-4">
  <?php if ($message !== ''): ?>
    <div class="alert <?php echo $success ? 'alert-success' : 'alert-warning'; ?>">
      <?php echo h($message); ?>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="cal-wrap p-3 mb-3">
        <h5 class="mb-3">➕ Add Holiday</h5>
        <form method="POST" action="holiday_calendar.php?month=<?php echo (int)$month; ?>&year=<?php echo (int)$year; ?>">
          <div class="mb-2">
            <label class="form-label">Date</label>
            <input type="date" name="holiday_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Holiday Name</label>
            <input type="text" name="holiday_name" class="form-control" placeholder="e.g. Company Holiday" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Save Holiday</button>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="cal-wrap p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <h5 class="mb-0">Calendar</h5>
            <div class="text-muted" style="font-size:12px;">HR can add holidays that will be used by the app.</div>
          </div>
          <div class="d-flex gap-2">
            <?php
              $prev = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('-1 month');
              $next = (new DateTime(sprintf('%04d-%02d-01', $year, $month)))->modify('+1 month');
            ?>
            <a class="btn btn-outline-secondary btn-sm" href="holiday_calendar.php?month=<?php echo (int)$prev->format('m'); ?>&year=<?php echo (int)$prev->format('Y'); ?>">← Prev</a>
            <a class="btn btn-outline-secondary btn-sm" href="holiday_calendar.php?month=<?php echo (int)$next->format('m'); ?>&year=<?php echo (int)$next->format('Y'); ?>">Next →</a>
          </div>
        </div>

        <div class="text-center mb-2 fw-bold">
          <?php echo h($firstDay->format('F Y')); ?>
        </div>

        <div class="row g-2 mb-2">
          <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dn): ?>
            <div class="col day-name text-center"><?php echo h($dn); ?></div>
          <?php endforeach; ?>
        </div>

        <?php
          $cells = [];
          for ($i=0;$i<$startWeekday;$i++) $cells[] = ['empty'=>true];
          for ($d=1;$d<=$daysInMonth;$d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $cells[] = [
              'empty'=>false,
              'day'=>$d,
              'date'=>$dateStr,
              'name'=>$holidaysByDate[$dateStr] ?? ''
            ];
          }
          while (count($cells) % 7 !== 0) $cells[] = ['empty'=>true];
        ?>

        <div class="row g-2">
          <?php foreach ($cells as $cell): ?>
            <div class="col-1">
              <?php if (!empty($cell['empty'])): ?>
                <div class="border rounded p-2" style="height:72px; background:#fafafa;"></div>
              <?php else: ?>
                <?php $isToday = (($cell['date'] ?? '') === $today); ?>
                <div class="border rounded p-2 holiday-cell <?php echo $isToday ? 'cell-today' : ''; ?>" style="height:72px; background:<?php echo $cell['name'] ? '#fff3cd' : '#fff'; ?>">
                  <div class="d-flex justify-content-between">
                    <div class="fw-bold" style="font-size:12px;"><?php echo (int)$cell['day']; ?></div>
                    <?php if ($isToday): ?><span class="badge text-bg-primary" style="font-size:10px;">Today</span><?php endif; ?>
                  </div>
                  <?php if (!empty($cell['name'])): ?>
                    <div class="mt-1">
                      <span class="badge text-bg-warning holiday-badge">Holiday</span>
                      <div style="font-size:11px; color:#7a5c00; margin-top:2px; line-height:1.1;"><?php echo h($cell['name']); ?></div>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>

