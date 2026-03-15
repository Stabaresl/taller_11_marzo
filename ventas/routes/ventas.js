const express = require('express');
const router = express.Router();
const Venta = require('../models/Venta');

// --- Helper: valida que una fecha en string sea válida ---
const esFechaValida = (str) => {
  const d = new Date(str);
  return !isNaN(d.getTime());
};

// --- Helper: convierte errores de Mongoose en mensajes legibles ---
const mensajeDeValidacion = (err) => {
  if (err.name === 'ValidationError') {
    const mensajes = Object.values(err.errors).map(e => e.message);
    return mensajes.join(', ');
  }
  if (err.name === 'CastError') {
    return `El valor "${err.value}" no es válido para el campo "${err.path}"`;
  }
  return 'Error interno del servidor';
};


// --- POST /api/ventas — Registrar una nueva venta ---
router.post('/', async (req, res) => {
  try {
    const { usuarioId, productoId, cantidad, total, fecha } = req.body;

    // Validar presencia de campos
    const faltantes = ['usuarioId', 'productoId', 'cantidad', 'total']
      .filter(campo => req.body[campo] === undefined || req.body[campo] === null);

    if (faltantes.length > 0) {
      return res.status(400).json({
        error: `Faltan campos obligatorios: ${faltantes.join(', ')}`
      });
    }

    // Validar tipos
    if (typeof usuarioId !== 'string' || !usuarioId.trim()) {
      return res.status(400).json({ error: '"usuarioId" debe ser un texto no vacío' });
    }
    if (typeof productoId !== 'string' || !productoId.trim()) {
      return res.status(400).json({ error: '"productoId" debe ser un texto no vacío' });
    }
    if (!Number.isInteger(cantidad) || cantidad < 1) {
      return res.status(400).json({ error: '"cantidad" debe ser un entero mayor a 0' });
    }
    if (typeof total !== 'number' || total < 0) {
      return res.status(400).json({ error: '"total" debe ser un número mayor o igual a 0' });
    }
    if (fecha && !esFechaValida(fecha)) {
      return res.status(400).json({ error: '"fecha" no tiene un formato de fecha válido' });
    }

    const nuevaVenta = new Venta({
      usuarioId: usuarioId.trim(),
      productoId: productoId.trim(),
      cantidad,
      total,
      fecha: fecha ? new Date(fecha) : undefined
    });

    const guardada = await nuevaVenta.save();
    res.status(201).json(guardada);

  } catch (err) {
    console.error('Error al registrar la venta:', err);
    const status = err.name === 'ValidationError' ? 400 : 500;
    res.status(status).json({ error: mensajeDeValidacion(err) });
  }
});


// --- GET /api/ventas — Consultar todas las ventas (con filtro opcional por fecha) ---
router.get('/', async (req, res) => {
  try {
    const { desde, hasta } = req.query;

    // Validar fechas si se enviaron
    if (desde && !esFechaValida(desde)) {
      return res.status(400).json({ error: '"desde" no tiene un formato de fecha válido' });
    }
    if (hasta && !esFechaValida(hasta)) {
      return res.status(400).json({ error: '"hasta" no tiene un formato de fecha válido' });
    }

    const filtro = {};
    if (desde || hasta) {
      filtro.fecha = {};
      if (desde) filtro.fecha.$gte = new Date(desde);
      if (hasta) filtro.fecha.$lte = new Date(hasta);
    }

    const ventas = await Venta.find(filtro).sort({ fecha: -1 });
    res.json(ventas);

  } catch (err) {
    console.error('Error al obtener las ventas:', err);
    res.status(500).json({ error: 'Error al obtener las ventas' });
  }
});


// --- GET /api/ventas/usuario/:usuarioId — Consultar ventas por usuario ---
router.get('/usuario/:usuarioId', async (req, res) => {
  try {
    const { usuarioId } = req.params;
    const { desde, hasta } = req.query;

    if (desde && !esFechaValida(desde)) {
      return res.status(400).json({ error: '"desde" no tiene un formato de fecha válido' });
    }
    if (hasta && !esFechaValida(hasta)) {
      return res.status(400).json({ error: '"hasta" no tiene un formato de fecha válido' });
    }

    const filtro = { usuarioId };
    if (desde || hasta) {
      filtro.fecha = {};
      if (desde) filtro.fecha.$gte = new Date(desde);
      if (hasta) filtro.fecha.$lte = new Date(hasta);
    }

    const ventas = await Venta.find(filtro).sort({ fecha: -1 });
    res.json(ventas);

  } catch (err) {
    console.error('Error al obtener ventas por usuario:', err);
    res.status(500).json({ error: 'Error al obtener ventas del usuario' });
  }
});


// --- GET /api/ventas/:id — Consultar una venta específica por ID ---
router.get('/:id', async (req, res) => {
  try {
    const venta = await Venta.findById(req.params.id);
    if (!venta) {
      return res.status(404).json({ error: 'Venta no encontrada' });
    }
    res.json(venta);

  } catch (err) {
    console.error('Error al obtener venta por ID:', err);
    // CastError ocurre cuando el ID no tiene formato válido de MongoDB
    const status = err.name === 'CastError' ? 400 : 500;
    res.status(status).json({ error: mensajeDeValidacion(err) });
  }
});

module.exports = router;