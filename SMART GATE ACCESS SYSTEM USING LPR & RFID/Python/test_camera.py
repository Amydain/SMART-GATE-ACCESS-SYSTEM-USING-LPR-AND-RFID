
import cv2

URL = "http://10.153.88.43/stream"

print("Membuka ESP32-CAM stream...")

cap = cv2.VideoCapture(URL, cv2.CAP_FFMPEG)

if not cap.isOpened():
    print("OpenCV gagal membuka stream.")
else:
    print("Stream dibuka. Menunggu frame...")

    while True:
        ret, frame = cap.read()

        if not ret:
            print("Frame gagal diterima.")
            break

        cv2.imshow("Smart Gate - ESP32-CAM", frame)

        if cv2.waitKey(1) & 0xFF == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()
