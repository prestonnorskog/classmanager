<?php
require 'includes/auth.php';
require 'includes/db.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_body'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $body = $conn->real_escape_string($_POST['message_body']);
    $conn->query("INSERT INTO messages (sender_id, receiver_id, message_body) VALUES ($user_id, $receiver_id, '$body')");
}

$selected_id = isset($_GET['with']) ? intval($_GET['with']) : null;

// mark conversation as read
if ($selected_id) {
    $conn->query("UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id AND sender_id = $selected_id");
}

// get contact list based on role
if ($role === 'student') {
    $contacts = $conn->query("SELECT user_id, name, profile_pic FROM users WHERE role = 'advisor'")->fetch_all(MYSQLI_ASSOC);
} else {
    $contacts = $conn->query("SELECT user_id, name, profile_pic FROM users WHERE role = 'student'")->fetch_all(MYSQLI_ASSOC);
}

// default to first contact
if (!$selected_id && count($contacts) > 0) {
    $selected_id = $contacts[0]['user_id'];
}

// get selected contact info
$contact = null;
if ($selected_id) {
    $contact = $conn->query("SELECT * FROM users WHERE user_id = $selected_id")->fetch_assoc();
}

// get messages for conversation
$messages = [];
if ($selected_id) {
    $sql = "SELECT m.*, u.name as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = $user_id AND m.receiver_id = $selected_id)
               OR (m.sender_id = $selected_id AND m.receiver_id = $user_id)
            ORDER BY m.sent_at ASC";
    $messages = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

require 'includes/avatar.php';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Messages</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
  <div class="topbar">
    <div>
      <div class="topbar-title"><?= $contact ? $contact['name'] : 'Messages' ?></div>
    </div>
    <div class="topbar-user">
      <?= $avatar_html ?>
      <span><?= $_SESSION['name'] ?></span>
    </div>
  </div>

<div class="chat-area" style="height:calc(100vh - 57px)">
    <?php if ($contact):
        $contact_pic = !empty($contact['profile_pic']) && file_exists($contact['profile_pic']);
        $contact_initials = implode('', array_map(fn($w) => $w[0], explode(' ', $contact['name'])));
    ?>
      <div class="chat-header" style="display:flex;align-items:center;gap:10px;">
        <div class="contact-avatar" style="<?= $contact_pic ? 'padding:0;overflow:hidden;' : '' ?>">
          <?php if ($contact_pic): ?>
            <img src="<?= htmlspecialchars($contact['profile_pic']) ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <?= $contact_initials ?>
          <?php endif; ?>
        </div>
        <?= htmlspecialchars($contact['name']) ?>
      </div>

      <!-- Chat messages display -->
      <div class="chat-messages" id="chat-messages">
        <?php if (empty($messages)): ?>
          <div class="empty-state">No messages yet. Say hello!</div>
        <?php endif; ?>
        <?php foreach ($messages as $m):
          $is_mine = $m['sender_id'] == $user_id;
          $side = $is_mine ? 'mine' : 'theirs';
        ?>
          <div class="msg-group <?= $side ?>">
            <?php if (!$is_mine): ?>
              <div style="display:flex;align-items:flex-end;gap:8px;">
                <div class="contact-avatar" style="width:26px;height:26px;font-size:10px;flex-shrink:0;<?= $contact_pic ? 'padding:0;overflow:hidden;' : '' ?>">
                  <?php if ($contact_pic): ?>
                    <img src="<?= htmlspecialchars($contact['profile_pic']) ?>" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <?= $contact_initials ?>
                  <?php endif; ?>
                </div>
                <div class="msg-bubble theirs"><?= htmlspecialchars($m['message_body']) ?></div>
              </div>
            <?php else: ?>
              <div class="msg-bubble mine"><?= htmlspecialchars($m['message_body']) ?></div>
            <?php endif; ?>
            <div class="msg-meta"><?= date('M j g:ia', strtotime($m['sent_at'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Message input form -->
      <form method="POST" class="chat-input-area" action="messages.php?with=<?= $selected_id ?>">
        <input type="hidden" name="receiver_id" value="<?= $selected_id ?>">
        <input type="text" name="message_body" placeholder="Type a message..." autocomplete="off" id="msg-input">
        <button type="submit" class="send-btn"><i class="ti ti-send"></i></button>
      </form>
    <?php else: ?>
      <div class="empty-state">Select a conversation from the sidebar</div>
    <?php endif; ?>
  </div>
</div>

  <script>
    // auto-scroll to chat bottom
    const chat = document.getElementById('chat-messages');
    if (chat) chat.scrollTop = chat.scrollHeight;

    // focus message input
    const input = document.getElementById('msg-input');
    if (input) input.focus();
  </script>
</body>
</html>