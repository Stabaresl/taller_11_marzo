const mongoose = require('mongoose');

const VentaSchema = new mongoose.Schema({
  usuarioId: {
    type: String,
    required: true
  },
  productoId: {
    type: String,
    required: true  // id del producto del microservicio Flask
  },
  cantidad: {
    type: Number,
    required: true,
    min: 1
  },
  total: {
    type: Number,
    required: true,
    min: 0
  },
  fecha: {
    type: Date,
    default: Date.now
  }
}, {
  timestamps: true // createdAt, updatedAt
});

module.exports = mongoose.model('Venta', VentaSchema);
