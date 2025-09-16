<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
require_once "db.php";

$user_id = $_SESSION['user_id'];

// Get the latest recommendation only
$history_stmt = $pdo->prepare("SELECT * FROM ootd_history WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$history_stmt->execute([$user_id]);
$latest = $history_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Wardrobe - Recommendation</title>
    <style>
    .loader {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: conic-gradient(#3b82f6, #06b6d4, #3b82f6);
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<nav class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center">
    <div class="flex items-center space-x-3">
        <img src="https://img.icons8.com/color/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
        <span class="text-xl font-bold text-gray-800">AI Wardrobe</span>
    </div>
    <div class="flex space-x-6">
        <a href="index.php" class="text-blue-600 font-semibold hover:text-blue-800">Home</a>
        <a href="recommend.php" class="text-gray-600 font-medium hover:text-blue-600">Recommendations</a>
        <a href="profile.php" class="text-gray-600 font-medium hover:text-blue-600">Profile</a>
        <a href="auth/logout.php" class="text-red-500 font-medium hover:text-red-700">Logout</a>
    </div>
</nav>

<div class="flex justify-center py-10 px-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-6xl">

        <!-- Card 1: Forms -->
        <div class="bg-white shadow-lg rounded-2xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Get Your AI Recommendation</h2>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-3 rounded mb-4 text-sm">
                ⚠️ Note: Accessories and shoes are excluded. Only upper, lower, or full-body wardrobe items are used.
            </div>

            <!-- Tabs -->
            <ul class="flex border-b mb-4 justify-center">
                <li class="mr-2">
                    <button type="button" onclick="showTab('manual')" id="tab-manual"
                        class="py-2 px-6 font-semibold text-blue-600 border-b-2 border-blue-600">
                        Manual
                    </button>
                </li>
                <li>
                    <button type="button" onclick="showTab('automatic')" id="tab-automatic"
                        class="py-2 px-6 font-semibold text-gray-500 hover:text-blue-600">
                        Automatic
                    </button>
                </li>
            </ul>

            <!-- Manual Form -->
            <div id="manual" class="tab-content">
                <form id="manual-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="mode" value="manual">

                    <div>
                        <label class="block font-medium">Event</label>
                        <select name="event" class="w-full border rounded p-2" required>
                            <option value="wedding">Wedding</option>
                            <option value="beach_party">Beach Party</option>
                            <option value="birthday">Birthday</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-600">Wardrobe Type</label>
                        <select name="wardrobe_type" class="w-full border p-2 rounded-md" required>
                            <option value="upper">Upper</option>
                            <option value="lower">Lower</option>
                            <option value="full-body">Full Body</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-600">Style Preference</label>
                        <select name="style" class="w-full border p-2 rounded-md" required>
                            <option value="feminine">Feminine</option>
                            <option value="masculine">Masculine</option>
                            <option value="androgynous">Androgynous</option>
                            <option value="gender_neutral">Gender Neutral</option>
                            <option value="no_preference">No Preference</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-600">Upload up to 5 images</label>
                        <input type="file" name="images[]" accept="image/*" multiple required 
                            class="w-full" onchange="previewImages(event, 'manual-preview')">
                        <div id="manual-preview" class="flex flex-wrap gap-2 mt-2"></div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        Get Recommendation
                    </button>
                </form>
            </div>

            <!-- Automatic Form -->
            <div id="automatic" class="tab-content hidden">
                <form id="automatic-form" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="mode" value="automatic">

                    <div>
                        <label class="block font-medium">Event</label>
                        <select name="event" class="w-full border rounded p-2" required>
                            <option value="wedding">Wedding</option>
                            <option value="beach_party">Beach Party</option>
                            <option value="birthday">Birthday</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-600">Style Preference</label>
                        <select name="style" class="w-full border p-2 rounded-md" required>
                            <option value="feminine">Feminine</option>
                            <option value="masculine">Masculine</option>
                            <option value="androgynous">Androgynous</option>
                            <option value="gender_neutral">Gender Neutral</option>
                            <option value="no_preference">No Preference</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-600">Upload up to 10 images</label>
                        <input type="file" name="images[]" accept="image/*" multiple required 
                            class="w-full" onchange="previewImages(event, 'auto-preview')">
                        <div id="auto-preview" class="flex flex-wrap gap-2 mt-2"></div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                        Get Recommendation
                    </button>
                </form>
            </div>
        </div>

        <!-- Card 2: Result -->
        <div class="bg-white shadow-lg rounded-2xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Latest Result</h2>
            <div id="latest-result">
                <p class="text-gray-500 text-center">No recommendations yet.</p>
            </div>
        </div>

    </div>
</div>

<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(div => div.classList.add('hidden'));
    document.getElementById(tab).classList.remove('hidden');
    document.getElementById('tab-manual').classList.remove('text-blue-600','border-blue-600','border-b-2');
    document.getElementById('tab-automatic').classList.remove('text-blue-600','border-blue-600','border-b-2');
    if(tab === 'manual'){
        document.getElementById('tab-manual').classList.add('text-blue-600','border-blue-600','border-b-2');
    } else {
        document.getElementById('tab-automatic').classList.add('text-blue-600','border-blue-600','border-b-2');
    }
}

function previewImages(event, previewId) {
    const container = document.getElementById(previewId);
    container.innerHTML = ""; 
    const files = event.target.files;
    if (!files) return;
    [...files].forEach(file => {
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.className = "h-20 w-20 object-cover rounded-lg shadow";
                container.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
}

async function submitForm(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    const resultDiv = document.getElementById("latest-result");

    resultDiv.innerHTML = `
        <div class="flex flex-col items-center justify-center py-10">
    <div class="loader mb-3"></div>
    <p class="text-gray-500 text-center text-sm">AI is thinking...</p>
    <p class="text-gray-400 text-center text-xs mt-2">
        💡 Note: AI will just recommend the nearest match based on your uploaded wardrobe.
    </p>
</div>

    `;

    try {
        const response = await fetch("recommend_process.php", { method: "POST", body: formData });
        const data = await response.json();
        if (data.success) {
            renderLatestResult(data);
        } else {
            resultDiv.innerHTML = `<p class='text-red-500 text-center'>❌ ${data.error || "Something went wrong"}</p>`;
        }
    } catch (err) {
        resultDiv.innerHTML = `<p class='text-red-500 text-center'>⚠️ Error: ${err.message}</p>`;
    }
}
function renderLatestResult(data, selectedWardrobeType) {
    let html = `
        <h3 class="text-lg font-bold text-gray-800 mb-3">Recommendation Result</h3>
        <p class="text-sm text-gray-600 mb-4">
            <strong>Event:</strong> ${data.event.replace("_", " ")} <br>
            <strong>Style:</strong> ${data.style || "unspecified"} <br>
            <strong>Description:</strong> ${data.event_description || ""}
        </p>
    `;

    // ---------------------------
    // TOP MATCH SECTION
    // ---------------------------
    let topMatches = [];
    if (data.top_match?.items?.length > 0) {
        if (data.mode === "manual") {
            topMatches = [data.top_match.items[0]];
        } else if (data.mode === "automatic") {
            if (selectedWardrobeType !== "full-body") {
                const upper = data.top_match.items.find(m => m.detected_type === "upper");
                const lower = data.top_match.items.find(m => m.detected_type === "lower");
                if (upper) topMatches.push(upper);
                if (lower) topMatches.push(lower);
            }
        }
    }

    if (topMatches.length > 0) {
        html += `
            <div class="mb-8 p-4 border-2 border-green-400 rounded-lg bg-green-50 shadow-sm">
                <h4 class="text-md font-semibold text-green-700 mb-3">
                    🎯 Top Match${topMatches.length > 1 ? "es" : ""}
                </h4>
                <div class="flex flex-wrap gap-3 justify-center">
        `;

        topMatches.forEach(match => {
            html += `
                <div class="relative w-32">
                    <span class="absolute top-1 left-1 bg-green-600 text-white text-[10px] px-2 py-0.5 rounded-md shadow">
                        ${match.detected_type === "upper" ? "Best Upper-body Match" : 
                          match.detected_type === "lower" ? "Best Lower-body Match" : "Top Match"}
                    </span>
                    <img src="${normalizePath(match.path)}" 
                         class="w-32 h-32 object-cover rounded-lg border-4 border-green-500 mb-2">
                    <p class="text-xs text-center text-green-600 font-bold">
                        ✅ ${match.recommendation || "No valid match"}
                    </p>
                    <p class="text-[10px] text-gray-500 text-center">
                        ${match.label} (${(match.similarity * 100).toFixed(1)}%)
                    </p>
                </div>
            `;
        });

        html += `</div></div>`;
    }

    // ---------------------------
    // UPLOADED ITEMS SECTION
    // ---------------------------
    if (data.items?.length > 0) {
        if (selectedWardrobeType) {
            html += `<p class="italic text-gray-600 mb-2">
                        Note: Only ${selectedWardrobeType.toLowerCase()} items are displayed
                     </p>`;
        }

        html += `
            <h4 class="text-md font-semibold text-gray-600 mb-2">Uploaded Items</h4>
            <div class="flex flex-wrap gap-3 mb-6 justify-center">
        `;

        data.items.forEach(it => {
            if (["accessory", "shoes"].includes(it.detected_type)) return;

            if (selectedWardrobeType &&
                it.detected_type.toLowerCase() !== selectedWardrobeType.toLowerCase()) {
                return;
            }

            // 🚫 Skip Top Matches
            if (topMatches.some(m => m.path === it.path)) return;

            html += `
                <div class="w-32">
                    <img src="${normalizePath(it.path)}" 
                         class="w-32 h-32 object-cover rounded-lg border mb-2">
                    <p class="text-xs text-center text-gray-600">
                        ${it.recommendation || "❌ No valid match"}
                    </p>
                    <p class="text-[10px] text-gray-500 text-center">
                        (${it.similarity ? (it.similarity * 100).toFixed(1) + "%" : "N/A"})
                    </p>
                </div>
            `;
        });

        html += `</div>`;
    }

    document.getElementById("latest-result").innerHTML = html;
}



function normalizePath(path) {
    return '/ai-wardrobe/' + path.replace(/\\/g, "/").replace(/^\/?ai-wardrobe\//, "");
}

document.getElementById("manual-form").addEventListener("submit", e => {
    e.preventDefault();
    submitForm("manual-form");
});
document.getElementById("automatic-form").addEventListener("submit", e => {
    e.preventDefault();
    submitForm("automatic-form");
});
</script>
</body>
</html>
