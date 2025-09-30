<?php
session_start();
require_once "../db.php";
$is_logged_in = isset($_SESSION['user_id']);

$login_success = false;
$login_error = false;

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role']; // store role in session

        $login_success = $user['role']; // instead of true, store role
    } else {
        $login_error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - StyleSense</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: linear-gradient(135deg, #f3f4f6, #ffffff, #e5e7eb);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
    }
    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }
    .login-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      transition: all 0.3s ease-in-out;
    }
    input:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
      transition: all 0.3s ease-in-out;
    }
    .navbar-link:hover {
      color: #4F46E5;
      text-decoration: underline;
    }
  </style>
</head>
<body class="min-h-screen flex flex-col">

<!-- Navbar -->
<nav class="bg-white shadow-lg px-4 sm:px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <a href="../index.php" class="flex items-center">
        <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="h-10 w-10" alt="StyleSense Logo">
        <span class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight ml-2">StyleSense</span>
    </a>
    <div class="flex items-center space-x-4 sm:space-x-6 text-sm sm:text-lg font-medium">
        <?php if ($is_logged_in): ?>
            <a href="../profile.php" class="navbar-link transition-colors duration-200">Profile</a>
            <a href="../auth/logout.php" class="text-red-500 hover:text-red-700 transition-colors duration-200">Logout</a>
        <?php else: ?>
            <a href="register.php" class="text-gray-700 hover:text-indigo-600 transition-colors duration-200">Register</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Login Section -->
<main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-12">
  <div class="w-full max-w-md bg-white rounded-2xl p-8 shadow-lg login-card">
    
    <!-- Logo + Title -->
    <div class="text-center mb-6">
      <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="mx-auto mb-4" alt="StyleSense Logo">
      <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">StyleSense</h1>
      <p class="text-gray-500 mt-1 text-sm">Sign in to your account</p>
    </div>

    <!-- Form -->
    <form method="POST" class="space-y-5">
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
        <input type="text" name="username" id="username" placeholder="Enter your username" required
          class="w-full px-4 py-2 border rounded-xl text-sm sm:text-base">
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter your password" required
          class="w-full px-4 py-2 border rounded-xl text-sm sm:text-base">
      </div>

      <button type="submit" name="login"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-md transition transform hover:-translate-y-1">
        Login
      </button>
    </form>

    <!-- Footer -->
    <p class="mt-6 text-center text-sm text-gray-600">
      Don’t have an account?
      <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium transition">Register</a>
    </p>
     <div class="mt-4 text-sm text-center">
      <a href="forgot_password.php" class="text-indigo-600 hover:underline font-medium transition">Forgot Password?</a>
    </div>
  </div>
</main>

<!-- SweetAlert -->
<?php if ($login_success): ?>
<script>
let role = "<?php echo $login_success; ?>";
let redirectUrl = role === "admin" ? "../admin/dashboard.php" : "../home.php";

Swal.fire({
  title: "Login Successful 🎉",
  text: "Welcome back " + role.charAt(0).toUpperCase() + role.slice(1) + "!",
  icon: "success",
  timer: 2000,
  showConfirmButton: false
}).then(() => {
  window.location.href = redirectUrl;
});
</script>
<?php endif; ?>

<?php if ($login_error): ?>
<script>
Swal.fire({
  title: "Login Failed ❌",
  text: "Invalid username or password.",
  icon: "error",
  confirmButtonColor: "#6366f1"
});
</script>
<?php endif; ?>

</body>
</html>
