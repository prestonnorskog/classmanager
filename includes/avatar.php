<?php
// get avatar from session or database
$avatar_pic = $_SESSION['profile_pic'] ?? null;

if (!$avatar_pic) {
    $avatar_row = $conn->query("SELECT profile_pic FROM users WHERE user_id = {$_SESSION['user_id']}")->fetch_assoc();
    $avatar_pic = $avatar_row['profile_pic'] ?? null;
    if ($avatar_pic) {
        $_SESSION['profile_pic'] = $avatar_pic;
    }
}

// create initials and check if pic exists
$avatar_initials = implode('', array_map(fn($w) => $w[0], explode(' ', $_SESSION['name'])));
$avatar_has_pic = !empty($avatar_pic) && file_exists($avatar_pic);

// build avatar HTML
if ($avatar_has_pic) {
    $avatar_html = '<div class="avatar" style="overflow:hidden;padding:0;">
        <img src="' . htmlspecialchars($avatar_pic) . '" style="width:100%;height:100%;object-fit:cover;">
    </div>';
} else {
    $avatar_html = '<div class="avatar">' . $avatar_initials . '</div>';
}
?>