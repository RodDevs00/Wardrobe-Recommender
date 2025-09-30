<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../db.php"; // adjust to your DB connection

// Queries
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$totalRecommendations = $pdo->query("SELECT COUNT(*) FROM ootd_history")->fetchColumn();
$manualRecs = $pdo->query("SELECT COUNT(*) FROM ootd_history WHERE mode='manual'")->fetchColumn();
$autoRecs = $pdo->query("SELECT COUNT(*) FROM ootd_history WHERE mode='automatic'")->fetchColumn();

//style distribution
$genderStats = $pdo->query("SELECT style, COUNT(*) as count FROM ootd_history WHERE style IS NOT NULL GROUP BY style")->fetchAll(PDO::FETCH_ASSOC);

// Recommendations per day (last 7 days)
$recStats = $pdo->query("
  SELECT DATE(created_at) as date, COUNT(*) as count 
  FROM ootd_history 
  WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

// User growth over time
$userGrowth = $pdo->query("
  SELECT DATE(created_at) as date, COUNT(*) as count 
  FROM users 
  GROUP BY DATE(created_at)
  ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - StyleSense</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gray-100">


<!-- Navbar -->
<nav class="bg-white shadow-lg px-6 py-4 flex justify-between items-center sticky top-0 z-50">
  <!-- Logo -->
  <div class="flex items-center space-x-4">
    <a href="" class="flex items-center">
      <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="h-10 w-10" alt="StyleSense Logo">
      <span class="text-2xl font-bold text-gray-900 tracking-tight ml-2">StyleSense Admin</span>
    </a>
  </div>

  <!-- Hamburger (Mobile only) -->
  <button id="menu-toggle" class="md:hidden text-gray-700 focus:outline-none">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
  </button>

  <!-- Links -->
  <div id="menu" class="hidden md:flex flex-col md:flex-row md:items-center md:space-x-6 text-lg font-medium absolute md:static top-16 left-0 w-full md:w-auto bg-white md:bg-transparent shadow-md md:shadow-none z-40">
    <a href="dashboard.php" class="block px-6 py-3 md:px-0 navbar-link transition-colors duration-200">Dashboard</a>
    <a href="users.php" class="block px-6 py-3 md:px-0 navbar-link transition-colors duration-200">Users</a>
    <a href="profile.php" class="block px-6 py-3 md:px-0 navbar-link transition-colors duration-200">Profile</a>
         <a href="setup.php" class="block px-6 py-3 md:px-0 navbar-link transition-colors duration-200">Setup</a>
    <a href="../auth/logout.php" class="block px-6 py-3 md:px-0 text-red-500 hover:text-red-700 transition-colors duration-200">Logout</a>
  </div>
</nav>

<script>
  const menuToggle = document.getElementById("menu-toggle");
  const menu = document.getElementById("menu");

  menuToggle.addEventListener("click", () => {
    menu.classList.toggle("hidden");
  });
</script>


<!-- Dashboard -->
<div class="p-8 max-w-7xl mx-auto">
  <h1 class="text-3xl font-bold mb-6">Welcome back, 
    <span class="text-indigo-600"><?php echo htmlspecialchars($_SESSION['username']); ?></span> 👋
  </h1>

  <!-- Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
    <div class="bg-white p-6 rounded-xl shadow text-center">
      <h2 class="text-lg font-semibold">👥 Users</h2>
      <p class="text-3xl font-bold text-indigo-600"><?php echo $totalUsers; ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow text-center">
      <h2 class="text-lg font-semibold">🛠 Admins</h2>
      <p class="text-3xl font-bold text-indigo-600"><?php echo $totalAdmins; ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow text-center">
      <h2 class="text-lg font-semibold">📦 Recommendations</h2>
      <p class="text-3xl font-bold text-indigo-600"><?php echo $totalRecommendations; ?></p>
    </div>
    <div class="bg-white p-6 rounded-xl shadow text-center">
      <h2 class="text-lg font-semibold">⚖️ Manual vs Auto</h2>
      <p class="text-md text-gray-600">
        Manual: <b><?php echo $manualRecs; ?></b> | Auto: <b><?php echo $autoRecs; ?></b>
      </p>
    </div>
  </div>

  <!-- Charts -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Recommendations per Day -->
    <div class="bg-white p-6 rounded-xl shadow">
      <h2 class="text-xl font-semibold mb-4">📊 Recommendations (Last 7 Days)</h2>
      <canvas id="recChart"></canvas>
    </div>

   <div class="bg-white p-6 rounded-xl shadow flex justify-center items-center">
  <canvas id="genderChart" class="max-w-[400px] max-h-[400px]"></canvas>
</div>
    

  
  </div>
</div>

<script>
const recData = <?php echo json_encode($recStats); ?>;
const genderData = <?php echo json_encode($genderStats); ?>;
const userGrowth = <?php echo json_encode($userGrowth); ?>;

// Recommendations per day chart
new Chart(document.getElementById('recChart'), {
  type: 'bar',
  data: {
    labels: recData.map(r => r.date),
    datasets: [{
      label: 'Recommendations',
      data: recData.map(r => r.count),
      backgroundColor: 'rgba(99, 102, 241, 0.7)'
    }]
  }
});

//style distribution pie chart
new Chart(document.getElementById('genderChart'), {
  type: 'pie',
  data: {
    labels: genderData.map(g => g.style),
    datasets: [{
      data: genderData.map(g => g.count),
      backgroundColor: ['#6366f1', '#f43f5e', '#10b981']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: '👗 Style Preference Distribution', // ✅ Chart name
        font: {
          size: 18,
          weight: 'bold'
        },
        padding: {
          top: 10,
          bottom: 20
        }
      },
      legend: {
        position: 'bottom'
      }
    }
  }
});

</script>

</body>
</html>
