from flask import Flask, render_template, request, jsonify
from flask_cors import CORS
from twilio.rest import Client
import subprocess
import mysql.connector
import os

app = Flask(__name__)  # Flask defaults to 'templates' for HTML files
CORS(app)

# Twilio credentials - Use environment variables for better security
TWILIO_ACCOUNT_SID = os.getenv('TWILIO_ACCOUNT_SID', 'ACc9ac24f6b52d9e066af70034539d63fc')
TWILIO_AUTH_TOKEN = os.getenv('TWILIO_AUTH_TOKEN', 'a4242f005d0ebbcc7c25570c608f3e51')
TWILIO_WHATSAPP_NUMBER = 'whatsapp:+14155238886'  # Twilio sandbox number
YOUR_WHATSAPP_NUMBER = 'whatsapp:+918248650042'

client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)

# Database connection
def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',  # Default username in XAMPP
            password='',  # Leave blank if no password is set
            database='v2'  # Ensure this database exists
        )
        return connection
    except mysql.connector.Error as err:
        print(f"Database connection error: {err}")
        raise

# Insert user info into the database
def insert_user(name, email):
    try:
        connection = get_db_connection()
        cursor = connection.cursor()

        sql_query = "INSERT INTO users (name, email) VALUES (%s, %s)"
        data = (name, email)
        cursor.execute(sql_query, data)

        connection.commit()
    except mysql.connector.Error as err:
        print(f"Database operation error: {err}")
        raise
    finally:
        if cursor:
            cursor.close()
        if connection:
            connection.close()

@app.route('/')
def home():
    return render_template('index.html')

@app.route('/measurement')
def measurement_page():
    return render_template('measurement.html')

@app.route('/measure', methods=['POST'])
def measure():
    try:
        # Run the `measure.py` script and capture its output
        result = subprocess.run(['python', 'measure.py'], capture_output=True, text=True)

        # If the script fails, return an error
        if result.returncode != 0:
            error_message = result.stderr.strip() or "Unknown error running measure.py"
            print(f"Measure.py error: {error_message}")
            return jsonify({"error": "Failed to run measure.py", "details": error_message}), 500

        measurements_output = result.stdout.strip()

        # Get name and email from request
        data = request.json
        name = data.get('name')
        email = data.get('email')

        if not name or not email:
            return jsonify({"error": "Name and email are required"}), 400

        # Debugging information
        print(f"Received name: {name}, email: {email}")

        # Insert user information into the database
        insert_user(name, email)

        # Create the message body
        message_body = f"Name: {name}\nEmail: {email}\nMeasurements:\n{measurements_output}"

        # Send WhatsApp message via Twilio
        try:
            message = client.messages.create(
                body=message_body,
                from_=TWILIO_WHATSAPP_NUMBER,
                to=YOUR_WHATSAPP_NUMBER
            )
            print(f"WhatsApp message sent with SID: {message.sid}")
            return jsonify({"message": "Measurement captured and sent successfully!", "sid": message.sid}), 200
        except Exception as send_error:
            print(f"Error sending WhatsApp message: {send_error}")
            return jsonify({"error": "Failed to send WhatsApp message. Please try again later."}), 500

    except Exception as e:
        print(f"Error in measure endpoint: {e}")
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
