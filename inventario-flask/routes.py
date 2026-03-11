from flask import jsonify, request

def register_routes(app, db):
    
    # 1. Registro de productos (POST /productos)
    @app.route('/api/productos', methods=['POST'])
    def registrar_producto():
        data = request.get_json()
        if not data or 'nombre' not in data or 'precio' not in data:
            return jsonify({'error': 'Faltan campos: nombre, precio'}), 400
        
        producto_data = {
            'nombre': data['nombre'],
            'precio': float(data['precio']),
            'stock': int(data.get('stock', 0))
        }
        
        doc_ref = db.collection('productos').add(producto_data)
        return jsonify({
            'id': doc_ref[1].id,
            'mensaje': 'Producto registrado'
        }), 201

    # 2. Consulta de productos (GET /productos)
    @app.route('/api/productos', methods=['GET'])
    def consultar_productos():
        productos = db.collection('productos').stream()
        lista = [{
            'id': p.id,
            'nombre': p.to_dict()['nombre'],
            'precio': p.to_dict()['precio'],
            'stock': p.to_dict()['stock']
        } for p in productos]
        return jsonify(lista)

    # 3. Verificación de stock (GET /productos/<id>/stock)
    @app.route('/api/productos/<producto_id>/stock', methods=['GET'])
    def verificar_stock(producto_id):
        doc = db.collection('productos').document(producto_id).get()
        if not doc.exists:
            return jsonify({'error': 'Producto no encontrado'}), 404
        
        data = doc.to_dict()
        return jsonify({
            'id': doc.id,
            'nombre': data['nombre'],
            'stock_actual': data['stock'],
            'disponible': data['stock'] > 0
        })

    # 4. Actualización de inventario después de venta (PUT /productos/<id>/stock)
    @app.route('/api/productos/<producto_id>/stock', methods=['PUT'])
    def actualizar_inventario(producto_id):
        data = request.get_json()
        cantidad = int(data.get('cantidad_vendida', 1))
        
        doc_ref = db.collection('productos').document(producto_id)
        doc = doc_ref.get()
        
        if not doc.exists:
            return jsonify({'error': 'Producto no encontrado'}), 404
        
        stock_actual = doc.to_dict()['stock']
        nuevo_stock = stock_actual - cantidad
        
        if nuevo_stock < 0:
            return jsonify({'error': 'Stock insuficiente'}), 400
        
        doc_ref.update({'stock': nuevo_stock})
        return jsonify({
            'mensaje': 'Inventario actualizado',
            'stock_anterior': stock_actual,
            'stock_nuevo': nuevo_stock
        })
