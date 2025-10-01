<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $sql = "SELECT * FROM Guards WHERE Email = ?";
        $stmt = sqlsrv_query($conn, $sql, [$email]);

        if ($stmt === false) {
            $error = "Database error: " . print_r(sqlsrv_errors(), true);
        } elseif ($guard = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (password_verify($password, $guard['PasswordHash'])) {
                // ✅ Set session variables
                $_SESSION['guard_id']    = $guard['GuardID'];
                $_SESSION['email']       = $guard['Email'];
                $_SESSION['username']    = explode('@', $guard['Email'])[0];
                $_SESSION['profile_pic'] = $guard['ProfilePic'] ?? 'default.png';

                // 🔒 Force password reset
                if (!empty($guard['ForcePasswordChange'])) {
                    $_SESSION['force_password_change'] = true;
                    header("Location: change_password.php");
                    exit;
                }

                // 🌐 Update LastLogin and IP
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $updateLogin = "UPDATE Guards SET LastLogin = GETDATE(), LastIP = ? WHERE GuardID = ?";
                sqlsrv_query($conn, $updateLogin, [$ip, $guard['GuardID']]);

                // 📝 Insert into Logins table
                $insertLogin = "INSERT INTO Logins (GuardID, IPAddress) VALUES (?, ?)";
                sqlsrv_query($conn, $insertLogin, [$guard['GuardID'], $ip]);

                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "No account found with this email!";
        }
    } else {
        $error = "Please enter both email and password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Guard Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: rgb(235,169,33);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      background-color: rgb(210,211,205);
      border-radius: 14px;
      padding: 25px;
      width: 380px;
    }
    .card h3 {
      font-weight: 600;
      color: #333;
    }
    .logo {
      display: block;
      margin: 0 auto 20px auto;
      max-height: 70px;
    }
    .form-control {
      border-radius: 10px;
      font-size: 0.9rem;
    }
    .form-control:focus {
      border-color: rgb(235,169,33);
      box-shadow: 0 0 6px rgba(235,169,33,0.5);
    }
    .btn-login {
      background-color: #333;
      color: #fff;
      border-radius: 10px;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.25);
    }
    .signup-link {
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow">
    <img src="logo.png" alt="Logo" class="logo">
    <h3 class="text-center mb-3"><i class="bi bi-shield-lock-fill me-2"></i> Login</h3>

    <?php if (!empty($error)) { ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php } ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label class="form-label fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <div class="mb-3 text-end">
      <a href="forgot_password.php" class="text-decoration-none text-primary small">
        Forgot password?
      </a>
      </div>
      <button type="submit" class="btn btn-login w-100 mt-2">
        <i class="bi bi-box-arrow-in-right me-1"></i> Login
      </button>
    </form>

    <div class="text-center mt-3 signup-link">
      <small>Don’t have an account? <a href="signup.php" class="fw-semibold text-decoration-none">Sign up</a></small>
    </div>
  </div>
</div>

</body>
</html>
