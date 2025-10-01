<?php
session_start();
include "db.php";

$pass = $_GET['pass'] ?? '';
if (!$pass) die("Pass number required");

$sql = "SELECT * FROM Visitors WHERE PassNumber = ?";
$stmt = sqlsrv_query($conn, $sql, [$pass]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) die("Visitor not found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visitor Pass</title>
<!-- Font Awesome for icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    padding: 30px;
    position: relative;
}

/* Home Button */
.home-btn {
    position: absolute;
    top: 20px;
    right: 30px;
    background-color: rgb(235,169,33);
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 30px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.home-btn i {
    font-size: 16px;
}
.home-btn:hover {
    background-color: #d4941a;
    transform: translateY(-3px);
}

.card {
    background-color: #ffffff;
    border: 2px solid #ccc;
    border-radius: 10px;
    padding: 30px;
    width: 450px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}
.card h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}
.card p {
    font-size: 15px;
    margin: 8px 0;
}
.card .label {
    font-weight: bold;
    color: #555;
}
.signature-section {
    margin-top: 35px;
    display: flex;
    justify-content: space-between;
}
.signature-box {
    width: 45%;
    text-align: center;
    border-top: 1px solid #000;
    padding-top: 5px;
    font-size: 14px;
    color: #333;
}
button.print-btn {
    margin-top: 30px;
    display: block;
    width: 100%;
    padding: 10px;
    background-color: rgb(235,169,33);
    border: none;
    color: #fff;
    font-size: 15px;
    cursor: pointer;
    border-radius: 5px;
    transition: background 0.2s, transform 0.2s;
}
button.print-btn:hover {
    background-color: #d4941a;
    transform: translateY(-2px);
}
@media print {
    button.print-btn, .home-btn { display: none; }
    body { background-color: #fff; padding: 0; }
    .card { box-shadow: none; border: 1px solid #000; }
}
</style>
</head>
<body>

<!-- Modern Home Button -->
<a href="index.php" class="home-btn"><i class="fa-solid fa-house-chimney"></i> Home</a>

<div class="card">
    <h2>Visitor Pass</h2>
    <p><span class="label">Pass #:</span> <?= htmlspecialchars($row['PassNumber']) ?></p>
    <p><span class="label">Name:</span> <?= htmlspecialchars($row['Name']) ?></p>
    <p><span class="label">NIC:</span> <?= htmlspecialchars($row['NIC']) ?></p>
    <p><span class="label">Phone:</span> <?= htmlspecialchars($row['Phone']) ?></p>
    <p><span class="label">Plant:</span> <?= htmlspecialchars($row['Plant']) ?></p>
    <p><span class="label">Department:</span> <?= htmlspecialchars($row['Department']) ?></p>
    <p><span class="label">Purpose:</span> <?= htmlspecialchars($row['Purpose']) ?></p>
    <p><span class="label">To Meet:</span> <?= htmlspecialchars($row['To_Meet']) ?></p>
    <p><span class="label">Date:</span> <?= $row['Date']->format('Y-m-d') ?></p>
    <p><span class="label">Time:</span> <?= $row['Time']->format('H:i') ?></p>
    <p><span class="label">Document:</span> <?= htmlspecialchars($row['Document']) ?></p>

    <div class="signature-section">
        <div class="signature-box">Visitor Signature</div>
        <div class="signature-box">Signature of <?= htmlspecialchars($row['To_Meet']) ?></div>
    </div>

    <button class="print-btn" onclick="window.print()">Print Pass</button>
</div>
</body>
</html>
