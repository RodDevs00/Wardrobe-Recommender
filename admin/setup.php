<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../db.php"; 

// Handle Add
if (isset($_POST['add'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);
    if ($value !== "") {
        $stmt = $pdo->prepare("INSERT INTO configs (type, value) VALUES (?, ?)");
        $stmt->execute([$type, $value]);
    }
    header("Location: setup.php?success=added");
    exit;
}

// Handle Edit
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $value = trim($_POST['value']);
    if ($value !== "") {
        $stmt = $pdo->prepare("UPDATE configs SET value=? WHERE id=?");
        $stmt->execute([$value, $id]);
    }
    header("Location: setup.php?success=edited");
    exit;
}

// Handle Delete
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM configs WHERE id=?");
    $stmt->execute([$id]);
    header("Location: setup.php?success=deleted");
    exit;
}

// Fetch all configs
$stmt = $pdo->query("SELECT * FROM configs ORDER BY type, value ASC");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup - StyleSense</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">


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


<!-- Main -->
<div class="max-w-5xl mx-auto mt-10 bg-white p-8 rounded-xl shadow-lg">
  <h1 class="text-3xl font-bold mb-8 text-gray-900">Manage Motifs & Palettes</h1>

  <!-- Add Form -->
  <form method="POST" class="flex flex-col sm:flex-row gap-4 mb-8">
    <select name="type" class="border rounded-lg p-2 w-full sm:w-40 focus:ring focus:ring-blue-300">
      <option value="motif">Motif</option>
      <option value="palette">Palette</option>
    </select>
    <input type="text" name="value" placeholder="Enter new value" class="border rounded-lg p-2 flex-1 focus:ring focus:ring-blue-300" required>
    <button type="submit" name="add" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow">Add</button>
  </form>

  <!-- List -->
  <div class="overflow-x-auto">
    <table class="w-full border border-gray-200 rounded-lg overflow-hidden shadow-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="border p-3 text-left">ID</th>
          <th class="border p-3 text-left">Type</th>
          <th class="border p-3 text-left">Value</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($configs as $c): ?>
        <tr class="hover:bg-gray-50">
          <td class="border p-3"><?= htmlspecialchars($c['id']) ?></td>
          <td class="border p-3 capitalize"><?= htmlspecialchars($c['type']) ?></td>
          <td class="border p-3">
            <form method="POST" class="flex gap-2 items-center">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <input type="text" name="value" value="<?= htmlspecialchars($c['value']) ?>" class="border rounded-lg p-2 flex-1 focus:ring focus:ring-green-300">
              <button type="submit" name="edit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg shadow">Save</button>
            </form>
          </td>
          <td class="border p-3 text-center">
          <form method="POST" onsubmit="return confirmDelete(event, this);">
          <input type="hidden" name="id" value="<?= $c['id'] ?>">
          <input type="hidden" name="delete" value="1">
          <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg shadow">Delete</button>
        </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (count($configs) === 0): ?>
        <tr>
          <td colspan="4" class="text-center text-gray-500 py-6">No entries found.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Confirm Delete with SweetAlert
function confirmDelete(event, form) {
  event.preventDefault();
  Swal.fire({
    title: 'Are you sure?',
    text: "This will delete the entry permanently.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}

// Handle success messages (Add/Edit/Delete)
const urlParams = new URLSearchParams(window.location.search);
const success = urlParams.get('success');
if (success) {
  let options = {};
  if (success === 'added') {
    options = { icon: 'success', title: 'Added!', text: 'New entry has been added.' };
  } else if (success === 'edited') {
    options = { icon: 'success', title: 'Updated!', text: 'Entry has been updated.' };
  } else if (success === 'deleted') {
    options = { icon: 'info', title: 'Deleted!', text: 'Entry has been deleted.' };
  }

  Swal.fire({
    ...options,
    timer: 2000,
    showConfirmButton: false
  }).then(() => {
    // Clean the URL to prevent re-trigger on refresh
    window.history.replaceState({}, document.title, "setup.php");
  });
}
</script>

</body>
</html>
