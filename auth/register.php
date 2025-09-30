<?php
session_start();
require_once "../db.php";
$is_logged_in = isset($_SESSION['user_id']);

$register_success = false;
$register_error = "";

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
     $contact = $_POST['contact'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $gender = !empty($_POST['gender']) ? $_POST['gender'] : null;

    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        $register_error = "Username or email already exists!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email,contact, password, age, gender) VALUES (?, ?,?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $full_name, $email,$contact, $password, $age, $gender])) {
            $register_success = true;
        } else {
            $register_error = "Registration failed!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - StyleSense</title>
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
    .card:hover {
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
<nav class="bg-white shadow-lg px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-4">
        <a href="../index.php" class="flex items-center">
            <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="h-10 w-10" alt="StyleSense Logo">
            <span class="text-2xl font-bold text-gray-900 tracking-tight ml-2">StyleSense</span>
        </a>
    </div>
    <div class="flex items-center space-x-6 text-lg font-medium">
        <?php if ($is_logged_in): ?>
            <a href="../profile.php" class="navbar-link transition-colors duration-200">Profile</a>
            <a href="../auth/logout.php" class="text-red-500 hover:text-red-700 transition-colors duration-200">Logout</a>
        <?php else: ?>
            <a href="login.php" class="text-black-600 hover:text-green-800 transition-colors duration-200">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Registration Card -->
<div class="flex-1 flex items-center justify-center">
  <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-lg card my-12">
    <div class="text-center mb-6">
      <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="mx-auto mb-4" alt="StyleSense Logo">
      <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">StyleSense</h1>
      <p class="text-gray-500 mt-1 text-sm">Create your account</p>
    </div>

    <form method="POST" id="registerForm" class="space-y-5">
  <div>
    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
    <input type="text" name="full_name" id="full_name" placeholder="Your full name" required
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
  </div>

  <div>
    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
    <input type="text" name="username" id="username" placeholder="Choose a username" required minlength="4"
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
  </div>

  <div>
    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
    <input type="email" name="email" id="email" placeholder="Enter your email" required
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
  </div>

   <div>
  <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">Contact No:</label>
  <input type="text" name="contact" id="contact"
    placeholder="09XXXXXXXXX or +639XXXXXXXXX" required
    pattern="^(09\d{9}|(\+639)\d{9})$"
    class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
</div>


  <div>
    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
    <input type="password" name="password" id="password" placeholder="Create a password" required minlength="6"
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
    <small class="text-gray-500">At least 6 characters</small>
  </div>

  <div>
    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
    <input type="password" id="confirm_password" placeholder="Re-enter password" required
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
  </div>

  <div>
    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
    <input type="number" name="age" id="age" min="13" max="100" placeholder="Your age"
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
  </div>

  <div>
    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
    <select name="gender" id="gender" required
      class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
      <option value="">Select gender</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
      <option value="Other">Other</option>
    </select>
  </div>

  <button type="submit" name="register"
    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-md transition transform hover:-translate-y-1">
    Register
  </button>
</form>

<script>
document.getElementById("registerForm").addEventListener("submit", function(e) {
  let fullName = document.getElementById("full_name").value.trim();
  let username = document.getElementById("username").value.trim();
  let email = document.getElementById("email").value.trim();
  let contact = document.getElementById("contact").value.trim();
  let password = document.getElementById("password").value;
  let confirmPassword = document.getElementById("confirm_password").value;
  let age = document.getElementById("age").value;
  let gender = document.getElementById("gender").value;

  // Full name check
  if (fullName.length < 3) {
    e.preventDefault();
    Swal.fire("Validation Error", "Full name must be at least 3 characters long.", "warning");
    return;
  }

  // Username check
  if (username.length < 4) {
    e.preventDefault();
    Swal.fire("Validation Error", "Username must be at least 4 characters long.", "warning");
    return;
  }

  // Email check
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    e.preventDefault();
    Swal.fire("Validation Error", "Please enter a valid email address.", "warning");
    return;
  }

  // Contact check
 const contactRegex = /^(09\d{9}|(\+639)\d{9})$/; // Philippine mobile number format
if (!contactRegex.test(contact)) {
  e.preventDefault();
  Swal.fire("Validation Error", "Please enter a valid Philippine mobile number (e.g., 09XXXXXXXXX or +639XXXXXXXXX).", "warning");
  return;
}

  // Password check
  if (password.length < 6) {
    e.preventDefault();
    Swal.fire("Validation Error", "Password must be at least 6 characters long.", "warning");
    return;
  }

  if (password !== confirmPassword) {
    e.preventDefault();
    Swal.fire("Validation Error", "Passwords do not match.", "warning");
    return;
  }

  // Age check (optional field, but validate if filled)
  if (age && (age < 13 || age > 100)) {
    e.preventDefault();
    Swal.fire("Validation Error", "Age must be between 13 and 100.", "warning");
    return;
  }

  // Gender check
  if (gender === "") {
    e.preventDefault();
    Swal.fire("Validation Error", "Please select your gender.", "warning");
    return;
  }
});
</script>



    <p class="mt-6 text-center text-sm text-gray-600">
      Already have an account?
      <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-medium transition">Login</a>
    </p>
  </div>
</div>

<!-- SweetAlert -->
<?php if ($register_success): ?>
<script>
Swal.fire({
  title: "Registration Successful 🎉",
  text: "You can now login with your account.",
  icon: "success",
  timer: 2000,
  showConfirmButton: false
}).then(() => {
  window.location.href = "login.php";
});
</script>
<?php endif; ?>

<?php if (!empty($register_error)): ?>
<script>
Swal.fire({
  title: "Registration Failed ❌",
  text: "<?php echo $register_error; ?>",
  icon: "error",
  confirmButtonColor: "#6366f1"
});
</script>
<?php endif; ?>

</body>
</html>
