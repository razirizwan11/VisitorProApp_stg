<?php
session_start();
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}
$enteredBy = $_SESSION['email'] ?? 'unknown'; // ✅ Store guard email
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <meta charset="UTF-8">
  <title>Add Visitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
        background-color: rgb(235,169,33);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .navbar { background-color: rgba(138, 138, 135, 1); }
    .card {
        background-color: rgb(245, 245, 245);
        border-radius: 14px;
        padding: 25px;
    }
    .card h3 { font-weight: 600; color: #333; }
    .logo { height: 42px; margin-right: 10px; }
    .form-control, .form-select {
        border-radius: 10px;
        font-size: 0.9rem;
    }
    .form-control:focus, .form-select:focus {
        border-color: rgb(235,169,33);
        box-shadow: 0 0 6px rgba(235,169,33,0.5);
    }
    .btn-interactive {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 10px;
    }
    .btn-interactive:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>

<!-- Back Button -->
<a href="index.php" class="btn btn-warning rounded-circle shadow-sm" 
   style="width:30px; height:30px; display:flex; align-items:center; justify-content:center; position:fixed; top:90px; left:30px; z-index:1000; color:#000;">
    <i class="fa fa-arrow-left"></i>
</a>

<!-- Navbar -->
<nav class="navbar shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand d-flex align-items-center text-white fw-bold">
      <img src="logo.png" alt="Logo" class="logo"> VisitorPro
    </span>
    <a href="logout.php" class="btn btn-dark btn-sm btn-interactive">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</nav>

<!-- Form Container -->
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow">
        <h3 class="text-center mb-4"><i class="bi bi-person-plus-fill me-2"></i>Add Visitor</h3>

        <form method="POST" action="save.php" id="visitorForm">
          <input type="hidden" name="entered_by" value="<?= htmlspecialchars($enteredBy) ?>">

          <!-- Row 1: Location + Department -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Location</label>
              <select name="plant" id="plant" class="form-select" required>
                <option value="">Select Location</option>
                <option value="A-18">A-18</option>
                <option value="Dhabeji">Dhabeji</option>
                <option value="Sales office">Sales Office</option>
                <option value="Sky towers">Sky Towers</option>
                <option value="D-89">D-89</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Department</label>
              <select name="department" id="department" class="form-select" required>
                <option value="">Select Department</option>
              </select>
            </div>
          </div>

          <!-- Row 2: NIC + Visitor Name -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">NIC</label>
              <input type="text" name="nic" id="nicField" class="form-control" placeholder="13 digits"
                     pattern="\d{13}" maxlength="13" title="Must be 13 digits" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Visitor Name</label>
              <input type="text" name="name" id="nameField" class="form-control" placeholder="Enter full name" required>
            </div>
          </div>

          <!-- Row 3: Phone + Purpose -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Phone</label>
              <input type="text" name="phone" id="phoneField" class="form-control" placeholder="11 digits"
                     pattern="\d{11}" maxlength="11" title="Must be 11 digits" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Purpose</label>
              <select name="purpose" class="form-select" required>
                <option value="">Select Purpose</option>
                <option>Interview</option>
                <option>Vendor</option>
                <option>Visitor</option>
                <option>Meeting Scheduled</option>
              </select>
            </div>
          </div>

          <!-- Row 4: To Meet + Document -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">To Meet</label>
              <input type="text" name="to_meet" id="toMeetField" class="form-control" placeholder="Person to meet" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Document Provided</label>
              <select name="document" class="form-select" required>
                <option value="">Select Document</option>
                <option>CNIC</option>
                <option>Driving License</option>
                <option>University ID</option>
                <option>Visiting Card</option>
              </select>
            </div>
          </div>

          <!-- Buttons -->
          <div class="d-flex justify-content-between mt-4">
            <a href="view.php" class="btn btn-secondary btn-interactive">
              <i class="bi bi-eye-fill me-2"></i> View Visitors
            </a>
            <button type="submit" class="btn btn-dark btn-interactive">
              <i class="bi bi-save-fill me-2"></i> Save & Print
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  // Prevent numbers in Name + To Meet fields
  function blockNumbers(e) {
    this.value = this.value.replace(/[0-9]/g, '');
  }
  document.getElementById('nameField').addEventListener('input', blockNumbers);
  document.getElementById('toMeetField').addEventListener('input', blockNumbers);

  // ✅ Prevent alphabets in NIC + Phone fields
  function blockAlphabets(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
  }
  document.getElementById('nicField').addEventListener('input', blockAlphabets);
  document.getElementById('phoneField').addEventListener('input', blockAlphabets);

  // Department dynamic options
  const plantSelect = document.getElementById('plant');
  const deptSelect = document.getElementById('department');
  const deptOptions = {
    "Sky towers": ["HR"],
    "Sales office": ["IT", "Finance", "Sales"],
    "A-18": ["Logistics", "Sales", "IT"],
    "D-89": ["Production", "Procurement", "Store"],
    "Dhabeji": ["Production", "Procurement", "Store"]
  };
  plantSelect.addEventListener('change', function () {
    const plant = this.value;
    deptSelect.innerHTML = '<option value="">Select Department</option>';
    if (deptOptions[plant]) {
      deptOptions[plant].forEach(function (dept) {
        let option = document.createElement('option');
        option.value = dept;
        option.textContent = dept;
        deptSelect.appendChild(option);
      });
    }
  });

  // Auto-fill by NIC
  document.getElementById('nicField').addEventListener('blur', function() {
    const nic = this.value;
    if (nic.length === 13) {
      fetch('get_visitor.php?nic=' + nic)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('nameField').value = data.name;
            document.getElementById('phoneField').value = data.phone;
            document.getElementById('toMeetField').value = data.to_meet;
            plantSelect.value = data.plant;
            plantSelect.dispatchEvent(new Event('change'));
            deptSelect.value = data.department;
          }
        })
        .catch(err => console.error(err));
    }
  });
</script>
</body>
</html>
