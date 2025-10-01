<?php
session_start();

// 1️⃣ Check if user is logged in
if (!isset($_SESSION['guard_id'])) {
    header("Location: login.php");
    exit;
}

// 2️⃣ Force password reset check
if (!empty($_SESSION['force_password_change'])) {
    header("Location: change_password.php");
    exit;
}

include "db.php";

// Extract username from email
$email = $_SESSION['email'] ?? '';
$username = explode('.', $email)[0] ?? 'Guest';
$username = ucfirst($username);

// --- Get real stats from DB ---
$totalVisitorsToday = $currentInside = $completedVisits = $pendingCheckouts = 0;

// Visitors today
$sqlToday = "SELECT COUNT(*) AS cnt FROM Visitors WHERE CAST(Date AS DATE) = CAST(GETDATE() AS DATE)";
$stmt = sqlsrv_query($conn, $sqlToday);
if ($stmt) {
    $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $totalVisitorsToday = (int) ($r['cnt'] ?? 0);
}

// Currently inside
$sqlInside = "SELECT COUNT(*) AS cnt FROM Visitors WHERE Remarks = 'Under Progress'";
$stmt = sqlsrv_query($conn, $sqlInside);
if ($stmt) {
    $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $currentInside = (int) ($r['cnt'] ?? 0);
}

// Completed visits
$sqlCompleted = "SELECT COUNT(*) AS cnt FROM Visitors WHERE ISNULL(Remarks,'') <> 'Under Progress'";
$stmt = sqlsrv_query($conn, $sqlCompleted);
if ($stmt) {
    $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $completedVisits = (int) ($r['cnt'] ?? 0);
}

// Pending checkouts (same as currently inside)
$pendingCheckouts = $currentInside;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Visitor Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{ --gold: rgb(235,169,33); }
html,body{height:100%}
body{
    margin:0;
    min-height:100%;
    background: var(--gold);
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
    color:#222;
}
.navbar { background-color: rgba(81, 81, 79, 1); }
.brand { display:flex;align-items:center;gap:12px;color:#fff;font-weight:700; }
.brand img{height:40px; width:auto;}
.page-wrap { padding:42px 16px 90px; }
.main-card {
    max-width:780px;
    margin: 0 auto;
    background:rgb(210, 211, 205);
    border-radius:12px;
    padding:22px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.controls-row .form-control, .controls-row .form-select { height:42px; border-radius:8px; }
.clock-pill {
    background: #f5f5f5;
    color:#222;
    padding:6px 12px;
    border-radius:999px;
    font-weight:600;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    display:flex;
    gap:8px;
    align-items:center;
    font-size:13px;
}
.clock-icon { color:var(--gold); font-size:15px; }
#welcome-popup {
    position: fixed;
    top:18px;
    left: 50%;
    transform: translateX(-50%) translateY(-30px);
    background-color: rgba(75,181,67,0.98);
    color: #fff;
    padding:10px 18px;
    border-radius:9px;
    font-weight:700;
    box-shadow: 0 8px 22px rgba(0,0,0,0.18);
    z-index:1100;
    opacity:0;
    transition: transform .45s, opacity .35s;
}
#welcome-popup.show { transform: translateX(-50%) translateY(0); opacity:1; }
#search-popup {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%) translateY(-30px);
    background-color: #dc3545;
    color: #fff;
    padding: 10px 18px;
    border-radius: 9px;
    font-weight: 600;
    box-shadow: 0 8px 22px rgba(0,0,0,0.2);
    z-index: 1200;
    opacity: 0;
    transition: transform .45s, opacity .35s;
}
.stats-footer {
    max-width:960px;
    margin: 24px auto 18px;
    display:flex;
    gap:14px;
    justify-content:center;
    flex-wrap:wrap;
}
.stat-mini {
    flex: 1 1 220px;
    min-width:180px;
    max-width:280px;
    background:rgb(210, 211, 205);
    border-radius:10px;
    padding:12px 16px;
    display:flex;
    gap:12px;
    align-items:center;
    box-shadow:0 4px 14px rgba(0,0,0,0.1);
}
.stat-icon {
    height:46px;width:46px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;
}
.stat-blue{ background:#007bff; }
.stat-green{ background:#28a745; }
.stat-orange{ background:#fd7e14; }
.stat-info { font-size:13px;margin:0;color:#555; }
.stat-number { font-size:20px;font-weight:700;margin:0; }
.btn-lg-rounded { border-radius:10px;padding:8px 16px; font-weight:600; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid px-3 d-flex align-items-center justify-content-between">
    <div class="brand">
      <img src="logo.png" alt="logo">
      <div style="line-height:1;">
        <div style="font-size:16px;color:#fff;font-weight:700">VisitorPro</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.85);margin-top:2px">Security Dashboard</div>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <div class="clock-pill" id="clockPill" style="display:flex;align-items:center;gap:8px;">
        <i class="bi bi-clock-history clock-icon"></i>
        <div id="clockText">--:--</div>

        <div style="position:relative; cursor:pointer;">
          <i class="bi bi-bell-fill" style="font-size:16px;color:#333;"></i>
          <?php if($pendingCheckouts > 0): ?>
            <span style="
              position:absolute;
              top:-6px;
              right:-6px;
              background:red;
              color:white;
              border-radius:50%;
              padding:2px 6px;
              font-size:10px;
              font-weight:bold;
            ">
              <?= $pendingCheckouts ?>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="dropdown">
        <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($username) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div id="welcome-popup">Welcome, <?= htmlspecialchars($username) ?>!</div>
<div id="search-popup">Search filters left blank!</div>

<div class="page-wrap">
  <div class="main-card">
    <h2>Dashboard</h2>
    <p class="lead">Quick actions and search — jump straight to visitor lists or add a visitor.</p>
    <form id="searchForm" action="view.php" method="get" class="row controls-row g-2 align-items-center">
      <div class="col-md-6">
        <input name="q" id="searchInput" class="form-control" placeholder="Search visitors by Name, NIC or Phone...">
      </div>
      
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark btn-lg-rounded w-100">
          <i class="bi bi-search me-1"></i> Search
        </button>
      </div>
    </form>

    <div class="d-flex gap-2 justify-content-center mt-4">
      <a href="view.php" class="btn btn-secondary btn-lg-rounded"><i class="bi bi-eye-fill me-2"></i> View Visitors</a>
      <a href="addvisitor.php" class="btn btn-dark btn-lg-rounded"><i class="bi bi-person-plus-fill me-2"></i> Add Visitor</a>
    </div>
  </div>
</div>

<div class="stats-footer">
  <div class="stat-mini">
    <div class="stat-icon stat-blue"><i class="bi bi-people-fill"></i></div>
    <div>
      <p class="stat-info">Visitors Today</p>
      <p class="stat-number"><?= htmlspecialchars($totalVisitorsToday) ?></p>
    </div>
  </div>
  <div class="stat-mini">
    <div class="stat-icon stat-green"><i class="bi bi-door-open"></i></div>
    <div>
      <p class="stat-info">Currently Inside</p>
      <p class="stat-number"><?= htmlspecialchars($currentInside) ?></p>
    </div>
  </div>
  <div class="stat-mini">
    <div class="stat-icon stat-orange"><i class="bi bi-check2-circle"></i></div>
    <div>
      <p class="stat-info">Completed Visits</p>
      <p class="stat-number"><?= htmlspecialchars($completedVisits) ?></p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const popup = document.getElementById('welcome-popup');
  setTimeout(()=> popup.classList.add('show'), 300);
  setTimeout(()=> popup.classList.remove('show'), 3900);

  function updateClock(){
    const el = document.getElementById('clockText');
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth()+1).padStart(2,'0');
    const d = String(now.getDate()).padStart(2,'0');
    const hh = String(now.getHours()).padStart(2,'0');
    const mm = String(now.getMinutes()).padStart(2,'0');
    el.textContent = `${y}-${m}-${d} ${hh}:${mm}`;
  }
  updateClock();
  setInterval(updateClock, 1000);

  // Blank search validation
  document.getElementById('searchForm').addEventListener('submit', function(e) {
    const q = document.getElementById('searchInput').value.trim();
    if (q === "") {
      e.preventDefault();
      const spopup = document.getElementById('search-popup');
      spopup.style.opacity = "1";
      spopup.style.transform = "translateX(-50%) translateY(0)";
      setTimeout(() => {
        spopup.style.opacity = "0";
        spopup.style.transform = "translateX(-50%) translateY(-30px)";
      }, 2500);
    }
  });
</script>

</body>
</html>
