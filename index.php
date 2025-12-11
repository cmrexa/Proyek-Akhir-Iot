<!DOCTYPE html>
<html>
<head>
    <title>Monitoring Suhu & Kelembapan</title>
    <style>
        body { font-family: Arial; background: #0f1624; color: white; text-align: center; }
        .container { display: flex; justify-content: center; gap: 20px; margin-top: 20px; flex-wrap: wrap; }
        .card { width: 200px; padding: 20px; background: #1e293b; border-radius: 10px; border: 1px solid #00eaff; box-shadow: 0 0 10px cyan; }
        .card h3 { font-size: 32px; margin-bottom: 5px; color: #00eaff; }
        .card p { margin: 0; font-size: 18px; }
        #chartContainer { width: 90%; margin: 40px auto; }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            width: 80%;
        }
        .on { background: #00e676; color:white; }
        .off { background: #ff1744; color:white; }

        /* ------- POPUP NOTIFIKASI ------- */
        #popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff5e57;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ff2424;
            color: white;
            font-size: 16px;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

<h2>🌡 Dashboard Monitoring Sensor DHT11 + Kontrol LED</h2>

<!-- POPUP NOTIFIKASI -->
<div id="popup"></div>

<!-- SENSOR -->
<div class="container">
    <div class="card">
        <h3 id="suhu">-- °C</h3>
        <p>Suhu</p>
    </div>

    <div class="card">
        <h3 id="kelembapan">-- %</h3>
        <p>Kelembapan</p>
    </div>
</div>

<!-- LED CONTROL -->
<h2>💡 Kontrol LED</h2>
<div class="container">
    <div class="card">
        <h3>LED 1 (D6)</h3>
        <button id="led6btn" class="btn off" onclick="toggleLED(6)">OFF</button>
    </div>

    <div class="card">
        <h3>LED 2 (D7)</h3>
        <button id="led7btn" class="btn off" onclick="toggleLED(7)">OFF</button>
    </div>

    <div class="card">
        <h3>LED 3 (D8)</h3>
        <button id="led8btn" class="btn off" onclick="toggleLED(8)">OFF</button>
    </div>
</div>

<!-- CHART -->
<div id="chartContainer">
    <canvas id="sensorChart"></canvas>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ------------------- CHART SETUP -------------------
let labels = [], suhuData = [], kelembapanData = [];

const ctx = document.getElementById("sensorChart").getContext("2d");
const sensorChart = new Chart(ctx, {
    type: "line",
    data: {
        labels: labels,
        datasets: [
            { label: "Suhu (°C)", data: suhuData, borderColor: "#00eaff",
              backgroundColor: "rgba(0,234,255,0.2)", fill: true, tension: 0.3 },

            { label: "Kelembapan (%)", data: kelembapanData, borderColor: "#ffb400",
              backgroundColor: "rgba(255,180,0,0.2)", fill: true, tension: 0.3 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: "white" } } },
        scales: {
            x: { ticks: { color: "white" } },
            y: { ticks: { color: "white" } }
        }
    }
});

// -------------------- SOUND BEEP --------------------
function beep(freq = 700, duration = 200) {
    const audio = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audio.createOscillator();
    osc.frequency.value = freq;
    osc.connect(audio.destination);
    osc.start();
    setTimeout(() => osc.stop(), duration);
}

// ------------------ POPUP NOTIFICATION ------------------
function showPopup(msg) {
    const popup = document.getElementById("popup");
    popup.innerText = msg;
    popup.style.display = "block";

    setTimeout(() => {
        popup.style.display = "none";
    }, 2500);
}

// ------------------ ALARM SUHU & KELEMBAPAN ------------------
function alarmCheck(data) {
    let beepCount = 0;
    let message = "";

    // --- aturan suhu ---
    if (data.suhu > 29 && data.suhu < 30) { beepCount = 1; message = "Suhu Meningkat (Beep 1x)"; }
    else if (data.suhu >= 30 && data.suhu <= 31) { beepCount = 2; message = "Suhu Tinggi (Beep 2x)"; }
    else if (data.suhu > 31) { beepCount = 3; message = "Suhu Sangat Tinggi! (Beep 3x)"; }

    // --- aturan kelembapan ---
    if (data.kelembapan >= 60 && data.kelembapan < 70) { 
        beepCount = Math.max(beepCount, 1);
        message = "Kelembapan Mulai Tinggi (Beep 1x)";
    }
    else if (data.kelembapan >= 70) {
        beepCount = Math.max(beepCount, 3);
        message = "Kelembapan Sangat Tinggi! (Beep 3x)";
    }

    // --- tampilkan notifikasi ---
    if (message !== "") showPopup(message);

    // --- beep ---
    for (let i = 0; i < beepCount; i++) {
        setTimeout(() => beep(), i * 400);
    }
}

// ------------------- LOAD DATA SENSOR -------------------
function loadData() {
    $.get("data.php", function(res) {

        let data;
        try {
            data = typeof res === "string" ? JSON.parse(res) : res;
        } catch (e) {
            console.log("Data PHP bukan JSON:", res);
            return;
        }

        $("#suhu").text(data.suhu + " °C");
        $("#kelembapan").text(data.kelembapan + " %");

        alarmCheck(data);

        const now = new Date().toLocaleTimeString();
        labels.push(now);
        suhuData.push(data.suhu);
        kelembapanData.push(data.kelembapan);

        if (labels.length > 20) {
            labels.shift(); suhuData.shift(); kelembapanData.shift();
        }

        sensorChart.update();
    });
}
setInterval(loadData, 2000);
loadData();

// ------------------- LED CONTROL -------------------
function toggleLED(pin) {
    $.post("led.php", { pin: pin }, function(res){
        const r = JSON.parse(res);
        updateButton(r.pin, r.state);
    });
}

function updateButton(pin, state) {
    let btn = null;

    if (pin == 6) btn = $("#led6btn");
    if (pin == 7) btn = $("#led7btn");
    if (pin == 8) btn = $("#led8btn");

    if (state === "on") {
        btn.removeClass("off").addClass("on").text("ON");
    } else {
        btn.removeClass("on").addClass("off").text("OFF");
    }
}

function loadLEDStatus() {
    $.get("ledstatus.php", function(res){
        const s = JSON.parse(res);
        updateButton(6, s.led6);
        updateButton(7, s.led7);
        updateButton(8, s.led8);
    });
}
setInterval(loadLEDStatus, 1000);
loadLEDStatus();

</script>
</body>
</html>
