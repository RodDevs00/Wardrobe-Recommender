<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
require_once "db.php";

/**
 * Helper function to normalize image paths for browser
 */
function webPath(string $path): string {
    if (!$path) return '';
    $path = str_replace(['\\\\', '\\'], '/', $path);
    $projectFolder = basename(dirname(__FILE__));
    return '/' . $projectFolder . '/' . ltrim($path, '/');
}
// function webPath(string $path): string {
//     if (!$path) return '';
//     $path = str_replace(['\\\\', '\\'], '/', $path);

//     // Strip leading "../" or "./"
//     $path = preg_replace('#^(\.\./|\.\/)#', '', $path);

//     // Always serve from /uploads/
//     if (strpos($path, 'uploads/') === 0) {
//         return '/' . $path;
//     }
//     return '/uploads/' . ltrim($path, '/');
// }

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters from GET request
$filterEvent = $_GET['event'] ?? '';
$filterStyle = $_GET['style'] ?? '';
$filterMode = $_GET['mode'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$filterMotif   = $_GET['motif'] ?? '';
$filterPalette = $_GET['palette'] ?? '';


// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM ootd_history WHERE user_id = ? ";
$params = [$user_id];

if ($filterEvent) { $query .= "AND event = ? "; $params[] = $filterEvent; }
if ($filterStyle) { $query .= "AND style = ? "; $params[] = $filterStyle; }
if ($filterMode) { $query .= "AND mode = ? "; $params[] = $filterMode; }
if ($searchQuery) { $query .= "AND event LIKE ? "; $params[] = "%$searchQuery%"; }
if ($filterMotif) { $query .= "AND motif LIKE ? "; $params[] = "%$filterMotif%"; }
if ($filterPalette) { $query .= "AND palette LIKE ? "; $params[] = "%$filterPalette%"; }


$query .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$history_stmt = $pdo->prepare($query);
foreach ($params as $i => $param) {
    $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $history_stmt->bindValue($i + 1, $param, $type);
}
$history_stmt->execute();
$history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$total_rows = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Distinct dropdown values (always fetch all, even if filtered)
$distinctEventsStmt = $pdo->prepare("SELECT DISTINCT event FROM ootd_history WHERE user_id=? AND event IS NOT NULL AND event != '' ORDER BY event");
$distinctEventsStmt->execute([$user_id]);
$distinctEvents = $distinctEventsStmt->fetchAll(PDO::FETCH_COLUMN);

$distinctStylesStmt = $pdo->prepare("SELECT DISTINCT style FROM ootd_history WHERE user_id=? AND style IS NOT NULL AND style != '' ORDER BY style");
$distinctStylesStmt->execute([$user_id]);
$distinctStyles = $distinctStylesStmt->fetchAll(PDO::FETCH_COLUMN);

$distinctMotifsStmt = $pdo->prepare("SELECT DISTINCT motif FROM ootd_history WHERE user_id=? AND motif IS NOT NULL AND motif != '' ORDER BY motif");
$distinctMotifsStmt->execute([$user_id]);
$distinctMotifs = $distinctMotifsStmt->fetchAll(PDO::FETCH_COLUMN);

$distinctPalettesStmt = $pdo->prepare("SELECT DISTINCT palette FROM ootd_history WHERE user_id=? AND palette IS NOT NULL AND palette != '' ORDER BY palette");
$distinctPalettesStmt->execute([$user_id]);
$distinctPalettes = $distinctPalettesStmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stylesense - Recommendations</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">

<nav class="bg-gray-50 border-b border-gray-200 px-6 py-4 flex justify-between items-center relative">
  <!-- Logo & Title -->
  <div class="flex items-center space-x-3">
      <img src="https://img.icons8.com/color/48/wardrobe.png" class="h-8 w-8" alt="AI Wardrobe Logo">
      <span class="text-xl font-bold text-gray-800">Stylesense</span>
  </div>

  <!-- Desktop Menu -->
  <div class="hidden md:flex space-x-6">
      <a href="home.php" class="text-gray-600 font-semibold hover:text-blue-800">Home</a>
      <a href="recommend.php" class="text-blue-600 font-semibold hover:text-blue-600">Recommendations</a>
      <a href="profile.php" class="text-gray-600 font-medium hover:text-blue-600">Profile</a>
      <a href="auth/logout.php" class="text-red-500 font-medium hover:text-red-700">Logout</a>
  </div>

  <!-- Burger Button -->
  <button id="menu-toggle" class="md:hidden text-gray-700 focus:outline-none">
      <!-- default burger icon -->
      <svg id="burger-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
      <!-- close (X) icon -->
      <svg id="close-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
  </button>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="absolute top-full left-0 w-full bg-gray-50 border-b border-gray-200 hidden flex-col space-y-2 px-6 py-4 transition-all duration-300 ease-in-out">
      <a href="home.php" class="block text-gray-600 font-semibold hover:text-blue-800">Home</a>
      <a href="recommend.php" class="block text-blue-600 font-semibold hover:text-blue-600">Recommendations</a>
      <a href="profile.php" class="block text-gray-600 hover:text-blue-600">Profile</a>
      <a href="auth/logout.php" class="block text-red-500 hover:text-red-700">Logout</a>
  </div>
</nav>



<div class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-700">Your Past Recommendations</h2>

    <!-- Filters -->
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <form method="GET" class="space-y-4 md:flex md:space-x-4 md:space-y-0 items-center">
            <input type="text" name="search" placeholder="Search by event..." value="<?= htmlspecialchars($searchQuery) ?>"
                   class="w-full md:flex-1 rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <select name="event" class="rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Events</option>
                <?php foreach($distinctEvents as $e): ?>
                    <option value="<?= htmlspecialchars($e) ?>" <?= $filterEvent === $e ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_',' ',$e))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="style" class="rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Styles</option>
                <?php foreach($distinctStyles as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= $filterStyle === $s ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_',' ',$s))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="motif" class="rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Motifs</option>
                <?php foreach($distinctMotifs as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= $filterMotif === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_',' ',$m))) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="palette" class="rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Palettes</option>
                <?php foreach($distinctPalettes as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $filterPalette === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucwords(str_replace('_',' ',$p))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="mode" class="rounded-md border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Modes</option>
                <option value="automatic" <?= $filterMode==='automatic'?'selected':'' ?>>Automatic</option>
                <option value="manual" <?= $filterMode==='manual'?'selected':'' ?>>Manual</option>
            </select>
        </form>
    </div>
<?php if ($history): ?>
    <div class="grid md:grid-cols-2 gap-6">
        <?php foreach($history as $h): ?>
            <?php
                $items = json_decode($h['items'], true) ?: [];
                $top_match = json_decode($h['top_match'], true) ?: [];

                // Determine actual top match paths for marking
                $topMatchPaths = [];
                if (!empty($top_match)) {
                    if (isset($top_match['items']) && is_array($top_match['items'])) {
                        foreach ($top_match['items'] as $tm) {
                            $topMatchPaths[] = $tm['path'] ?? '';
                        }
                    } else {
                        $topMatchPaths[] = $top_match['path'] ?? '';
                    }
                }

                // Sort items: top match first
                usort($items, function($a, $b) use ($topMatchPaths) {
                    $aTop = in_array($a['path'] ?? '', $topMatchPaths);
                    $bTop = in_array($b['path'] ?? '', $topMatchPaths);
                    return $aTop === $bTop ? 0 : ($aTop ? -1 : 1);
                });
            ?>
            <div class="bg-white shadow rounded-xl p-5 relative">
                <!-- Delete Icon -->
                <button class="delete-btn absolute top-3 right-3 text-red-500 hover:text-red-700" data-id="<?= $h['id'] ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <h3 class="font-semibold text-gray-700 mb-1">
                    <?= htmlspecialchars(ucwords(str_replace("_"," ",$h['event']))) ?>
                    <span class="text-sm text-gray-500">(<?= htmlspecialchars($h['mode']) ?>)</span>
                </h3>
                <p class="text-xs text-gray-500 mb-3"><?= htmlspecialchars($h['created_at']) ?></p>

                <div class="flex flex-wrap gap-3 mb-3">
                    <?php foreach($items as $it): 
                        $imgPath = webPath($it['path'] ?? '');
                        if (!$imgPath) continue;

                        $similarity = isset($it['similarity']) ? number_format(floatval($it['similarity'])*100, 2) : 0;
                        $recommendation = $it['recommendation'] ?? '';
                        $detected_type = $it['detected_type'] ?? '';
                        $isTopMatch = in_array($it['path'], $topMatchPaths);
                    ?>
                        <div class="w-28 relative">
                            <img src="<?= htmlspecialchars($imgPath) ?>" class="w-28 h-28 object-cover rounded-lg border <?= $isTopMatch ? 'border-green-500' : 'border-gray-300' ?>">
                            <?php if($isTopMatch): ?>
                                <span class="absolute top-1 left-1 px-2 py-0.5 bg-green-600 text-white text-[10px] rounded-md font-semibold">Top Match</span>
                            <?php endif; ?>
                            <p class="text-xs mt-1 text-center <?= $isTopMatch ? 'text-green-700 font-semibold' : 'text-gray-600' ?>">
                                <?= htmlspecialchars($detected_type ? ucfirst($detected_type) : '') ?><br>
                                <?= htmlspecialchars($recommendation) ?><br>
                                <?= $similarity ? htmlspecialchars($similarity).'%' : '' ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>





    <!-- Pagination -->
<div class="mt-8 flex justify-center space-x-2">
    <?php
    $range = 2; // how many numbers to show around current page
    ?>

    <!-- Prev button -->
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&search=<?= htmlspecialchars($searchQuery) ?>&event=<?= htmlspecialchars($filterEvent) ?>&style=<?= htmlspecialchars($filterStyle) ?>&mode=<?= htmlspecialchars($filterMode) ?>"
           class="px-3 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">Prev</a>
    <?php else: ?>
        <span class="px-3 py-2 rounded-md bg-gray-100 text-gray-400 text-sm cursor-not-allowed">Prev</span>
    <?php endif; ?>

    <!-- First page -->
    <?php if ($page > $range + 1): ?>
        <a href="?page=1&search=<?= htmlspecialchars($searchQuery) ?>&event=<?= htmlspecialchars($filterEvent) ?>&style=<?= htmlspecialchars($filterStyle) ?>&mode=<?= htmlspecialchars($filterMode) ?>"
           class="px-3 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">1</a>
        <?php if ($page > $range + 2): ?>
            <span class="px-2 py-2 text-gray-500 text-sm">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Page numbers around current -->
    <?php for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= htmlspecialchars($searchQuery) ?>&event=<?= htmlspecialchars($filterEvent) ?>&style=<?= htmlspecialchars($filterStyle) ?>&mode=<?= htmlspecialchars($filterMode) ?>"
           class="px-3 py-2 rounded-md text-sm <?= $page == $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
           <?= $i ?>
        </a>
    <?php endfor; ?>

    <!-- Last page -->
    <?php if ($page < $total_pages - $range): ?>
        <?php if ($page < $total_pages - $range - 1): ?>
            <span class="px-2 py-2 text-gray-500 text-sm">...</span>
        <?php endif; ?>
        <a href="?page=<?= $total_pages ?>&search=<?= htmlspecialchars($searchQuery) ?>&event=<?= htmlspecialchars($filterEvent) ?>&style=<?= htmlspecialchars($filterStyle) ?>&mode=<?= htmlspecialchars($filterMode) ?>"
           class="px-3 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Next button -->
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page+1 ?>&search=<?= htmlspecialchars($searchQuery) ?>&event=<?= htmlspecialchars($filterEvent) ?>&style=<?= htmlspecialchars($filterStyle) ?>&mode=<?= htmlspecialchars($filterMode) ?>"
           class="px-3 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm">Next</a>
    <?php else: ?>
        <span class="px-3 py-2 rounded-md bg-gray-100 text-gray-400 text-sm cursor-not-allowed">Next</span>
    <?php endif; ?>
</div>



    <?php else: ?>
        <p class="text-gray-500 text-center">No recommendations found matching your criteria.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select');

    inputs.forEach(el => {
        if (el.tagName === 'SELECT') {
            el.addEventListener('change', () => form.submit());
        }
        if (el.type === 'text') {
            el.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') form.submit();
            });
        }
    });

    // Delete history
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Are you sure?',
                text: "This history record will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_history.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success){
                            Swal.fire('Deleted!', 'Your history record has been deleted.', 'success');
                            this.closest('.bg-white').remove();
                        } else {
                            Swal.fire('Error!', data.message || 'Could not delete.', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error!', 'Could not delete.', 'error'));
                }
            });
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle mobile menu
    const toggleBtn = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const burgerIcon = document.getElementById('burger-icon');
    const closeIcon = document.getElementById('close-icon');

    toggleBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        burgerIcon.classList.toggle('hidden');
        closeIcon.classList.toggle('hidden');
    });
});
</script>


</body>
</html>
