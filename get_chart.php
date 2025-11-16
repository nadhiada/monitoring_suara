<?php
include "koneksi.php";

header("Content-Type: application/json");

// Validasi parameter
if (!isset($_GET['studio_id'])) {
    echo json_encode(["error" => "studio_id missing"]);
    exit();
}

$studio_id = intval($_GET['studio_id']);

// Ambil data 24 jam terakhir untuk grafik
$q = mysqli_query($conn, "
    SELECT sound_level, created_at
    FROM sensor_log
    WHERE studio_id = $studio_id
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at ASC
");

$labels = [];
$values = [];

while ($row = mysqli_fetch_assoc($q)) {
    $labels[] = date("H:i", strtotime($row['created_at']));
    $values[] = $row['sound_level'];
}

echo json_encode([
    "labels" => $labels,
    "values" => $values
]);
?>
