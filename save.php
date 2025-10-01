<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}

include "db.php";

// Visitor class
class Visitor {
    private $conn;
    public $name;
    public $nic;
    public $phone;
    public $purpose;
    public $to_meet;
    public $plant;
    public $department;
    public $documents;
    public $date;
    public $time;
    public $passNumber;
    public $enteredBy;

    public function __construct($conn, $enteredBy) {
        $this->conn = $conn;
        $this->date = date("Y-m-d");
        $this->time = date("H:i:s");
        $this->enteredBy = $enteredBy;
    }

    public function generatePassNumber() {
        do {
            $this->passNumber = rand(1000, 9999);
            $stmt = sqlsrv_query($this->conn, "SELECT PassNumber FROM Visitors WHERE PassNumber = ?", [$this->passNumber]);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } while ($row);
    }

    public function save() {
        // Handle single or multiple documents safely
        if (is_array($this->documents)) {
            $docStr = implode(", ", $this->documents);
        } else {
            $docStr = $this->documents; // string
        }

        $sql = "INSERT INTO Visitors 
                (Name, NIC, Phone, Purpose, To_Meet, Plant, Department, Document, Date, Time, PassNumber, Remarks, EnteredBy)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Under Progress', ?)";
        $params = [
            $this->name, $this->nic, $this->phone, $this->purpose, $this->to_meet, 
            $this->plant, $this->department, $docStr, $this->date, $this->time, $this->passNumber,
            $this->enteredBy
        ];

        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if (!$stmt) {
            die("Error saving visitor: " . print_r(sqlsrv_errors(), true));
        }
    }
}

// Get guard email and extract username
if (isset($_SESSION['email'])) {
    $enteredBy = explode('@', $_SESSION['email'])[0]; // only part before @
} else {
    $enteredBy = 'unknown';
}

// Collect & validate input
$visitor = new Visitor($conn, $enteredBy);
$visitor->name = preg_replace('/[^A-Za-z\s]/','', $_POST['name'] ?? '');
$visitor->nic = $_POST['nic'] ?? '';
$visitor->phone = $_POST['phone'] ?? '';
$visitor->purpose = $_POST['purpose'] ?? '';
$visitor->to_meet = preg_replace('/[^A-Za-z\s]/','', $_POST['to_meet'] ?? '');
$visitor->plant = $_POST['plant'] ?? '';
$visitor->department = $_POST['department'] ?? '';
$visitor->documents = $_POST['document'] ?? '';

// Server-side validation
if (empty($visitor->name) || empty($visitor->nic) || empty($visitor->phone) || 
    empty($visitor->purpose) || empty($visitor->to_meet) || empty($visitor->plant) || empty($visitor->department)) {
    die("All fields are required!");
}

if (!preg_match('/^\d{13}$/', $visitor->nic)) die("NIC must be 13 digits!");
if (!preg_match('/^\d{11}$/', $visitor->phone)) die("Phone must be 11 digits!");

// Generate PassNumber and save
$visitor->generatePassNumber();
$visitor->save();

// Redirect to print page
header("Location: print.php?pass=" . $visitor->passNumber);
exit;
?>
