<?php
include "koneksi.php";

header('Content-Type: application/json');

// Ambil status studio + last update + sound level
$result = mysqli_query($conn, "
    SELECT 
        s.studio_id, 
        s.studio_name,
        (
            SELECT sound_level 
            FROM sensor_log 
            WHERE studio_id = s.studio_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS sound_level,
        (
            SELECT sound_status 
            FROM sensor_log 
            WHERE studio_id = s.studio_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS sound_status,
        (
            SELECT created_at 
            FROM sensor_log 
            WHERE studio_id = s.studio_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS last_update
    FROM studios s
    ORDER BY s.studio_id ASC
");

$data = [];

while ($row = mysqli_fetch_assoc($result)) {

    // Jika NULL → isi default
    $row['sound_level']  = $row['sound_level']  !== null ? intval($row['sound_level']) : 0;
    $row['sound_status'] = $row['sound_status'] !== null ? $row['sound_status']          : "RENDAH";
    $row['last_update']  = $row['last_update']  !== null ? $row['last_update']          : "-";

    $data[] = $row;
}

echo json_encode($data);
?>
