import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    # Clave secreta de Flask (sesiones, firmas internas)
    SECRET_KEY = os.getenv('SECRET_KEY', 'cambia-esta-clave-en-produccion')

    # Clave interna para validar que las peticiones vengan del Gateway
    INTERNAL_KEY = os.getenv('INTERNAL_KEY')

    # Ruta al archivo de credenciales de Firebase
    # En producción esto debería ser una variable de entorno, no un archivo
    GOOGLE_APPLICATION_CREDENTIALS = os.getenv(
        'GOOGLE_APPLICATION_CREDENTIALS',
        'serviceAccountKey.json'
    )

    # Puerto del servidor
    PORT = int(os.getenv('PORT', 5000))

    # Modo debug (nunca True en producción)
    DEBUG = os.getenv('FLASK_DEBUG', 'false').lower() == 'true'
