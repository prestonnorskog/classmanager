<?php
// get sidebar data
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// count unread messages
$unread = $conn->query("
    SELECT COUNT(*) as count FROM messages
    WHERE receiver_id = $user_id AND is_read = 0
")->fetch_assoc()['count'];

// set active link and labels
$active_link = $role === 'advisor' ? 'advisor.php' : 'student.php';
$schedule_label = $role === 'advisor' ? 'All Students' : 'My Schedule';
$schedule_icon = $role === 'advisor' ? 'ti-users' : 'ti-calendar';

// get contacts based on role
if ($role === 'student') {
    $contacts = $conn->query("SELECT user_id, name, profile_pic FROM users WHERE role = 'advisor'")->fetch_all(MYSQLI_ASSOC);
} else {
    $contacts = $conn->query("SELECT user_id, name, profile_pic FROM users WHERE role = 'student'")->fetch_all(MYSQLI_ASSOC);
}

// get current page and selected contact
$current = basename($_SERVER['PHP_SELF']);
$selected_id = isset($_GET['with']) ? intval($_GET['with']) : null;
$msgs_open = ($current === 'messages.php' || $unread > 0) ? 'true' : 'false';
?>
<div class="sidebar">
  <div class="sidebar-logo">Class Manager</div>
  <nav>
    <a href="<?= $active_link ?>" class="<?= $current === $active_link ? 'active' : '' ?>">
      <i class="ti <?= $schedule_icon ?>"></i> <?= $schedule_label ?>
    </a>
	<?php if ($role === 'student'): ?>
        <a href="course_catalog.php" class="<?= $current === 'course_catalog.php' ? 'active' : '' ?>">
          <i class="ti ti-books"></i> Course Catalog
        </a>
      <?php elseif ($role === 'advisor' && $current === 'course_catalog.php'): ?>
        <a href="course_catalog.php?student_id=<?= $_GET['student_id'] ?? '' ?>" class="active">
          <i class="ti ti-books"></i> Course Catalog
        </a>
    <?php endif; ?>
    </a>
    <!-- Messages toggle button -->
    <div class="msg-toggle <?= $current === 'messages.php' ? 'active' : '' ?>" onclick="toggleMessages()">
      <div style="display:flex;align-items:center;gap:10px;flex:1">
        <i class="ti ti-mail"></i> Messages
        <?php if ($unread > 0): ?>
          <span class="badge-count"><?= $unread ?></span>
        <?php endif; ?>
      </div>
      <i class="ti ti-chevron-down" id="msg-chevron" style="font-size:12px;transition:transform .2s;<?= $msgs_open === 'true' ? 'transform:rotate(180deg)' : '' ?>"></i>
    </div>

    <!-- Contact list drawer -->
    <div class="contact-drawer" id="contact-drawer" style="display:<?= $msgs_open === 'true' ? 'flex' : 'none' ?>;flex-direction:column;gap:1px;padding-left:10px">
      <?php foreach ($contacts as $c):
        $contact_initials = implode('', array_map(fn($w) => $w[0], explode(' ', $c['name'])));
		$contact_pic = !empty($c['profile_pic']) && file_exists($c['profile_pic']);
        $is_active = $selected_id == $c['user_id'];

        $unread_from = $conn->query("
            SELECT COUNT(*) as count FROM messages
            WHERE receiver_id = $user_id AND sender_id = {$c['user_id']} AND is_read = 0
        ")->fetch_assoc()['count'];
      ?>
        <a href="messages.php?with=<?= $c['user_id'] ?>" class="contact-drawer-item <?= $is_active ? 'active' : '' ?>">
          <div class="contact-avatar-sm" style="<?= $contact_pic ? 'padding:0;overflow:hidden;' : '' ?>">
            <?php if ($contact_pic): ?>
                <img src="<?= htmlspecialchars($c['profile_pic']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= $contact_initials ?>
            <?php endif; ?>
        </div>
          <span class="contact-drawer-name"><?= $c['name'] ?></span>
          <?php if ($unread_from > 0): ?>
            <span class="badge-count" style="margin-left:auto"><?= $unread_from ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

  </nav>
  <div class="sidebar-bottom">
    <a href="profile_editor.php" class="<?= $current === 'profile_editor.php' ? 'active' : '' ?>">
      <i class="ti ti-user"></i> Profile
    </a>
    <a href="logout.php" class="signout"><i class="ti ti-logout"></i> Sign out</a>
  </div>
</div>

<script>
// toggle contact drawer
function toggleMessages() {
  const drawer = document.getElementById('contact-drawer');
  const chevron = document.getElementById('msg-chevron');
  const open = drawer.style.display === 'none';
  drawer.style.display = open ? 'flex' : 'none';
  drawer.style.flexDirection = 'column';
  chevron.style.transform = open ? 'rotate(180deg)' : '';
}
</script>