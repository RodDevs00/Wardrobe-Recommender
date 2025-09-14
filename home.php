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
    /* Animated spinning gradient loader */
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
    <!-- Logo & Title -->
    <div class="flex items-center space-x-3">
        <img src="https://img.icons8.com/color/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
        <span class="text-xl font-bold text-gray-800">AI Wardrobe</span>
    </div>

    <!-- Navigation Links -->
    <div class="flex space-x-6">
        <a href="index.php" class="text-blue-600 font-semibold transition-colors duration-200 hover:text-blue-800">Home</a>
        <a href="recommend.php" class="text-gray-600 font-medium transition-colors duration-200 hover:text-blue-600">Recommendations</a>
        <a href="profile.php" class="text-gray-600 font-medium transition-colors duration-200 hover:text-blue-600">Profile</a>
        <a href="auth/logout.php" class="text-red-500 font-medium transition-colors duration-200 hover:text-red-700">Logout</a>
    </div>
</nav>


<!-- Main Layout -->
<div class="flex justify-center py-10 px-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-6xl">

        <!-- Card 1: Forms -->
        <div class="bg-white shadow-lg rounded-2xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Get Your AI Recommendation</h2>

            <!-- Info Box -->
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
                            <option value="formal_wedding">Formal Wedding</option>
                            <option value="casual_wedding">Casual Wedding</option>
                            <option value="summer_party">Summer Party</option>
                            <option value="beach_party">Beach Party</option>
                            <option value="casual_office">Casual Office</option>
                            <option value="formal_office">Formal Office</option>
                            <option value="streetwear">Streetwear</option>
                            <option value="gym">Gym / Workout</option>
                            <option value="winter_casual">Winter Casual</option>
                            <option value="travel">Travel</option>
                            <option value="date_night">Date Night</option>
                            <option value="music_festival">Music Festival</option>
                            <option value="business_meeting">Business Meeting</option>
                            <option value="graduation">Graduation</option>
                            <option value="funeral">Funeral</option>
                            <option value="sports_event">Sports Event</option>
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
                            <option value="formal_wedding">Formal Wedding</option>
                            <option value="casual_wedding">Casual Wedding</option>
                            <option value="summer_party">Summer Party</option>
                            <option value="beach_party">Beach Party</option>
                            <option value="casual_office">Casual Office</option>
                            <option value="formal_office">Formal Office</option>
                            <option value="streetwear">Streetwear</option>
                            <option value="gym">Gym / Workout</option>
                            <option value="winter_casual">Winter Casual</option>
                            <option value="travel">Travel</option>
                            <option value="date_night">Date Night</option>
                            <option value="music_festival">Music Festival</option>
                            <option value="business_meeting">Business Meeting</option>
                            <option value="graduation">Graduation</option>
                            <option value="funeral">Funeral</option>
                            <option value="sports_event">Sports Event</option>
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
                <?php if ($latest): ?>
                    <?php 
                        $items = json_decode($latest['items'], true) ?? [];
                        $top_match = json_decode($latest['top_match'], true) ?? [];

                        // Normalize top_match to always have items[]
                        if (isset($top_match['path'])) {
                            $top_match = ['items' => [$top_match]];
                        }

                        $style = $latest['style'] ?? ($items[0]['details']['style'] ?? 'N/A');
                        $wardrobe_type = null;
                        if ($latest['mode'] === 'manual') {
                            $wardrobe_type = $items[0]['detected_type'] ?? 'N/A';
                        }
                    ?>

                    <div class="mb-4 text-center">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <?= htmlspecialchars(ucwords(str_replace("_"," ",$latest['event']))) ?>
                            <span class="text-sm text-gray-500">
                                (<?= $latest['mode'] ?>, <?= htmlspecialchars($style) ?>
                                <?php if ($wardrobe_type): ?>, <?= htmlspecialchars($wardrobe_type) ?><?php endif; ?>)
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500"><?= $latest['created_at'] ?></p>
                    </div>

                    <?php if(!empty($top_match['items'])): ?>
                        <h4 class="text-md font-semibold text-gray-600 mb-2">Top Matches</h4>
                        <div class="flex flex-wrap gap-3 mb-6 justify-center">
                            <?php foreach($top_match['items'] as $match): ?>
                                <?php 
                                    $imgPath = isset($match['path']) ? str_replace("\\", "/", $match['path']) : '';
                                    if ($imgPath) $imgPath = '/ai-wardrobe/' . ltrim($imgPath, '/');
                                ?>
                                <?php if($imgPath): ?>
                                    <div class="w-32">
                                        <img src="<?= htmlspecialchars($imgPath) ?>" class="w-32 h-32 object-cover rounded-lg border mb-2">
                                        <p class="text-xs text-center text-gray-700 font-medium">
                                            <?= htmlspecialchars($match['recommendation'] ?? '') ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($items): ?>
                        <h4 class="text-md font-semibold text-gray-600 mb-2">Uploaded Items</h4>
                        <div class="flex flex-wrap gap-3 mb-4">
                            <?php foreach($items as $it): ?>
                                <?php 
                                    $imgPath = isset($it['path']) ? str_replace("\\", "/", $it['path']) : '';
                                    if ($imgPath) $imgPath = '/ai-wardrobe/' . ltrim($imgPath, '/');
                                    $detType = $it['detected_type'] ?? '';
                                    if (in_array($detType, ['accessory','shoes'])) continue;
                                ?>
                                <?php if($imgPath): ?>
                                    <div class="w-28">
                                        <img src="<?= htmlspecialchars($imgPath) ?>" class="w-28 h-28 object-cover rounded-lg border">
                                        <p class="text-xs mt-1 text-center text-gray-600">
                                            <?= htmlspecialchars($it['recommendation'] ?? '') ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center">No recommendations yet.</p>
                <?php endif; ?>
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

// // AJAX form submission
// async function submitForm(formId) {
//     const form = document.getElementById(formId);
//     const formData = new FormData(form);

//     const resultDiv = document.getElementById("latest-result");
//     // resultDiv.innerHTML = "<p class='text-gray-500 text-center'>⏳ Generating recommendation...</p>";
//     resultDiv.innerHTML = `
//         <div class="flex flex-col items-center justify-center py-10">
//             <div class="loader mb-3"></div>
//             <p class="text-gray-500 text-center text-sm">AI is thinking...</p>
//         </div>
//     `;

//     try {
//         const response = await fetch("recommend_process.php", {
//             method: "POST",
//             body: formData
//         });
//         const data = await response.json();

//         if (data.success) {
//             renderLatestResult(data);
//         } else {
//             resultDiv.innerHTML = `<p class='text-red-500 text-center'>❌ ${data.error || "Something went wrong"}</p>`;
//         }
//     } catch (err) {
//         resultDiv.innerHTML = `<p class='text-red-500 text-center'>⚠️ Error: ${err.message}</p>`;
//     }
// }

async function submitForm(formId) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);

    const resultDiv = document.getElementById("latest-result");
    // Loader display
    resultDiv.innerHTML = `
        <div class="flex flex-col items-center justify-center py-10">
            <div class="loader mb-3"></div>
            <p class="text-gray-500 text-center text-sm">AI is thinking...</p>
        </div>
    `;

    try {
        const response = await fetch("recommend_process.php", {
            method: "POST",
            body: formData
        });
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
      if (data.top_match && !Array.isArray(data.top_match.items) && data.top_match.path) {
        data.top_match = { items: [data.top_match] };
    }

    if (data.wardrobe_type === 'full-body') {
        data.wardrobe_type = 'full_body';
    }

    data.event = (data.event || 'Unknown Event').replace(/_/g, ' ');
    const resultDiv = document.getElementById("latest-result");
    let html = `
        <div class="mb-4 text-center">
            <h3 class="text-lg font-semibold text-gray-700">
                ${data.event}
                <span class="text-sm text-gray-500">
                    (${data.mode}, ${data.style}${data.wardrobe_type ? ", " + data.wardrobe_type : ""})
                </span>
            </h3>
            <p class="text-xs text-gray-500">${data.created_at}</p>
        </div>
    `;

    const normalizePath = (p) => "/ai-wardrobe/" + p.replace(/^\/+/, "");

    // Handle top matches as array
    if (data.top_match?.items?.length > 0) {
        html += `<h4 class="text-md font-semibold text-gray-600 mb-2">Top Matches</h4>
                 <div class="flex flex-wrap gap-3 mb-6 justify-center">`;
        data.top_match.items.forEach(match => {
            html += `
                <div class="w-32">
                    <img src="${normalizePath(match.path)}" class="w-32 h-32 object-cover rounded-lg border mb-2">
                    <p class="text-xs text-center text-gray-700 font-medium">${match.recommendation || ""}</p>
                </div>
            `;
        });
        html += `</div>`;
    }

    // Uploaded items
    if (data.items?.length > 0) {
        html += `<h4 class="text-md font-semibold text-gray-600 mb-2">Uploaded Items</h4>
                 <div class="flex flex-wrap gap-3 mb-4">`;
        data.items.forEach(it => {
            if (["accessory","shoes"].includes(it.detected_type)) return;
            html += `
                <div class="w-28">
                    <img src="${normalizePath(it.path)}" class="w-28 h-28 object-cover rounded-lg border">
                    <p class="text-xs mt-1 text-center text-gray-600">${it.recommendation || ""}</p>
                </div>
            `;
        });
        html += `</div>`;
    }

    resultDiv.innerHTML = html;
}

// Hook forms
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
