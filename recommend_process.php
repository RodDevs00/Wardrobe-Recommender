<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

require_once "db.php";

function normalizePath($path) {
    // Ensure consistent forward slashes + only filename
    return "/uploads/" . basename(str_replace("\\", "/", $path));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? null;
    $event = trim($_POST['event'] ?? "");
    $wardrobe_type = $_POST['wardrobe_type'] ?? null;
    $style = $_POST['style'] ?? "no_preference";
    $user_id = $_SESSION['user_id'];

    if (!$mode || !$event) {
        echo json_encode(["success" => false, "error" => "Missing required fields"]);
        exit;
    }

    // ==============================
    // 1. Handle file uploads
    // ==============================
    $uploaded_files = [];
    $upload_dir = __DIR__ . "/uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (!isset($_FILES['images'])) {
        echo json_encode(["success" => false, "error" => "No images uploaded"]);
        exit;
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $mime = mime_content_type($tmp_name);
            if (!in_array($mime, ['image/jpeg', 'image/png'])) continue;

            $filename = uniqid() . "_" . basename($_FILES['images']['name'][$key]);
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($tmp_name, $filepath)) {
                $uploaded_files[] = [
                    "filesystem" => $filepath, 
                    "web" => "/uploads/" . $filename
                ];
            }
        }
    }

    if (empty($uploaded_files)) {
        echo json_encode(["success" => false, "error" => "No valid images uploaded"]);
        exit;
    }

    // ==============================
    // 2. Send to Python API
    // ==============================
    $api_url = "http://127.0.0.1:5000/recommend/$mode";

    $cfile_array = [];
    foreach ($uploaded_files as $file) {
        $cfile_array[] = curl_file_create(
            $file["filesystem"],
            mime_content_type($file["filesystem"]),
            basename($file["filesystem"])
        );
    }

    $post_fields = [
        "event" => $event,
        "style" => $style
    ];
    if ($mode === "manual" && $wardrobe_type) {
        $post_fields["wardrobe_type"] = $wardrobe_type;
    }

    foreach ($cfile_array as $i => $cfile) {
        $post_fields["images[$i]"] = $cfile;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(["success" => false, "error" => "Error connecting to AI API: $error"]);
        exit;
    }

    $result = json_decode($response, true);
    if (!$result) {
        echo json_encode(["success" => false, "error" => "Invalid response from AI API", "raw" => $response]);
        exit;
    }

    $items = $result['items'] ?? [];
    $top_match = $result['top_match'] ?? ["recommendation" => "❌ No recommendation"];

    // ==============================
    // 3. Normalize paths for web
    // ==============================
    foreach ($items as &$item) {
        if (isset($item['path'])) {
            $item['path'] = normalizePath($item['path']);
        }
    }
    unset($item);

    if (isset($top_match['path'])) {
        $top_match['path'] = normalizePath($top_match['path']);
    }

    if (isset($top_match['items']) && is_array($top_match['items'])) {
        foreach ($top_match['items'] as &$tmItem) {
            if (isset($tmItem['path'])) {
                $tmItem['path'] = normalizePath($tmItem['path']);
            }
        }
        unset($tmItem);
    }

    // ==============================
    // 4. Save history in DB
    // ==============================
    $stmt = $pdo->prepare("
        INSERT INTO ootd_history 
        (user_id, event, mode, wardrobe_type, style, items, top_match, full_response, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $user_id,
        $event,
        $mode,
        $wardrobe_type,
        $style,
        json_encode($items),
        json_encode($top_match),
        json_encode($result)
    ]);

    // ==============================
    // 5. Respond with JSON
    // ==============================
    echo json_encode([
        "success" => true,
        "event" => ucwords(str_replace("_", " ", $event)),
        "mode" => $mode,
        "style" => $style,
        "wardrobe_type" => $wardrobe_type,
        "items" => $items,
        "top_match" => $top_match,
        "created_at" => date("Y-m-d H:i:s")
    ]);
    exit;

} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}
