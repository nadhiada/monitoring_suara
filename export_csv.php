<?php
include "koneksi.php";

if (!isset($_GET['studio_id'])) {
    die("studio_id tidak ditemukan.");
}

$studio_id = intval($_GET['studio_id']);

// Header file CSV
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=studio_{$studio_id}_export.csv");

// Tambahkan BOM supaya Excel tidak rusak
echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

// Header kolom CSV
fputcsv($output, ["created_at", "sound_level", "sound_status"]);

// Query data
$q = mysqli_query($conn, "
    SELECT created_at, sound_level, sound_status
    FROM sensor_log
    WHERE studio_id = $studio_id
    ORDER BY created_at DESC
");

// Tuliskan ke CSV
while ($row = mysqli_fetch_assoc($q)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
