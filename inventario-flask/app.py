from flask import Flask
from flask_cors import CORS
from config import Config
import firebase_admin
from firebase_admin import credentials, firestore
import routes

# Inicializar Firebase
cred = credentials.Certificate('firebase-key.json')
firebase_admin.initialize_app(cred)
db = firestore.client()

app = Flask(__name__)
app.config.from_object(Config)
CORS(app)

routes.register_routes(app, db)

if __name__ == '__main__':
    app.run(debug=True, port=5000)
