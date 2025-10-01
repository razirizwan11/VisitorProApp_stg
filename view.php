<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}

include "db.php";

class VisitorView {
    private $conn;
    public $filters = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getVisitors() {
        $sql = "SELECT * FROM Visitors WHERE 1=1";
        $params = [];

        if (!empty($this->filters['q'])) {
            $sql .= " AND (Name LIKE ? OR NIC LIKE ? OR Phone LIKE ?)";
            $params[] = "%" . $this->filters['q'] . "%";
            $params[] = "%" . $this->filters['q'] . "%";
            $params[] = "%" . $this->filters['q'] . "%";
        }

        if (!empty($this->filters['plant'])) {
            $sql .= " AND Plant = ?";
            $params[] = $this->filters['plant'];
        }
        if (!empty($this->filters['department'])) {
            $sql .= " AND Department = ?";
            $params[] = $this->filters['department'];
        }
        if (!empty($this->filters['month'])) {
            $sql .= " AND MONTH(Date) = ?";
            $params[] = $this->filters['month'];
        }
        if (!empty($this->filters['purpose'])) {
            $sql .= " AND Purpose = ?";
            $params[] = $this->filters['purpose'];
        }
        if (!empty($this->filters['date'])) {
            $sql .= " AND CAST(Date AS DATE) = ?";
            $params[] = $this->filters['date'];
        }

        $sql .= " ORDER BY Date DESC, Time DESC";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if (!$stmt) die(print_r(sqlsrv_errors(), true));

        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    public function checkout($passNumber) {
        $sql = "UPDATE Visitors SET Remarks = 'Completed', Checkout = GETDATE() WHERE PassNumber = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$passNumber]);
        return $stmt !== false;
    }
}

$view = new VisitorView($conn);
if (isset($_GET['checkout'])) {
    $view->checkout($_GET['checkout']);
}

$view->filters['q'] = $_GET['q'] ?? '';
$view->filters['plant'] = $_GET['plant'] ?? '';
$view->filters['department'] = $_GET['department'] ?? '';
$view->filters['month'] = $_GET['month'] ?? '';
$view->filters['purpose'] = $_GET['purpose'] ?? '';
$view->filters['date'] = $_GET['date'] ?? '';

$visitors = $view->getVisitors();

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=visitors.xls");
    $output = fopen("php://output", "w");

    fputcsv($output, ["Pass#", "Name", "NIC", "Phone", "Purpose", "To Meet", "Plant", "Department", "Document", "Date", "Time", "Remarks", "Checkout", "Entered By"], "\t");

    foreach ($visitors as $row) {
        $doc = is_array($row['Document']) ? implode(",", $row['Document']) : $row['Document'];
        fputcsv($output, [
            $row['PassNumber'],
            $row['Name'],
            $row['NIC'],
            $row['Phone'],
            $row['Purpose'],
            $row['To_Meet'],
            $row['Plant'],
            $row['Department'],
            $doc,
            $row['Date']->format('Y-m-d'),
            $row['Time']->format('H:i'),
            $row['Remarks'],
            (!empty($row['Checkout']) ? $row['Checkout']->format('H:i') : ''),
            $row['EnteredBy'] ?? 'unknown'
        ], "\t");
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visitor Records</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: rgb(235,169,33);
    padding: 20px;
    font-size: 14px;
}
.card {
    background:rgb(210,211,205);
    border-radius: 12px;
    padding: 20px;
    max-width: 95%;
    margin: 0 auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
h2 {
    font-weight: bold;
    color: #333;
    font-size: 20px;
}
.table th {
    background: #212529;
    color: #fff;
    text-align: center;
    font-size: 13px;
}
.table td {
    vertical-align: middle;
    font-size: 13px;
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}
.table-hover tbody tr:hover {
    background-color: #f1f1f1;
}
.btn-custom {
    border-radius: 25px;
    font-weight: 500;
    font-size: 13px;
    transition: all 0.3s ease;
    padding: 6px 14px;
}
.btn-custom:hover {
    transform: scale(1.03);
}
.remarks-under { background: #ffe5e5; color: #d9534f; font-weight: bold; font-size: 12px; }
.remarks-completed { background: #e6ffed; color: #28a745; font-weight: bold; font-size: 12px; }
</style>
</head>
<body>
<!-- Back Button -->
<a href="index.php" class="btn btn-warning rounded-circle shadow-sm" 
   style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; 
          position:fixed; top:15px; left:10px; z-index:1000; color:#000;">
    <i class="fa-solid fa-arrow-left"></i>
</a>



<div class="card shadow">
    <h2 class="text-center mb-4"><i class="fa-solid fa-users"></i> Visitor Records</h2>

    <form method="GET" class="mb-4 row g-2">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search by Name, NIC, Phone">
        </div>
        <div class="col-md-2"><select name="plant" class="form-select form-select-sm">
            <option value="">Plant</option>
            <option <?= ($_GET['plant'] ?? '') === 'A-18' ? 'selected' : '' ?>>A-18</option>
            <option <?= ($_GET['plant'] ?? '') === 'Dhabeji' ? 'selected' : '' ?>>Dhabeji</option>
            <option <?= ($_GET['plant'] ?? '') === 'Sales office' ? 'selected' : '' ?>>Sales office</option>
            <option <?= ($_GET['plant'] ?? '') === 'Sky towers' ? 'selected' : '' ?>>Sky towers</option>
            <option <?= ($_GET['plant'] ?? '') === 'D-89' ? 'selected' : '' ?>>D-89</option>
        </select></div>
        <div class="col-md-2"><select name="department" class="form-select form-select-sm">
            <option value="">Department</option>
            <option <?= ($_GET['department'] ?? '') === 'IT' ? 'selected' : '' ?>>IT</option>
            <option <?= ($_GET['department'] ?? '') === 'Finance' ? 'selected' : '' ?>>Finance</option>
            <option <?= ($_GET['department'] ?? '') === 'Sales' ? 'selected' : '' ?>>Sales</option>
            <option <?= ($_GET['department'] ?? '') === 'HR' ? 'selected' : '' ?>>HR</option>
            <option <?= ($_GET['department'] ?? '') === 'Production' ? 'selected' : '' ?>>Production</option>
            <option <?= ($_GET['department'] ?? '') === 'Store' ? 'selected' : '' ?>>Store</option>
        </select></div>
        <div class="col-md-2"><select name="purpose" class="form-select form-select-sm">
            <option value="">Purpose</option>
            <option <?= ($_GET['purpose'] ?? '') === 'Interview' ? 'selected' : '' ?>>Interview</option>
            <option <?= ($_GET['purpose'] ?? '') === 'Vendor' ? 'selected' : '' ?>>Vendor</option>
            <option <?= ($_GET['purpose'] ?? '') === 'Visitor' ? 'selected' : '' ?>>Visitor</option>
            <option <?= ($_GET['purpose'] ?? '') === 'Meeting Scheduled' ? 'selected' : '' ?>>Meeting Scheduled</option>
        </select></div>
        <div class="col-md-2">
    <select name="month" class="form-select form-select-sm">
        <option value="">Month</option>
        <?php
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        foreach ($months as $num => $name) {
            $selected = ((int)($_GET['month'] ?? 0) === $num) ? 'selected' : '';
            echo "<option value='$num' $selected>$name</option>";
        }
        ?>
    </select>
        </div>
        <div class="col-md-2"><input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>"></div>
        <div class="col-md-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-dark btn-custom"><i class="fa-solid fa-search"></i> Search</button>
            <a href="view.php" class="btn btn-secondary btn-custom">Reset</a>
            <a href="?export=excel" class="btn btn-success btn-custom"><i class="fa-solid fa-file-excel"></i> Export</a>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>#</th><th>Pass#</th><th>Name</th><th>NIC</th><th>Phone</th>
                <th>Purpose</th><th>To Meet</th><th>Plant</th><th>Dept</th>
                <th>Doc</th><th>Date</th><th>Time</th><th>Remarks</th><th>Checkout</th><th>Entered By</th>
            </tr>
        </thead>
        <tbody>
<?php
$count = 0;
if (!empty($visitors)) {
    foreach ($visitors as $row) {
        $count++;
        $remarkClass = ($row['Remarks'] === 'Under Progress') ? 'remarks-under' : 'remarks-completed';
        echo "<tr>";
        echo "<td>$count</td>";
        echo "<td>{$row['PassNumber']}</td>";
        echo "<td>{$row['Name']}</td>";
        echo "<td>{$row['NIC']}</td>";
        echo "<td>{$row['Phone']}</td>";
        echo "<td>{$row['Purpose']}</td>";
        echo "<td>{$row['To_Meet']}</td>";
        echo "<td>{$row['Plant']}</td>";
        echo "<td>{$row['Department']}</td>";
        echo "<td>{$row['Document']}</td>";
        echo "<td>{$row['Date']->format('Y-m-d')}</td>";
        echo "<td>{$row['Time']->format('H:i')}</td>";
        echo "<td class='$remarkClass'>{$row['Remarks']}</td>";
        echo "<td>";
        if ($row['Remarks'] === 'Under Progress') {
            echo "<a href='view.php?checkout={$row['PassNumber']}' class='btn btn-sm btn-success btn-custom'><i class='fa-solid fa-check'></i> Checkout</a>";
        } elseif (!empty($row['Checkout'])) {
            echo $row['Checkout']->format('H:i');
        }
        echo "</td>";
        echo "<td>" . ($row['EnteredBy'] ?? 'unknown') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='15' class='text-center text-danger fw-bold'>No results found.</td></tr>";
}
?>
        </tbody>
    </table>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <p class="mb-0"><b>Total Records:</b> <?= $count ?></p>
    </div>
</div>

</body>
</html>
