<?php
include "koneksi.php";

header("Content-Type: application/json");

// Validasi studio_id
if (!isset($_GET['studio_id']) || intval($_GET['studio_id']) < 1) {
    echo json_encode(["error" => "studio_id missing or invalid"]);
    exit();
}

$studio_id = intval($_GET['studio_id']);

// Ambil 20 riwayat terbaru
$q = mysqli_query($conn, "
    SELECT sound_level, sound_status, created_at
    FROM sensor_log 
    WHERE studio_id = $studio_id
    ORDER BY id DESC 
    LIMIT 20
");

$data = [];
while ($row = mysqli_fetch_assoc($q)) {
    $data[] = $row;
}

// Kembalikan JSON
echo json_encode($data);
?>
