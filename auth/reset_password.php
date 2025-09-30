<?php
require_once "../db.php";

$error = null;
$success = null;
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token=?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset && strtotime($reset['expires_at']) > time()) {
        $showForm = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $pdo->prepare("UPDATE users SET password=? WHERE email=?")
                ->execute([$newPass, $reset['email']]);

            $pdo->prepare("DELETE FROM password_resets WHERE token=?")->execute([$token]);

            $success = "Password updated successfully! <a href='login.php' class='font-semibold text-indigo-600 hover:underline ml-1'>Login</a>";
            $showForm = false;
        }
    } else {
        $error = "⚠️ Invalid or expired reset link.";
    }
} else {
    $error = "⚠️ No token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Reset Password</title>
</head>
<body class="bg-gray-100">

  <!-- Navbar -->
  <nav class="bg-gray-50 border-b border-gray-200 px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-50">
      <!-- Left: Logo -->
      <div class="flex items-center space-x-3">
          <img src="https://img.icons8.com/color/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
          <span class="text-lg sm:text-xl font-bold text-gray-800">Stylesense</span>
      </div>

      <!-- Mobile Hamburger -->
      <button class="md:hidden text-gray-700 focus:outline-none" id="menu-toggle">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
      </button>

      <!-- Desktop Menu -->
      <div class="hidden md:flex space-x-6 text-sm sm:text-base font-medium" id="menu">
          <a href="login.php" class="text-black-500 hover:text-gray-700">Login</a>
      </div>
  </nav>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="md:hidden hidden flex-col space-y-2 px-4 pb-4 border-b border-gray-200 bg-gray-50 transition-all duration-300 ease-in-out">
      <a href="login.php" class="text-black-500 hover:text-gray-700">Login</a>
  </div>

  <!-- Page Content -->
  <div class="flex items-center justify-center min-h-[80vh] px-4">
    <div class="bg-white p-6 rounded-xl shadow-md w-full max-w-md">
      <h2 class="text-xl font-bold mb-4">Reset Password</h2>

      <?php if($error): ?>
        <div class="p-4 mb-4 text-red-800 border border-red-300 rounded-lg bg-red-50">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <?php if($success): ?>
        <div class="p-4 mb-4 text-green-800 border border-green-300 rounded-lg bg-green-50">
          <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <?php if($showForm): ?>
        <form method="post" class="space-y-4">
          <input type="password" name="password" placeholder="Enter new password" required
                 class="w-full px-4 py-2 border rounded-lg">
          <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">
            Update Password
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mobile Menu Toggle Script -->
  <script>
    document.getElementById("menu-toggle").addEventListener("click", function() {
      document.getElementById("mobile-menu").classList.toggle("hidden");
    });
  </script>

</body>
</html>
