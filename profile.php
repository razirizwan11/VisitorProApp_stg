<?php
session_start();
include "db.php";

// ✅ Redirect if not logged in
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}

// 🔒 Force password reset check
if (!empty($_SESSION['force_password_change'])) {
    header("Location: change_password.php");
    exit;
}

// Initialize variables
$username   = $_SESSION['username'] ?? 'Unknown';
$email      = '-';
$profilePic = $_SESSION['profile_pic'] ?? 'default.png';
$lastLogin  = 'Never';
$success    = '';
$error      = '';
$hashFromDB = null;

// Fetch data from DB
$sql = "SELECT Email, ProfilePic, LastLogin, PasswordHash FROM Guards WHERE GuardID = ?";
$stmt = sqlsrv_query($conn, $sql, [$_SESSION['guard_id']]);
if ($stmt && $guard = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $email      = $guard['Email'] ?? '-';
    $profilePic = $guard['ProfilePic'] ?? $profilePic;
    $hashFromDB = $guard['PasswordHash'] ?? null;
    if (!empty($guard['LastLogin']) && $guard['LastLogin'] instanceof DateTime) {
        $lastLogin = $guard['LastLogin']->format('Y-m-d H:i');
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowedExt)) {
            $filename = "guard_" . $username . "." . $ext;
            $target = "uploads/" . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $_SESSION['profile_pic'] = $filename;
                $profilePic = $filename;
                $update = "UPDATE Guards SET ProfilePic = ? WHERE GuardID = ?";
                sqlsrv_query($conn, $update, [$filename, $_SESSION['guard_id']]);
                $success = "Profile picture updated!";
            } else $error = "File upload failed.";
        } else $error = "Only JPG, JPEG, PNG, GIF, WEBP allowed.";
    } else $error = "File upload error.";
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'])) {
    $current = trim($_POST['current_password']);
    $new     = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if (!$hashFromDB || !password_verify($current, $hashFromDB)) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new) || !preg_match('/[\W]/', $new)) {
        $error = "Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $sql = "UPDATE Guards SET PasswordHash = ? WHERE GuardID = ?";
        $stmt = sqlsrv_query($conn, $sql, [$hash, $_SESSION['guard_id']]);
        if ($stmt) $success = "Password updated successfully!";
        else $error = "Error updating password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #eba921;
    padding: 20px;
}
.card {
    max-width: 700px;
    margin: auto;
    background: #f4f4f4;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
}
h3, h5 { margin-bottom: 10px; }
.profile-pic {
    width: 120px; height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #eba921;
    margin-bottom: 10px;
}
.btn-custom {
    background: #eba921;
    color: white;
    font-weight: bold;
    padding: 4px 10px;
    font-size: 0.9rem;
}
.btn-custom:hover { background: #d4941a; }
.progress { height: 12px; border-radius: 6px; margin-top: 5px; }
.strength-1 { background: #d9534f; }
.strength-2 { background: #e67e22; }
.strength-3 { background: #f1c40f; }
.strength-4 { background: #28a745; } /* ✅ Green for very strong */
.text-weak   { color: #d9534f; }
.text-medium { color: #e67e22; }
.text-strong { color: #f1c40f; }
.text-very   { color: #28a745; font-weight: bold; }
.form-label { margin-bottom: 2px; font-weight: 600; }
.form-control { padding: 6px 10px; }
.alert { padding: 6px 12px; margin-bottom: 10px; }
</style>
</head>
<body>
<a href="index.php" class="btn btn-warning rounded-circle shadow-sm" 
   style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; position:fixed; top:20px; left:20px; z-index:1000; color:#000;">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="card text-center">
    <h3>Profile</h3>
    <img src="uploads/<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture" class="profile-pic">
    <p><b>Name:</b> <?= htmlspecialchars($username) ?> &nbsp; | &nbsp; <b>Email:</b> <?= htmlspecialchars($email) ?></p>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Profile Picture Upload -->
    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center justify-content-center gap-2 mb-3">
        <input type="file" name="profile_pic" class="form-control form-control-sm" style="max-width:300px;">
        <button type="submit" class="btn btn-custom">Upload</button>
    </form>

    <!-- Change Password Form -->
    <h5>Change Password</h5>
    <form method="POST" class="text-start">
        <div class="mb-2">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" id="new_password" class="form-control" required>
            <div class="progress">
                <div id="strengthBar" class="progress-bar" style="width:0%; transition: width 0.3s;"></div>
            </div>
            <small id="strengthText" class="form-text fw-bold d-block text-center"></small>
        </div>
        <div class="mb-2">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-dark w-100">Update Password</button>
    </form>
</div>

<script>
const passwordInput = document.getElementById("new_password");
const strengthBar   = document.getElementById("strengthBar");
const strengthText  = document.getElementById("strengthText");

passwordInput.addEventListener("input", () => {
    let val = passwordInput.value, strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[\W]/.test(val)) strength++;

    strengthBar.className = "progress-bar";
    strengthText.className = "form-text fw-bold d-block text-center";

    if (strength === 0) {
        strengthBar.style.width = "0%"; strengthText.textContent = "";
    } else if (strength === 1) {
        strengthBar.style.width = "25%"; strengthBar.classList.add("strength-1");
        strengthText.textContent = "Weak"; strengthText.classList.add("text-weak");
    } else if (strength === 2) {
        strengthBar.style.width = "50%"; strengthBar.classList.add("strength-2");
        strengthText.textContent = "Medium"; strengthText.classList.add("text-medium");
    } else if (strength === 3) {
        strengthBar.style.width = "75%"; strengthBar.classList.add("strength-3");
        strengthText.textContent = "Strong"; strengthText.classList.add("text-strong");
    } else {
        strengthBar.style.width = "100%"; strengthBar.classList.add("strength-4");
        strengthText.textContent = "Very Strong"; strengthText.classList.add("text-very");
    }
});
</script>
</body>
</html>
