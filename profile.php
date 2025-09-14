<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

require_once "db.php";

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, email, password, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? $user['username'];
    $email = $_POST['email'] ?? $user['email'];
    $password = $_POST['password'] ?? '';

    // Only hash and update if the password field is not empty
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $user['password']; // keep old password if blank
    }

    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
    $update_stmt->execute([$username, $email, $hashed_password, $user_id]);

    header("Location: profile.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - AI Wardrobe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
    <!-- Logo & Title -->
    <div class="flex items-center space-x-3">
        <img src="https://img.icons8.com/color/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
        <span class="text-xl font-bold text-gray-800">AI Wardrobe</span>
    </div>

    <!-- Navigation Links -->
    <div class="flex space-x-6">
        <a href="home.php" class="text-gray-600 font-semibold transition-colors duration-200 hover:text-blue-800">Home</a>
        <a href="recommend.php" class="text-gray-600 font-medium transition-colors duration-200 hover:text-blue-600">Recommendations</a>
        <a href="profile.php" class="text-blue-600 font-medium transition-colors duration-200 hover:text-blue-600">Profile</a>
        <a href="auth/logout.php" class="text-red-500 font-medium transition-colors duration-200 hover:text-red-700">Logout</a>
    </div>
</nav>


    <!-- Profile Container -->
    <div class="max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">User Profile</h2>

        <form method="POST" class="space-y-6">
            <!-- Profile picture -->
            <div class="flex justify-center mb-6">
                <img src="https://api.dicebear.com/6.x/avataaars/png?seed=<?= htmlspecialchars($user['username']) ?>" 
                     class="h-24 w-24 rounded-full border-2 border-gray-300" 
                     alt="Profile Picture">
            </div>

            <!-- Editable fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">ID</label>
                    <input type="text" value="<?= htmlspecialchars($user['id']) ?>" disabled
                           class="w-full border border-gray-300 rounded-md p-2 bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Created At</label>
                    <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" disabled
                           class="w-full border border-gray-300 rounded-md p-2 bg-gray-100 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                           class="w-full border border-gray-300 rounded-md p-2">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                           class="w-full border border-gray-300 rounded-md p-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-1">Password</label>
                    <input type="password" name="password" value=""
                           class="w-full border border-gray-300 rounded-md p-2">
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
