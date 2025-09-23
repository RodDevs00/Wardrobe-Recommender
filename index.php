<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stylesense - Smart Outfit Recommendations</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .btn-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .feature-card:hover {
        transform: translateY(-5px) scale(1.03);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    .fashion-svg {
        animation: float-svg 6s ease-in-out infinite;
    }
    @keyframes float-svg {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
</style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">

<!-- Navbar -->
<nav class="bg-white shadow-md px-4 sm:px-6 py-4 sticky top-0 z-50">
  <div class="flex justify-between items-center">
    <!-- Logo -->
    <a href="index.php" class="flex items-center">
      <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
      <span class="ml-2 text-xl font-bold text-gray-900">StyleSense</span>
    </a>

    <!-- Mobile toggle -->
    <button id="menu-toggle" class="sm:hidden text-gray-800 focus:outline-none">
      ☰
    </button>

    <!-- Links (desktop) -->
    <div class="hidden sm:flex space-x-6 font-medium text-base">
      <?php if ($is_logged_in): ?>
        <a href="profile.php" class="hover:text-indigo-600">Profile</a>
        <a href="auth/logout.php" class="hover:text-red-600">Logout</a>
      <?php else: ?>
        <a href="auth/login.php" class="hover:text-green-600">Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Links (mobile) -->
  <div id="mobile-menu" class="hidden mt-3 flex flex-col space-y-2 sm:hidden text-base font-medium">
    <?php if ($is_logged_in): ?>
      <a href="profile.php" class="hover:text-indigo-600">Profile</a>
      <a href="auth/logout.php" class="hover:text-red-600">Logout</a>
    <?php else: ?>
      <a href="auth/login.php" class="hover:text-green-600">Login</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-100 via-purple-200 to-pink-100 py-16 sm:py-24">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row items-center">
    <!-- Text -->
    <div class="w-full md:w-1/2 text-center md:text-left space-y-6">
      <h1 class="text-3xl sm:text-5xl font-extrabold text-gray-900">
        Discover AI-Powered Fashion <br class="hidden sm:block"> with StyleSense
      </h1>
      <p class="text-gray-700 text-base sm:text-lg md:text-xl">
        StyleSense uses advanced AI to recommend outfits perfectly suited to your style, occasion, and mood.
      </p>
      <div class="flex flex-col sm:flex-row justify-center md:justify-start gap-3">
        <a href="home.php" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700">Get Recommendations</a>
        <a href="#features" class="bg-white border border-indigo-600 text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-indigo-50">Learn More</a>
      </div>
    </div>
    <!-- Image -->
    <div class="w-full md:w-1/2 mt-8 md:mt-0">
      <img src="css/undraw_fashion-photoshoot_zjiu.svg" alt="Fashion Illustration" class="fashion-svg mx-auto w-64 sm:w-80 md:w-full">
    </div>
  </div>
</section>

<!-- Features -->
<section id="features" class="max-w-7xl mx-auto px-4 sm:px-6 py-16">
  <h2 class="text-2xl sm:text-4xl font-bold text-center mb-12">Why Choose StyleSense?</h2>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
    <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center">
      <img src="https://img.icons8.com/ios/452/brain.png" class="mx-auto mb-4 w-20 h-20" alt="">
      <h3 class="font-bold text-xl mb-2">Smart Recommendations</h3>
      <p class="text-gray-600 text-sm sm:text-base">Outfit suggestions tailored to your style, body type, and occasion using AI.</p>
    </div>
    <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center">
      <img src="https://img.icons8.com/ios/452/color-palette.png" class="mx-auto mb-4 w-20 h-20" alt="">
      <h3 class="font-bold text-xl mb-2">Style Insights</h3>
      <p class="text-gray-600 text-sm sm:text-base">Understand which colors, patterns, and cuts work best for you with fashion insights.</p>
    </div>
    <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center">
      <img src="https://img.icons8.com/ios/452/artificial-intelligence.png" class="mx-auto mb-4 w-20 h-20" alt="">
      <h3 class="font-bold text-xl mb-2">AI Powered</h3>
      <p class="text-gray-600 text-sm sm:text-base">Our AI engine learns your preferences and recommends outfits that fit your lifestyle.</p>
    </div>
  </div>
</section>

<!-- Call to Action -->
<section class="bg-gradient-to-r from-purple-200 to-blue-100 py-12 text-center">
  <h2 class="text-2xl sm:text-4xl font-bold text-gray-900 mb-4">Ready to Upgrade Your Wardrobe?</h2>
  <p class="text-gray-700 mb-6 text-base sm:text-lg">Join thousands who trust AI Wardrobe every day.</p>
  <a href="home.php" class="btn-hover bg-indigo-600 text-white px-6 py-3 sm:px-8 sm:py-4 rounded-lg font-semibold hover:bg-indigo-700">Get Your Recommendations Now</a>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-6 mt-16">
  <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center text-center md:text-left">
    <p class="text-sm">&copy; <?= date("Y") ?> StyleSense. All rights reserved.</p>
    <div class="flex space-x-4 mt-3 md:mt-0">
      <a href="#" class="hover:text-indigo-400">Facebook</a>
      <a href="#" class="hover:text-indigo-400">Twitter</a>
      <a href="#" class="hover:text-indigo-400">Instagram</a>
    </div>
  </div>
</footer>

<script>
// Mobile menu toggle
document.getElementById('menu-toggle').addEventListener('click', () => {
  document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script>

</body>
</html>
