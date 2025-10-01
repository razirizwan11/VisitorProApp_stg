<?php
session_start();
include "db.php";

// Check if user is logged in and password change is forced
if (!isset($_SESSION['guard_id']) || empty($_SESSION['force_password_change'])) {
    header("Location: index.php");
    exit;
}

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPass = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // Validate inputs
    if ($newPass !== $confirm) {
        $error = "Passwords do not match!";
    } 
    elseif (strlen($newPass) < 8 || 
            !preg_match('/[A-Z]/', $newPass) || 
            !preg_match('/[a-z]/', $newPass) || 
            !preg_match('/[0-9]/', $newPass) || 
            !preg_match('/[\W]/', $newPass)) {
        $error = "Password must be at least 8 characters long, and include uppercase, lowercase, number, and special character.";
    } 
    else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);

        $sql = "UPDATE Guards SET PasswordHash = ? WHERE GuardID = ?";
        $params = [$hash, $_SESSION['guard_id']];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            // Security: regenerate session ID and remove force flag
            session_regenerate_id(true);
            unset($_SESSION['force_password_change']);

            // Redirect to dashboard with success message
            header("Location: index.php?msg=password_updated");
            exit;
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card p-4 mx-auto shadow" style="max-width:400px;">
        <h3 class="mb-3 text-center">Change Password</h3>

        <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Update Password</button>
        </form>
    </div>
</div>
</body>
</html>
