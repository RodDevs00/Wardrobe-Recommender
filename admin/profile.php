<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../db.php"; // adjust to your DB connection

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, email, password, created_at, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? $user['username']);
    $email = trim($_POST['email'] ?? $user['email']);
    $password = $_POST['password'] ?? '';

    $profile_pic = $user['profile_pic'];

    // Handle delete profile picture
    if (isset($_POST['delete_pic']) && $user['profile_pic']) {
        if (file_exists(__DIR__ . '/' . $user['profile_pic'])) {
            unlink(__DIR__ . '/' . $user['profile_pic']);
        }
        $profile_pic = null;
    }

    // Handle upload
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                $profile_pic = 'uploads/' . $new_filename;
            }
        }
    }

    $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : $user['password'];

    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
    $update_stmt->execute([$username, $email, $hashed_password, $profile_pic, $user_id]);

    header("Location: profile.php");
    exit;
}

if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic'])) {
    $pic = htmlspecialchars($user['profile_pic']);
} else {
    $pic = "https://api.dicebear.com/6.x/avataaars/png?seed=" . urlencode($user['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile - Stylesense</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
        const burger = document.getElementById("burger");
        const menu = document.getElementById("mobile-menu");

        burger.addEventListener("click", () => {
            menu.classList.toggle("hidden");
            menu.classList.toggle("flex");
        });
    });
  </script>
</head>
<body class="bg-gray-100 min-h-screen">

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
<!-- Profile card -->
<div class="max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-md p-6 sm:p-8">
  <h2 class="text-2xl font-bold text-gray-800 mb-6">User Profile</h2>

  <form method="POST" enctype="multipart/form-data" class="space-y-6">
    <div class="flex flex-col items-center mb-6 space-y-3">
      <img src="<?= $pic ?>" class="h-24 w-24 rounded-full border-2 border-gray-300 object-cover" alt="Profile Picture">

      <label class="bg-gray-200 px-4 py-2 rounded-md cursor-pointer hover:bg-gray-300">
        Upload New
        <input type="file" name="profile_pic" accept="image/*" class="hidden">
      </label>

      <?php if ($user['profile_pic']): ?>
      <button type="submit" name="delete_pic" value="1"
        class="text-red-500 hover:text-red-700 text-sm underline">Delete Picture</button>
      <?php endif; ?>
    </div>

    <!-- User Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-gray-700 font-semibold mb-1">ID</label>
        <input type="text" value="<?= htmlspecialchars($user['id']) ?>" disabled
               class="w-full border rounded-md p-2 bg-gray-100 cursor-not-allowed">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Created At</label>
        <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" disabled
               class="w-full border rounded-md p-2 bg-gray-100 cursor-not-allowed">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
               class="w-full border rounded-md p-2 focus:ring focus:ring-blue-200">
      </div>
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
               class="w-full border rounded-md p-2 focus:ring focus:ring-blue-200">
      </div>
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-semibold mb-1">Password</label>
        <input type="password" name="password" placeholder="Leave blank to keep current"
               class="w-full border rounded-md p-2 focus:ring focus:ring-blue-200">
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit"
              class="bg-blue-600 text-white px-6 py-2 rounded-md font-semibold hover:bg-blue-700 transition-colors">
        Save
      </button>
    </div>
  </form>
</div>

</body>
</html>
