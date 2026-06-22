<?php
require 'includes/auth.php';
require 'includes/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$user = $conn->query("SELECT name, email, profile_pic FROM users WHERE user_id = $user_id")->fetch_assoc();

// handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // update profile name and email
    if ($action === 'profile') {
        $name = trim($conn->real_escape_string($_POST['name']));
        $email = trim($conn->real_escape_string($_POST['email']));

        if ($name === '' || $email === '') {
            $error = 'Name and email cannot be empty.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $exists = $conn->query("SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id")->num_rows;
            if ($exists > 0) {
                $error = 'That email address is already in use.';
            } else {
                $conn->query("UPDATE users SET name = '$name', email = '$email' WHERE user_id = $user_id");
                $_SESSION['name'] = $name;
                $user['name'] = $name;
                $user['email'] = $email;
                $success = 'Profile updated.';
            }
        }

    // update password
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $row = $conn->query("SELECT password_hash FROM users WHERE user_id = $user_id")->fetch_assoc();

        if (!password_verify($current, $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pw) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new_pw !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
            $conn->query("UPDATE users SET password_hash = '$hashed' WHERE user_id = $user_id");
            $success = 'Password changed.';
        }

    // upload profile picture
    } elseif ($action === 'avatar') {
        $file = $_FILES['avatar'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed. Please try again.';
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime = mime_content_type($file['tmp_name']);

            if (!in_array($mime, $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = 'Image must be under 2MB.';
            } else {
                $upload_dir = 'uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) {
                    unlink($user['profile_pic']);
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $safe_dest = $conn->real_escape_string($dest);
                    $conn->query("UPDATE users SET profile_pic = '$safe_dest' WHERE user_id = $user_id");
                    $user['profile_pic'] = $dest;
                    $success = 'Profile picture updated.';
                } else {
                    $error = 'Could not save the image. Check folder permissions.';
                }
            }
        }
    }
}

$initials = implode('', array_map(fn($w) => $w[0], explode(' ', $_SESSION['name'])));
$has_pic = !empty($user['profile_pic']) && file_exists($user['profile_pic']);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Profile</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <?php require 'includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div>
        <div class="topbar-title">Edit Profile</div>
        <div class="topbar-sub">Update your account details</div>
      </div>
      <div class="topbar-user">
        <?php if ($has_pic): ?>
          <div class="avatar" style="overflow:hidden;padding:0;">
            <img src="<?= htmlspecialchars($user['profile_pic']) ?>" style="width:100%;height:100%;object-fit:cover;">
          </div>
        <?php else: ?>
          <div class="avatar"><?= $initials ?></div>
        <?php endif; ?>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
      </div>
    </div>

    <div class="container">

      <?php if ($success): ?>
        <div class="success-msg"><i class="ti ti-check"></i> <?= $success ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert"><i class="ti ti-alert-circle"></i> <?= $error ?></div>
      <?php endif; ?>

      <!-- Picture upload section -->
      <div class="form-card">
        <div class="form-card-title">Profile Picture</div>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="avatar">
          <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAvatar(event)">
          <div class="avatar-upload-row">
            <div class="avatar-preview" id="avatar-preview">
              <?php if ($has_pic): ?>
                <img id="preview-img" src="<?= htmlspecialchars($user['profile_pic']) ?>">
              <?php else: ?>
                <span id="preview-initials"><?= $initials ?></span>
              <?php endif; ?>
            </div>
            <div>
              <label for="avatar-input" class="avatar-upload-btn">
                <i class="ti ti-upload"></i> Choose image
              </label>
              <div class="avatar-hint">JPG, PNG, GIF or WEBP · Max 2MB</div>
            </div>
          </div>
          <button type="submit" class="save-btn">Upload Picture</button>
        </form>
      </div>

      <div class="profile-grid">
        <!-- Name and email form -->
        <div class="form-card">
          <div class="form-card-title">Profile Info</div>
          <form method="POST">
            <input type="hidden" name="action" value="profile">
            <div class="field">
              <label>Full Name</label>
              <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="field">
              <label>Email Address</label>
              <input type="text" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <button type="submit" class="save-btn">Save Changes</button>
          </form>
        </div>

        <!-- Password change form -->
        <div class="form-card">
          <div class="form-card-title">Change Password</div>
          <form method="POST">
            <input type="hidden" name="action" value="password">
            <div class="field">
              <label>Current Password</label>
              <input type="password" name="current_password" required>
            </div>
            <div class="field">
              <label>New Password</label>
              <input type="password" name="new_password" required>
              <span class="hint">Minimum 8 characters.</span>
            </div>
            <div class="field">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="save-btn">Change Password</button>
          </form>
        </div>
      </div>

    </div>
  </div>

  <script>
    // show preview when file selected
    function previewAvatar(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById('avatar-preview');
        preview.innerHTML = '<img id="preview-msg" src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
      };
      reader.readAsDataURL(file);
    }
  </script>
</body>
</html>