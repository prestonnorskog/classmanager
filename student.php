<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/scheduler.php';

// handle accept revised schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_plan'])) {
    $suggestions = json_decode($_POST['suggestions'], true);
    foreach ($suggestions as $s) {
        $eid = intval($s['enrollment_id']);
        $to_term = $conn->real_escape_string($s['to_term']);
        $conn->query("UPDATE enrollments SET term_name = '$to_term', status = 'planned' WHERE enrollment_id = $eid");
    }
    header('Location: student.php');
    exit();
}

// get current student info
$student_id = $_SESSION['user_id'];
$suggestions = get_revised_schedule($conn, $student_id);
$sql = "SELECT e.status, e.term_name, c.course_code, c.course_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = $student_id
        ORDER BY e.term_name, c.course_code";

$result = $conn->query($sql);
$courses = $result->fetch_all(MYSQLI_ASSOC);

// organize courses by term
$by_term = [];
foreach ($courses as $c) {
    $by_term[$c['term_name']][] = $c;
}

// calculate course stats
$completed = count(array_filter($courses, fn($c) => $c['status'] === 'completed'));
$remaining = count(array_filter($courses, fn($c) => $c['status'] !== 'completed'));
$has_missed = in_array('missed', array_column($courses, 'status'));

require 'includes/avatar.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>My Schedule</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title">My Schedule</div>
        <div class="topbar-sub">Spring 2026</div>
      </div>
      <div class="topbar-user">
        <?= $avatar_html ?>
        <span><?= $_SESSION['name'] ?></span>
      </div>
    </div>
    <div class="container">
      <?php if ($has_missed): ?>
        <div class="alert">You have a missed registration. Contact your advisor to update your plan.</div>
      <?php endif; ?>
      <div class="stat-grid">
        <div class="stat-card"><div class="label">Completed</div><div class="value"><?= $completed ?></div></div>
        <div class="stat-card"><div class="label">Remaining</div><div class="value"><?= $remaining ?></div></div>
        <div class="stat-card"><div class="label">Est. grad</div><div class="value">Sp 27</div></div>
      </div>
        <?php if ($suggestions): ?>
      <div class="term-block" style="border-left:3px solid #f0a500;padding-left:16px;">
        <div class="term-label" style="color:#f0a500;">
          <i class="ti ti-alert-triangle"></i> Suggested Revised Schedule
        </div>
        <table class="course-table" style="margin-top:8px;">
          <thead>
            <tr><th>Course</th><th>Name</th><th>Move to</th></tr>
          </thead>
          <tbody>
            <?php foreach ($suggestions as $s): ?>
              <tr>
                <td><span class="course-code"><?= htmlspecialchars($s['course_code']) ?></span></td>
                <td><?= htmlspecialchars($s['course_name']) ?></td>
                <td><?= htmlspecialchars($s['from_term']) ?> → <?= htmlspecialchars($s['to_term']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <form method="POST" style="margin-top:12px;">
          <input type="hidden" name="suggestions" value="<?= htmlspecialchars(json_encode($suggestions)) ?>">
          <button type="submit" name="accept_plan" class="add-btn">
            <i class="ti ti-check"></i> Accept Plan
          </button>
        </form>
      </div>
    <?php endif; ?>
      <?php foreach ($by_term as $term => $term_courses): ?>
        <div class="term-block">
          <div class="term-label"><?= $term ?></div>
          <?php foreach ($term_courses as $c): ?>
            <div class="course-row">
              <span class="course-code"><?= $c['course_code'] ?></span>
              <span class="course-name"><?= $c['course_name'] ?></span>
              <span class="badge <?= $c['status'] ?>"><?= $c['status'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>