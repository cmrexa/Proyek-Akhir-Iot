<?php
$mysqli = new mysqli("localhost", "root", "", "iot_dashboard");

if ($mysqli->connect_errno) {
    die(json_encode(["error" => "Gagal Connect DB"]));
}

// Ambil data terbaru
$result = $mysqli->query("SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 1");
$row = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'suhu' => $row ? floatval($row['suhu']) : null,
    'kelembapan' => $row ? floatval($row['kelembapan']) : null,
    'status' => $row ? "OK" : "NO DATA"
]);
?>
