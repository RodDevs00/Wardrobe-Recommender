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
function renderLatestResult(data) {
    // Safely handle undefined event
    let eventName = data.event ? data.event.replace("_", " ") : "Unknown event";

    let html = `
        <h3 class="text-lg font-bold text-gray-800 mb-3">Recommendation Result</h3>
        <p class="text-sm text-gray-600 mb-4">
            <strong>Event:</strong> ${eventName} <br>
            <strong>Style:</strong> ${data.style || "unspecified"} <br>
            <strong>Description:</strong> ${data.event_description || ""}
        </p>
    `;

    // ---------------------------
    // Determine Top Matches
    // ---------------------------
    let topMatches = [];
    const uploadedItems = data.items || [];

    if (data.mode === "manual") {
        if (data.top_match) topMatches.push(data.top_match);
    } else { // automatic
        // Get all items of each type
        const upperItems = uploadedItems.filter(it => it.detected_type === "upper");
        const lowerItems = uploadedItems.filter(it => it.detected_type === "lower");
        const fullBodyItems = uploadedItems.filter(it => it.detected_type === "full-body");

        // Pick the highest similarity for each type
        const bestUpper = upperItems.length
            ? upperItems.reduce((prev, curr) => curr.similarity > prev.similarity ? curr : prev)
            : null;

        const bestLower = lowerItems.length
            ? lowerItems.reduce((prev, curr) => curr.similarity > prev.similarity ? curr : prev)
            : null;

        const bestFullBody = fullBodyItems.length
            ? fullBodyItems.reduce((prev, curr) => curr.similarity > prev.similarity ? curr : prev)
            : null;

        // Determine whether full-body or upper+lower combo
        const fullSim = bestFullBody ? bestFullBody.similarity : 0;
        const upperSim = bestUpper ? bestUpper.similarity : 0;
        const lowerSim = bestLower ? bestLower.similarity : 0;
        const comboSim = ((upperSim + lowerSim) / ((bestUpper ? 1 : 0) + (bestLower ? 1 : 0) || 1));

        if (bestFullBody && fullSim >= comboSim) {
            topMatches.push(bestFullBody);
        } else {
            if (bestUpper) topMatches.push(bestUpper);
            if (bestLower) topMatches.push(bestLower);
        }

        // Fallback: pick highest similarity if nothing selected
        if (topMatches.length === 0 && uploadedItems.length > 0) {
            const highest = uploadedItems.reduce((prev, curr) => curr.similarity > prev.similarity ? curr : prev);
            topMatches.push(highest);
        }
    }

   // ---------------------------
// Display Top Matches (stack upper+lower combo)
// ---------------------------
if (topMatches.length > 0) {
    html += `
        <div class="mb-8 p-4 border-2 border-green-400 rounded-lg bg-green-50 shadow-sm">
            <h4 class="text-md font-semibold text-green-700 mb-3">🎯 Top Match${topMatches.length > 1 ? "es" : ""}</h4>
            <div class="flex flex-wrap gap-3 justify-center">
    `;

    // Detect if both upper and lower exist
    const hasUpper = topMatches.some(m => m.detected_type === "upper");
    const hasLower = topMatches.some(m => m.detected_type === "lower");

    if (hasUpper && hasLower) {
        const upper = topMatches.find(m => m.detected_type === "upper");
        const lower = topMatches.find(m => m.detected_type === "lower");

        html += `
            <div class="relative flex flex-col items-center w-32">
                <!-- Upper -->
                <span class="absolute top-1 left-1 bg-green-600 text-white text-[10px] px-2 py-0.5 rounded-md shadow">
                    Best Upper-body
                </span>
                <img src="${normalizePath(upper.path)}" class="w-32 h-32 object-cover rounded-lg border-4 border-green-500 mb-1">
                <!-- Lower -->
                <span class="absolute bottom-1 left-1 bg-green-600 text-white text-[10px] px-2 py-0.5 rounded-md shadow">
                    Best Lower-body
                </span>
                <img src="${normalizePath(lower.path)}" class="w-32 h-32 object-cover rounded-lg border-4 border-green-500 mt-1">
                <p class="text-xs text-center text-green-600 font-bold mt-1">
                    ✅ Outfit Combo
                </p>
            </div>
        `;
    } else {
        // Fallback: display each separately (including full-body)
        topMatches.forEach(match => {
            let label = match.detected_type || "unknown";
            let recText = match.recommendation || "Top Match";
            let similarity = match.similarity ? (match.similarity * 100).toFixed(1) : "N/A";
            let path = match.path ? normalizePath(match.path) : "#";

            html += `
                <div class="relative w-32">
                    <span class="absolute top-1 left-1 bg-green-600 text-white text-[10px] px-2 py-0.5 rounded-md shadow">
                        ${label === "upper" ? "Best Upper-body" : label === "lower" ? "Best Lower-body" : "Top Match"}
                    </span>
                    <img src="${path}" class="w-32 h-32 object-cover rounded-lg border-4 border-green-500 mb-2">
                    <p class="text-xs text-center text-green-600 font-bold">
                        ✅ ${recText}
                    </p>
                    <p class="text-[10px] text-gray-500 text-center">
                        ${match.label || "unknown"} (${similarity}%)
                    </p>
                </div>
            `;
        });
    }

    html += `</div></div>`;
}

    // ---------------------------
    // Display Remaining Uploaded Items
    // ---------------------------
    if (uploadedItems.length > 0) {
        html += `
            <h4 class="text-md font-semibold text-gray-600 mb-2">Uploaded Items</h4>
            <div class="flex flex-wrap gap-3 mb-6 justify-center">
        `;

        uploadedItems.forEach(it => {
            if (["accessory", "shoes"].includes(it.detected_type)) return;
            if (topMatches.some(m => m.path === it.path)) return; // skip top matches

            // Automatic mode: skip full-body if upper/lower exist
            if (data.mode === "automatic" && it.detected_type === "full-body" && topMatches.some(m => m.detected_type !== "full-body")) {
                return;
            }

            // Categorize similarity
            let categoryLabel = "";
            if (it.similarity >= 0.6) categoryLabel = "Highly recommended";
            else if (it.similarity >= 0.35) categoryLabel = "Moderate match";
            else categoryLabel = "Weak match";

            html += `
                <div class="w-32 relative">
                    <img src="${normalizePath(it.path)}" class="w-32 h-32 object-cover rounded-lg border mb-2">
                    <p class="text-[10px] text-gray-500 text-center">
                        ${it.label || "unknown"} (${it.similarity ? (it.similarity*100).toFixed(1) : "N/A"}%)
                    </p>
                    <span class="absolute top-1 left-1 px-2 py-0.5 rounded-md text-[10px] font-semibold
                        ${categoryLabel === "Highly recommended" ? "bg-green-600 text-white" :
                          categoryLabel === "Moderate match" ? "bg-yellow-500 text-white" :
                          "bg-gray-400 text-white"}">
                        ${categoryLabel}
                    </span>
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
