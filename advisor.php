<?php
require 'includes/auth.php';
require 'includes/db.php';

// check if user is advisor
if ($_SESSION['role'] !== 'advisor') {
    header("Location: student.php");
    exit();
}

// get all students with missed count
$sql = "SELECT u.user_id, u.name, u.email,
        SUM(CASE WHEN e.status = 'missed' THEN 1 ELSE 0 END) as missed_count
        FROM users u
        LEFT JOIN enrollments e ON u.user_id = e.student_id
        WHERE u.role = 'student'
        GROUP BY u.user_id";

$result = $conn->query($sql);
$students = $result->fetch_all(MYSQLI_ASSOC);

require 'includes/avatar.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Advisor View</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title">All Students</div>
        <div class="topbar-sub">Spring 2026</div>
      </div>
      <div class="topbar-user">
        <?= $avatar_html ?>
        <span><?= $_SESSION['name'] ?></span>
      </div>
    </div>
    <div class="container">
      <input type="text" id="search" placeholder="Search by name..." onkeyup="filterStudents()">
      <table class="student-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Edit</th>
              <th>View</th>
            <th>Add course(s)</th>
          </tr>
        </thead>
        <tbody id="student-list">
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?= $s['name'] ?></td>
              <td><?= $s['email'] ?></td>
              <td>
                <?php if ($s['missed_count'] > 0): ?>
                  <span class="badge missed">Missed class</span>
                <?php else: ?>
                  <span class="badge completed">On track</span>
                <?php endif; ?>
              </td>
                <td><a href="edit_student.php?id=<?= $s['user_id'] ?>" style="color:#7c9fdb;font-size:12px;">
                    <i class="ti ti-pencil"></i> Edit
                </a></td>
                <td><a href="student_detail.php?id=<?= $s['user_id'] ?>" style="color:#7c9fdb;font-size:12px;">
                    <i class="ti ti-eye"></i> View
                </a></td>
                <td><a href="course_catalog.php?student_id=<?= $s['user_id'] ?>" style="color:#7c9fdb;font-size:12px;">
                    <i class="ti ti-books"></i> Add courses
                </a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <script>
    // filter table by search text
    function filterStudents() {
      const search = document.getElementById('search').value.toLowerCase();
      document.querySelectorAll('#student-list tr').forEach(row => {
        row.style.display = row.cells[0].textContent.toLowerCase().includes(search) ? '' : 'none';
      });
    }
  </script>
</body>
</html>