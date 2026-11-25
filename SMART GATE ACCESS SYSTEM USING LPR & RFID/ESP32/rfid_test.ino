
#include <SPI.h>https://googleads.g.doubleclick.net/dbm/clk?sa=L&ai=C7iFxq571aoPmFJ6Zk7QPp6-BoQqbl87BiAGhzrm_vhWSwu7zw0YQASD7m9VJYMuDgYWcF6ABi-KKoAHIAQmoAwHIA5sEqgTzAU_Qt0ynUi13sRJ-a0JHoSxW3STv_Yu_z7bvwegJdezMiMdzulD_-HK5dUYcvs7OZ-eUbMJ08fzWAr3dkfF8Cbx85F5lASpam0nldqpVz1MyprWox9S14OuBCzlpxWEnq14DNJkWLm3tfvQLVanc-B63IxpZH8aIulKynA5NbCHF8dk3KCE6FWf50gz-Nt5IFxmbDU9PmStYzKNtFuEDbAGcOCsuxEqK8QPIbRoJaOSJ9j6P0SS1oNkP9O07LIIDfUZNNYN-LDtc8IH09yfQScAi4WCxg3aH3lIy0movdBLe_uinEG4L4crwZucnPoEiVT-mfsAE7OWhqrIF4AQDiAXXqPOVVJAGAaAGTYAH3Z313wKoB6nrsQKoB6fMsQKoB6a-G6gHzM6xAqgH89EbqAeW2BuoB6qbsQKoB_7osQKoB47OG6gHk9gbqAfw4BuoB-6WsQKoB_6esQKoB6--sQKoB5_hsQKoB6brsQKoB9XJG6gH2baxAqgHmgaoB_-esQKoB9-fsQKoB_jCsQKoB_vCsQKoB-fXsQLYBwDSCDIIgGEQARgdMgiKgoCAgICACDoPgECAwICAgICogAKog4AYSL39wTpYuILk4qWCmANgAYAKA5gLAcgLAYAMAaIMCCoGCgT28rECqg0CTVniDRMI9eLk4qWCmAMVnsyEAB2nVyCk6g0TCK7y5eKlgpgDFZ7MhAAdp1cgpPANAYgO____________AbAT-fG3JtATANgTDYgUAdgUAdAVAcoWAgoA-BYBgBcBshcQGAIqCjY2MDc2ODUwMDhQBqoYFwkAAAAAzIkWQRIKNjYwNzY4NTAwOBgBshgJEgKdYxhNIgEA0BkB&ae=1&num=1&cid=CAQSoAIAogo4df2RlaKsCwr664LTMwqV17BSBQXD8H0QZzMmhrK0ZlsCBo4dVTwETsQ0FK3VBHFvK4hS9kXikDOIbfmF0uub2zfoPyQW6h70f2MxyZf4Mx0xnaQbPN0wBAj2tYH-qHFblobuSfLIvDrpuXqpN4COGZN_dVcLURsitUEI6LXhiNSjIXgswAUtoFWRyMpYsN9v8gVKl0b0xeHr-mCM-w0HLHaIS7QY2erjV0zuWXv4DP_tRCPtfwwY4uPM98DCVP5MH-FR_Ah767l6QokEfaId-4HREO3on-CJvMtNsbaZnxRzpj0hmJayXpvRLmHRkXUDuedCf_p9_Ylkt9X-SAgiyM2dtXCbzOg12JVqhY3oI2fs3OWbtqIO6KKd13cYAQ&sig=AOD64_1LUtmE8XWjK_k6Ru3JkBSwvqh11g&dbm_c=AKAmf-CSfr_siWSDUwiiDyVh4tRQ1-TO0qippGN6g_ngUirx92kJlKj1_C6Y9eUB7ClN9JxHN5MfEXtmrruEzACk3QnZ4wq_Ks9na0EIykeFWjQIVlml8ZJy7NRSDd0wTcNguG_sHnv6k3LFspSz6jJTydqpPtchHMDY99r0RcwHwyCNwpHMCWDQIU9rHU5TvDQcEFMmegcqBZMtj6EiKvfZZ6fNvLK9mg0rQGwmGwzsy-7FgvfZI6liQ2bsspl9odt_5draBbcwvOngfsoZ16BqQW2nGIS1cgBKpnBR1oKfcexfIRsxIFkc4P1erxhbdLiTyCrFpe_W&cry=1&dbm_d=AKAmf-B8MIVP5sbeurJp_4-GwWofKtU47IMfaLV1iHmw_urbAqA52uLPyC3ByCsYLYGW8OPymdDPGwEkNLe6jS58KNltfcSMMejdBSTRtl3g5p8CY3E8ByExX9xeMGoN8Q8D_fCLe5Q8weypBCK8q39Gg7xUBMWgw0SfdyxwlBZAKBBPvEDtMQX87Tav6C_qf1Y9C12xMN3WE_PJ2KbJZcLwmWvCALzUx2iqsAZhf99JfvCaDOui-i9EHVAQYsp2S954MO437kfsuskALtIax9UlFZHpaTEQewrcBhZuURRfubyCuP_xAaQT5A5vdBF4-dkxoVoBQ7Z2qa9dQlVDOIekSCPdJLdTwfQvK59cbAYw-q_TndHrCbBpRYJzWvDOCtP4ybuLZg20ZVWI495LFFHl7W9WIKAhgY7sihsJ8zZWcmYBSVBPwUgwi3FlSC6Qv-V8NUY0KZ-b05-6kHoDr0iZeKkUbYyG4FLKcqPOPvC4B94AVJkLBfKwHJOO9Y6mlV02dX9fTABbWm8CVH9NdRhGhsLsbnBAwGf3ijCxfmDFUbT1w7759zSgLD_IHvRW_Ai9uWGF0orWxqlV00uSpXMHEAL4bAwj81aAxEQVTgH9w6LGW82uzyCkmxEiA4ruLLs0J_anZFGKtzoPGHlKxIFHu_bFpHXo0mO5al1Mf3GAMAxYb16o2Oqrh4BgDVjLhcsO2H9hU7F2-UVbODfxMySomc3iBmZ9mQ&adurl=https://servedby.flashtalking.com/click/7/296653;10338424;6541587;210;0/?us_privacy=!!US_PRIVACY!&gdpr=0&ft_partnerimpid=ABAjH0h9489h169eTDyTQ7fLTSIL&ft_impID=5F17B546-01AD-2529-8FA6-EEE756924F04&ft_section=22594507863&g=6662EA49D836AB&random=16820.764895098037&ft_width=300&ft_height=600&url=https://www.adobe.com/my_en/education/students/creativecloud.html?&sdid=PQ7SPXQB&mv=display&mv2=display
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ESP32Servo.h>
#include <ArduinoJson.h>
#include <ESPmDNS.h>

WebServer server(80);

// Isi dengan nilai asal dalam kod boss
const char* qrCommandKey = "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5";
const char* lprCommandKey = "SG_LPR_GATE_7xP9mK2vR8nL4wT6aB3cD5";

#define SS_PIN 5
#define RST_PIN 22

#define GREEN_LED 2
#define RED_LED 4
#define BUZZER 15
#define IR_SENSOR 27
#define IR_SENSOR_2 26
#define SERVO_PIN 13


Servo gateServo;

// Wi-Fi: isi menggunakan maklumat asal boss
const char* ssid = "SmartGate";
const char* password = "SmartGate123";

// PHP server
const char* rfidVerifyURL =
  "http://192.168.137.1/smart_gate/verify_visitor_rfid.php";

const char* qrGetURL =
  "http://192.168.137.1/smart_gate/get_qr_command.php";


// Use the same secret configured in both PHP endpoints.
const char* esp32CommandKey = 
  "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5";

unsigned long lastQRCheck = 0;
const unsigned long qrCheckInterval = 1000;  

MFRC522 rfid(SS_PIN, RST_PIN);

// QR command sementara
bool qrPending = false;
int pendingVisitId = 0;
int pendingCommandId = 0;
String pendingCommandToken = "";

bool vehicleDetected = false;
bool accessProcessed = false;
bool gateBusy = false;


// =====================================================
// BUKA DAN TUTUP PALANG
// =====================================================

void openGate(const char* source) {
  if (gateBusy) {
    Serial.println("Gate is busy. Request ignored.");
    return;
  }

  gateBusy = true;

  Serial.print("Access granted by: ");
  Serial.println(source);

  // GATE OPEN
  digitalWrite(RED_LED, LOW);
  digitalWrite(GREEN_LED, HIGH);
  digitalWrite(BUZZER, LOW);

  Serial.println("Opening gate...");
  gateServo.write(0);

  delay(3000);

  Serial.println("Closing gate...");
  gateServo.write(90);

  delay(500); // Masa tambahan untuk servo bergerak

  // GATE CLOSED
  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, HIGH);

  gateBusy = false;

  Serial.println("Gate closed.");
}


// =====================================================
// SEMAK KUNCI
// =====================================================

bool validLPRKey() {
  if (!server.hasHeader("X-LPR-Key")) {
    return false;
  }

  return server.header("X-LPR-Key") ==
         String(lprCommandKey);
}


// =====================================================
// ENDPOINT LPR
// POST http://ESP32_IP/lpr-open
// Header: X-LPR-Key: your_secret_key
// =====================================================

void handleLPROpen() {
  if (!validLPRKey()) {
    server.send(403, "application/json",
                "{\"ok\":false,\"reason\":\"unauthorized\"}");
    return;
  }

  if (WiFi.status() != WL_CONNECTED) {
    server.send(503, "application/json",
                "{\"ok\":false,\"reason\":\"wifi_disconnected\"}");
    return;
  }

  // IR LOW bermaksud kenderaan dikesan
  if (digitalRead(IR_SENSOR) != LOW &&
    digitalRead(IR_SENSOR_2) != LOW) {
    server.send(409, "application/json",
                "{\"ok\":false,\"reason\":\"no_vehicle_detected\"}");
    return;
  }

  if (gateBusy) {
    server.send(409, "application/json",
                "{\"ok\":false,\"reason\":\"gate_busy\"}");
    return;
  }

  // Respons dihantar sebelum palang bergerak
  server.send(200, "application/json",
              "{\"ok\":true,\"decision\":\"GRANTED\"}");

  openGate("LPR");
}


// =====================================================
// ENDPOINT QR UJIAN SEDIA ADA
// =====================================================

void setupQRTestEndpoints() {
  server.on("/qr-authorize-test", HTTP_POST, []() {
    if (!server.hasHeader("X-QR-Key")) {
      server.send(401, "text/plain", "Unauthorized");
      return;
    }

    if (server.header("X-QR-Key") != String(qrCommandKey)) {
      server.send(403, "text/plain", "Forbidden");
      return;
    }

    server.send(200, "application/json",
                "{\"ok\":true,\"decision\":\"authorized_test_only\"}");
  });

  server.on("/qr-command-test", HTTP_POST, []() {
    if (!server.hasHeader("X-QR-Key")) {
      server.send(401, "application/json",
                  "{\"ok\":false,\"reason\":\"unauthorized\"}");
      return;
    }

    if (server.header("X-QR-Key") != String(qrCommandKey)) {
      server.send(403, "application/json",
                  "{\"ok\":false,\"reason\":\"forbidden\"}");
      return;
    }

    server.send(200, "application/json",
                "{\"ok\":true,\"decision\":\"qr_command_received_test_only\"}");
  });
}


// =====================================================
// SETUP
// =====================================================

void setup() {
  Serial.begin(115200);

  pinMode(IR_SENSOR, INPUT);
  pinMode(IR_SENSOR_2, INPUT);
  pinMode(GREEN_LED, OUTPUT);
  pinMode(RED_LED, OUTPUT);
  pinMode(BUZZER, OUTPUT);

  digitalWrite(GREEN_LED, LOW);
  digitalWrite(RED_LED, HIGH);
  digitalWrite(BUZZER, LOW);

  // Sambung Wi-Fi
  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);
  WiFi.begin(ssid, password);

  Serial.print("Connecting to Wi-Fi...");

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("Wi-Fi connected!");
    Serial.print("ESP32 IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("Wi-Fi connection failed!");
  }


  if (MDNS.begin("smartgate-main")) {
  Serial.println("mDNS started successfully!");
  Serial.println("Main Controller: http://smartgate-main.local/");
} else {
  Serial.println("mDNS failed to start!");
}

  // RFID
  SPI.begin();
  rfid.PCD_Init();

  // Servo
  gateServo.setPeriodHertz(50);
  gateServo.attach(SERVO_PIN);
  gateServo.write(90);

  // Daftar endpoint
  setupQRTestEndpoints();

  server.on("/lpr-open", HTTP_POST, handleLPROpen);

  const char* headerKeys[] = {
    "X-QR-Key",
    "X-LPR-Key"
  };

  server.collectHeaders(headerKeys, 2);
  server.begin();

  Serial.println("Smart Gate HTTP server started.");
  Serial.println("LPR endpoint: /lpr-open");
  Serial.println("SMART GATE SYSTEM READY");
}


// =====================================================
// HANTAR REKOD KE PHP
// =====================================================


void sendRFIDToServer(String uid) {

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected. RFID request cancelled.");
    return;
  }



  HTTPClient http;

  http.begin(rfidVerifyURL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-ESP32-Key", esp32CommandKey);

  JsonDocument payload;

  payload["uid"] = uid;
  payload["ir_detected"] =
    (digitalRead(IR_SENSOR) == LOW ||
     digitalRead(IR_SENSOR_2) == LOW);

  // Sertakan QR jika ada command yang menunggu
  if (qrPending) {
    payload["visit_id"] = pendingVisitId;
    payload["command_id"] = pendingCommandId;
    payload["command_token"] = pendingCommandToken;
  }

  String jsonPayload;
  serializeJson(payload, jsonPayload);

  http.setConnectTimeout(3000);
  http.setTimeout(5000);

  Serial.println("Sending RFID to PHP...");
  Serial.println(jsonPayload);

  int responseCode = http.POST(jsonPayload);

  if (responseCode > 0) {

    String response = http.getString();

    Serial.print("PHP response: ");
    Serial.println(response);

    JsonDocument doc;
    DeserializationError error = deserializeJson(doc, response);

    if (!error && doc["ok"] == true) {

      String source = doc["source"] | "";
      String action = doc["action"] | "";

      if (source == "SECURITY" ||
          action == "TIME_IN" ||
          action == "TIME_OUT") {

        // Semak sekali lagi sebelum membuka gate
        qrPending = false;
        pendingVisitId = 0;
        pendingCommandId = 0;
        pendingCommandToken = "";

        openGate(source == "SECURITY" ? "SECURITY RFID" :
          action.c_str());

      } else {
        Serial.println("Unexpected PHP response.");
      }

    } else {
      Serial.println("ACCESS DENIED or invalid PHP response.");

      digitalWrite(BUZZER, HIGH);
      delay(1000);
      digitalWrite(BUZZER, LOW);
    }

  } else {
    Serial.print("HTTP Error: ");
    Serial.println(http.errorToString(responseCode));
  }

  http.end();
}



void pollQRCommand() {

  if (WiFi.status() != WL_CONNECTED ||
      gateBusy || qrPending) {
    return;
  }


  if (millis() - lastQRCheck < qrCheckInterval) {
    return;
  }

  lastQRCheck = millis();

  HTTPClient http;

  http.setConnectTimeout(2000);
  http.setTimeout(3000);

  http.begin(qrGetURL);
  http.addHeader("X-ESP32-Key", esp32CommandKey);

  int responseCode = http.GET();

  if (responseCode != 200) {
    Serial.print("QR polling failed: ");
    Serial.println(responseCode);
    http.end();
    return;
  }

  String response = http.getString();
  http.end();

  JsonDocument doc;

  DeserializationError error = deserializeJson(doc, response);

  if (error) {
    Serial.println("Invalid QR response.");
    return;
  }

  if (!doc["ok"].as<bool>() ||
      doc["command"].isNull()) {
    return;
  }

  pendingCommandId = doc["command"]["id"] | 0;
  pendingVisitId = doc["command"]["visit_id"] | 0;
  pendingCommandToken = doc["command"]["token"] | "";

  if (pendingCommandId <= 0 ||
      pendingVisitId <= 0 ||
      pendingCommandToken.length() != 64) {

    Serial.println("Invalid QR command data.");

    pendingCommandId = 0;
    pendingVisitId = 0;
    pendingCommandToken = "";

    return;
  }

  qrPending = true;

  Serial.println("QR command received!");
  Serial.println("Waiting for visitor RFID card...");
}



// =====================================================
// LOOP
// =====================================================


void loop() {

  // Handle HTTP requests (LPR endpoint)
  server.handleClient();

  int irState1 = digitalRead(IR_SENSOR);
  int irState2 = digitalRead(IR_SENSOR_2);

  bool vehiclePresent =
      (irState1 == LOW || irState2 == LOW);

  // Vehicle detection
  if (vehiclePresent) {

    if (!vehicleDetected) {
      vehicleDetected = true;

      Serial.println();
      Serial.println("Vehicle detected!");
    }

    // QR command hanya diambil apabila ada kenderaan
    pollQRCommand();

  } else {

    if (vehicleDetected) {
      Serial.println("Vehicle left.");
      Serial.println("Ready for next vehicle.");
    }

    vehicleDetected = false;
    accessProcessed = false;
  }

  // RFID boleh dibaca walaupun IR HIGH
  if (!rfid.PICC_IsNewCardPresent()) {
    delay(20);
    return;
  }

  if (!rfid.PICC_ReadCardSerial()) {
    delay(20);
    return;
  }

  // Read RFID UID
  String uidString = "";

  Serial.print("Card UID: ");

  for (byte i = 0; i < rfid.uid.size; i++) {

    if (rfid.uid.uidByte[i] < 0x10) {
      uidString += "0";
      Serial.print("0");
    }

    uidString += String(rfid.uid.uidByte[i], HEX);
    Serial.print(rfid.uid.uidByte[i], HEX);
    Serial.print(" ");
  }

  uidString.toUpperCase();
  Serial.println();

  Serial.println("Sending RFID for database verification...");

  sendRFIDToServer(uidString);

  accessProcessed = true;

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  Serial.println("RFID process completed.");

  delay(100);
}

