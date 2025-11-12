<?php
include "koneksi.php";

if (isset($_GET['level']) && isset($_GET['status']) && isset($_GET['lamp'])) {
    $sound_level = intval($_GET['level']);
    $sound_status = mysqli_real_escape_string($conn, $_GET['status']);
    $lamp_id = intval($_GET['lamp']);
    $sensor_id = 1; // id sensor

    // Insert log
    $sql = "INSERT INTO sensor_log (sensor_id, lamp_id, sound_level, sound_status) 
            VALUES ('$sensor_id', '$lamp_id', '$sound_level', '$sound_status')";
    mysqli_query($conn, $sql);

    // reset lampu dulu
    mysqli_query($conn, "UPDATE lampu SET status='OFF'");

    // nyalain setelah mati
    mysqli_query($conn, "UPDATE lampu SET status='ON' WHERE id='$lamp_id'");
    

    echo "OK";
} else {
    echo "Invalid parameters!";
}
?>