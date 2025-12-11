<?php
$pin = $_POST["pin"];

$file = "led.json";
$data = json_decode(file_get_contents($file), true);

if($data["led$pin"] == "off"){
    $data["led$pin"] = "on";
} else {
    $data["led$pin"] = "off";
}

file_put_contents($file, json_encode($data));

echo json_encode(["pin"=>$pin, "state"=>$data["led$pin"]]);
