<?php
session_start();
include "db.php";

// Pakistan timezone
date_default_timezone_set("Asia/Karachi");

$pass = $_GET['pass'] ?? '';
if (!$pass) {
    die("Error: Pass number is required.");
}

// Set checkout time
$checkoutTime = date("H:i:s");

// Update record
$sql = "UPDATE Visitors 
        SET Remarks = 'Visit Completed', 
            Checkout = ? 
        WHERE PassNumber = ?";
$params = [$checkoutTime, $pass];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    header("Location: view.php?msg=Checked+out");
    exit;
} else {
    die("Error checking out: " . print_r(sqlsrv_errors(), true));
}
?>
