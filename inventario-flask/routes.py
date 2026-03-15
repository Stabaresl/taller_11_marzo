from flask import Blueprint, request, jsonify
from firebase_admin import db as firebase_db

bp = Blueprint('productos', __name__)

def ref():
    return firebase_db.reference('/productos')


@bp.route('/productos', methods=['POST'])
def add_producto():
    data = request.json
    if not data or 'nombre' not in data or 'stock' not in data or 'precio' not in data:
        return jsonify({'error': 'Faltan campos: nombre, precio, stock'}), 400
    nuevo = ref().push(data)
    return jsonify({'message': 'Producto creado', 'id': nuevo.key}), 201

@bp.route('/productos', methods=['GET'])
def get_productos():
    data = ref().get()
    if not data:
        return jsonify([])
    productos = [{'id': k, **v} for k, v in data.items()]
    return jsonify(productos)

@bp.route('/productos/<id>', methods=['GET'])
def get_producto(id):
    data = ref().child(id).get()
    if not data:
        return jsonify({'error': 'Producto no encontrado'}), 404
    return jsonify({'id': id, **data})

@bp.route('/productos/<id>/stock', methods=['GET'])
def verificar_stock(id):
    data = ref().child(id).get()
    if not data:
        return jsonify({'error': 'Producto no encontrado'}), 404
    stock = data.get('stock', 0)
    return jsonify({'id': id, 'stock': stock, 'disponible': stock > 0})

@bp.route('/productos/<id>/stock', methods=['PUT'])
def actualizar_stock(id):
    data = request.json
    if 'cantidad' not in data:
        return jsonify({'error': 'Falta el campo: cantidad'}), 400
    producto = ref().child(id).get()
    if not producto:
        return jsonify({'error': 'Producto no encontrado'}), 404
    stock_actual = producto.get('stock', 0)
    if stock_actual < data['cantidad']:
        return jsonify({'error': 'Stock insuficiente'}), 400
    nuevo_stock = stock_actual - data['cantidad']
    ref().child(id).update({'stock': nuevo_stock})
    return jsonify({'message': 'Stock actualizado', 'stock_nuevo': nuevo_stock})