<?php
require 'includes/auth.php';
require 'includes/db.php';

// check if user is advisor
if ($_SESSION['role'] !== 'advisor') {
    header("Location: student.php");
    exit();
}

$student_id = $_GET['id'];

// handle status update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'];
    $new_status = $_POST['status'];
    $conn->query("UPDATE enrollments SET status = '$new_status' WHERE enrollment_id = $enrollment_id");
}

$student = $conn->query("SELECT * FROM users WHERE user_id = $student_id")->fetch_assoc();

// get student courses
$sql = "SELECT e.enrollment_id, e.status, e.term_name, c.course_code, c.course_name
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

require 'includes/avatar.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Student</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title"><?= $student['name'] ?></div>
        <div class="topbar-sub"><?= $student['email'] ?></div>
      </div>
      <div class="topbar-user">
        <a href="advisor.php" style="color:#7c9fdb;font-size:13px;text-decoration:none"><i class="ti ti-arrow-left"></i> Back</a>
        <?= $avatar_html ?>
        <span><?= $_SESSION['name'] ?></span>
      </div>
    </div>
    <div class="container">
      <?php foreach ($by_term as $term => $term_courses): ?>
        <div class="term-block">
          <div class="term-label"><?= $term ?></div>
          <?php foreach ($term_courses as $c): ?>
            <div class="course-row">
              <span class="course-code"><?= $c['course_code'] ?></span>
              <span class="course-name"><?= $c['course_name'] ?></span>
              <form method="POST" style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="enrollment_id" value="<?= $c['enrollment_id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <option value="planned"   <?= $c['status']==='planned'   ? 'selected':'' ?>>Planned</option>
                  <option value="enrolled"  <?= $c['status']==='enrolled'  ? 'selected':'' ?>>Enrolled</option>
                  <option value="completed" <?= $c['status']==='completed' ? 'selected':'' ?>>Completed</option>
                  <option value="missed"    <?= $c['status']==='missed'    ? 'selected':'' ?>>Missed</option>
                </select>
              </form>
              <span class="badge <?= $c['status'] ?>"><?= $c['status'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>