const mongoose = require('mongoose');

const VentaSchema = new mongoose.Schema({
  usuarioId: {
    type: String,
    required: true,
    trim: true
  },
  productoId: {
    type: String,
    required: true,  // id del producto del microservicio Flask
    trim: true
  },
  cantidad: {
    type: Number,
    required: true,
    min: [1, 'La cantidad debe ser al menos 1']
  },
  total: {
    type: Number,
    required: true,
    min: [0, 'El total no puede ser negativo']
  },
  fecha: {
    type: Date,
    default: Date.now
  }
}, {
  timestamps: true  // createdAt, updatedAt
});

module.exports = mongoose.model('Venta', VentaSchema);