<?php
require 'includes/auth.php';
require 'includes/db.php';
require 'includes/avatar.php';
require 'includes/scheduler.php';

// check if user is advisor
if ($_SESSION['role'] !== 'advisor') {
    header('Location: student.php');
    exit();
}

// get student ID from URL
$student_id = intval($_GET['id'] ?? 0);
if (!$student_id) {
    header('Location: advisor.php');
    exit();
}

$student = $conn->query("SELECT * FROM users WHERE user_id = $student_id")->fetch_assoc();
if (!$student) {
    header('Location: advisor.php');
    exit();
}

// handle plan approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_plan'])) {
    $suggestions = json_decode($_POST['suggestions'], true);
    foreach ($suggestions as $s) {
        $eid = intval($s['enrollment_id']);
        $to_term = $conn->real_escape_string($s['to_term']);
        $conn->query("UPDATE enrollments SET term_name = '$to_term', status = 'planned' WHERE enrollment_id = $eid");
    }
    header("Location: student_detail.php?id=$student_id&approved=1");
    exit();
}

// get student courses
$sql = "SELECT e.status, e.term_name, c.course_code, c.course_name
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.student_id = $student_id
        ORDER BY e.term_name, c.course_code";

$courses = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// group by term
$by_term = [];
foreach ($courses as $c) {
    $by_term[$c['term_name']][] = $c;
}

$suggestions = get_revised_schedule($conn, $student_id);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($student['name']) ?> - Schedule</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title"><?= htmlspecialchars($student['name']) ?></div>
        <div class="topbar-sub">Student Schedule</div>
      </div>
      <div class="topbar-user">
        <?= $avatar_html ?>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
      </div>
    </div>

    <div class="container">

      <?php if (isset($_GET['approved'])): ?>
        <div class="success-msg"><i class="ti ti-check"></i> Revised schedule applied.</div>
      <?php endif; ?>

      <div style="margin-bottom:16px;">
        <a href="advisor.php" style="color:#7c9fdb;font-size:13px;">
          <i class="ti ti-arrow-left"></i> Back to students
        </a>
        <a href="course_catalog.php?student_id=<?= $student_id ?>" style="color:#7c9fdb;font-size:13px;margin-left:16px;">
          <i class="ti ti-books"></i> Add courses
        </a>
      </div>

      <?php if ($suggestions): ?>
        <div class="term-block" style="border-left:3px solid #f0a500;padding-left:16px;margin-bottom:24px;">
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
              <i class="ti ti-check"></i> Apply Plan
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

      <?php if (empty($courses)): ?>
        <p style="color:#555;">No courses on schedule.</p>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>