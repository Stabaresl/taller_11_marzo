from flask import Flask, jsonify, request
from flask_cors import CORS
from dotenv import load_dotenv
import firebase_admin
from firebase_admin import credentials, db as firebase_db
import os

load_dotenv()

app = Flask(__name__)
CORS(app)

@app.before_request
def verificar_gateway():
    clave_recibida  = request.headers.get('X-Internal-Key')
    clave_esperada  = os.getenv('INTERNAL_KEY')

    if clave_recibida != clave_esperada:
        return jsonify({'error': 'Acceso no autorizado - solo el Gateway puede acceder'}), 403

@app.errorhandler(404)
def not_found(e):
    return jsonify({'error': 'Recurso no encontrado'}), 404

@app.errorhandler(500)
def server_error(e):
    return jsonify({'error': 'Error interno del servidor'}), 500

@app.errorhandler(400)
def bad_request(e):
    return jsonify({'error': 'Solicitud incorrecta'}), 400

cred = credentials.Certificate("serviceAccountKey.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': os.getenv('DATABASE_URL')
})

from routes import bp
app.register_blueprint(bp)


if __name__ == '__main__':
    app.run(debug=True, port=5000)