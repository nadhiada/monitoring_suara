<?php
date_default_timezone_set('Asia/Jakarta');
include "koneksi.php";

if (isset($_GET['studio_id']) && isset($_GET['level']) &&
    isset($_GET['status']) && isset($_GET['lamp'])) {

    $studio_id = intval($_GET['studio_id']);
    $sound_level = intval($_GET['level']);
    $sound_status = mysqli_real_escape_string($conn, $_GET['status']);
    $lamp_id = intval($_GET['lamp']);

    // Masukkan data ke tabel
    $sql = "INSERT INTO sensor_log (studio_id, lamp_id, sound_level, sound_status)
            VALUES ('$studio_id', '$lamp_id', '$sound_level', '$sound_status')";

    if (mysqli_query($conn, $sql)) {
        echo "OK";
    } else {
        echo "DB ERROR: " . mysqli_error($conn);
    }

} else {
    echo "Invalid parameters!";
}
?>
