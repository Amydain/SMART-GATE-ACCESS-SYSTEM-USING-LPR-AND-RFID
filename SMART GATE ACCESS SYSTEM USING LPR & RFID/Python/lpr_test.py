
import cv2
import easyocr
import requests
import time
import re

STREAM_URL = "http://smartgate-lpr.local/stream"
PHP_URL = "http://localhost/smart_gate/verify_lpr.php"
ESP32_URL = "http://smartgate-main.local/lpr-open"

# Gantikan dengan kunci rahsia baharu yang sama dalam firmware ESP32
LPR_KEY = "SG_LPR_GATE_7xP9mK2vR8nL4wT6aB3cD5"

OCR_INTERVAL = 1.5
MIN_CONFIDENCE = 0.50
PLATE_RESET_SECONDS = 3.0

print("Memulakan Smart Gate LPR...", flush=True)
print("Memuatkan EasyOCR. Tunggu sebentar...", flush=True)

reader = easyocr.Reader(["en"], gpu=False)

print("EasyOCR sedia!", flush=True)
print("Membuka kamera...", flush=True)

cap = cv2.VideoCapture(STREAM_URL, cv2.CAP_FFMPEG)

if not cap.isOpened():
    print("Gagal membuka stream kamera.", flush=True)
    raise SystemExit

print("Kamera berjaya dibuka!", flush=True)
print("LPR automatik aktif. Tekan Q untuk keluar.", flush=True)

last_ocr_time = 0
last_seen_plate = ""
last_seen_time = 0
processed_plate = ""
vehicle_access_done = False

display_message = "Waiting for license plate..."


def read_plate(frame):
    results = reader.readtext(
        frame,
        allowlist="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
        detail=1,
        paragraph=False
    )

    parts = []

    for box, text, confidence in results:
        cleaned = re.sub(r"[^A-Z0-9]", "", text.upper())

        if confidence >= MIN_CONFIDENCE and cleaned:
            parts.append((box, cleaned, confidence))

    if not parts:
        return None, None

    # Susun baris dari atas ke bawah
    parts.sort(key=lambda item: min(point[1] for point in item[0]))

    plate_number = "".join(item[1] for item in parts)
    average_confidence = sum(
        item[2] for item in parts
    ) / len(parts)

    if not 4 <= len(plate_number) <= 10:
        return None, None

    return plate_number, average_confidence


try:
    while True:
        ret, frame = cap.read()

        if not ret:
            print("Frame gagal diterima.", flush=True)
            time.sleep(0.5)
            continue

        now = time.time()

        if now - last_ocr_time >= OCR_INTERVAL:
            last_ocr_time = now

            plate_number, confidence = read_plate(frame)

            if plate_number:
                print(
                    f"Plat dikesan: {plate_number} "
                    f"(confidence: {confidence:.2f})",
                    flush=True
                )

                last_seen_plate = plate_number
                last_seen_time = now

                # Proses sekali bagi setiap plat yang berterusan kelihatan
                if not vehicle_access_done and plate_number != processed_plate:
                    try:
                        response = requests.post(
                            PHP_URL,
                            json={
                                "plate_no": plate_number,
                                "confidence": confidence
                            },
                            timeout=5
                        )

                        if not response.ok:
                            display_message = (
                                f"Ralat PHP: HTTP {response.status_code}"
                            )
                            print(
                                "Ralat PHP:",
                                response.status_code,
                                response.text,
                                flush=True
                            )
                        else:
                            try:
                                data = response.json()
                            except ValueError:
                                data = {}

                            decision = data.get("decision", "UNKNOWN")
                            reason = data.get("reason", "")

                            print(
                                f"Keputusan PHP: {decision} | "
                                f"Plat: {plate_number} | "
                                f"Sebab: {reason}",
                                flush=True
                            )

                            if (
                                decision == "GRANTED"
                                and data.get("ok") is True
                            ):
                                display_message = (
                                    f"GRANTED: {plate_number}"
                                )

                                # Hantar arahan kepada ESP32
                                try:
                                    gate_response = requests.post(
                                        ESP32_URL,
                                        headers={
                                            "X-LPR-Key": LPR_KEY
                                        },
                                        timeout=5
                                    )

                                    print(
                                        "Respons ESP32:",
                                        gate_response.status_code,
                                        gate_response.text,
                                        flush=True
                                    )

                                    gate_data = {}

                                    if gate_response.ok:
                                        try:
                                            gate_data = gate_response.json()
                                        except ValueError:
                                            pass

                                    if gate_data.get("ok") is True:
                                        display_message = f"GRANTED: {plate_number}"
                                        vehicle_access_done = True

                                        print(
                                            "Vehicle access completed.",
                                            "Waiting for the vehicle to leave.",
                                            flush=True
                                        )
                                    else:
                                        display_message = "ESP32 rejected the command"
                                        print(
                                            "ESP32 rejected the command:",
                                            gate_response.text,
                                            flush=True
                                        )

                                except requests.RequestException as error:
                                    display_message = (
                                        "Sambungan ESP32 gagal"
                                    )
                                    print(
                                        f"Gagal menghubungi ESP32: {error}",
                                        flush=True
                                    )

                            elif decision == "DENIED":
                                display_message = (
                                    f"DENIED: {plate_number}"
                                )
                            else:
                                display_message = (
                                    f"Keputusan tidak sah: {plate_number}"
                                )

                        # Elak mengulang permintaan bagi plat sama
                        # sehingga plat hilang daripada pandangan
                        processed_plate = plate_number

                    except requests.RequestException as error:
                        display_message = "Sambungan PHP gagal"
                        print(
                            f"Gagal menghubungi PHP: {error}",
                            flush=True
                        )

            elif now - last_seen_time >= PLATE_RESET_SECONDS:
                last_seen_plate = ""
                processed_plate = ""

                if vehicle_access_done:
                    print(
                        "Kenderaan tidak lagi dikesan. "
                        "Sistem sedia untuk kenderaan seterusnya.",
                        flush=True
                    )

                vehicle_access_done = False
                display_message = "Menunggu nombor plat..."
                # Benarkan plat diproses semula selepas ia hilang
                last_seen_plate = ""
                processed_plate = ""

        color = (
            (0, 255, 0)
            if display_message.startswith("GRANTED")
            else (0, 0, 255)
        )

        cv2.putText(
            frame,
            display_message,
            (10, 30),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.7,
            color,
            2
        )

        cv2.imshow("Smart Gate - Automatic LPR", frame)

        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

finally:
    cap.release()
    cv2.destroyAllWindows()
    print("Sistem LPR ditutup.", flush=True)
