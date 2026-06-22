<?php



require 'includes/auth.php';
require 'includes/db.php';
require 'includes/avatar.php';
require 'includes/prereq.php';

// get user info
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// check if advisor has student selected
if ($role === 'advisor' && !isset($_GET['student_id'])) {
    header('Location: advisor.php');
    exit();
}

// get target student for course adding
$target_id = ($role === 'advisor' && isset($_GET['student_id']))
    ? intval($_GET['student_id'])
    : $user_id;

$target = $conn->query("SELECT * FROM users WHERE user_id = $target_id")->fetch_assoc();

// process add course form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $course_id = intval($_POST['course_id']);
    $term_name = $conn->real_escape_string(trim($_POST['term_name']));
    $sid = intval($_POST['student_id']);
    
    if (!student_meets_prereq($conn, $sid, $course_id)) {
        header("Location: course_catalog.php?" . http_build_query(array_filter([
            'student_id' => $role === 'advisor' ? $sid : null,
            'dept' => $_GET['dept'] ?? null,
            'q' => $_GET['q'] ?? null,
            'quarter' => $_GET['quarter'] ?? null,
            'added' => 1
        ])));
        exit();
    }
    
    // only advisors can add for others
    if ($role === 'student') $sid = $user_id;

    // check if already enrolled
    $exists = $conn->query("SELECT 1 FROM enrollments WHERE student_id = $sid AND course_id = $course_id AND term_name = '$term_name'")->num_rows;
    if (!$exists) {
        $conn->query("INSERT INTO enrollments (student_id, course_id, term_name, status) VALUES ($sid, $course_id, '$term_name', 'planned')");
    }
    header("Location: course_catalog.php?" . http_build_query(array_filter([
        'student_id' => $role === 'advisor' ? $sid : null,
        'dept' => $_GET['dept'] ?? null,
        'q' => $_GET['q'] ?? null,
        'quarter' => $_GET['quarter'] ?? null,
        'prereq_err' => 1
    ])));
    exit();
}

// get filter parameters
$dept = strtoupper(trim($_GET['dept'] ?? ''));
$search = $conn->real_escape_string($_GET['q'] ?? '');
$view_quarter = trim($_GET['quarter'] ?? '');
$courses = [];

// fetch courses if filters selected
if ($dept && $view_quarter) {

    $dept_safe = $conn->real_escape_string($dept);
    $where = [];
    $where[] = "UPPER(c.course_code) LIKE '{$dept_safe} %'";
    if ($search) {
        $where[] = "(c.course_code LIKE '%$search%' OR c.course_name LIKE '%$search%')";
    }

    $where_sql = 'WHERE ' . implode(' AND ', $where);
    $courses = $conn->query("
    SELECT c.*, p.course_code AS prereq_code, p.course_name AS prereq_name
    FROM courses c
    LEFT JOIN courses p ON c.prereq_course_id = p.course_id
    $where_sql
    ORDER BY c.course_code
    ")->fetch_all(MYSQLI_ASSOC);
}

// get department list for filter
$depts = $conn->query("SELECT DISTINCT SUBSTRING_INDEX(course_code, ' ', 1) as prefix FROM courses ORDER BY prefix")->fetch_all(MYSQLI_ASSOC);

// get enrolled courses for target student
$enrolled_ids = array_column(
    $conn->query("SELECT course_id FROM enrollments WHERE student_id = $target_id")->fetch_all(MYSQLI_ASSOC),
    'course_id'
);

// available terms list
$quarters = ['Fall 2025','Winter 2026','Spring 2026','Summer 2026','Fall 2026','Winter 2027','Spring 2027'];

$initials = implode('', array_map(fn($w) => $w[0], explode(' ', $_SESSION['name'])));
?>
<!DOCTYPE html>
<html>
<head>
  <title>Course Catalog</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Course Catalog</div>
        <div class="topbar-sub">Columbia Basin College</div>
      </div>
      <div class="topbar-user">
        <?= $avatar_html ?>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
      </div>
    </div>

    <div class="container">
      <?php if (isset($_GET['added'])): ?>
        <div class="success-msg"><i class="ti ti-check"></i> Course added to schedule.</div>
      <?php endif; ?>

      <?php if ($role === 'advisor' && $target_id !== $user_id): ?>
        <div class="for-student-banner">
          <i class="ti ti-user"></i>
          Adding courses for: <strong><?= htmlspecialchars($target['name']) ?></strong>
          <a href="course_catalog.php" style="margin-left:auto;color:#7c9fdb;font-size:12px;">Clear</a>
        </div>
      <?php endif; ?>

      <!-- Filter and search form -->
      <form method="GET" class="filter-bar">
      <?php if ($role === 'advisor' && isset($_GET['student_id'])): ?>
        <input type="hidden" name="student_id" value="<?= intval($_GET['student_id']) ?>">
      <?php endif; ?>

        <input
            type="text"
            name="q"
            placeholder="Search courses..."
            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
        >

        <input
            list="department-list"
            name="dept"
            placeholder="Department (CS, CS&, CSIA...)"
            value="<?= htmlspecialchars($dept) ?>"
            autocomplete="off"
            required
        >

        <datalist id="department-list">
            <?php foreach ($depts as $d): ?>
                <option value="<?= htmlspecialchars($d['prefix']) ?>">
            <?php endforeach; ?>
        </datalist>

        <select name="quarter" required>
            <option value="">Select Quarter</option>

            <?php foreach ($quarters as $quarter): ?>
                <option
                    value="<?= $quarter ?>"
                    <?= ($view_quarter === $quarter ? 'selected' : '') ?>
                >
                    <?= $quarter ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">
            <i class="ti ti-search"></i> Search
        </button>

    </form>

      <div class="result-count">
        <?php if (!$dept || !$view_quarter): ?>
            Select a department and quarter to view courses.
        <?php else: ?>
            <?= count($courses) ?> courses found
        <?php endif; ?>
    </div>

      <!-- Courses table -->
      <table class="course-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Course Name</th>
            <th>Credits</th>
            <th>Add to Quarter</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td><span class="course-code" style="width:auto"><?= htmlspecialchars($c['course_code']) ?></span></td>
              <td><?= htmlspecialchars($c['course_name']) ?></td>
              <td><?= $c['credits'] ?></td>
              <td>
                <?php if (in_array($c['course_id'], $enrolled_ids)): ?>
                  <span class="already-added"><i class="ti ti-check"></i> On schedule</span>
                <?php elseif ($c['prereq_course_id'] && !student_meets_prereq($conn, $target_id, $c['course_id'])): ?>
                  <span class="already-added" style="color:#888;display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-lock"></i> Requires <?= htmlspecialchars($c['prereq_code']) ?> to be completed
                    <a href="course_catalog.php?<?= http_build_query(array_filter([
                        'student_id' => $role === 'advisor' ? $target_id : null,
                        'dept' => explode(' ', $c['prereq_code'])[0],
                        'quarter' => $view_quarter,
                        'q' => explode(' ', $c['prereq_code'])[1] ?? null,
                    ])) ?>" style="font-size:11px;color:#7c9fdb;white-space:nowrap;">
                      <i class="ti ti-arrow-right"></i> View
                    </a>
                  </span>
                <?php else: ?>
                  <form method="POST" class="add-form">
                    <input type="hidden" name="course_id"  value="<?= $c['course_id'] ?>">
                    <input type="hidden" name="student_id" value="<?= $target_id ?>">
                    <input type="hidden" name="term_name" value="<?= htmlspecialchars($view_quarter) ?>">
                     <button type="submit" name="add_course" class="add-btn">
                      <i class="ti ti-plus"></i> Add
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
            <?php if (empty($courses)): ?>
                <tr>
                    <td colspan="4" style="text-align:center;color:#555;padding:24px;">
                        <?php if (!$dept || !$view_quarter): ?>
                            Select a department and quarter to view available courses.
                        <?php else: ?>
                            No courses found.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
      </table>

    </div>
  </div>
</body>
</html>