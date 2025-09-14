<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Wardrobe - Smart Outfit Recommendations</title>
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
    .navbar-link:hover {
        color: #4F46E5;
        text-decoration: underline;
    }

    /* Floating circles */
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    @keyframes pulse-slow {
        0%, 100% { transform: scale(1); opacity: 0.3; }
        50% { transform: scale(1.2); opacity: 0.6; }
    }
    .animate-bounce-slow { animation: bounce-slow 6s infinite ease-in-out; }
    .animate-pulse-slow { animation: pulse-slow 8s infinite ease-in-out; }

    /* Hero SVG floating animation */
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
<nav class="bg-white shadow-md px-6 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center space-x-4">
        <a href="index.php" class="flex items-center">
            <img src="https://img.icons8.com/fluency/48/wardrobe.png" class="h-10 w-10" alt="AI Wardrobe Logo">
            <span class="text-2xl font-bold text-gray-900 tracking-tight ml-2">StyleSense</span>
        </a>
    </div>
    <div class="flex items-center space-x-6 text-lg font-medium">
        
        <?php if ($is_logged_in): ?>
            <a href="profile.php" class="navbar-link text-gray-800 hover:text-gray-900 transition-colors duration-200">Profile</a>
            <a href="auth/logout.php" class="text-gray-800 hover:text-red-600 transition-colors duration-200">Logout</a>
        <?php else: ?>
            <a href="auth/login.php" class="text-gray-800 hover:text-green-600 transition-colors duration-200">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-r from-blue-100 via-purple-200 to-pink-100 py-24">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between relative z-10">
        <!-- Text Content -->
        <div class="md:w-1/2 space-y-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 leading-tight">
                Discover AI-Powered Fashion <br> with StyleSense
            </h1>
            <p class="text-gray-700 text-lg md:text-xl">
                StyleSense uses advanced AI to recommend outfits perfectly suited to your style, occasion, and mood. Transform your wardrobe effortlessly.
            </p>
            <div class="flex space-x-4">
                <a href="home.php" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-transform duration-300 transform hover:-translate-y-1">Get Recommendations</a>
                <a href="#features" class="bg-white border border-indigo-600 text-indigo-600 px-6 py-3 rounded-lg font-semibold hover:bg-indigo-50 transition-transform duration-300 transform hover:-translate-y-1">Learn More</a>
            </div>
        </div>

        <!-- Hero SVG -->
        <div class="md:w-1/2 mt-10 md:mt-0 relative h-80 md:h-96 flex items-center justify-center">
            <img src="css/undraw_fashion-photoshoot_zjiu.svg" alt="Fashion Illustration" class="w-full h-full object-contain fashion-svg">
        </div>
    </div>

    <!-- Floating circles -->
    <div class="absolute top-0 left-1/4 w-32 h-32 bg-indigo-300 rounded-full opacity-40 animate-bounce-slow"></div>
    <div class="absolute bottom-10 right-1/3 w-24 h-24 bg-pink-300 rounded-full opacity-30 animate-pulse-slow"></div>
</section>

<!-- Features Section -->
<section id="features" class="max-w-7xl mx-auto px-6 py-20">
    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-12">Why Choose StyleSense?</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center hover:scale-105 transition-transform duration-300">
            <img src="https://img.icons8.com/ios/452/brain.png" class="mx-auto mb-4 w-24 h-24" alt="Smart Recommendations">
            <h3 class="font-bold text-xl mb-2">Smart Recommendations</h3>
            <p class="text-gray-600">Get outfit suggestions tailored to your style, body type, and occasion using cutting-edge AI technology.</p>
        </div>
        <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center hover:scale-105 transition-transform duration-300">
            <img src="https://img.icons8.com/ios/452/color-palette.png" class="mx-auto mb-4 w-24 h-24" alt="Style Insights">
            <h3 class="font-bold text-xl mb-2">Style Insights</h3>
            <p class="text-gray-600">Understand which colors, patterns, and cuts work best for you with professional fashion insights.</p>
        </div>
        <div class="feature-card bg-white shadow-lg rounded-xl p-6 text-center hover:scale-105 transition-transform duration-300">
            <img src="https://img.icons8.com/ios/452/artificial-intelligence.png" class="mx-auto mb-4 w-24 h-24" alt="AI Powered">
            <h3 class="font-bold text-xl mb-2">AI Powered</h3>
            <p class="text-gray-600">Our AI engine learns your preferences and recommends outfits that fit your lifestyle, events, and personality.</p>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-gradient-to-r from-purple-200 to-blue-100 py-16 mt-20 text-center">
    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Ready to Upgrade Your Wardrobe?</h2>
    <p class="text-gray-700 mb-6 text-lg">Join thousands of fashion enthusiasts who trust AI Wardrobe to look their best every day.</p>
    <a href="home.php" class="btn-hover bg-indigo-600 text-white px-8 py-4 rounded-lg font-semibold hover:bg-indigo-700 transition-transform duration-300 text-lg">Get Your Recommendations Now</a>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white py-8 mt-20">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center">
        <p>&copy; <?= date("Y") ?> StyleSense. All rights reserved.</p>
        <div class="flex space-x-4 mt-4 md:mt-0">
            <a href="#" class="hover:text-indigo-400 transition-colors">Facebook</a>
            <a href="#" class="hover:text-indigo-400 transition-colors">Twitter</a>
            <a href="#" class="hover:text-indigo-400 transition-colors">Instagram</a>
        </div>
    </div>
</footer>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
<script>
    // Particles.js init
    particlesJS("particles-js", {
        "particles": {
            "number": { "value": 50 },
            "color": { "value": "#6b5b95" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.3 },
            "size": { "value": 3 },
            "move": { "enable": true, "speed": 1, "direction": "none", "random": true, "out_mode": "out" }
        },
        "interactivity": {
            "events": { "onhover": { "enable": true, "mode": "repulse" } }
        }
    });
</script>

</body>
</html>
