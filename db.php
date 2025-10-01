<?php
$serverName = "localhost\\SQLEXPRESS"; // keep same unless your SQL Server instance differs
$connectionOptions = [
    "Database" => "VisitorProApp_stg", // ✅ staging DB
    "TrustServerCertificate" => true
];

// Connect to SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Always set timezone
date_default_timezone_set("Asia/Karachi");
?>
