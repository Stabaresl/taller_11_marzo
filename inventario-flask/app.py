from flask import Flask, jsonify, request
from flask_cors import CORS
from dotenv import load_dotenv
from config import Config
import firebase_admin
from firebase_admin import credentials, firestore
import os

load_dotenv()

app = Flask(__name__)
app.config.from_object(Config)
CORS(app)

# --- Middleware: solo el Gateway puede acceder ---
@app.before_request
def verificar_gateway():
    # Las rutas de health-check no requieren la clave interna
    if request.path == '/health':
        return

    clave_recibida = request.headers.get('X-Internal-Key')
    clave_esperada = os.getenv('INTERNAL_KEY')

    if not clave_esperada:
        return jsonify({'error': 'Configuración interna inválida'}), 500

    if clave_recibida != clave_esperada:
        return jsonify({'error': 'Acceso no autorizado - solo el Gateway puede acceder'}), 403

# --- Manejo de errores global ---
@app.errorhandler(404)
def not_found(e):
    return jsonify({'error': 'Recurso no encontrado'}), 404

@app.errorhandler(500)
def server_error(e):
    return jsonify({'error': 'Error interno del servidor'}), 500

@app.errorhandler(400)
def bad_request(e):
    return jsonify({'error': 'Solicitud incorrecta'}), 400

# --- Inicializar Firebase con Firestore ---
cred_path = os.getenv('GOOGLE_APPLICATION_CREDENTIALS', 'serviceAccountKey.json')
cred = credentials.Certificate(cred_path)
firebase_admin.initialize_app(cred)

# Exponemos el cliente de Firestore para usarlo en las rutas
db = firestore.client()

# --- Ruta de health-check ---
@app.route('/health')
def health():
    return jsonify({'status': 'ok', 'servicio': 'inventario-flask'}), 200

# --- Registrar blueprint de productos ---
from routes import bp
app.register_blueprint(bp)

if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(debug=os.getenv('FLASK_DEBUG', 'false').lower() == 'true', port=port)
