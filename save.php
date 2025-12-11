<?php
$mysqli = new mysqli("localhost","root","","iot_dashboard");

$data = json_decode(file_get_contents("php://input"), true);

$suhu = $data["temperature"];
$hum  = $data["humidity"];

$query = "INSERT INTO sensor_data (suhu, kelembapan) VALUES ('$suhu','$hum')";
$mysqli->query($query);

echo "DATA OK";
?>
