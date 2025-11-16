<?php
include "koneksi.php";

$query = mysqli_query($conn, "
    SELECT s.studio_id,
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

while ($row = mysqli_fetch_assoc($query)) {

    // fallback jika belum ada data masuk
    if ($row['sound_level'] === null) {
        $row['sound_level']  = 0;
        $row['sound_status'] = "RENDAH";
        $row['last_update']  = "-";
    }

    $data[] = [
        "studio_id"    => (int)$row['studio_id'],
        "studio_name"  => $row['studio_name'],   // ⬅️ DITAMBAH WAJIB
        "sound_level"  => (int)$row['sound_level'],
        "sound_status" => $row['sound_status'],
        "last_update"  => $row['last_update']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
