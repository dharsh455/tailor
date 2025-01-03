from flask import Flask, render_template, request, jsonify
from flask_cors import CORS
import subprocess
import mysql.connector
from twilio.rest import Client

app = Flask(__name__)
CORS(app)

TWILIO_ACCOUNT_SID = 'ACc9ac24f6b52d9e066af70034539d63fc'
TWILIO_AUTH_TOKEN = 'a4242f005d0ebbcc7c25570c608f3e51'
TWILIO_WHATSAPP_NUMBER = 'whatsapp:+14155238886'
YOUR_WHATSAPP_NUMBER = 'whatsapp:+918248650042'

client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN)

def get_db_connection():
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='v2'
    )
    return connection

@app.route('/', methods=['GET', 'HEAD'])
def home():
    return render_template('index.html')

@app.route('/measurement', methods=['GET'])
def measurement_page():
    return render_template('measurement.html')

@app.route('/measure', methods=['POST'])
def measure():
    try:
        result = subprocess.run(['python', 'measure.py'], capture_output=True, text=True)
        measurements_output = result.stdout.strip()

        if result.returncode != 0:
            return jsonify({"error": "Failed to run measure.py"}), 500

        data = request.json
        name = data.get('name')
        email = data.get('email')

        if not name or not email:
            return jsonify({"error": "Name and email are required"}), 400

        connection = get_db_connection()
        cursor = connection.cursor()
        cursor.execute("INSERT INTO users (name, email) VALUES (%s, %s)", (name, email))
        connection.commit()
        cursor.close()
        connection.close()

        message_body = f"Name: {name}\nEmail: {email}\nMeasurements:\n{measurements_output}"

        message = client.messages.create(
            body=message_body,
            from_=TWILIO_WHATSAPP_NUMBER,
            to=YOUR_WHATSAPP_NUMBER
        )
        return jsonify({"message": "Measurement captured and sent successfully!", "sid": message.sid}), 200

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
