
import cv2
import easyocr
import mysql.connector
from lpr_database import save_lpr_log

STREAM_URL = "http://10.153.88.43/stream"


def check_plate(plate_number):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="smart_gate"
    )

    cursor = conn.cursor()
    cursor.execute(
        "SELECT plate_no FROM registered_plates WHERE plate_no = %s",
        (plate_number,)
    )

    result = cursor.fetchone()
    cursor.close()
    conn.close()

    return result is not None


def save_lpr_log(plate_number, status, confidence):
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="smart_gate"
    )

    cursor = conn.cursor()
    cursor.execute(
        """
        INSERT INTO lpr_logs (plate_no, status, confidence)
        VALUES (%s, %s, %s)
        """,
        (plate_number, status, confidence)
    )

    conn.commit()
    cursor.close()
    conn.close()






print("Memulakan LPR + Database...", flush=True)
reader = easyocr.Reader(["en"], gpu=False)

cap = cv2.VideoCapture(STREAM_URL, cv2.CAP_FFMPEG)

if not cap.isOpened():
    print("Gagal membuka stream kamera.")
    raise SystemExit

print("Kamera berjaya dibuka!", flush=True)
print("Tekan S untuk imbas, Q untuk keluar.", flush=True)

while True:
    ret, frame = cap.read()

    if not ret:
        print("Frame gagal diterima.")
        break

    cv2.imshow("Smart Gate - LPR Database", frame)
    key = cv2.waitKey(1) & 0xFF


    if key == ord("s"):
        print("Mengimbas teks...", flush=True)

        results = reader.readtext(
            frame,
            allowlist="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            detail=1,
            paragraph=False
        )

        if results:
            plate_parts = []
            confidences = []

            for box, text, confidence in results:
                if confidence >= 0.50:
                    cleaned = ''.join(
                        ch for ch in text.upper()
                        if ch.isalnum()
                    )

                    if cleaned:
                        plate_parts.append(cleaned)
                        confidences.append(confidence)

            plate_number = ''.join(plate_parts)

            if plate_number and confidences:
                avg_confidence = sum(confidences) / len(confidences)

                print(
                    f"Plate Number: {plate_number}",
                    flush=True
                )

                print("Menyimpan rekod LPR...", flush=True)

                try:
                    save_lpr_log(
                        plate_number,
                        "DETECTED",
                        avg_confidence
                    )
                    print("Rekod LPR dihantar ke fungsi database.",
                          flush=True)

                except Exception as e:
                    print(f"Gagal menyimpan rekod LPR: {e}",
                          flush=True)

            else:
                print("Tiada teks yang cukup yakin.", flush=True)

        else:
            print("Tiada teks dikesan.", flush=True)


    elif key == ord("q"):
        break

cap.release()
cv2.destroyAllWindows()
print("Sistem LPR ditutup.", flush=True)
