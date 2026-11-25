
import mysql.connector

PLATE_NUMBER = "AMY1234"

try:
    conn = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="smart_gate"
    )

    cursor = conn.cursor()

    cursor.execute(
        "SELECT plate_no FROM registered_plates WHERE plate_no = %s",
        (PLATE_NUMBER,)
    )

    result = cursor.fetchone()

    if result:
        print(f"Plate Number: {PLATE_NUMBER}")
        print("Status: REGISTERED")
    else:
        print(f"Plate Number: {PLATE_NUMBER}")
        print("Status: NOT REGISTERED")

    cursor.close()
    conn.close()

except mysql.connector.Error as error:
    print("Database connection error:", error)
