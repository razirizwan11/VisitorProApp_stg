<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}

include "db.php";

// Collect filters
$plant = $_POST['plant'] ?? '';
$department = $_POST['department'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$month = $_POST['month'] ?? '';

$sql = "SELECT * FROM Visitors WHERE 1=1";
$params = [];

if ($plant) { $sql .= " AND Plant = ?"; $params[] = $plant; }
if ($department) { $sql .= " AND Department = ?"; $params[] = $department; }
if ($purpose) { $sql .= " AND Purpose = ?"; $params[] = $purpose; }
if ($month) { $sql .= " AND MONTH(Date) = ?"; $params[] = $month; }

$sql .= " ORDER BY Date DESC, Time DESC";
$stmt = sqlsrv_query($conn, $sql, $params);
if (!$stmt) die(print_r(sqlsrv_errors(), true));

// Output headers for CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=visitor_records.csv');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['PassNumber','Name','NIC','Phone','Purpose','To Meet','Plant','Department','Document','Date','Time','Remarks','Checkout']);

// Fetch and write rows
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    fputcsv($output, [
        $row['PassNumber'],
        $row['Name'],
        $row['NIC'],
        $row['Phone'],
        $row['Purpose'],
        $row['To_Meet'],
        $row['Plant'],
        $row['Department'],
        $row['Document'],
        $row['Date']->format('Y-m-d'),
        $row['Time']->format('H:i'),
        $row['Remarks'],
        !empty($row['Checkout']) ? $row['Checkout']->format('H:i') : ''
    ]);
}

fclose($output);
exit;
?>
