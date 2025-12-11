#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <DHT.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ============================= DHT =============================
#define DHTPIN D4
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

// ============================= LCD =============================
LiquidCrystal_I2C lcd(0x27, 16, 2);   // ganti ke 0x3F jika tidak tampil

// ============================= LED =============================
#define LED1 D6
#define LED2 D7
#define LED3 D8

// ============================= WiFi ============================
const char* ssid = "Zigy Tandang";
const char* password = "HIGHSPEED";

// ============================= MQTT ============================
const char* clientID   = "G.231.23.0030";      // Sesuai ketentuan
const char* mqtt_server= "x2.revolusi-it.com";
const char* mqtt_user  = "usm";
const char* mqtt_pass  = "usmjaya25";

// TOPIC MQTT
String topikPub = "iot/G.231.23.0030";          // Publish data sensor
String topikSub = "iot/G.231.23.0030/cmd";       // Subscribe perintah LED

WiFiClient esp;
PubSubClient client(esp);

unsigned long lastLCD = 0;
unsigned long lastPub = 0;

// ======================= MQTT CALLBACK ========================
void callback(char* topic, byte* payload, unsigned int len){
  String cmd = "";
  for(int i=0; i<len; i++) cmd += (char)payload[i];
  Serial.println("CMD: " + cmd);

  if(cmd=="LED1_ON")  digitalWrite(LED1,HIGH);
  if(cmd=="LED1_OFF") digitalWrite(LED1,LOW);
  if(cmd=="LED2_ON")  digitalWrite(LED2,HIGH);
  if(cmd=="LED2_OFF") digitalWrite(LED2,LOW);
  if(cmd=="LED3_ON")  digitalWrite(LED3,HIGH);
  if(cmd=="LED3_OFF") digitalWrite(LED3,LOW);

  lcd.clear();
  lcd.setCursor(0,0); lcd.print("CMD: " + cmd);
  delay(800);
}

// ======================= MQTT RECONNECT =======================
void reconnect(){
  while(!client.connected()){
    Serial.println("Reconnecting MQTT...");
    if(client.connect(clientID, mqtt_user, mqtt_pass)){
      Serial.println("MQTT Connected");
      client.subscribe(topikSub.c_str());
    }else{
      Serial.print("Retry in 2s Code=");
      Serial.println(client.state());
      delay(2000);
    }
  }
}

// ============================= SETUP ===========================
void setup(){
  Serial.begin(115200);
  Serial.println("\nBooting ...");

  // FIX LCD I2C (WAJIB AGAR TIDAK BLANK)
  Wire.begin(D2, D1);     // <- baris ini penyelamat LCD
  lcd.init();
  lcd.backlight();

  dht.begin();

  pinMode(LED1,OUTPUT);
  pinMode(LED2,OUTPUT);
  pinMode(LED3,OUTPUT);

  // ================= WIFI CONNECT ==================
  lcd.setCursor(0,0); lcd.print("Connecting WiFi");
  WiFi.begin(ssid,password);
  while(WiFi.status()!=WL_CONNECTED){
    delay(200);
    Serial.print(".");
  }
  Serial.println("\nWiFi OK");

  lcd.clear();
  lcd.setCursor(0,0); lcd.print("WiFi Connected");
  lcd.setCursor(0,1); lcd.print(WiFi.localIP());
  delay(1000);
  lcd.clear();

  client.setServer(mqtt_server,1883);
  client.setCallback(callback);
}

// ============================= LOOP ============================
void loop(){
  if(!client.connected()) reconnect();
  client.loop();

  float h = dht.readHumidity();
  float t = dht.readTemperature();
  if(isnan(t)||isnan(h)) return;

  unsigned long now = millis();

  // ================= LCD UPDATE Smooth =================
  if(now - lastLCD >= 300){
    lcd.setCursor(0,0); lcd.print("Suhu: "); lcd.print(t); lcd.print((char)223); lcd.print("C  ");
    lcd.setCursor(0,1); lcd.print("Humid: "); lcd.print(h); lcd.print("%   ");
    lastLCD = now;
  }

  // ================= Publish MQTT tiap 1.2 detik =================
  if(now - lastPub >= 1200){
    String json = "{\"temperature\":"+String(t)+",\"humidity\":"+String(h)+"}";
    client.publish(topikPub.c_str(), json.c_str());
    Serial.println("Send => " + json);
    lastPub = now;
  }
}
  