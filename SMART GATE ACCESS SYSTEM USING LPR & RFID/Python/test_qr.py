
import cv2
import requests
import time

CAMERA_URL = "http://smartgate-qr.local/stream"
VERIFY_URL = "http://localhost/smart_gate/verify_and_use_qr_v2.php"
ESP32_URL = "http://smartgate-main.local/qr-test"
ESP32_QR_KEY = "SG_QR_GATE_7xP9mK2vR8nL4wT6aB3cD5"
SCANNER_KEY = "SmartGateScanner_2026_Aimi_X9p7K2mQ"

cap = cv2.VideoCapture(CAMERA_URL, cv2.CAP_FFMPEG)
qr_detector = cv2.QRCodeDetector()

last_qr = ""
last_sent_time = 0
COOLDOWN = 5

def test_esp32_connection():
    try:
        response = requests.get(
            ESP32_URL,
            headers={"X-QR-Key": ESP32_QR_KEY},
            timeout=5
        )

        print("ESP32 HTTP:", response.status_code)
        print("ESP32 Response:", response.text)

    except requests.RequestException as error:
        print("ESP32 connection failed:", error)
        

print("Menyambungkan ESP32-CAM...")

if not cap.isOpened():
    print("Gagal membuka kamera. Semak IP dan Wi-Fi.")
    raise SystemExit

print("Kamera berjaya dibuka!")
print("Halakan QR ke kamera. Tekan Q untuk keluar.")

while True:
    ret, frame = cap.read()

    if not ret:
        print("Frame gagal diterima.")
        break


    try:
        data, points, _ = qr_detector.detectAndDecode(frame)
    except cv2.error:
        data, points = "", None


    if points is not None:
        points = points.astype(int).reshape(-1, 2)

        for i in range(len(points)):
            p1 = tuple(points[i])
            p2 = tuple(points[(i + 1) % len(points)])
            cv2.line(frame, p1, p2, (0, 255, 0), 2)

        if data:
            now = time.time()

            if data != last_qr or now - last_sent_time >= COOLDOWN:
                last_qr = data
                last_sent_time = now

                print("\nQR DIKESAN:", data)
                print("Menghantar token kepada PHP...")

                try:
                    response = requests.post(
                        VERIFY_URL,
                        json={"qr": data},
                        headers={"X-Scanner-Key": SCANNER_KEY},
                        timeout=5
                    )

                    print("HTTP:", response.status_code)

                    try:
                        result = response.json()
                        print("Keputusan:", result)
                    except ValueError:
                        print("Respons bukan JSON:", response.text)

                except requests.RequestException as error:
                    print("Gagal menghubungi PHP:", error)

    else:
        # Benarkan QR yang sama dihantar semula selepas ia hilang
        # daripada pandangan kamera.
        last_qr = ""

    cv2.imshow("Smart Gate - QR Scanner Test", frame)

    if cv2.waitKey(1) & 0xFF == ord("q"):
        break

cap.release()
cv2.destroyAllWindows()
test_esp32_connection()
