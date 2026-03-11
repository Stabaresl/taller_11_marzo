const express = require('express');
const router = express.Router();
const Venta = require('../models/Venta');

// 1. Registrar venta (Almacenar ventas realizadas)
router.post('/', async (req, res) => {
  try {
    const { usuarioId, productoId, cantidad, total, fecha } = req.body;

    if (!usuarioId || !productoId || !cantidad || !total) {
      return res.status(400).json({ error: 'Faltan campos obligatorios' });
    }

    const nuevaVenta = new Venta({
      usuarioId,
      productoId,
      cantidad,
      total,
      fecha: fecha ? new Date(fecha) : undefined
    });

    const guardada = await nuevaVenta.save();
    res.status(201).json(guardada);
  } catch (err) {
    console.error('Error al registrar la venta:', err);
    res.status(500).json({ error: 'Error al registrar la venta' });
  }
});

// 2. Consultar ventas registradas (todas, con filtro opcional por fecha)
router.get('/', async (req, res) => {
  try {
    const { desde, hasta } = req.query;
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

// 3. Consultar ventas por usuario
router.get('/usuario/:usuarioId', async (req, res) => {
  try {
    const { usuarioId } = req.params;
    const { desde, hasta } = req.query;

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

// (Opcional) Consultar venta específica por ID
router.get('/:id', async (req, res) => {
  try {
    const venta = await Venta.findById(req.params.id);
    if (!venta) {
      return res.status(404).json({ error: 'Venta no encontrada' });
    }
    res.json(venta);
  } catch (err) {
    console.error('Error al obtener venta por ID:', err);
    res.status(500).json({ error: 'Error al obtener la venta' });
  }
});

module.exports = router;
