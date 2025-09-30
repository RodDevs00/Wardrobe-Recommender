<?php
session_start();
require_once "../db.php";

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $age = $_POST['age'] ?: null;
    $gender = $_POST['gender'] ?: null;
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET full_name=?, age=?, gender=?, email=?, contact=?, role=? WHERE id=?");
    $stmt->execute([$full_name, $age, $gender, $email, $contact, $role, $id]);

    $_SESSION['success'] = "User updated successfully!";
    header("Location: users.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    $_SESSION['success'] = "User deleted successfully!";
    header("Location: users.php");
    exit;
}

// Fetch users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users - StyleSense Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gray-100 text-gray-800">


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
<div class="p-8">
  <div class="max-w-6xl mx-auto bg-white rounded-xl shadow p-6">
    <h1 class="text-xl font-semibold mb-6">Users Management</h1>

    <?php if (!empty($_SESSION['success'])): ?>
      <script>
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: '<?php echo $_SESSION['success']; ?>',
          timer: 2000,
          showConfirmButton: false
        });
      </script>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b bg-gray-50 text-left text-gray-600">
            <!-- <th class="px-4 py-2">ID</th> -->
            <th class="px-4 py-2">Username</th>
            <th class="px-4 py-2">Full Name</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Role</th>
            <th class="px-4 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <?php foreach ($users as $user): ?>
          <tr class="hover:bg-gray-50">
            <!-- <td class="px-4 py-2"><?php echo $user['id']; ?></td> -->
            <td class="px-4 py-2"><?php echo htmlspecialchars($user['username']); ?></td>
            <td class="px-4 py-2"><?php echo htmlspecialchars($user['full_name']); ?></td>
            <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
            <td class="px-4 py-2">
              <span class="px-2 py-1 rounded-md text-xs border <?php echo $user['role']=='admin'?'border-red-300 text-red-600':'border-green-300 text-green-600'; ?>">
                <?php echo ucfirst($user['role']); ?>
              </span>
            </td>
            <td class="px-4 py-2 text-center space-x-2">
              <button onclick='viewUser(<?php echo json_encode($user); ?>)' class="px-2 py-1 bg-blue-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">View</button>
              <button onclick='editUser(<?php echo json_encode($user); ?>)' class="px-2 py-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">Edit</button>
              <button onclick="confirmDelete(<?php echo $user['id']; ?>)" class="px-2 py-1 bg-red-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
  <div class="bg-white rounded-xl p-6 w-full max-w-md shadow">
    <h2 class="text-lg font-semibold mb-4">User Details</h2>
    <div id="viewContent" class="space-y-2 text-sm"></div>
    <button onclick="closeModal('viewModal')" class="mt-4 text-sm px-4 py-2 border rounded-md hover:bg-gray-100">Close</button>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">
  <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow">
    <h2 class="text-lg font-semibold mb-4">Edit User</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="id" id="edit_id">
      <div>
        <label class="block text-sm">Full Name</label>
        <input type="text" name="full_name" id="edit_full_name" class="w-full border px-3 py-2 rounded-md" required>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm">Age</label>
          <input type="number" name="age" id="edit_age" class="w-full border px-3 py-2 rounded-md">
        </div>
        <div>
          <label class="block text-sm">Gender</label>
          <select name="gender" id="edit_gender" class="w-full border px-3 py-2 rounded-md">
            <option value="">--</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm">Email</label>
        <input type="email" name="email" id="edit_email" class="w-full border px-3 py-2 rounded-md" required>
      </div>
      <div>
        <label class="block text-sm">Contact</label>
        <input type="text" name="contact" id="edit_contact" class="w-full border px-3 py-2 rounded-md">
      </div>
      <div>
        <label class="block text-sm">Role</label>
        <select name="role" id="edit_role" class="w-full border px-3 py-2 rounded-md" required>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="flex justify-end space-x-2">
        <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border rounded-md hover:bg-gray-100">Cancel</button>
        <button type="submit" name="edit_user" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function viewUser(user) {
  const content = `
    <p><b>ID:</b> ${user.id}</p>
    <p><b>Username:</b> ${user.username}</p>
    <p><b>Full Name:</b> ${user.full_name}</p>
    <p><b>Age:</b> ${user.age ?? '-'}</p>
    <p><b>Gender:</b> ${user.gender ?? '-'}</p>
    <p><b>Email:</b> ${user.email}</p>
    <p><b>Contact:</b> ${user.contact}</p>
    <p><b>Role:</b> ${user.role}</p>
    <p><b>Created At:</b> ${user.created_at}</p>
  `;
  document.getElementById("viewContent").innerHTML = content;
  document.getElementById("viewModal").classList.remove("hidden");
}
function editUser(user) {
  document.getElementById("edit_id").value = user.id;
  document.getElementById("edit_full_name").value = user.full_name;
  document.getElementById("edit_age").value = user.age ?? '';
  document.getElementById("edit_gender").value = user.gender ?? '';
  document.getElementById("edit_email").value = user.email;
  document.getElementById("edit_contact").value = user.contact;
  document.getElementById("edit_role").value = user.role;
  document.getElementById("editModal").classList.remove("hidden");
}
function closeModal(id) {
  document.getElementById(id).classList.add("hidden");
}
function confirmDelete(id) {
  Swal.fire({
    title: "Delete this user?",
    text: "This action cannot be undone!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = "users.php?delete=" + id;
    }
  });
}
</script>

</body>
</html>
