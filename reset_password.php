<?php
session_start();
include "db.php";

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputOtp = $_POST['otp'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!isset($_SESSION['otp'], $_SESSION['otp_email'])) {
        $error = "No OTP generated. Please start again.";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $error = "OTP expired. Generate a new one.";
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    } elseif ($inputOtp != $_SESSION['otp']) {
        $error = "Invalid OTP.";
    } elseif ($newPass !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $sql = "UPDATE Guards SET PasswordHash = ? WHERE Email = ?";
        sqlsrv_query($conn, $sql, [$hash, $_SESSION['otp_email']]);
        $success = "Password updated successfully!";
        unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
        header("Refresh:2; url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: rgb(235,169,33); /* Golden yellow theme */
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-top: 50px;
}
.card {
    background-color: rgb(210,211,205);
    border-radius: 12px;
    padding: 25px;
}
.btn-dark {
    background-color: #333;
    border: none;
}
.btn-dark:hover {
    background-color: #222;
}
</style>
</head>
<body>
<div class="container py-5">
  <div class="col-md-4 mx-auto card shadow">
    <h4 class="mb-3 text-center">Reset Password</h4>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">OTP</label>
        <input type="text" name="otp" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-dark w-100">Reset Password</button>
    </form>
  </div>
</div>
</body>
</html>
