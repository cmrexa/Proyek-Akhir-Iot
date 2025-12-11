<?php
require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\MqttClientException;

// ===== Database =====
$mysqli = new mysqli("localhost", "root", "", "iot_dashboard");
if ($mysqli->connect_errno) { die("Failed to connect to MySQL: " . $mysqli->connect_error); }

// ===== MQTT Config =====
$server   = 'x2.revolusi-it.com';
$port     = 1883;
$clientId = 'G.231.23.0030-'.rand(1000,9999); // client ID NodeMCU/PC
$username = 'usm';
$password = 'usmjaya25';
$topic    = 'iot/G.231.23.0030';

try {
    $mqtt = new MqttClient($server, $port, $clientId);

    $connectionSettings = (new ConnectionSettings)
        ->setUsername($username)
        ->setPassword($password)
        ->setKeepAliveInterval(60);

    $mqtt->connect($connectionSettings, true);

    echo "MQTT connected, listening to topic: $topic\n";

    $mqtt->subscribe($topic, function ($topic, $message) use ($mysqli) {
        $data = json_decode($message, true);
        if ($data) {
            $suhu = floatval($data['temperature']);
            $kelembapan = floatval($data['humidity']);

            // Insert ke database
            $stmt = $mysqli->prepare("INSERT INTO sensor_data (suhu, kelembapan) VALUES (?, ?)");
            $stmt->bind_param("dd", $suhu, $kelembapan);
            $stmt->execute();
            $stmt->close();

            echo "Data inserted: Suhu=$suhu, Kelembapan=$kelembapan\n";
        }
    }, 0);

    $mqtt->loop(true); // loop forever
} catch (MqttClientException $e) {
    echo "MQTT error: ".$e->getMessage();
}
