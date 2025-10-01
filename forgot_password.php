<?php
session_start();
include "db.php";

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    if (!empty($email)) {
        // Check if email exists in DB
        $sql = "SELECT GuardID FROM Guards WHERE Email = ?";
        $stmt = sqlsrv_query($conn, $sql, [$email]);

        if ($stmt && sqlsrv_fetch($stmt)) {
            // Generate OTP and store in session
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_expiry'] = time() + 600; // 10 minutes

            $success = "OTP generated: $otp";

            // Redirect after 2 seconds
            header("Refresh:2; url=reset_password.php");
        } else {
            $error = "No account found with that email.";
        }
    } else {
        $error = "Please enter your email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ✅ Added Font Awesome for back button icon -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

<style>
  body {
      background-color: rgb(235,169,33);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
</style>
<script>
function copyOTP() {
    const otpText = document.getElementById('otpText');
    if (otpText) {
        const otp = otpText.innerText.split(': ')[1];
        navigator.clipboard.writeText(otp).then(() => {
            alert("OTP copied to clipboard!");
        }).catch(err => {
            alert("Failed to copy OTP: " + err);
        });
    }
}
</script>
</head>
<body>

<div class="container py-5">
  <div class="col-md-4 mx-auto card p-4 shadow">
    <h4 class="mb-3">Forgot Password</h4>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span id="otpText"><?= htmlspecialchars($success) ?></span>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyOTP()">Copy OTP</button>
        </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Enter your email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-warning w-100">Generate OTP</button>
    </form>
  </div>
</div>


<a href="index.php" class="btn btn-warning rounded-circle shadow-sm" 
   style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; 
          position:fixed; top:15px; left:30px; z-index:1000; color:#000;">
    <i class="fa-solid fa-arrow-left"></i>
</a>

</body>
</html>
