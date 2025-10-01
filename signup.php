<?php
session_start();
include "db.php";

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($email) || empty($password) || empty($phone)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (!preg_match('/^\d{11}$/', $phone)) {
        $error = "Phone must be 11 digits!";
    } else {
        $stmt = sqlsrv_query($conn, "SELECT * FROM Guards WHERE Email = ?", [$email]);
        if ($stmt && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $error = "Email already registered!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Guards (Email, PasswordHash, Phone) VALUES (?, ?, ?)";
            $insert = sqlsrv_query($conn, $sql, [$email, $hash, $phone]);
            if ($insert) {
                $success = "Signup successful! You can now login.";
            } else {
                $error = "Signup failed! Database error:<br>" . print_r(sqlsrv_errors(), true);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    background-color: rgb(235,169,33);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: Arial, sans-serif;
}
.card {
    background-color: rgb(210,211,205);
    padding: 30px;
    border-radius: 12px;
    width: 380px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.25);
}
.logo {
    display: block;
    margin: 0 auto 15px auto;
    max-height: 70px;
}
h3 {
    font-weight: bold;
    font-size: 22px;
    margin-bottom: 20px;
}

/* Success sliding alert */
.alert-success {
    position: fixed;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #4BB543;
    color: #fff;
    padding: 15px 25px;
    border-radius: 8px;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
    animation: slideDown 4s forwards;
}
@keyframes slideDown {
    0% { top: -60px; opacity: 0; }
    10% { top: 20px; opacity: 1; }
    80% { top: 20px; opacity: 1; }
    100% { top: -60px; opacity: 0; }
}
.alert-danger {
    background-color: #e74c3c;
    color: #fff;
    border: none;
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
}

/* Input fields */
.form-control {
    border-radius: 6px;
    font-size: 14px;
}

/* Buttons */
.btn-custom {
    background-color: #212529;
    color: #fff;
    font-weight: bold;
    border-radius: 6px;
    transition: 0.3s;
}
.btn-custom:hover {
    background-color: #000;
}
</style>
</head>
<body>

<?php if ($success): ?>
    <div class="alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card shadow">
    <img src="logo.png" alt="Logo" class="logo">
    <h3 class="text-center"><i class="fa-solid fa-user-plus"></i> Sign Up</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="signup.php">
        <div class="mb-3">
            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
            <input type="email" name="email" class="form-control" required placeholder="@amrelisteels.com">
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fa-solid fa-phone"></i> Phone</label>
            <input type="text" name="phone" class="form-control" required maxlength="11" pattern="\d{11}" placeholder="11 digits only">
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fa-solid fa-lock"></i> Password</label>
            <input type="password" name="password" class="form-control" required placeholder="********">
        </div>
        <button type="submit" class="btn btn-custom w-100"><i class="fa-solid fa-user-plus"></i> Sign Up</button>
    </form>

    <div class="text-center mt-3">
        <small>Already have an account? <a href="login.php">Login</a></small>
    </div>
</div>

</body>
</html>
