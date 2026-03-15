from flask import Blueprint, request, jsonify, current_app
from firebase_admin import firestore

bp = Blueprint('productos', __name__)

def get_collection():
    """Retorna la referencia a la colección 'productos' en Firestore."""
    db = firestore.client()
    return db.collection('productos')


def validar_producto(data):
    """
    Valida que el body tenga los campos requeridos con los tipos correctos.
    Retorna (True, None) si es válido, (False, mensaje) si no.
    """
    if not data:
        return False, 'El cuerpo de la solicitud está vacío'

    campos_requeridos = ['nombre', 'precio', 'stock']
    for campo in campos_requeridos:
        if campo not in data:
            return False, f'Falta el campo requerido: {campo}'

    if not isinstance(data['nombre'], str) or not data['nombre'].strip():
        return False, 'El campo "nombre" debe ser un texto no vacío'

    if not isinstance(data['precio'], (int, float)) or data['precio'] < 0:
        return False, 'El campo "precio" debe ser un número mayor o igual a 0'

    if not isinstance(data['stock'], int) or data['stock'] < 0:
        return False, 'El campo "stock" debe ser un número entero mayor o igual a 0'

    return True, None


# --- POST /productos — Registrar un nuevo producto ---
@bp.route('/productos', methods=['POST'])
def add_producto():
    data = request.get_json(silent=True)

    valido, error = validar_producto(data)
    if not valido:
        return jsonify({'error': error}), 400

    # Normalizar el nombre antes de guardar
    data['nombre'] = data['nombre'].strip()

    nuevo_ref = get_collection().add(data)
    # nuevo_ref es una tupla (timestamp, DocumentReference)
    nuevo_id = nuevo_ref[1].id

    return jsonify({'message': 'Producto creado', 'id': nuevo_id}), 201


# --- GET /productos — Listar todos los productos ---
@bp.route('/productos', methods=['GET'])
def get_productos():
    docs = get_collection().stream()
    productos = [{'id': doc.id, **doc.to_dict()} for doc in docs]
    return jsonify(productos), 200


# --- GET /productos/<id> — Obtener un producto por ID ---
@bp.route('/productos/<id>', methods=['GET'])
def get_producto(id):
    doc = get_collection().document(id).get()
    if not doc.exists:
        return jsonify({'error': 'Producto no encontrado'}), 404
    return jsonify({'id': doc.id, **doc.to_dict()}), 200


# --- GET /productos/<id>/stock — Verificar disponibilidad de stock ---
@bp.route('/productos/<id>/stock', methods=['GET'])
def verificar_stock(id):
    doc = get_collection().document(id).get()
    if not doc.exists:
        return jsonify({'error': 'Producto no encontrado'}), 404

    data = doc.to_dict()
    stock = data.get('stock', 0)
    return jsonify({
        'id': id,
        'nombre': data.get('nombre'),
        'stock': stock,
        'disponible': stock > 0
    }), 200


# --- PUT /productos/<id>/stock — Actualizar stock después de una venta ---
@bp.route('/productos/<id>/stock', methods=['PUT'])
def actualizar_stock(id):
    data = request.get_json(silent=True)

    if not data or 'cantidad' not in data:
        return jsonify({'error': 'Falta el campo: cantidad'}), 400

    if not isinstance(data['cantidad'], int) or data['cantidad'] <= 0:
        return jsonify({'error': 'El campo "cantidad" debe ser un entero positivo'}), 400

    doc_ref = get_collection().document(id)
    doc = doc_ref.get()

    if not doc.exists:
        return jsonify({'error': 'Producto no encontrado'}), 404

    stock_actual = doc.to_dict().get('stock', 0)

    if stock_actual < data['cantidad']:
        return jsonify({
            'error': 'Stock insuficiente',
            'stock_disponible': stock_actual,
            'cantidad_solicitada': data['cantidad']
        }), 400

    nuevo_stock = stock_actual - data['cantidad']
    doc_ref.update({'stock': nuevo_stock})

    return jsonify({
        'message': 'Stock actualizado correctamente',
        'id': id,
        'stock_anterior': stock_actual,
        'cantidad_descontada': data['cantidad'],
        'stock_nuevo': nuevo_stock
    }), 200
