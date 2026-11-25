
import requests

URL = "http://localhost/smart_gate/check_rfid.php"
API_KEY = "SG_RFID_CHECK_2026_K8mP4xR7"

uid = input("Masukkan UID RFID: ").strip().replace(" ", "").upper()

try:
    response = requests.post(
        URL,
        json={"rfid_uid": uid},
        headers={"X-RFID-Key": API_KEY},
        timeout=5
    )

    print("HTTP:", response.status_code)
    print("Response:", response.text)

except requests.RequestException as error:
    print("Connection error:", error)
