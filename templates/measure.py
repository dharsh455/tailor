import cv2
import mediapipe as mp
import numpy as np
import os
import mysql.connector
from twilio.rest import Client
from datetime import datetime

# Initialize MediaPipe Pose and Drawing Utilities
mp_pose = mp.solutions.pose
mp_drawing = mp.solutions.drawing_utils
pose = mp_pose.Pose()

# Twilio Credentials
ACCOUNT_SID = 'ACc9ac24f6b52d9e066af70034539d63fc'
AUTH_TOKEN = 'a4242f005d0ebbcc7c25570c608f3e51'
TWILIO_NUMBER = 'whatsapp:+14155238886'
RECIPIENT_NUMBER = 'whatsapp:+918248650042'


def get_distance(point1, point2):
    """Calculate Euclidean distance between two points"""
    return np.sqrt((point2.x - point1.x) ** 2 + (point2.y - point1.y) ** 2)


def get_measurements(landmarks):
    """Calculate body measurements from pose landmarks"""
    measurements = {}

    if landmarks:
        shoulder_left = landmarks[mp_pose.PoseLandmark.LEFT_SHOULDER]
        shoulder_right = landmarks[mp_pose.PoseLandmark.RIGHT_SHOULDER]
        measurements['Shoulder Width'] = get_distance(shoulder_left, shoulder_right)

        chest_left = landmarks[mp_pose.PoseLandmark.LEFT_ELBOW]
        chest_right = landmarks[mp_pose.PoseLandmark.RIGHT_ELBOW]
        measurements['Chest Width'] = get_distance(chest_left, chest_right)

        waist_left = landmarks[mp_pose.PoseLandmark.LEFT_HIP]
        waist_right = landmarks[mp_pose.PoseLandmark.RIGHT_HIP]
        measurements['Waist Width'] = get_distance(waist_left, waist_right)

        sleeve_left = landmarks[mp_pose.PoseLandmark.LEFT_ELBOW]
        sleeve_right = landmarks[mp_pose.PoseLandmark.RIGHT_ELBOW]
        measurements['Left Sleeve Length'] = get_distance(shoulder_left, sleeve_left)
        measurements['Right Sleeve Length'] = get_distance(shoulder_right, sleeve_right)

    return measurements


def save_measurements_to_file(measurements):
    """Save measurements to a text file"""
    os.makedirs('measurements', exist_ok=True)
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f'measurements/measurements_{timestamp}.csv'

    with open(filename, 'w') as file:
        for name, value in measurements.items():
            file.write(f"{name}: {value:.2f}\n")

    print(f"Measurements saved to '{filename}'.")


def capture_measurements():
    """Capture body measurements from webcam using MediaPipe"""
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("Error: Camera not accessible.")
        return {}

    measurements = {}

    while cap.isOpened():
        success, image = cap.read()
        if not success:
            print("Error: Failed to capture image.")
            break

        image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
        results = pose.process(image_rgb)

        annotated_image = image.copy()
        if results.pose_landmarks:
            mp_drawing.draw_landmarks(annotated_image, results.pose_landmarks, mp_pose.POSE_CONNECTIONS)
            landmarks = results.pose_landmarks.landmark
            measurements = get_measurements(landmarks)

            for i, (measurement, value) in enumerate(measurements.items()):
                cv2.putText(annotated_image, f"{measurement}: {value:.2f}", (10, 30 + i * 30),
                            cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2, cv2.LINE_AA)

        cv2.imshow('Virtual Tailor', annotated_image)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()

    return measurements


def send_whatsapp_message(measurements):
    """Send measurements via WhatsApp using Twilio"""
    client = Client(ACCOUNT_SID, AUTH_TOKEN)
    message_body = "Measurements:\n" + "\n".join(f"{name}: {value:.2f}" for name, value in measurements.items())

    try:
        message = client.messages.create(
            body=message_body,
            from_=TWILIO_NUMBER,
            to=RECIPIENT_NUMBER
        )
        print(f"Message sent! SID: {message.sid}")
    except Exception as e:
        print(f"Error: {e}")


def save_to_database(measurements):
    """Save measurements to MySQL database"""
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",  # Default user for XAMPP
            password="",  # Blank password for default
            database="v2"
        )
        cursor = connection.cursor()

        sql_query = """
        INSERT INTO measurements (shoulder_width, chest_width, waist_width, left_sleeve_length, right_sleeve_length)
        VALUES (%s, %s, %s, %s, %s)
        """
        data = (
            float(measurements.get("Shoulder Width", 0)),
            float(measurements.get("Chest Width", 0)),
            float(measurements.get("Waist Width", 0)),
            float(measurements.get("Left Sleeve Length", 0)),
            float(measurements.get("Right Sleeve Length", 0))
        )

        cursor.execute(sql_query, data)
        connection.commit()
        print("Measurements saved to the database.")
    except mysql.connector.Error as error:
        print(f"Error: Could not save to database. {error}")
    finally:
        if connection and connection.is_connected():
            cursor.close()
            connection.close()


if __name__ == "__main__":
    # Capture measurements
    measurements = capture_measurements()

    if measurements:
        # Save measurements to file and database
        save_measurements_to_file(measurements)
        save_to_database(measurements)

        # Print measurements to console
        measurement_output = "\n".join(f"{name}: {value:.2f}" for name, value in measurements.items())
        print(measurement_output)

        # Send WhatsApp message
        send_whatsapp_message(measurements)
    else:
        print("No measurements captured.")
