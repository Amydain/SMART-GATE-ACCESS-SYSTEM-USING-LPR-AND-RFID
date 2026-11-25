#include "esp_camera.h"
#include <WiFi.h>
#include <WebServer.h>
#include "img_converters.h"
#include <ESPmDNS.h>

// =====================================================

// WIFI
// =====================================================

const char* ssid = "SmartGate";
const char* password = "SmartGate123";

// =====================================================
// ESP32-CAM PIN
// =====================================================

#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27

#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

// =====================================================
// WEB SERVER
// =====================================================

WebServer server(80);

// =====================================================
// WEB PAGE
// =====================================================

void handleRoot() {

  String html = R"rawliteral(
<!DOCTYPE html>

<html>

<head>

<title>Smart Gate - LPR Camera</title>

<meta name="viewport"
content="width=device-width, initial-scale=1">

<style>

body {
  font-family: Arial;
  text-align: center;
  background: #f2f2f2;
  margin: 0;
  padding: 20px;
}

h1 {
  margin-bottom: 20px;
}

.camera {
  width: 100%;
  max-width: 640px;
  border: 3px solid black;
  border-radius: 10px;
}

.status {
  margin-top: 15px;
  font-size: 18px;
}

</style>


</head>

<body>

<h1>Smart Gate - License Plate Camera</h1>

<img
  class="camera"
  src="/stream"
>

<div class="status">
  Camera: <b>LIVE</b>
</div>

</body>

</html>
)rawliteral";

  server.send(
    200,
    "text/html",
    html
  );
}

// =====================================================
// LIVE STREAM
// =====================================================


void handleStream() {
  WiFiClient client = server.client();

  client.println("HTTP/1.1 200 OK");
  client.println("Content-Type: multipart/x-mixed-replace; boundary=frame");
  client.println("Cache-Control: no-cache");
  client.println("Pragma: no-cache");
  client.println("Connection: close");
  client.println();

  Serial.println("Live stream started.");

  while (client.connected()) {
    camera_fb_t *fb = esp_camera_fb_get();

    if (!fb) {
      Serial.println("Camera capture failed");
      break;
    }

    uint8_t *jpg_buf = nullptr;
    size_t jpg_len = 0;
    bool converted = false;

    if (fb->format == PIXFORMAT_JPEG) {
      jpg_buf = fb->buf;
      jpg_len = fb->len;
      converted = true;
    } else {
      converted = frame2jpg(fb, 80, &jpg_buf, &jpg_len);
    }

    esp_camera_fb_return(fb);

    if (!converted || jpg_buf == nullptr) {
      Serial.println("JPEG conversion failed");
      if (jpg_buf != nullptr) {
        free(jpg_buf);
      }
      break;
    }

    client.printf(
      "--frame\r\n"
      "Content-Type: image/jpeg\r\n"
      "Content-Length: %u\r\n\r\n",
      (unsigned int)jpg_len
    );

    size_t sent = client.write(jpg_buf, jpg_len);
    client.print("\r\n");

    if (jpg_buf != nullptr && jpg_buf != fb->buf) {
      free(jpg_buf);
    }

    if (sent != jpg_len) {
      Serial.println("Stream send failed");
      break;
    }

    delay(50);
  }

  Serial.println("Live stream stopped.");
}


// =====================================================
// SETUP
// =====================================================

void setup() {

  Serial.begin(115200);

  delay(1000);

  Serial.println();
  Serial.println(
    "================================"
  );
  Serial.println(
    "ESP32-CAM LIVE STREAM"
  );
  Serial.println(
    "================================"
  );

  // ===================================================
  // CAMERA CONFIGURATION
  // ===================================================

  camera_config_t config;

  config.ledc_channel =
    LEDC_CHANNEL_0;

  config.ledc_timer =
    LEDC_TIMER_0;

  config.pin_d0 =
    Y2_GPIO_NUM;

  config.pin_d1 =
    Y3_GPIO_NUM;

  config.pin_d2 =
    Y4_GPIO_NUM;

  config.pin_d3 =
    Y5_GPIO_NUM;

  config.pin_d4 =
    Y6_GPIO_NUM;

  config.pin_d5 =
    Y7_GPIO_NUM;

  config.pin_d6 =
    Y8_GPIO_NUM;

  config.pin_d7 =
    Y9_GPIO_NUM;

  config.pin_xclk =
    XCLK_GPIO_NUM;

  config.pin_pclk =
    PCLK_GPIO_NUM;

  config.pin_vsync =
    VSYNC_GPIO_NUM;

  config.pin_href =
    HREF_GPIO_NUM;

  config.pin_sscb_sda =
    SIOD_GPIO_NUM;

  config.pin_sscb_scl =
    SIOC_GPIO_NUM;

  config.pin_pwdn =
    PWDN_GPIO_NUM;

  config.pin_reset =
    RESET_GPIO_NUM;

  config.xclk_freq_hz =
    20000000;

  // IMPORTANT
    config.pixel_format =
    PIXFORMAT_RGB565;

  // ===================================================
  // CAMERA QUALITY
  // ===================================================

  if (psramFound()) {

    config.frame_size =
      FRAMESIZE_QVGA;

    config.jpeg_quality =
      12;

    config.fb_count =
      2;

  } else {

    config.frame_size =
      FRAMESIZE_QQVGA;

    config.jpeg_quality =
      12;

    config.fb_count =
      1;
  }

  // ===================================================
  // INITIALIZE CAMERA
  // ===================================================

  Serial.println(
    "Initializing camera..."
  );

  esp_err_t err =
    esp_camera_init(
      &config
    );

  if (err != ESP_OK) {

    Serial.printf(
      "Camera init failed: 0x%x\n",
      err
    );

    return;
  }

  Serial.println(
    "Camera initialized successfully!"
  );


  // CAMERA ORIENTATION
sensor_t *s = esp_camera_sensor_get();

s->set_vflip(s, 1);
s->set_hmirror(s, 0);

Serial.println("Camera orientation adjusted!");

  // ===================================================
  // WIFI
  // ===================================================


  Serial.println("Connecting to WiFi...");

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);

  WiFi.begin(ssid, password);




  int attempts = 0;

  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi failed! Check hotspot settings.");
    return;
  }

  Serial.println("WiFi connected!");

  Serial.print("Camera MAC: ");
  Serial.println(WiFi.macAddress());

  Serial.print("ESP32-CAM IP address: ");
  Serial.println(WiFi.localIP());


// ===================================
// mDNS - ACCESS CAMERA BY NAME
// ===================================

if (MDNS.begin("smartgate-lpr")) {
  Serial.println("mDNS started successfully!");
  Serial.println("Camera: http://smartgate-lpr.local/");
  Serial.println("Stream: http://smartgate-lpr.local/stream");
} else {
  Serial.println("mDNS failed to start!");
}


  // ===================================================
  // IP ADDRESS
  // ===================================================

  Serial.print(
    "ESP32-CAM IP address: "
  );

  Serial.print("Camera IP: ");
  Serial.println(WiFi.localIP().toString());
  // ===================================================
  // WEB SERVER ROUTES
  // ===================================================

  server.on(
    "/",
    HTTP_GET,
    handleRoot
  );

  server.on(
    "/stream",
    HTTP_GET,
    handleStream
  );

  // ===================================================
  // START SERVER
  // ===================================================

  server.begin();

  Serial.println();
  Serial.println(
    "Web server started!"
  );

  Serial.println(
    "================================"
  );

  Serial.println(
    "SYSTEM READY"
  );

  Serial.println(
    "================================"
  );
}

// =====================================================
// LOOP
// =====================================================

void loop() {

  server.handleClient();

  delay(10);
}